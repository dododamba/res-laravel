<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Exécute le peuplement de la table de rôles et permissions.
     */
    public function run(): void
    {
        // 1. Définition des permissions par catégories (Sujets d'enquêtes, Admin)
        $permissions = [
            // Catégorie : Habitations
            ['name' => 'MAISON_VIEW', 'description' => 'Visualiser les fiches d\'habitations', 'category' => 'Habitations'],
            ['name' => 'MAISON_CREATE', 'description' => 'Créer de nouvelles habitations', 'category' => 'Habitations'],
            ['name' => 'MAISON_EDIT', 'description' => 'Modifier les habitations existantes', 'category' => 'Habitations'],
            ['name' => 'MAISON_DELETE', 'description' => 'Archiver/Supprimer des habitations', 'category' => 'Habitations'],

            // Catégorie : Ménages & Recensement
            ['name' => 'RECENSEMENT_VIEW', 'description' => 'Visualiser les recensements de ménages', 'category' => 'Recensement'],
            ['name' => 'RECENSEMENT_CREATE', 'description' => 'Créer de nouveaux recensements', 'category' => 'Recensement'],
            ['name' => 'RECENSEMENT_EDIT', 'description' => 'Modifier des fiches de recensements', 'category' => 'Recensement'],
            ['name' => 'RECENSEMENT_DELETE', 'description' => 'Archiver des fiches de recensements', 'category' => 'Recensement'],

            // Catégorie : Opérateurs Économiques
            ['name' => 'OPERATEUR_VIEW', 'description' => 'Visualiser les opérateurs économiques', 'category' => 'Opérateurs'],
            ['name' => 'OPERATEUR_CREATE', 'description' => 'Créer de nouveaux opérateurs', 'category' => 'Opérateurs'],
            ['name' => 'OPERATEUR_EDIT', 'description' => 'Modifier les opérateurs économiques', 'category' => 'Opérateurs'],
            ['name' => 'OPERATEUR_DELETE', 'description' => 'Archiver les opérateurs économiques', 'category' => 'Opérateurs'],

            // Catégorie : Paramétrages
            ['name' => 'PARAM_VIEW', 'description' => 'Visualiser les paramètres de l\'application', 'category' => 'Paramétrages'],
            ['name' => 'PARAM_MANAGE', 'description' => 'Créer, éditer et archiver les paramètres de base', 'category' => 'Paramétrages'],

            // Catégorie : Administration Système
            ['name' => 'USER_MANAGE', 'description' => 'Gérer les comptes utilisateurs et profils agents', 'category' => 'Administration'],
            ['name' => 'ROLE_MANAGE', 'description' => 'Gérer la table RBAC de droits d\'accès', 'category' => 'Administration'],
            ['name' => 'AUDIT_VIEW', 'description' => 'Consulter les logs d\'audit système', 'category' => 'Administration'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['name']] = Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'description' => $perm['description'],
                    'category' => $perm['category']
                ]
            );
        }

        // 2. Définition et association des rôles
        $roles = [
            'ROLE_SUPER_ADMIN' => [
                'name' => 'Super Administrateur',
                'description' => 'Accès absolu et sans restriction sur l\'intégralité du système',
                'permissions' => array_keys($permissionModels) // Toutes les permissions
            ],
            'ROLE_ADMIN' => [
                'name' => 'Administrateur',
                'description' => 'Gestion d\'exploitation, validation de données et gestion d\'agents',
                'permissions' => [
                    'MAISON_VIEW', 'MAISON_EDIT', 'MAISON_DELETE',
                    'RECENSEMENT_VIEW', 'RECENSEMENT_EDIT', 'RECENSEMENT_DELETE',
                    'OPERATEUR_VIEW', 'OPERATEUR_EDIT', 'OPERATEUR_DELETE',
                    'PARAM_VIEW', 'PARAM_MANAGE',
                    'USER_MANAGE', 'AUDIT_VIEW'
                ]
            ],
            'ROLE_ENQUETEUR' => [
                'name' => 'Enquêteur / Recenseur',
                'description' => 'Saisie brute des enquêtes sur le terrain via l\'application mobile ou web',
                'permissions' => [
                    'MAISON_VIEW', 'MAISON_CREATE', 'MAISON_EDIT',
                    'RECENSEMENT_VIEW', 'RECENSEMENT_CREATE', 'RECENSEMENT_EDIT',
                    'OPERATEUR_VIEW', 'OPERATEUR_CREATE', 'OPERATEUR_EDIT'
                ]
            ],
            'ROLE_CHEF_CARRE' => [
                'name' => 'Chef de Carré',
                'description' => 'Supervision locale et lecture seule sur son carré géographique',
                'permissions' => [
                    'MAISON_VIEW',
                    'RECENSEMENT_VIEW',
                    'OPERATEUR_VIEW'
                ]
            ],
            'ROLE_DELEGUE' => [
                'name' => 'Délégué de Quartier',
                'description' => 'Consultation globale de l\'avancement d\'enquêtes sur son quartier',
                'permissions' => [
                    'MAISON_VIEW',
                    'RECENSEMENT_VIEW',
                    'OPERATEUR_VIEW'
                ]
            ]
        ];

        foreach ($roles as $slug => $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description']
                ]
            );

            // Synchronisation propre des relations de permissions
            $syncIds = [];
            foreach ($roleData['permissions'] as $permName) {
                if (isset($permissionModels[$permName])) {
                    $syncIds[] = $permissionModels[$permName]->id;
                }
            }
            $role->permissions()->sync($syncIds);
        }
    }
}
