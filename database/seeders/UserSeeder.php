<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Personne;
use App\Models\Agent;
use App\Models\Parameters\Fonction;
use App\Enums\AgentStatut;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Exécute le peuplement des comptes utilisateurs de tests d'authentification.
     */
    public function run(): void
    {
        // 1. Récupération des rôles système créés par le RoleAndPermissionSeeder
        $roleSuperAdmin = Role::where('slug', 'ROLE_SUPER_ADMIN')->first();
        $roleAdmin = Role::where('slug', 'ROLE_ADMIN')->first();
        $roleEnqueteur = Role::where('slug', 'ROLE_ENQUETEUR')->first();

        // Récupération de la fonction paramétrique associée aux agents
        $fonctionAdmin = Fonction::where('code', 'ADMIN')->first();
        $fonctionEnqueteur = Fonction::where('code', 'ENQUETEUR')->first();

        // 2. CRÉATION DU COMPTE : SUPER ADMINISTRATEUR (Accès total)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@recensement.gov'],
            [
                'password' => 'password123',
                'firstname' => 'Super',
                'lastname' => 'Admin',
                'telephone' => '0101010101',
                'is_verified' => true,
                'is_active' => true,
                'status' => 'active',
            ]
        );
        if ($roleSuperAdmin) {
            $superAdmin->roles()->syncWithoutDetaching([$roleSuperAdmin->id]);
        }

        // 3. CRÉATION DU COMPTE : ADMINISTRATEUR (Rôle d'administration et validations)
        $admin = User::firstOrCreate(
            ['email' => 'admin@recensement.gov'],
            [
                'password' => 'password123',
                'firstname' => 'Paul',
                'lastname' => 'Administrateur',
                'telephone' => '0202020202',
                'is_verified' => true,
                'is_active' => true,
                'status' => 'active',
            ]
        );
        if ($roleAdmin) {
            $admin->roles()->syncWithoutDetaching([$roleAdmin->id]);
        }

        // 4. CRÉATION DU COMPTE : RECENSEUR / ENQUÊTEUR (Saisies de terrain terrain)
        // L'enquêteur requiert un profil Personne et Agent pour le cloisonnement de sécurité !
        $emailRecenseur = 'recenseur@recensement.gov';
        $userRecenseur = User::where('email', $emailRecenseur)->first();

        if (!$userRecenseur) {
            // a. Fiche d'identité civile
            $personne = Personne::create([
                'id' => (string) Str::uuid(),
                'prenom' => 'Jean',
                'nom' => 'Recenseur',
                'email' => $emailRecenseur,
                'telephone' => '0612345678',
                'role' => 'user'
            ]);

            // b. Fiche utilisateur d'accès d'authentification
            $userRecenseur = User::create([
                'email' => $emailRecenseur,
                'password' => 'password123',
                'firstname' => 'Jean',
                'lastname' => 'Recenseur',
                'telephone' => '0612345678',
                'is_verified' => true,
                'is_active' => true,
                'status' => 'active'
            ]);
            if ($roleEnqueteur) {
                $userRecenseur->roles()->syncWithoutDetaching([$roleEnqueteur->id]);
            }

            // c. Fiche technique d'agent lié
            $agent = Agent::create([
                'id' => (string) Str::uuid(),
                'personne_id' => $personne->id,
                'fonction_id' => $fonctionEnqueteur ? $fonctionEnqueteur->id : Fonction::first()->id,
                'user_id' => $userRecenseur->id,
                'sexe' => 'M',
                'matricule' => 'AGT-2026-0001',
                'statut' => AgentStatut::ACTIF,
                'date_nomination' => now(),
            ]);
        }
    }
}
