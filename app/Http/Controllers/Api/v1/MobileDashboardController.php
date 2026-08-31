<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\Affectation;
use App\Models\Parameters\Quartier;
use App\Services\AgentScopeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use DB;

class MobileDashboardController extends Controller
{
    use ApiResponse;

    protected AgentScopeService $scopeService;
    protected \App\Services\StatisticsService $statsService;

    public function __construct(AgentScopeService $scopeService, \App\Services\StatisticsService $statsService)
    {
        $this->scopeService = $scopeService;
        $this->statsService = $statsService;
    }

    /**
     * Endpoint API : Indicateurs de pilotage dynamiques (Mobile / Web API)
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = $this->scopeService->isAdmin($user);

        // 1. Calcul des indicateurs clés réels cloisonnés par le scope géographique
        $menagesQuery = Recensement::query();
        $habitatsQuery = Maison::query();
        $operateursQuery = Operateur::query();

        $this->scopeService->applyScope($menagesQuery, Recensement::class, $user);
        $this->scopeService->applyScope($habitatsQuery, Maison::class, $user);
        $this->scopeService->applyScope($operateursQuery, Operateur::class, $user);

        $menagesCount = $menagesQuery->count();
        $habitatsCount = $habitatsQuery->count();
        $operateursCount = $operateursQuery->count();

        // 2. Génération d'une Timeline d'activités dynamique basée sur le scope
        $activities = [];

        // Derniers recensements (ménages)
        $recensementsQuery = Recensement::orderBy('created_at', 'desc')->limit(5);
        $this->scopeService->applyScope($recensementsQuery, Recensement::class, $user);
        foreach ($recensementsQuery->get() as $r) {
            $activities[] = [
                'id' => 'recensement_' . $r->id,
                'title' => 'Enquête Ménage',
                'description' => "Recensement de la famille de Chef {$r->chef_prenom} {$r->chef_nom}",
                'timestamp' => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : now()->toDateTimeString(),
                'type' => 'menage',
                'status' => 'success',
            ];
        }

        // Dernières habitations (maisons)
        $maisonsQuery = Maison::orderBy('created_at', 'desc')->limit(5);
        $this->scopeService->applyScope($maisonsQuery, Maison::class, $user);
        foreach ($maisonsQuery->get() as $m) {
            $activities[] = [
                'id' => 'maison_' . $m->id,
                'title' => 'Enquête Habitation',
                'description' => "Saisie de l'Habitation n°{$m->numero_porte} ({$m->adresse})",
                'timestamp' => $m->created_at ? $m->created_at->format('Y-m-d H:i:s') : now()->toDateTimeString(),
                'type' => 'maison',
                'status' => 'success',
            ];
        }

        // Derniers opérateurs (commerces)
        $operateursQueryList = Operateur::orderBy('created_at', 'desc')->limit(5);
        $this->scopeService->applyScope($operateursQueryList, Operateur::class, $user);
        foreach ($operateursQueryList->get() as $o) {
            $activities[] = [
                'id' => 'operateur_' . $o->id,
                'title' => 'Opérateur Économique',
                'description' => "Enregistrement du commerce " . ($o->nom_commercial ?: $o->nom_entreprise ?: 'Inconnu'),
                'timestamp' => $o->created_at ? $o->created_at->format('Y-m-d H:i:s') : now()->toDateTimeString(),
                'type' => 'operateur',
                'status' => 'success',
            ];
        }

        // Tri combiné descendant par date de création
        usort($activities, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

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
     * Endpoint API : Liste des affectations actives
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $user = auth()->user();
        $agent = $this->scopeService->getCurrentAgent($user);

        if (!$agent) {
            return $this->buildResponse(
                success: true,
                message: "Aucune affectation trouvée.",
                data: []
            );
        }

        $affectations = $this->scopeService->getActiveAssignments($user);

        $mapped = [];
        foreach ($affectations as $aff) {
            $realisedCount = 0;
            if ($aff->quartier_id) {
                $realisedCount = Recensement::where('quartier_id', $aff->quartier_id)->count();
            } elseif ($aff->carre_id) {
                $realisedCount = Recensement::where('carre_id', $aff->carre_id)->count();
            }

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
                'responsable' => $agent->personne ? trim("{$agent->personne->prenom} {$agent->personne->nom}") : 'Agent',
                'telephoneResponsable' => $agent->personne ? $agent->personne->telephone : '',
                'chefDeCarre' => $chefDeCarre,
                'telephoneChefDeCarre' => $telChefDeCarre,
                'dateDebut' => $aff->date_debut ? Carbon::parse($aff->date_debut)->toDateString() : '2026-06-01',
                'dateFin' => $aff->date_fin ? Carbon::parse($aff->date_fin)->toDateString() : '2026-07-31',
                'statut' => 'Active',
                'fichesAttribuees' => 100,
                'fichesRealisees' => $realisedCount,
            ];
        }

        return $this->buildResponse(
            success: true,
            message: "Affectations récupérées avec succès.",
            data: $mapped
        );
    }

    /**
     * Endpoint API : Statistiques démographiques globales (Niveau 1)
     */
    public function getGlobalStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->statsService->getGlobalStats($user);

