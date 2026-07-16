<?php

namespace Database\Seeders;

use App\Models\Parameters\Fonction;
use App\Models\Parameters\BesoinPrioritaire;
use App\Models\Parameters\CategorieActivite;
use App\Models\Parameters\TypeBatiment;
use App\Models\Parameters\TypePropriete;
use App\Models\Parameters\SourceEau;
use App\Models\Parameters\SourceEnergie;
use App\Models\Parameters\Assainissement;
use App\Models\Parameters\GestionDechet;
use App\Models\Parameters\CategorieOperateur;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    /**
     * Exécute le peuplement des tables de paramétrages d'origine.
     */
    public function run(): void
    {
        // 1. Peuplement des fonctions d'administration et d'affectation terrain
        $fonctions = [
            ['nom' => 'Enquêteur / Recenseur', 'code' => 'ENQUETEUR', 'description' => 'Agent de collecte terrain', 'couleur' => '#009EF7', 'icone' => 'bi-pencil', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Chef de Carré', 'code' => 'CHEF_CARRE', 'description' => 'Superviseur de bloc/carré', 'couleur' => '#50CD89', 'icone' => 'bi-shield-check', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Délégué de Quartier', 'code' => 'DELEGUE', 'description' => 'Superviseur de quartier', 'couleur' => '#7239EA', 'icone' => 'bi-geo-alt', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Expert Démographe', 'code' => 'EXPERT', 'description' => 'Analyste d\'exploitation des données', 'couleur' => '#FFC700', 'icone' => 'bi-graph-up', 'ordre_affichage' => 40, 'is_default' => false],
            ['nom' => 'Administrateur', 'code' => 'ADMIN', 'description' => 'Gestion d\'exploitation globale', 'couleur' => '#F1416C', 'icone' => 'bi-gear', 'ordre_affichage' => 50, 'is_default' => false],
        ];

        foreach ($fonctions as $fonc) {
            Fonction::firstOrCreate(
                ['code' => $fonc['code']],
                [
                    'nom' => $fonc['nom'],
                    'description' => $fonc['description'],
                    'couleur' => $fonc['couleur'],
                    'icone' => $fonc['icone'],
                    'ordre_affichage' => $fonc['ordre_affichage'],
                    'is_default' => $fonc['is_default'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Peuplement des besoins prioritaires des ménages
        $besoins = [
            ['nom' => 'Accès à l\'eau potable', 'code' => 'EAU', 'description' => 'Forage, puits améliorés, réseau', 'couleur' => '#009EF7', 'icone' => 'bi-water', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Électrification stable', 'code' => 'ELECTRICITE', 'description' => 'Réseau SNE, solaire, éclairage public', 'couleur' => '#FFC700', 'icone' => 'bi-lightning-charge', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Sécurisation du quartier', 'code' => 'SECURITE', 'description' => 'Poste de police, patrouilles, éclairage', 'couleur' => '#F1416C', 'icone' => 'bi-shield-lock', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Santé et Dispensaires', 'code' => 'SANTE', 'description' => 'Accès aux soins de proximité', 'couleur' => '#50CD89', 'icone' => 'bi-heart-pulse', 'ordre_affichage' => 40, 'is_default' => false],
            ['nom' => 'Évacuation des déchets', 'code' => 'ASSAINISSEMENT', 'description' => 'Caniveaux, poubelles collectives', 'couleur' => '#7239EA', 'icone' => 'bi-trash', 'ordre_affichage' => 50, 'is_default' => false],
            ['nom' => 'Écoles et Éducation', 'code' => 'EDUCATION', 'description' => 'Construction de salles de classe', 'couleur' => '#00A3FF', 'icone' => 'bi-book', 'ordre_affichage' => 60, 'is_default' => false],
        ];

        foreach ($besoins as $besoin) {
            BesoinPrioritaire::firstOrCreate(
                ['code' => $besoin['code']],
                [
                    'nom' => $besoin['nom'],
                    'description' => $besoin['description'],
                    'couleur' => $besoin['couleur'],
                    'icone' => $besoin['icone'],
                    'ordre_affichage' => $besoin['ordre_affichage'],
                    'is_default' => $besoin['is_default'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Peuplement des catégories d'activité (usages principaux)
        $categoriesActivite = [
            ['nom' => 'Habitation', 'code' => 'HABITATION', 'description' => 'Usage habitation exclusivement', 'couleur' => '#009EF7', 'icone' => 'bi-house', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Mixte (Habitation + Commerce)', 'code' => 'MIXTE', 'description' => 'Usage mixte', 'couleur' => '#FFC700', 'icone' => 'bi-shop-window', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Professionnel', 'code' => 'PROFESSIONNEL', 'description' => 'Usage professionnel uniquement', 'couleur' => '#F1416C', 'icone' => 'bi-briefcase', 'ordre_affichage' => 30, 'is_default' => false],
        ];
        foreach ($categoriesActivite as $item) {
            CategorieActivite::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 4. Peuplement des types de bâtiment (constructions)
        $typesBatiment = [
            ['nom' => 'Villa', 'code' => 'VILLA', 'description' => 'Villa ou maison individuelle', 'couleur' => '#009EF7', 'icone' => 'bi-house-fill', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Appartement', 'code' => 'APPARTEMENT', 'description' => 'Appartement dans immeuble', 'couleur' => '#50CD89', 'icone' => 'bi-building', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Baraque', 'code' => 'BARAQUE', 'description' => 'Baraque ou habitat précaire', 'couleur' => '#F1416C', 'icone' => 'bi-patch-minus', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Immeuble', 'code' => 'IMMEUBLE', 'description' => 'Immeuble entier', 'couleur' => '#7239EA', 'icone' => 'bi-buildings', 'ordre_affichage' => 40, 'is_default' => false],
        ];
        foreach ($typesBatiment as $item) {
            TypeBatiment::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 5. Peuplement des types de propriété (statuts fonciers)
        $typesPropriete = [
            ['nom' => 'Titre Foncier', 'code' => 'TITRE_FONCIER', 'description' => 'Détenteur d\'un Titre Foncier (TF)', 'couleur' => '#50CD89', 'icone' => 'bi-file-earmark-check', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Bail', 'code' => 'BAIL', 'description' => 'Location avec bail', 'couleur' => '#009EF7', 'icone' => 'bi-file-earmark-text', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Droit Coutumier', 'code' => 'DROIT_COUTUMIER', 'description' => 'Occupation de droit coutumier', 'couleur' => '#FFC700', 'icone' => 'bi-people', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Sans Papier', 'code' => 'SANS_PAPIER', 'description' => 'Sans papiers (Occupation spontanée)', 'couleur' => '#F1416C', 'icone' => 'bi-file-earmark-x', 'ordre_affichage' => 40, 'is_default' => false],
        ];
        foreach ($typesPropriete as $item) {
            TypePropriete::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 6. Peuplement des sources d'eau
        $sourcesEau = [
            ['nom' => 'Robinet', 'code' => 'ROBINET', 'description' => 'Robinet intérieur branché au réseau', 'couleur' => '#50CD89', 'icone' => 'bi-droplet-half', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Borne', 'code' => 'BORNE', 'description' => 'Borne fontaine publique', 'couleur' => '#009EF7', 'icone' => 'bi-droplet', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Puits', 'code' => 'PUITS', 'description' => 'Puits traditionnel ou moderne', 'couleur' => '#FFC700', 'icone' => 'bi-bucket', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Aucun', 'code' => 'AUCUN', 'description' => 'Pas d\'accès direct', 'couleur' => '#F1416C', 'icone' => 'bi-x-circle', 'ordre_affichage' => 40, 'is_default' => false],
        ];
        foreach ($sourcesEau as $item) {
            SourceEau::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 7. Peuplement des sources d'énergie
        $sourcesEnergie = [
            ['nom' => 'TchadElec', 'code' => 'TCHADELEC', 'description' => 'Réseau TchadElec / SNE', 'couleur' => '#50CD89', 'icone' => 'bi-lightning-charge-fill', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Solaire', 'code' => 'SOLAIRE', 'description' => 'Panneaux Solaires autonomes', 'couleur' => '#FFC700', 'icone' => 'bi-sun', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Groupe', 'code' => 'GROUPE', 'description' => 'Groupe Électrogène', 'couleur' => '#7239EA', 'icone' => 'bi-cpu', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Aucun', 'code' => 'AUCUN', 'description' => 'Pas d\'électricité', 'couleur' => '#F1416C', 'icone' => 'bi-x-circle', 'ordre_affichage' => 40, 'is_default' => false],
        ];
        foreach ($sourcesEnergie as $item) {
            SourceEnergie::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 8. Peuplement des modes d'assainissement
        $assainissements = [
            ['nom' => 'Fosse septique', 'code' => 'FOSSE_SEPTIQUE', 'description' => 'Fosse septique individuelle', 'couleur' => '#50CD89', 'icone' => 'bi-shield-check', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Tout-à-l\'égout', 'code' => 'TOUT_A_L_EGOUT', 'description' => 'Réseau collectif', 'couleur' => '#009EF7', 'icone' => 'bi-shuffle', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Latrines', 'code' => 'LATRINES', 'description' => 'Latrines simples', 'couleur' => '#FFC700', 'icone' => 'bi-filter', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Aucun', 'code' => 'AUCUN', 'description' => 'Pas d\'assainissement', 'couleur' => '#F1416C', 'icone' => 'bi-x-circle', 'ordre_affichage' => 40, 'is_default' => false],
        ];
        foreach ($assainissements as $item) {
            Assainissement::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 9. Peuplement des modes de gestion de déchets
        $gestionDechets = [
            ['nom' => 'Camion poubelle', 'code' => 'CAMION', 'description' => 'Collecte par camion', 'couleur' => '#50CD89', 'icone' => 'bi-truck', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Incinération', 'code' => 'INCINERATION', 'description' => 'Brûlage sur place', 'couleur' => '#FFC700', 'icone' => 'bi-fire', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Décharge sauvage', 'code' => 'DECHARGE', 'description' => 'Décharge sauvage', 'couleur' => '#F1416C', 'icone' => 'bi-trash3', 'ordre_affichage' => 30, 'is_default' => false],
        ];
        foreach ($gestionDechets as $item) {
            GestionDechet::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }

        // 10. Peuplement des catégories d'opérateur
        $categoriesOperateur = [
            ['nom' => 'Commerce de détail', 'code' => 'COMMERCE_DETAIL', 'description' => 'Boutiques, épiceries, kiosques', 'couleur' => '#009EF7', 'icone' => 'bi-shop', 'ordre_affichage' => 10, 'is_default' => true],
            ['nom' => 'Restauration / Alimentation', 'code' => 'RESTAURATION', 'description' => 'Restaurants, maquis, gargotes', 'couleur' => '#50CD89', 'icone' => 'bi-cup-straw', 'ordre_affichage' => 20, 'is_default' => false],
            ['nom' => 'Services (Coiffure, Lavage...)', 'code' => 'SERVICES', 'description' => 'Salons, pressings, ateliers', 'couleur' => '#FFC700', 'icone' => 'bi-tools', 'ordre_affichage' => 30, 'is_default' => false],
            ['nom' => 'Santé / Pharmacie', 'code' => 'SANTE_PHARMACIE', 'description' => 'Pharmacies, cliniques', 'couleur' => '#F1416C', 'icone' => 'bi-heart-pulse-fill', 'ordre_affichage' => 40, 'is_default' => false],
        ];
        foreach ($categoriesOperateur as $item) {
            CategorieOperateur::firstOrCreate(['code' => $item['code']], $item + ['is_active' => true]);
        }
    }
}
