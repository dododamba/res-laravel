<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthApiController extends Controller
{
    use ApiResponse; // Fournit buildResponse() unifié pour l'API v1

    /**
     * Endpoint API : Connexion d'un Agent d'Enquêtes (Mobile / Web API)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required_without:username|email',
            'username' => 'required_without:email',
            'password' => 'required|string',
        ]);

        $email = $request->input('email') ?? $request->input('username');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        // 1. Vérifier si l'utilisateur existe
        if (!$user) {
            return $this->buildResponse(
                success: false,
                message: "Identifiants de connexion incorrects.",
                statusCode: 401
            );
        }

        // 2. Vérifier si le compte est temporairement verrouillé (Sécurité Symfony d'origine)
        if ($user->is_locked && $user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            $diffMinutes = Carbon::now()->diffInMinutes($user->locked_until) + 1;
            return $this->buildResponse(
                success: false,
                message: "Ce compte est verrouillé en raison de tentatives de connexion infructueuses excessives. Veuillez réessayer dans {$diffMinutes} minute(s).",
                statusCode: 403
            );
        }

        // 3. Vérifier si le mot de passe est correct
        if (!Hash::check($password, $user->password)) {
            
            // Incrémenter les tentatives infructueuses (Règle d'or de sécurité de l'application)
            $user->login_attempts = ($user->login_attempts ?? 0) + 1;

            if ($user->login_attempts >= 5) {
                $user->is_locked = true;
                $user->locked_until = Carbon::now()->addMinutes(15); // Verrouillage de 15 minutes
                $user->login_attempts = 0; // Réinitialisation du compteur pour le déverrouillage futur
                $user->save();

                return $this->buildResponse(
                    success: false,
                    message: "Le compte a été verrouillé pour 15 minutes suite à 5 tentatives infructueuses.",
                    statusCode: 403
                );
            }

            $user->save();

            $attemptsLeft = 5 - $user->login_attempts;
            return $this->buildResponse(
                success: false,
                message: "Mot de passe incorrect. Il vous reste {$attemptsLeft} tentative(s) avant le verrouillage du compte.",
                statusCode: 401
            );
        }

        // 4. Vérifier si le compte est actif
        if (!$user->is_active || $user->status !== 'active') {
            return $this->buildResponse(
                success: false,
                message: "Votre compte utilisateur est suspendu ou inactif. Veuillez contacter votre administrateur.",
                statusCode: 403
            );
        }

        // 5. Succès de la connexion : réinitialiser le compteur d'échecs et enregistrer le login
        $user->is_locked = false;
        $user->locked_until = null;
        $user->login_attempts = 0;
        $user->last_login = Carbon::now();
        $user->save();

        // 6. Génération du Token d'accès sécurisé Sanctum (Remplacement de LexikJWT)
        $agent = $user->agent;
        $roleName = $user->roles()->first()?->name ?? 'Utilisateur';
        
        $token = $user->createToken('survey-mobile-token', [
            'survey:submit',
            'survey:read'
        ])->plainTextToken;

        return $this->buildResponse(
            success: true,
            message: "Connexion réussie.",
            data: [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'fullname' => trim("{$user->firstname} {$user->lastname}"),
                    'telephone' => $user->telephone,
                    'role' => $roleName,
                    'agent_matricule' => $agent ? $agent->matricule : null,
                    'agent_id' => $agent ? $agent->id : null,
                    'avatar' => $user->avatar ? asset('uploads/avatars/' . $user->avatar) : null,
                ]
            ]
        );
    }

    /**
     * Endpoint API : Profil de l'Agent d'Enquêtes connecté (Mobile / Web API)
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();
        $agent = $user->agent;
        $roleName = $user->roles()->first()?->name ?? 'Utilisateur';

        return $this->buildResponse(
            success: true,
            message: "Profil récupéré avec succès.",
            data: [
                'id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'fullname' => trim("{$user->firstname} {$user->lastname}"),
                'telephone' => $user->telephone,
                'role' => $roleName,
                'agent_matricule' => $agent ? $agent->matricule : null,
                'agent_id' => $agent ? $agent->id : null,
                'avatar' => $user->avatar ? asset('uploads/avatars/' . $user->avatar) : null,
            ]
        );
    }

    /**
     * Endpoint API : Modifier le profil de l'Agent d'Enquêtes connecté (Mobile)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $request->validate([
            'email' => 'required|email|max:180|unique:users,email,' . $user->id,
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|max:2048', // Max 2Mo
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user) {
            $user->fill($request->only('email', 'firstname', 'lastname', 'telephone'));

            // Gestion du mot de passe
            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            // Gestion de l'image de profil (Avatar)
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = (string) \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                // Ensure directory exists
                $destinationPath = public_path('uploads/avatars');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $user->avatar = $filename;
            }

            $user->save();

            // Règle métier Symfony : Si un Agent physique est lié, synchroniser sa fiche civile
            if ($user->agent && $user->agent->personne) {
                $user->agent->personne->update([
                    'email' => $user->email,
                    'prenom' => $user->firstname ?? $user->agent->personne->prenom,
                    'nom' => $user->lastname ?? $user->agent->personne->nom,
                    'telephone' => $user->telephone ?? $user->agent->personne->telephone,
                ]);
            }
        });

        $agent = $user->agent;
        $roleName = $user->roles()->first()?->name ?? 'Utilisateur';

        return $this->buildResponse(
            success: true,
            message: "Profil mis à jour avec succès.",
            data: [
                'id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'fullname' => trim("{$user->firstname} {$user->lastname}"),
                'telephone' => $user->telephone,
                'role' => $roleName,
                'agent_matricule' => $agent ? $agent->matricule : null,
                'agent_id' => $agent ? $agent->id : null,
                'avatar' => $user->avatar ? asset('uploads/avatars/' . $user->avatar) : null,
            ]
        );
    }
}