        return $this->buildResponse(
            success: true,
            message: "Statistiques globales récupérées avec succès.",
            data: $stats
        );
    }

    /**
     * Endpoint API : Statistiques Détaillées par Quartier (Niveau 2)
     * (GET /api/v1/dashboard/statistics, GET /api/v1/statistics/by-quartier, GET /api/v1/statistics/quartiers)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = auth()->user();
        $requestedQuartierId = $request->input('quartier_id');

        try {
            $data = $this->statsService->getQuartierStats($user, $requestedQuartierId ? (string)$requestedQuartierId : null);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->buildResponse(
                success: false,
                message: $e->getMessage(),
                statusCode: 403
            );
        }

        // Structural adaptation to preserve backward compatibility for byQuartier & scope payload
        $data['scope'] = [
            'type' => $this->scopeService->isAdmin($user) ? 'global' : 'quartier',
            'quartiers' => array_map(fn($item) => ['id' => $item['id'], 'nom' => $item['nom']], $data['items']),
        ];

        $data['byQuartier'] = array_map(function ($item) {
            return array_merge($item, [
                'quartier_id' => (string) $item['id'],
                'quartier_nom' => $item['nom'],
                'taxes_encaissees' => $item['paiements'],
                'montant_encaisse' => $item['montantEncaisse'],
                'fiches_synchronisees' => $item['fiches_validees'],
            ]);
        }, $data['items']);

        return $this->buildResponse(
            success: true,
            message: "Statistiques par quartier récupérées avec succès.",
            data: $data
        );
    }

    /**
     * Endpoint API : Statistiques Détaillées par Carré (Niveau 3)
     * (GET /api/v1/statistics/quartiers/{quartier}/carres, GET /api/v1/statistics/carres)
     */
    public function getCarreStatistics(Request $request, $quartier = null): JsonResponse
    {
        $user = auth()->user();
        
        if ($quartier instanceof \App\Models\Parameters\Quartier) {
            $qId = (string) $quartier->id;
        } elseif (!empty($quartier)) {
            $qId = (string) $quartier;
        } else {
            $qId = $request->input('quartier_id') ? (string) $request->input('quartier_id') : null;
        }

        $cId = $request->input('carre_id') ? (string) $request->input('carre_id') : null;

        if (!$qId) {
            return $this->buildResponse(
                success: false,
                message: "Le paramètre quartier_id est obligatoire pour consulter les carrés.",
                statusCode: 422
            );
        }

        try {
            $data = $this->statsService->getCarreStats($qId, $user, $cId);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->buildResponse(
                success: false,
                message: $e->getMessage(),
                statusCode: 403
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->buildResponse(
                success: false,
                message: "Quartier introuvable.",
                statusCode: 404
            );
        }

        return $this->buildResponse(
            success: true,
            message: "Statistiques des carrés récupérées avec succès.",
            data: $data
        );
    }
}
