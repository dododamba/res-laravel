<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Services\TaxAssignmentService;
use App\Services\TaxCalculationService;
use App\Services\TaxPaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\TaxeStatut;
use Exception;

/**
 * MobileTaxApiController
 *
 * Endpoints dédiés à la collecte fiscale depuis l'application mobile :
 *   GET  /api/v1/operators/{id}/taxes       → Situation fiscale de l'opérateur
 *   POST /api/v1/tax-payments               → Enregistrer un paiement (online)
 *   POST /api/v1/tax-payments/sync          → Synchroniser un lot hors-ligne
 *   GET  /api/v1/mobile/tax-dashboard       → KPIs fiscaux de l'enquêteur
 */
use App\Services\AgentScopeService;

class MobileTaxApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TaxPaymentService     $taxPaymentService,
        protected TaxAssignmentService  $assignmentService,
        protected TaxCalculationService $calculationService,
        protected AgentScopeService     $scopeService
    ) {}

    // =========================================================================
    // GET /api/v1/operators/{id}/taxes
    // Retourne la situation fiscale complète d'un opérateur pour l'agent mobile
    // =========================================================================
    public function getOperateurTaxes(string $id): JsonResponse
    {
        try {
            $operateur = Operateur::where('id', $id)
                ->orWhere('uuid', $id)
                ->firstOrFail();

            // Vérification de sécurité : l'agent doit avoir le quartier de l'opérateur dans son périmètre
            if (!$this->scopeService->canAccessQuartier($operateur->quartier_id)) {
                return $this->buildResponse(
                    success: false,
                    message: "Accès non autorisé : cet opérateur n'est pas dans votre zone d'affectation.",
                    statusCode: 403
                );
            }

            // Auto-affectation si aucune taxe n'existe encore
            if ($operateur->taxesAffectees()->count() === 0) {
                $this->assignmentService->autoAssignTaxesForOperateur($operateur);
            }

            $taxes = $operateur->taxesAffectees()
                ->with(['taxe', 'paiements', 'exonerations'])
                ->get();

            // Recalcul serveur du statut de chaque taxe avant affichage
            foreach ($taxes as $taxeOp) {
                $this->calculationService->updateTaxeOperateurStatus($taxeOp);
            }

            $taxes->fresh(); // Reload after status update

            return $this->buildResponse(
                success: true,
                message: "Situation fiscale de l'opérateur récupérée.",
                data: [
                    'operateur' => [
                        'id'                => $operateur->id,
                        'uuid'              => $operateur->uuid,
                        'nom_commercial'    => $operateur->nom_commercial,
                        'nom_entreprise'    => $operateur->nom_entreprise,
                        'nif'               => $operateur->nif,
                        'rccm'              => $operateur->rccm,
                        'quartier_id'       => $operateur->quartier_id,
                        'total_du'          => $operateur->total_du,
                        'total_paye'        => $operateur->total_paye,
                        'reste_a_payer'     => $operateur->reste_a_payer,
                        'taux_recouvrement' => $operateur->taux_recouvrement,
                    ],
                    'taxes' => $taxes->map(fn($t) => $this->formatTaxeOp($t)),
                ]
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->buildResponse(
                success: false,
                message: "Opérateur introuvable.",
                statusCode: 404
            );
        } catch (Exception $e) {
            Log::error('[MobileTaxApi] getOperateurTaxes error', ['msg' => $e->getMessage()]);
            return $this->buildResponse(
                success: false,
                message: "Erreur interne lors du chargement de la situation fiscale.",
                statusCode: 500
            );
        }
    }

    // =========================================================================
    // POST /api/v1/tax-payments
    // Enregistre un paiement unique (mode connecté)
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'uuid'              => 'nullable|string|uuid',
                'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
                'montant'           => 'required|numeric|min:1',
                'mode_paiement'     => 'required|string',
                'date_paiement'     => 'nullable|date',
                'reference'         => 'nullable|string|max:255',
                'numero_recu'       => 'nullable|string|max:100',
                'observation'       => 'nullable|string',
                'signature_agent'   => 'nullable|string',
                'signature_client'  => 'nullable|string',
                'justificatif_base64' => 'nullable|string',
                // GPS
                'latitude'          => 'nullable|numeric|between:-90,90',
                'longitude'         => 'nullable|numeric|between:-180,180',
                'altitude'          => 'nullable|numeric',
                'precision_gps'     => 'nullable|numeric|min:0',
                'periode_fiscale'   => 'nullable|string|max:10',
                'device_id'         => 'nullable|string|max:255',
            ]);

            // Rattacher l'agent et l'utilisateur connecté
            if (auth()->check()) {
                $user = auth()->user();
                $validated['user_id'] = $user->id;
                if ($user->agent) {
                    $validated['agent_id'] = $user->agent->id;
                }
            }

            $paiement = $this->taxPaymentService->recordMobilePayment($validated);
            $paiement->load(['taxeOperateur.taxe', 'taxeOperateur.operateur']);

            return $this->buildResponse(
                success: true,
                message: "Paiement enregistré avec succès.",
                data: [
                    'uuid'          => $paiement->uuid,
                    'numero_recu'   => $paiement->numero_recu,
                    'montant'       => $paiement->montant,
                    'reste_a_payer' => $paiement->taxeOperateur?->reste_a_payer,
                    'statut_taxe'   => $paiement->taxeOperateur?->statut?->label(),
                ],
                statusCode: 201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->buildResponse(
                success: false,
                message: "Données invalides.",
                errors: $e->errors(),
                statusCode: 422
            );
        } catch (Exception $e) {
            Log::error('[MobileTaxApi] store error', ['msg' => $e->getMessage()]);
            return $this->buildResponse(
                success: false,
                message: $e->getMessage(),
                statusCode: 422
            );
        }
    }

    // =========================================================================
    // POST /api/v1/tax-payments/sync
    // Synchronise un lot de paiements hors-ligne (idempotent)
    // =========================================================================
    public function syncBatch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'payments'                      => 'required|array|min:1|max:100',
                'payments.*.uuid'               => 'required|string|uuid',
                'payments.*.taxe_operateur_id'  => 'required|exists:taxe_operateurs,id',
                'payments.*.montant'            => 'required|numeric|min:1',
                'payments.*.mode_paiement'      => 'required|string',
                'payments.*.date_paiement'      => 'nullable|date',
                'payments.*.latitude'           => 'nullable|numeric|between:-90,90',
                'payments.*.longitude'          => 'nullable|numeric|between:-180,180',
                'payments.*.precision_gps'      => 'nullable|numeric|min:0',
                'payments.*.periode_fiscale'    => 'nullable|string|max:10',
                'payments.*.device_id'          => 'nullable|string|max:255',
                'payments.*.justificatif_base64' => 'nullable|string',
            ]);

            $userId = auth()->check() ? auth()->id() : null;
            $result = $this->taxPaymentService->processBatch($validated['payments'], $userId);

            return $this->buildResponse(
                success: true,
                message: sprintf(
                    "%d paiement(s) synchronisé(s). %d erreur(s).",
                    count($result['synced']),
                    count($result['errors'])
                ),
                data: $result
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->buildResponse(
                success: false,
                message: "Données de synchronisation invalides.",
                errors: $e->errors(),
                statusCode: 422
            );
        } catch (Exception $e) {
            Log::error('[MobileTaxApi] syncBatch error', ['msg' => $e->getMessage()]);
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de la synchronisation.",
                statusCode: 500
            );
        }
    }

    // =========================================================================
    // GET /api/v1/mobile/tax-dashboard
    // KPIs fiscaux personnalisés pour l'enquêteur connecté
    // =========================================================================
    public function taxDashboard(Request $request): JsonResponse
    {
        try {
            $user  = auth()->user();
            $agent = $this->scopeService->getCurrentAgent($user);

            // Zone d'affectation active via AgentScopeService
            $quartierIds = $this->scopeService->getAuthorizedQuartierIds($user) ?: [];
            $affectation = null;
            if ($agent) {
                $affectation = $agent->affectationsActives()->with(['quartier', 'campagne'])->first();
            }

            // Période fiscale (défaut = année courante)
            $annee = $request->input('annee', date('Y'));

            // ── Totaux de la zone ───────────────────────────────────────────
            $baseQuery = PaiementTaxe::query()
                ->whereYear('date_paiement', $annee)
                ->when($agent, fn($q) => $q->where('agent_id', $agent->id));

            $totalEncaisse    = $baseQuery->sum('montant');
            $nombrePaiements  = $baseQuery->count();

            // Collectes du jour
            $collectesDuJour  = (clone $baseQuery)->whereDate('date_paiement', today())->sum('montant');
            $paiementsDuJour  = (clone $baseQuery)->whereDate('date_paiement', today())->count();

            // Collectes du mois
            $collectesDuMois  = (clone $baseQuery)->whereMonth('date_paiement', date('m'))->sum('montant');

            // Opérateurs recouvrés (au moins 1 taxe soldée)
            $operateursRecouvres = 0;
            $operateursEnRetard  = 0;
            if (!empty($quartierIds)) {
                $operateursRecouvres = \App\Models\TaxeOperateur::query()
                    ->whereHas('operateur', fn($q) => $q->whereIn('quartier_id', $quartierIds))
                    ->where('statut', TaxeStatut::SOLDE)
                    ->whereYear('updated_at', $annee)
                    ->count();

                $operateursEnRetard = \App\Models\TaxeOperateur::query()
                    ->whereHas('operateur', fn($q) => $q->whereIn('quartier_id', $quartierIds))
                    ->where('statut', TaxeStatut::EN_RETARD)
                    ->count();
            }

            return $this->buildResponse(
                success: true,
                message: "Tableau de bord fiscal récupéré.",
                data: [
                    'annee'                => $annee,
                    'agent_nom'            => $agent ? ($agent->personne?->prenom . ' ' . $agent->personne?->nom) : null,
                    'affectation'          => $affectation ? [
                        'quartier'   => $affectation->quartier?->nom,
                        'campagne'   => $affectation->campagne?->nom,
                        'statut'     => $affectation->statut,
                    ] : null,
                    'kpis' => [
                        'total_encaisse'        => round($totalEncaisse, 2),
                        'nombre_paiements'      => $nombrePaiements,
                        'collecte_du_jour'      => round($collectesDuJour, 2),
                        'paiements_du_jour'     => $paiementsDuJour,
                        'collecte_du_mois'      => round($collectesDuMois, 2),
                        'operateurs_recouvres'  => $operateursRecouvres,
                        'operateurs_en_retard'  => $operateursEnRetard,
                    ],
                ]
            );
        } catch (Exception $e) {
            Log::error('[MobileTaxApi] taxDashboard error', ['msg' => $e->getMessage()]);
            return $this->buildResponse(
                success: false,
                message: "Erreur lors du chargement du tableau de bord fiscal.",
                statusCode: 500
            );
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function formatTaxeOp($taxeOp): array
    {
        return [
            'id'               => $taxeOp->id,
            'taxe_id'          => $taxeOp->taxe_id,
            'taxe_code'        => $taxeOp->taxe?->code,
            'taxe_nom'         => $taxeOp->taxe?->nom,
            'categorie'        => $taxeOp->taxe?->categorie,
            'annee_fiscale'    => $taxeOp->annee_fiscale,
            'montant_attendu'  => $taxeOp->montant_attendu,
            'montant_paye'     => $taxeOp->montant_paye,
            'reste_a_payer'    => $taxeOp->reste_a_payer,
            'date_limite'      => $taxeOp->date_limite?->format('Y-m-d'),
            'statut'           => $taxeOp->statut?->value,
            'statut_label'     => $taxeOp->statut?->label(),
            'jours_retard'     => $taxeOp->jours_retard,
            'est_solde'        => $taxeOp->est_solde,
            'paiements'        => $taxeOp->paiements->map(fn($p) => [
                'uuid'         => $p->uuid,
                'numero_recu'  => $p->numero_recu,
                'montant'      => $p->montant,
                'date'         => $p->date_paiement?->format('Y-m-d'),
                'mode'         => is_object($p->mode_paiement) ? $p->mode_paiement->value : $p->mode_paiement,
            ]),
        ];
    }
}
