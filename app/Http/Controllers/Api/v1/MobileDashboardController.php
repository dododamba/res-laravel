<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\Affectation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class MobileDashboardController extends Controller
{
    use ApiResponse;

    /**
     * Endpoint API : Indicateurs de pilotage dynamiques (Mobile / Web API)
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $agent = $user->agent;
        $agentId = $agent ? $agent->id : null;

        // 1. Calcul des indicateurs clés réels (global pour les admins, cloisonné pour les enquêteurs)
        if ($isAdmin) {
            $menagesCount = Recensement::count();
            $habitatsCount = Maison::count();
            $operateursCount = Operateur::count();
        } else {
            $menagesCount = $agentId ? Recensement::where('enqueteur_id', $agentId)->count() : 0;
            $habitatsCount = $agentId ? Maison::where('enqueteur_id', $agentId)->count() : 0;
            $operateursCount = $agentId ? Operateur::where('enqueteur_id', $agentId)->count() : 0;
        }

        // 2. Génération d'une Timeline d'activités strictement dynamique basée sur les dernières saisies
        $activities = [];

        if ($isAdmin || $agentId) {
            // Derniers recensements (ménages)
            $recensementsQuery = Recensement::orderBy('created_at', 'desc')->limit(5);
            if (!$isAdmin) {
                $recensementsQuery->where('enqueteur_id', $agentId);
            }
            $recensements = $recensementsQuery->get();

            foreach ($recensements as $r) {
                $activities[] = [
                    'id' => 'recensement_' . $r->id,
                    'title' => 'Enquête Ménage',
                    'description' => "Recensement de la famille de Chef {$r->chef_prenom} {$r->chef_nom}",
                    'timestamp' => $r->created_at->format('Y-m-d H:i:s'),
                    'type' => 'menage',
                    'status' => 'success',
                ];
            }

            // Dernières habitations (maisons)
            $maisonsQuery = Maison::orderBy('created_at', 'desc')->limit(5);
            if (!$isAdmin) {
                $maisonsQuery->where('enqueteur_id', $agentId);
            }
            $maisons = $maisonsQuery->get();

            foreach ($maisons as $m) {
                $activities[] = [
                    'id' => 'maison_' . $m->id,
                    'title' => 'Enquête Habitation',
                    'description' => "Saisie de l'Habitation n°{$m->numero_porte} ({$m->adresse})",
                    'timestamp' => $m->created_at->format('Y-m-d H:i:s'),
                    'type' => 'maison',
                    'status' => 'success',
                ];
            }

            // Derniers opérateurs (commerces)
            $operateursQuery = Operateur::orderBy('created_at', 'desc')->limit(5);
            if (!$isAdmin) {
                $operateursQuery->where('enqueteur_id', $agentId);
            }
            $operateurs = $operateursQuery->get();

            foreach ($operateurs as $o) {
                $activities[] = [
                    'id' => 'operateur_' . $o->id,
                    'title' => 'Opérateur Économique',
                    'description' => "Enregistrement du commerce " . ($o->nom_commercial ?: $o->nom_entreprise ?: 'Inconnu'),
                    'timestamp' => $o->created_at->format('Y-m-d H:i:s'),
                    'type' => 'operateur',
                    'status' => 'success',
                ];
            }
        }

        // Tri combiné descendant par date de création
        usort($activities, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        // Limiter aux 8 activités récentes les plus pertinentes
        $recentActivity = array_slice($activities, 0, 8);

        return $this->buildResponse(
            success: true,
            message: "Indicateurs de pilotage récupérés avec succès.",
            data: [
                'stats' => [
                    'menages' => $menagesCount,
                    'habitats' => $habitatsCount,
                    'fiscal' => $operateursCount,
                ],
                'recentActivity' => $recentActivity
            ]
        );
    }

    /**
     * Endpoint API : Liste historique et active des affectations de terrain (Secteurs de l'Agent)
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $user = auth()->user();
        $agent = $user->agent;

        if (!$agent) {
            return $this->buildResponse(
                success: true,
                message: "Aucune affectation trouvée.",
                data: []
            );
        }

        // Récupération des affectations actives
        $affectations = Affectation::with(['quartier', 'carre.chef_carre.personne'])
            ->where('agent_id', $agent->id)
            ->where('statut', 'actif')
            ->get();

        $mapped = [];
        foreach ($affectations as $aff) {
            
            // Calcul des fiches réalisées sur cette affectation précise
            $realisedCount = 0;
            if ($aff->quartier_id) {
                $realisedCount = Recensement::where('enqueteur_id', $agent->id)
                    ->where('quartier_id', $aff->quartier_id)
                    ->count();
            } elseif ($aff->carre_id) {
                $realisedCount = Recensement::where('enqueteur_id', $agent->id)
                    ->where('carre_id', $aff->carre_id)
                    ->count();
            }

            // Informations du superviseur (Chef de Carré)
            $chefDeCarre = null;
            $telChefDeCarre = null;
            if ($aff->carre && $aff->carre->chef_carre) {
                $chefDeCarre = trim("{$aff->carre->chef_carre->personne->prenom} {$aff->carre->chef_carre->personne->nom}");
                $telChefDeCarre = $aff->carre->chef_carre->personne->telephone;
            }

            $mapped[] = [
                'id' => $aff->id,
                'campaign' => [
                    'id' => 1,
                    'nom' => 'Recensement National 2026',
                    'statut' => 'ACTIVE',
                    'dateDebut' => '2026-06-01',
                    'dateFin' => '2026-07-31',
                    'annee' => 2026,
                ],
                'quartier' => $aff->quartier ? [
                    'id' => $aff->quartier->id,
                    'nom' => $aff->quartier->nom,
                ] : null,
                'carre' => $aff->carre ? [
                    'id' => $aff->carre->id,
                    'nom' => $aff->carre->nom,
                ] : null,
                'secteurs' => [],
                'responsable' => trim("{$agent->personne->prenom} {$agent->personne->nom}"),
                'telephoneResponsable' => $agent->personne->telephone,
                'chefDeCarre' => $chefDeCarre,
                'telephoneChefDeCarre' => $telChefDeCarre,
                'dateDebut' => $aff->date_debut ? Carbon::parse($aff->date_debut)->toDateString() : '2026-06-01',
                'dateFin' => $aff->date_fin ? Carbon::parse($aff->date_fin)->toDateString() : '2026-07-31',
                'statut' => 'Active',
                'fichesAttribuees' => 100, // Objectif par défaut
                'fichesRealisees' => $realisedCount,
            ];
        }

        return $this->buildResponse(
            success: true,
            message: "Affectations récupérées avec succès.",
            data: $mapped
        );
    }
}
