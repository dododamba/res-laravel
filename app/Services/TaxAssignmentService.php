<?php

namespace App\Services;

use App\Models\Operateur;
use App\Models\Taxe;
use App\Models\TaxeOperateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxAssignmentService
{
    public function __construct(
        protected TaxCalculationService $calculationService
    ) {}

    /**
     * Détermine et affecte automatiquement les taxes applicables à un opérateur économique.
     */
    public function autoAssignTaxesForOperateur(Operateur $operateur, int $anneeFiscale = 2026): array
    {
        $activeTaxes = Taxe::actif()->orderBy('ordre')->get();
        $assigned = [];

        DB::transaction(function () use ($activeTaxes, $operateur, $anneeFiscale, &$assigned) {
            foreach ($activeTaxes as $taxe) {
                if ($this->isTaxApplicableToOperateur($taxe, $operateur)) {
                    $taxeOp = $this->assignTaxToOperateur($operateur, $taxe, $anneeFiscale);
                    $assigned[] = $taxeOp;
                }
            }
        });

        return $assigned;
    }

    /**
     * Vérifie si une taxe s'applique à un opérateur selon ses critères (activité, catégorie, taille, effectif, quartier).
     */
    public function isTaxApplicableToOperateur(Taxe $taxe, Operateur $operateur): bool
    {
        // 1. Les taxes de base (Patente Commerciale & Salubrité) s'appliquent à tous les opérateurs
        if (in_array($taxe->code, ['PAT-COMM', 'TAX-SALU'])) {
            return true;
        }

        // 2. Licence de débit de boissons
        if ($taxe->code === 'LIC-BOIS') {
            $catNom = Str::lower($operateur->categorie?->nom ?? '');
            $opNom = Str::lower($operateur->nom_commercial ?? '');
            return Str::contains($catNom, ['boisson', 'bar', 'maquis', 'restaurant', 'nuit', 'hôtellerie']) ||
                   Str::contains($opNom, ['bar', 'maquis', 'lounge', 'resto', 'cave', 'pub', 'club']);
        }

        // 3. Occupation du domaine public
        if ($taxe->code === 'ODP-TERR') {
            $catNom = Str::lower($operateur->categorie?->nom ?? '');
            return Str::contains($catNom, ['marché', 'kiosque', 'terrasse', 'vendeur', 'restauration', 'artisanat']);
        }

        // 4. Publicités & Enseignes
        if ($taxe->code === 'PUB-ENSI') {
            // S'applique aux entreprises moyennes et grandes ou secteurs commerciaux avec enseigne
            return in_array($operateur->taille?->value ?? '', ['petite', 'moyenne', 'grande']) || $operateur->effectif_total >= 5;
        }

        // 5. Droits de place & Stationnement
        if ($taxe->code === 'TAX-STAT') {
            $catNom = Str::lower($operateur->categorie?->nom ?? '');
            return Str::contains($catNom, ['transport', 'logistique', 'garage', 'parking', 'marché']);
        }

        // 6. Règle générique via le champ JSON regles_affectation si présent
        if (!empty($taxe->regles_affectation)) {
            $rules = $taxe->regles_affectation;
            if (isset($rules['categories']) && is_array($rules['categories'])) {
                if (in_array($operateur->categorie_id, $rules['categories'])) {
                    return true;
                }
            }
            if (isset($rules['quartiers']) && is_array($rules['quartiers'])) {
                if (in_array($operateur->quartier_id, $rules['quartiers'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Crée ou met à jour l'affectation d'une taxe à un opérateur.
     */
    public function assignTaxToOperateur(Operateur $operateur, Taxe $taxe, int $anneeFiscale = 2026): TaxeOperateur
    {
        $existing = TaxeOperateur::where('operateur_id', $operateur->id)
            ->where('taxe_id', $taxe->id)
            ->where('annee_fiscale', $anneeFiscale)
            ->first();

        if ($existing) {
            // Si la taxe est déjà soldée, on ne réinitialise pas le montant dû
            if (!$existing->est_solde) {
                $montantAttendu = $this->calculationService->calculateAmountForOperateur($taxe, $operateur);
                $existing->montant_attendu = $montantAttendu;
                $this->calculationService->updateTaxeOperateurStatus($existing);
            }
            return $existing;
        }

        $montantAttendu = $this->calculationService->calculateAmountForOperateur($taxe, $operateur);
        $dueDate = $this->calculationService->calculateDueDate($taxe, $anneeFiscale);

        $uuid = (string) Str::uuid();
        $taxeOp = TaxeOperateur::create([
            'id' => $uuid,
            'uuid' => $uuid,
            'operateur_id' => $operateur->id,
            'taxe_id' => $taxe->id,
            'annee_fiscale' => $anneeFiscale,
            'montant_attendu' => $montantAttendu,
            'montant_paye' => 0,
            'reste_a_payer' => $montantAttendu,
            'date_limite' => $dueDate,
            'statut' => \App\Enums\TaxeStatut::A_PAYER,
        ]);

        return $this->calculationService->updateTaxeOperateurStatus($taxeOp);
    }
}
