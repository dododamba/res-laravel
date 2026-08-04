<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Exoneration;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\TaxeOperateur;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class FiscalDashboardApiController extends Controller
{
    use ApiResponse;

    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $annee = $request->input('annee', date('Y'));

            $montantAttendu = (float) TaxeOperateur::where('annee_fiscale', $annee)->sum('montant_attendu');
            $montantEncaisse = (float) TaxeOperateur::where('annee_fiscale', $annee)->sum('montant_paye');
            $montantRestant = (float) TaxeOperateur::where('annee_fiscale', $annee)->sum('reste_a_payer');
            $tauxRecouvrement = $montantAttendu > 0 ? round(($montantEncaisse / $montantAttendu) * 100, 2) : 100.0;

            $totalOperateurs = Operateur::count();
            $operateursAJour = Operateur::whereHas('taxesAffectees', function ($q) use ($annee) {
                $q->where('annee_fiscale', $annee);
            })->whereDoesntHave('taxesAffectees', function ($q) use ($annee) {
                $q->where('annee_fiscale', $annee)->where('reste_a_payer', '>', 0);
            })->count();

            $operateursEnRetard = Operateur::whereHas('taxesAffectees', function ($q) use ($annee) {
                $q->where('annee_fiscale', $annee)->where('statut', 'En retard');
            })->count();

            $paiementsAujourdhui = (float) PaiementTaxe::whereDate('date_paiement', today())->sum('montant');
            $paiementsCeMois = (float) PaiementTaxe::whereYear('date_paiement', $annee)->whereMonth('date_paiement', now()->month)->sum('montant');
            $totalExonerations = (float) Exoneration::whereYear('date_exoneration', $annee)->sum('montant_exonere');

            return $this->buildResponse(
                success: true,
                message: "Statistiques fiscales récupérées avec succès.",
                data: [
                    'annee' => (int) $annee,
                    'montant_attendu' => $montantAttendu,
                    'montant_encaisse' => $montantEncaisse,
                    'montant_restant' => $montantRestant,
                    'taux_recouvrement' => $tauxRecouvrement,
                    'total_operateurs' => $totalOperateurs,
                    'operateurs_a_jour' => $operateursAJour,
                    'operateurs_en_retard' => $operateursEnRetard,
                    'paiements_aujourdhui' => $paiementsAujourdhui,
                    'paiements_ce_mois' => $paiementsCeMois,
                    'total_exonerations' => $totalExonerations,
                ]
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors du calcul des statistiques fiscales.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }
}
