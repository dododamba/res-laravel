<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Exoneration;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\Parameters\Quartier;
use App\Models\Taxe;
use App\Models\TaxeOperateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiscalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $annee = $request->input('annee', date('Y'));

        // KPIs Généraux
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

        $taxesImpayees = TaxeOperateur::where('annee_fiscale', $annee)->where('reste_a_payer', '>', 0)->count();

        $paiementsAujourdhui = (float) PaiementTaxe::whereDate('date_paiement', today())->sum('montant');
        $paiementsCeMois = (float) PaiementTaxe::whereYear('date_paiement', $annee)->whereMonth('date_paiement', now()->month)->sum('montant');
        $paiementsCetteAnnee = (float) PaiementTaxe::whereYear('date_paiement', $annee)->sum('montant');
        $totalExonerations = (float) Exoneration::whereYear('date_exoneration', $annee)->sum('montant_exonere');

        // Graphique 1 : Évolution mensuelle des encaissements (12 mois)
        $monthlyEvolution = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyEvolution[] = (float) PaiementTaxe::whereYear('date_paiement', $annee)
                ->whereMonth('date_paiement', $m)
                ->sum('montant');
        }

        // Graphique 2 : Taxes par Catégorie
        $taxesByCategorie = TaxeOperateur::join('taxes', 'taxe_operateurs.taxe_id', '=', 'taxes.id')
            ->where('taxe_operateurs.annee_fiscale', $annee)
            ->select('taxes.categorie', DB::raw('SUM(taxe_operateurs.montant_paye) as total_paye'))
            ->groupBy('taxes.categorie')
            ->get();

        // Graphique 3 : Répartition par Mode de Paiement
        $modesPaiement = PaiementTaxe::whereYear('date_paiement', $annee)
            ->select('mode_paiement', DB::raw('SUM(montant) as total'))
            ->groupBy('mode_paiement')
            ->get();

        // Tops & Classements
        $topTaxes = TaxeOperateur::join('taxes', 'taxe_operateurs.taxe_id', '=', 'taxes.id')
            ->where('taxe_operateurs.annee_fiscale', $annee)
            ->select('taxes.nom', 'taxes.code', DB::raw('SUM(taxe_operateurs.montant_paye) as total_encaisse'), DB::raw('COUNT(taxe_operateurs.id) as total_affectations'))
            ->groupBy('taxes.id', 'taxes.nom', 'taxes.code')
            ->orderByDesc('total_encaisse')
            ->limit(5)
            ->get();

        $topQuartiers = Quartier::join('operateurs', 'quartiers.id', '=', 'operateurs.quartier_id')
            ->join('taxe_operateurs', 'operateurs.id', '=', 'taxe_operateurs.operateur_id')
            ->where('taxe_operateurs.annee_fiscale', $annee)
            ->select('quartiers.nom', DB::raw('SUM(taxe_operateurs.montant_paye) as total_encaisse'), DB::raw('SUM(taxe_operateurs.montant_attendu) as total_attendu'))
            ->groupBy('quartiers.id', 'quartiers.nom')
            ->orderByDesc('total_encaisse')
            ->limit(5)
            ->get();

        $topActivites = Operateur::join('categorie_operateurs', 'operateurs.categorie_id', '=', 'categorie_operateurs.id')
            ->join('taxe_operateurs', 'operateurs.id', '=', 'taxe_operateurs.operateur_id')
            ->where('taxe_operateurs.annee_fiscale', $annee)
            ->select('categorie_operateurs.nom', DB::raw('SUM(taxe_operateurs.montant_paye) as total_encaisse'))
            ->groupBy('categorie_operateurs.id', 'categorie_operateurs.nom')
            ->orderByDesc('total_encaisse')
            ->limit(5)
            ->get();

        $topAgents = Agent::join('paiement_taxes', 'agents.id', '=', 'paiement_taxes.agent_id')
            ->join('personnes', 'agents.personne_id', '=', 'personnes.id')
            ->whereYear('paiement_taxes.date_paiement', $annee)
            ->select('personnes.nom', 'personnes.prenom', 'agents.matricule', DB::raw('SUM(paiement_taxes.montant) as total_encaisse'), DB::raw('COUNT(paiement_taxes.id) as nb_paiements'))
            ->groupBy('agents.id', 'personnes.nom', 'personnes.prenom', 'agents.matricule')
            ->orderByDesc('total_encaisse')
            ->limit(5)
            ->get();

        // Alertes Automatiques
        $alertesEcheances = TaxeOperateur::with(['operateur', 'taxe'])
            ->where('annee_fiscale', $annee)
            ->where('reste_a_payer', '>', 0)
            ->whereBetween('date_limite', [now(), now()->addDays(15)])
            ->limit(10)
            ->get();

        $alertesRetards = TaxeOperateur::with(['operateur', 'taxe'])
            ->where('annee_fiscale', $annee)
            ->where('statut', 'En retard')
            ->orderByDesc('reste_a_payer')
            ->limit(10)
            ->get();

        $operateursSansTaxe = Operateur::doesntHave('taxesAffectees')->limit(10)->get();

        return view('fiscalite.dashboard', compact(
            'annee',
            'montantAttendu',
            'montantEncaisse',
            'montantRestant',
            'tauxRecouvrement',
            'totalOperateurs',
            'operateursAJour',
            'operateursEnRetard',
            'taxesImpayees',
            'paiementsAujourdhui',
            'paiementsCeMois',
            'paiementsCetteAnnee',
            'totalExonerations',
            'monthlyEvolution',
            'taxesByCategorie',
            'modesPaiement',
            'topTaxes',
            'topQuartiers',
            'topActivites',
            'topAgents',
            'alertesEcheances',
            'alertesRetards',
            'operateursSansTaxe'
        ));
    }
}
