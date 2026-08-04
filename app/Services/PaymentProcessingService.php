<?php

namespace App\Services;

use App\Enums\TaxeStatut;
use App\Models\AuditLog;
use App\Models\HistoriquePaiement;
use App\Models\PaiementTaxe;
use App\Models\TaxeOperateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentProcessingService
{
    public function __construct(
        protected TaxCalculationService $calculationService
    ) {}

    /**
     * Enregistre un encaissement de taxe avec traçabilité complète et immuabilité.
     */
    public function recordPayment(array $data): PaiementTaxe
    {
        return DB::transaction(function () use ($data) {
            $taxeOp = TaxeOperateur::with(['operateur', 'taxe'])->findOrFail($data['taxe_operateur_id']);

            // Règle Métier Immuabilité : Une taxe soldée ne peut plus être modifiée ou payée
            if ($taxeOp->statut === TaxeStatut::SOLDE) {
                throw ValidationException::withMessages([
                    'taxe_operateur_id' => 'Cette taxe est déjà intégralement soldée. Aucun nouveau paiement ne peut y être affecté.'
                ]);
            }

            $montant = (float) ($data['montant'] ?? 0);
            if ($montant <= 0) {
                throw ValidationException::withMessages([
                    'montant' => 'Le montant du paiement doit être supérieur à 0 FCFA.'
                ]);
            }

            // Génération du numéro de reçu unique s'il n'est pas transmis
            $numeroRecu = $data['numero_recu'] ?? ('REC-' . date('Ymd') . '-' . strtoupper(Str::random(6)));

            // Vérification d'unicité du numéro de reçu
            if (PaiementTaxe::where('numero_recu', $numeroRecu)->exists()) {
                $numeroRecu = 'REC-' . date('Ymd') . '-' . strtoupper(Str::random(8));
            }

            $uuid = (string) Str::uuid();

            $paiement = PaiementTaxe::create([
                'id' => $uuid,
                'uuid' => $uuid,
                'taxe_operateur_id' => $taxeOp->id,
                'date_paiement' => $data['date_paiement'] ?? now(),
                'montant' => $montant,
                'mode_paiement' => $data['mode_paiement'] ?? 'Espèces',
                'reference' => $data['reference'] ?? null,
                'numero_recu' => $numeroRecu,
                'agent_id' => $data['agent_id'] ?? null,
                'user_id' => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'observation' => $data['observation'] ?? null,
                'justificatif' => $data['justificatif'] ?? null,
                'signature_agent' => $data['signature_agent'] ?? null,
                'signature_client' => $data['signature_client'] ?? null,
                'statut' => 'valide',
            ]);

            // Recalcul automatique et mise à jour du statut
            $this->calculationService->updateTaxeOperateurStatus($taxeOp);

            // Historique local du paiement
            HistoriquePaiement::create([
                'taxe_operateur_id' => $taxeOp->id,
                'paiement_id' => $paiement->id,
                'action' => 'encaissement',
                'details' => [
                    'numero_recu' => $paiement->numero_recu,
                    'montant' => $paiement->montant,
                    'mode_paiement' => is_object($paiement->mode_paiement) ? $paiement->mode_paiement->value : $paiement->mode_paiement,
                    'reference' => $paiement->reference,
                    'nouveau_reste' => $taxeOp->reste_a_payer,
                    'nouveau_statut' => $taxeOp->statut->label(),
                ],
                'user_identifier' => auth()->check() ? auth()->user()->email : 'api_agent',
            ]);

            // Log d'audit système global
            AuditLog::create([
                'id' => (string) Str::uuid(),
                'user_identifier' => auth()->check() ? auth()->user()->email : 'api_agent',
                'action' => 'ENCAISSEMENT_TAXE',
                'object_class' => PaiementTaxe::class,
                'object_id' => $paiement->id,
                'data_before' => null,
                'data_after' => [
                    'numero_recu' => $paiement->numero_recu,
                    'montant' => $paiement->montant,
                    'taxe' => $taxeOp->taxe?->nom,
                    'operateur' => $taxeOp->operateur?->nom_entreprise ?? $taxeOp->operateur?->nom_commercial,
                ],
                'result' => 'success',
            ]);

            return $paiement;
        });
    }
}
