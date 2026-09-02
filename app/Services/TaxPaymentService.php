<?php

namespace App\Services;

use App\Enums\TaxeStatut;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\HistoriquePaiement;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\TaxeOperateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * TaxPaymentService
 *
 * Service dédié à l'enregistrement des paiements de taxes par les agents mobiles.
 * Règles fondamentales :
 *   1. Le montant restant DÛ est TOUJOURS recalculé côté serveur (le mobile ne décide jamais).
 *   2. L'UUID généré par le mobile est la clé d'idempotence (rejeu sans doublon).
 *   3. L'agent ne peut encaisser que dans sa zone d'affectation active.
 */
class TaxPaymentService
{
    public function __construct(
        protected TaxCalculationService $calculationService,
        protected TaxAssignmentService $assignmentService
    ) {}

    /**
     * Enregistre un paiement mobile avec idempotence, validation d'affectation et traçabilité GPS.
     *
     * @param  array $data  Données validées transmises par l'API
     * @return PaiementTaxe
     * @throws ValidationException
     */
    public function recordMobilePayment(array $data): PaiementTaxe
    {
        return DB::transaction(function () use ($data) {

            // ─── Idempotence : rejeu sans doublon ────────────────────────────
            $clientUuid = $data['uuid'] ?? null;
            if ($clientUuid) {
                $existing = PaiementTaxe::where('uuid', $clientUuid)->first();
                if ($existing) {
                    Log::info('[TaxPaymentService] Idempotence: paiement déjà enregistré.', [
                        'uuid' => $clientUuid,
                        'numero_recu' => $existing->numero_recu,
                    ]);
                    return $existing;
                }
            }

            // ─── Chargement ou Résolution de la taxe assignée à l'opérateur ───
            $taxeOp = null;
            $taxeOpId = $data['taxe_operateur_id'] ?? null;
            if ($taxeOpId && $taxeOpId !== '0' && $taxeOpId !== 0) {
                $taxeOp = TaxeOperateur::with(['operateur', 'taxe'])->lockForUpdate()->find($taxeOpId);
            }

            if (!$taxeOp && !empty($data['operateur_id'])) {
                $operateur = Operateur::where('id', $data['operateur_id'])
                    ->orWhere('uuid', $data['operateur_id'])
                    ->first();

                if ($operateur) {
                    $taxeSearch = $data['taxe_code'] ?? $data['selected_tax_type_id'] ?? $data['taxe_nom'] ?? null;
                    $taxe = null;
                    if ($taxeSearch) {
                        $cleanSearch = str_replace('taxe_', '', strtolower($taxeSearch));
                        $taxe = \App\Models\Taxe::where('id', $taxeSearch)
                            ->orWhere('uuid', $taxeSearch)
                            ->orWhere('code', $taxeSearch)
                            ->orWhere('nom', 'like', "%{$taxeSearch}%")
                            ->orWhere('nom', 'like', "%{$cleanSearch}%")
                            ->first();
                    }

                    if (!$taxe) {
                        $taxe = \App\Models\Taxe::actif()->first();
                    }

                    if ($taxe) {
                        $anneeFiscale = (int) ($data['periode_fiscale'] ?? date('Y'));
                        $assignedModel = $this->assignmentService->assignTaxToOperateur($operateur, $taxe, $anneeFiscale);
                        $taxeOp = TaxeOperateur::with(['operateur', 'taxe'])->lockForUpdate()->find($assignedModel->id);
                    }
                }
            }

            if (!$taxeOp) {
                throw ValidationException::withMessages([
                    'taxe_operateur_id' => 'Taxe affectée introuvable. Veuillez vérifier l\'opérateur et le type de taxe sélectionné.',
                ]);
            }

            // Règle : une taxe déjà soldée ne peut être payée de nouveau
            if ($taxeOp->statut === TaxeStatut::SOLDE) {
                throw ValidationException::withMessages([
                    'taxe_operateur_id' => 'Cette taxe est déjà intégralement soldée.',
                ]);
            }

            // ─── Validation de l'agent et de l'affectation ───────────────────
            $agent = null;
            if (!empty($data['agent_id'])) {
                $agent = Agent::with('affectationsActives')->find($data['agent_id']);
                if ($agent) {
                    $this->validateAgentAssignment($agent, $taxeOp->operateur);
                }
            }

            // ─── Validation du montant côté serveur ──────────────────────────
            $montant = (float) ($data['montant'] ?? 0);
            if ($montant <= 0) {
                throw ValidationException::withMessages([
                    'montant' => 'Le montant du paiement doit être supérieur à 0 FCFA.',
                ]);
            }

            // Le mobile peut proposer un montant supérieur au reste dû ;
            // On plafonne au reste réel pour éviter tout sur-paiement.
            $resteServeur = (float) $taxeOp->reste_a_payer;
            if ($montant > $resteServeur && $resteServeur > 0) {
                $montant = $resteServeur;
                Log::warning('[TaxPaymentService] Montant plafonné au reste dû.', [
                    'montant_mobile' => $data['montant'],
                    'montant_plafonne' => $montant,
                    'taxe_operateur_id' => $taxeOp->id,
                ]);
            }

            // ─── Génération du numéro de reçu ────────────────────────────────
            $numeroRecu = $data['numero_recu'] ?? null;
            if (!$numeroRecu || PaiementTaxe::where('numero_recu', $numeroRecu)->exists()) {
                $numeroRecu = 'MOB-' . date('Ymd') . '-' . strtoupper(Str::random(8));
            }

            $uuid = $clientUuid ?: (string) Str::uuid();

            $lat  = $data['latitude'] ?? $data['gps_latitude'] ?? null;
            $lng  = $data['longitude'] ?? $data['gps_longitude'] ?? null;
            $acc  = $data['precision_gps'] ?? $data['gps_accuracy'] ?? null;
            $alt  = $data['altitude'] ?? $data['gps_altitude'] ?? null;
            $per  = $data['periode_fiscale'] ?? $data['periode'] ?? date('Y');
            $dev  = $data['device_id'] ?? null;

            // ─── Création du paiement ─────────────────────────────────────────
            $paiement = PaiementTaxe::create([
                'id'              => $uuid,
                'uuid'            => $uuid,
                'taxe_operateur_id' => $taxeOp->id,
                'date_paiement'   => $data['date_paiement'] ?? now(),
                'montant'         => $montant,
                'mode_paiement'   => $data['mode_paiement'] ?? 'Espèces',
                'reference'       => $data['reference'] ?? null,
                'numero_recu'     => $numeroRecu,
                'agent_id'        => $data['agent_id'] ?? null,
                'user_id'         => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'observation'     => $data['observation'] ?? null,
                'signature_agent' => $data['signature_agent'] ?? null,
                'signature_client' => $data['signature_client'] ?? null,
                'statut'          => 'valide',
                // Champs GPS & Période (nommage officiel base de données)
                'gps_latitude'    => $lat,
                'gps_longitude'   => $lng,
                'gps_altitude'    => $alt,
                'gps_accuracy'    => $acc,
                'periode'         => $per,
                'device_id'       => $dev,
            ]);

            // ─── Justificatif base64 (photo de reçu papier) ──────────────────
            if (!empty($data['justificatif_base64'])) {
                try {
                    $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $data['justificatif_base64']));
                    $tmpPath = sys_get_temp_dir() . '/receipt_' . $uuid . '.jpg';
                    file_put_contents($tmpPath, $imageData);
                    $paiement->addMedia($tmpPath)
                             ->usingName('recu_' . $paiement->numero_recu)
                             ->toMediaCollection('justificatifs_paiement');
                } catch (\Throwable $e) {
                    Log::warning('[TaxPaymentService] Échec upload justificatif base64.', [
                        'uuid' => $uuid,
                        'error' => $e->getMessage(),
                    ]);
                    // Non-bloquant : le paiement est créé même si la photo échoue
                }
            }

            // ─── Recalcul serveur du statut de la taxe ───────────────────────
            $this->calculationService->updateTaxeOperateurStatus($taxeOp);

            // ─── Traçabilité : historique de paiement ────────────────────────
            HistoriquePaiement::create([
                'taxe_operateur_id' => $taxeOp->id,
                'paiement_id'       => $paiement->id,
                'action'            => 'encaissement_mobile',
                'details'           => [
                    'numero_recu'    => $paiement->numero_recu,
                    'montant'        => $paiement->montant,
                    'mode_paiement'  => is_object($paiement->mode_paiement)
                        ? $paiement->mode_paiement->value
                        : $paiement->mode_paiement,
                    'reference'      => $paiement->reference,
                    'nouveau_reste'  => $taxeOp->reste_a_payer,
                    'nouveau_statut' => $taxeOp->statut->label(),
                    'gps'            => [
                        'lat' => $paiement->latitude,
                        'lng' => $paiement->longitude,
                    ],
                    'source'         => 'mobile',
                ],
                'user_identifier'   => auth()->check() ? auth()->user()->email : ($agent?->personne?->email ?? 'mobile_agent'),
            ]);

            // ─── Log d'audit système ──────────────────────────────────────────
            AuditLog::create([
                'id'             => (string) Str::uuid(),
                'user_identifier' => auth()->check() ? auth()->user()->email : 'mobile_agent',
                'action'         => 'ENCAISSEMENT_TAXE_MOBILE',
                'object_class'   => PaiementTaxe::class,
                'object_id'      => $paiement->id,
                'data_before'    => null,
                'data_after'     => [
                    'numero_recu' => $paiement->numero_recu,
                    'montant'     => $paiement->montant,
                    'taxe'        => $taxeOp->taxe?->nom,
                    'operateur'   => $taxeOp->operateur?->nom_entreprise ?? $taxeOp->operateur?->nom_commercial,
                    'periode'     => $paiement->periode_fiscale,
                    'gps'         => [
                        'lat' => $paiement->latitude,
                        'lng' => $paiement->longitude,
                    ],
                ],
                'result'         => 'success',
            ]);

            Log::info('[TaxPaymentService] Paiement mobile enregistré.', [
                'uuid'        => $paiement->uuid,
                'numero_recu' => $paiement->numero_recu,
                'montant'     => $paiement->montant,
                'statut'      => $taxeOp->statut->label(),
            ]);

            return $paiement;
        });
    }

    /**
     * Traite un lot de paiements en attente (sync batch depuis le mobile).
     *
     * @param  array  $payments  Tableau de données de paiements
     * @param  string|null $userId
     * @return array  ['synced' => [...], 'errors' => [...]]
     */
    public function processBatch(array $payments, ?string $userId = null): array
    {
        $synced = [];
        $errors = [];

        foreach ($payments as $paymentData) {
            try {
                if ($userId) {
                    $paymentData['user_id'] = $userId;
                }
                $paiement = $this->recordMobilePayment($paymentData);
                $synced[] = [
                    'uuid'        => $paiement->uuid,
                    'numero_recu' => $paiement->numero_recu,
                    'statut'      => 'synced',
                ];
            } catch (\Throwable $e) {
                Log::error('[TaxPaymentService] Erreur batch.', [
                    'uuid'  => $paymentData['uuid'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = [
                    'uuid'    => $paymentData['uuid'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return compact('synced', 'errors');
    }

    /**
     * Vérifie que l'agent est bien affecté dans la zone géographique de l'opérateur.
     */
    private function validateAgentAssignment(Agent $agent, ?Operateur $operateur): void
    {
        if (!$operateur) {
            return;
        }

        $affectation = $agent->affectationsActives()
            ->where('quartier_id', $operateur->quartier_id)
            ->first();

        if (!$affectation) {
            throw ValidationException::withMessages([
                'agent_id' => "Vous n'êtes pas affecté dans la zone de cet opérateur (quartier #{$operateur->quartier_id}).",
            ]);
        }
    }
}
