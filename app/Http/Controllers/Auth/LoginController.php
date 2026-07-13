<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class LoginController extends Controller
{
    /**
     * Affiche le formulaire de connexion.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Gère la tentative de connexion de l'utilisateur.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        // 1. Vérifier si l'utilisateur existe en base
        if (!$user) {
            return back()->withInput()->withErrors([
                'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ]);
        }

        // 2. Vérifier si le compte est temporairement verrouillé (Anti-BruteForce Symfony d'origine)
        if ($user->is_locked && $user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            $diffMinutes = Carbon::now()->diffInMinutes($user->locked_until) + 1;
            return back()->withErrors([
                'email' => "Ce compte est temporairement verrouillé suite à de nombreuses tentatives de connexion infructueuses. Veuillez réessayer dans {$diffMinutes} minute(s).",
            ]);
        }

        // 3. Vérifier si le mot de passe est correct
        if (!Hash::check($password, $user->password)) {
            
            // Incrémenter le compteur de tentatives infructueuses (Règle d'or de sécurité)
            $user->login_attempts = ($user->login_attempts ?? 0) + 1;

            if ($user->login_attempts >= 5) {
                $user->is_locked = true;
                $user->locked_until = Carbon::now()->addMinutes(15); // Verrouillage temporaire de 15 min
                $user->login_attempts = 0; // Réinitialisation du compteur pour le prochain déverrouillage
                $user->save();

                return back()->withErrors([
                    'email' => 'Ce compte a été verrouillé pour 15 minutes suite à 5 tentatives de connexion infructueuses.',
                ]);
            }

            $user->save();

            $attemptsLeft = 5 - $user->login_attempts;
            return back()->withInput()->withErrors([
                'password' => "Mot de passe incorrect. Il vous reste {$attemptsLeft} tentative(s) avant le verrouillage temporaire du compte.",
            ]);
        }

        // 4. Vérifier si le compte est actif (Sécurité d'activité d'agent)
        if (!$user->is_active || $user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Votre compte d\'accès est inactif ou suspendu. Veuillez contacter votre administrateur.',
            ]);
        }

        // 5. Connexion réussie : Réinitialiser les compteurs de sécurité et logger la connexion
        $user->is_locked = false;
        $user->locked_until = null;
        $user->login_attempts = 0;
        $user->last_login = Carbon::now();
        $user->save();

        // 6. Connecter physiquement l'utilisateur et régénérer sa session
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', "Ravi de vous revoir, {$user->firstname} ! Connexion établie avec succès.");
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Vous avez été déconnecté avec succès de la plateforme de bureau.');
    }
}
