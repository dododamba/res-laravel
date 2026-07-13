<?php

namespace App\Http\Controllers;

use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord d'administration global de recensement.
     */
    public function index()
    {
        // On met en cache les statistiques complexes pendant 15 minutes (900 secondes)
        // afin de garantir des temps de chargement ultra-rapides sous Metronic.
        $stats = Cache::remember('dashboard.stats', 900, function () {
            
            // 1. Agrégations de données démographiques complexes déportées côté SQL
            $menagesAgreges = Recensement::query()
                ->selectRaw('
                    COUNT(id) as total_menages,
                    SUM(nombre_personnes) as total_population,
                    SUM(nombre_hommes) as total_hommes,
                    SUM(nombre_femmes) as total_femmes,
                    SUM(nombre_enfants) as total_enfants,
                    SUM(nombre_jeunes) as total_jeunes,
                    SUM(nombre_handicapes) as total_handicapes
                ')
                ->first();

            // 2. Décomptes d'enquêtes par sujets métiers
            $totalHabitations = Maison::count();
            $totalEntreprises = Operateur::count();
            $totalAgentsActifs = Agent::where('statut', 'actif')->count();

            // 3. Répartition démographique par genres
            $hommeRatio = 0;
            $femmeRatio = 0;
            $totalPop = (int)($menagesAgreges->total_population ?? 0);
            
            if ($totalPop > 0) {
                $hommeRatio = round((($menagesAgreges->total_hommes ?? 0) / $totalPop) * 100, 1);
                $femmeRatio = round((($menagesAgreges->total_femmes ?? 0) / $totalPop) * 100, 1);
            }

            return [
                'total_menages' => $menagesAgreges->total_menages ?? 0,
                'total_population' => $totalPop,
                'total_hommes' => $menagesAgreges->total_hommes ?? 0,
                'total_femmes' => $menagesAgreges->total_femmes ?? 0,
                'total_enfants' => $menagesAgreges->total_enfants ?? 0,
                'total_jeunes' => $menagesAgreges->total_jeunes ?? 0,
                'total_handicapes' => $menagesAgreges->total_handicapes ?? 0,
                'total_habitations' => $totalHabitations,
                'total_entreprises' => $totalEntreprises,
                'total_agents_actifs' => $totalAgentsActifs,
                'homme_ratio' => $hommeRatio,
                'femme_ratio' => $femmeRatio,
            ];
        });

        // 4. Statistiques d'enquêtes récentes (sans cache pour l'interactivité en direct)
        $recentRecensements = Recensement::query()
            ->with(['quartier', 'carre', 'enqueteur.personne'])
            ->latest()
            ->limit(5)
            ->get();

        $recentMaisons = Maison::query()
            ->with(['carre', 'enqueteur.personne'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'stats' => $stats,
            'recentRecensements' => $recentRecensements,
            'recentMaisons' => $recentMaisons,
        ]);
    }

    /**
     * Force la purge manuelle des caches statistiques du tableau de bord.
     */
    public function purgeCache()
    {
        Cache::forget('dashboard.stats');

        return redirect()
            ->route('dashboard')
            ->with('success', 'Le cache des statistiques du tableau de bord a été purgé avec succès.');
    }
}
