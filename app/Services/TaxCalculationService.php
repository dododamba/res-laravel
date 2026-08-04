<?php

namespace App\Services;

use App\Enums\ModeCalculTaxe;
use App\Enums\TaxeStatut;
use App\Models\Operateur;
use App\Models\Taxe;
use App\Models\TaxeOperateur;
use Carbon\Carbon;

class TaxCalculationService
{
    /**
     * Calcule le montant attendu pour une taxe donnée et un opérateur spécifique.
     */
    public function calculateAmountForOperateur(Taxe $taxe, Operateur $operateur): float
    {
        $baseMontant = (float) $taxe->montant;

        return match ($taxe->mode_calcul) {
            ModeCalculTaxe::FIXE => $baseMontant,
            ModeCalculTaxe::EFFECTIF => $baseMontant * max(1, (int) $operateur->effectif_total),
            ModeCalculTaxe::SURFACE => $baseMontant * max(1.0, (float) ($taxe->surface ?? 1.0)),
            ModeCalculTaxe::VOLUME => $baseMontant * max(1.0, (float) ($taxe->volume ?? 1.0)),
            ModeCalculTaxe::POURCENTAGE => $baseMontant,
            default => $baseMontant,
        };
    }

    /**
     * Recalcule les soldes et met à jour le statut dynamique de la taxe affectée.
     */
    public function updateTaxeOperateurStatus(TaxeOperateur $taxeOp): TaxeOperateur
    {
        $montantAttendu = (float) $taxeOp->montant_attendu;
        $totalPaye = (float) $taxeOp->paiements()->where('statut', 'valide')->sum('montant');
        $totalExonere = (float) $taxeOp->exonerations()->sum('montant_exonere');

        $effectiveAttendu = max(0.0, $montantAttendu - $totalExonere);
        $resteAPayer = max(0.0, $effectiveAttendu - $totalPaye);

        $taxeOp->montant_paye = $totalPaye;
        $taxeOp->reste_a_payer = $resteAPayer;

        // Détermination du statut dynamique
        if ($totalExonere >= $montantAttendu && $montantAttendu > 0) {
            $taxeOp->statut = TaxeStatut::EXONERE;
        } elseif ($resteAPayer <= 0) {
            $taxeOp->statut = TaxeStatut::SOLDE;
        } else {
            $isOverdue = $taxeOp->date_limite && Carbon::parse($taxeOp->date_limite)->isPast();
            if ($totalPaye > 0) {
                $taxeOp->statut = $isOverdue ? TaxeStatut::EN_RETARD : TaxeStatut::PARTIELLEMENT_PAYE;
            } else {
                $taxeOp->statut = $isOverdue ? TaxeStatut::EN_RETARD : TaxeStatut::A_PAYER;
            }
        }

        $taxeOp->save();

        return $taxeOp;
    }

    /**
     * Calcule la date limite de paiement selon la périodicité de la taxe et l'année fiscale.
     */
    public function calculateDueDate(Taxe $taxe, int $anneeFiscale = 2026): Carbon
    {
        return match ($taxe->periodicite?->value ?? 'annuelle') {
            'mensuelle' => Carbon::create($anneeFiscale, now()->month, 28),
            'trimestrielle' => Carbon::create($anneeFiscale, min(12, ceil(now()->month / 3) * 3), 30),
            'ponctuelle' => now()->addDays(30),
            default => Carbon::create($anneeFiscale, 12, 31), // annuelle
        };
    }
}
