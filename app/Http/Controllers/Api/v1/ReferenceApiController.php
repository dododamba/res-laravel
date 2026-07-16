<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Secteur;
use App\Models\Parameters\Avenue;
use App\Models\Parameters\BesoinPrioritaire;
use App\Models\Parameters\Fonction;
use App\Models\Parameters\CategorieActivite;
use App\Models\Parameters\TypeBatiment;
use App\Models\Parameters\TypePropriete;
use App\Models\Parameters\SourceEau;
use App\Models\Parameters\SourceEnergie;
use App\Models\Parameters\Assainissement;
use App\Models\Parameters\GestionDechet;
use App\Models\Campagne;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReferenceApiController extends Controller
{
    use ApiResponse; // Fournit buildResponse()

    /**
     * Retourne l'intégralité des référentiels paramétriques et géographiques
     * nécessaires au fonctionnement hors-ligne (offline) de l'application mobile.
     */
    public function index(): JsonResponse
    {
        // Récupération de l'ensemble des données de références actives
        $quartiers = Quartier::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone', 'chef_nom', 'chef_telephone']);
        $carres = Carre::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'quartier_id', 'est_chef', 'chef_nom', 'chef_telephone']);
        $secteurs = Secteur::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'carre_id']);
        $avenues = Avenue::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'secteur_id']);
        
        $priorites = BesoinPrioritaire::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $fonctions = Fonction::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $campagnes = Campagne::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'statut']);

        $usagesPrincipaux = CategorieActivite::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $typesConstruction = TypeBatiment::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $statutsFonciers = TypePropriete::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $sourcesEau = SourceEau::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $sourcesEnergie = SourceEnergie::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $assainissements = Assainissement::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);
        $gestionDechets = GestionDechet::active()->orderBy('ordre_affichage')->get(['id', 'nom', 'code', 'slug', 'couleur', 'icone']);

        return $this->buildResponse(
            success: true,
            message: "Référentiels mobiles récupérés avec succès.",
            data: [
                'geographie' => [
                    'quartiers' => $quartiers,
                    'carres' => $carres,
                    'secteurs' => $secteurs,
                    'avenues' => $avenues,
                ],
                'parametres' => [
                    'besoins_prioritaires' => $priorites,
                    'fonctions_agents' => $fonctions,
                    'usages_principaux' => $usagesPrincipaux,
                    'types_construction' => $typesConstruction,
                    'statuts_fonciers' => $statutsFonciers,
                    'sources_eau' => $sourcesEau,
                    'sources_energie' => $sourcesEnergie,
                    'assainissements' => $assainissements,
                    'gestion_dechets' => $gestionDechets,
                ],
                'campagnes' => $campagnes,
            ]
        );
    }
}
