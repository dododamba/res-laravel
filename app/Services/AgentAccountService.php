<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;
use App\Models\Role;
use App\Enums\AgentStatut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class AgentAccountService
{
    /**
     * Résout le slug du rôle à partir du code de fonction de l'Agent.
     */
    public function resolveRoleSlug(string $fonctionCode): string
    {
        return match (strtoupper($fonctionCode)) {
            'ENQUETEUR' => 'ROLE_ENQUETEUR',
            'EXPERT' => 'ROLE_EXPERT',
            'CHEF_CARRE' => 'ROLE_CHEF_CARRE',
            'DELEGUE' => 'ROLE_DELEGUE',
            'CONSEILLER' => 'ROLE_CONSEILLER',
            'COMMISSION' => 'ROLE_COMMISSION',
            'ADMIN' => 'ROLE_ADMIN',
            'SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',
            default => 'ROLE_USER',
        };
    }

    /**
     * Résout l'intitulé convivial d'un rôle.
     */
    public function resolveRoleName(string $roleSlug): string
    {
        return match ($roleSlug) {
            'ROLE_ENQUETEUR' => 'Enquêteur',
            'ROLE_EXPERT' => 'Expert',
            'ROLE_CHEF_CARRE' => 'Chef de Carré',
            'ROLE_DELEGUE' => 'Délégué',
            'ROLE_CONSEILLER' => 'Conseiller',
            'ROLE_COMMISSION' => 'Président de Commission',
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_SUPER_ADMIN' => 'Super Administrateur',
            default => 'Utilisateur',
        };
    }

    /**
     * Récupère ou instancie un Rôle en base de données.
     */
    public function getOrCreateRole(string $roleSlug): Role
    {
        return Role::firstOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => $this->resolveRoleName($roleSlug),
                'description' => 'Rôle créé automatiquement pour la fonction associée',
            ]
        );
    }

    /**
     * Provisionne automatiquement un compte utilisateur d'authentification pour un Agent Territorial.
     */
    public function provisionAccount(Agent $agent): User
    {
        $personne = $agent->personne;
        if (is_null($personne) || empty($personne->email)) {
            throw new InvalidArgumentException("L'agent doit avoir une fiche de personne civile avec un email valide.");
        }

        // Vérification d'unicité stricte de l'email
        $existingUser = User::where('email', $personne->email)->first();
        if ($existingUser !== null) {
            throw new LogicException(sprintf("Un compte utilisateur avec l'email '%s' existe déjà.", $personne->email));
        }

        return DB::transaction(function () use ($agent, $personne) {
            
            // 1. Instanciation du compte utilisateur
            $user = User::create([
                'email' => $personne->email,
                'firstname' => $personne->prenom,
                'lastname' => $personne->nom,
                'telephone' => $personne->telephone,
                'password' => Hash::make('12345678'), // Mot de passe par défaut sécurisé
                'is_verified' => true,
                'is_active' => true,
                'status' => 'active',
                'is_locked' => false,
                'login_attempts' => 0,
            ]);

            // 2. Association bidirectionnelle
            $agent->user_id = $user->id;
            $agent->save();

            // 3. Résolution et assignation du rôle basé sur la fonction
            if ($agent->fonction) {
                $roleSlug = $this->resolveRoleSlug($agent->fonction->code);
                $role = $this->getOrCreateRole($roleSlug);
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            return $user;
        });
    }
}
