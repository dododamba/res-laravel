<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Exécute le peuplement global de la base de données.
     */
    public function run(): void
    {
        $this->call([
            // 1. Initialisation des Rôles & Permissions système (Spatie/Custom RBAC)
            RoleAndPermissionSeeder::class,

            // 2. Initialisation des paramètres métiers et géographiques initiaux
            ParameterSeeder::class,

            // 3. Initialisation des utilisateurs d'administration et d'enquêtes terrain
            UserSeeder::class,
        ]);
    }
}
