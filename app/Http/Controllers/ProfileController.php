<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Mon Profil Agent - Affichage du profil de l'utilisateur connecté et de ses affectations.
     */
    public function show()
    {
        $user = auth()->user()->load([
            'roles',
            'agent.personne',
            'agent.fonction',
            'agent.affectations.quartier',
            'agent.affectations.carre'
        ]);

        return view('profile.show', compact('user'));
    }

    /**
     * Paramètres du compte - Formulaire d'édition de l'utilisateur connecté.
     */
    public function edit()
    {
        $user = auth()->user();
        return view('user.edit', [
            'user' => $user,
            'roles' => $user->roles, // En lecture seule pour l'utilisateur
            'isSelfEdit' => true // Permet d'adapter l'affichage pour la modification personnelle
        ]);
    }

    /**
     * Enregistrement des modifications du compte.
     */
    public function update(Request $request)
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

        DB::transaction(function () use ($request, $user) {
            $user->fill($request->only('email', 'firstname', 'lastname', 'telephone'));

            // Gestion du mot de passe
            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            // Gestion de l'image de profil (Avatar)
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/avatars'), $filename);
                $user->avatar = $avatarFileName = $filename; // On peut sauver l'avatar sur l'user
                
                // Ou si la colonne avatar n'est pas fillable, on la définit directement
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

        return redirect()
            ->route('profile.show')
            ->with('success', "Vos paramètres de profil ont été mis à jour de manière cohérente.");
    }
}
