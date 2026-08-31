<?php

namespace App\Services;

use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class StatisticsService
{
    protected AgentScopeService $scopeService;

    public function __construct(AgentScopeService $scopeService)
    {
        $this->scopeService = $scopeService;
    }

    /**
     * Niveau 1 — Statistiques Globales (ou restreintes au périmètre de l'utilisateur)
     */
    public function getGlobalStats(?User $user = null): array
    {
        $isAdmin = $this->scopeService->isAdmin($user);

        $recQuery = Recensement::query();
        $maisonQuery = Maison::query();
        $opQuery = Operateur::query();

        if ($user) {
            $this->scopeService->applyScope($recQuery, Recensement::class, $user);
            $this->scopeService->applyScope($maisonQuery, Maison::class, $user);
            $this->scopeService->applyScope($opQuery, Operateur::class, $user);
        }

        $menagesAgreges = $recQuery
            ->selectRaw('
                COUNT(id) as total_menages,
                SUM(nombre_personnes) as total_population,
                SUM(nombre_hommes) as total_hommes,
                SUM(nombre_femmes) as total_femmes,
                SUM(nombre_enfants) as total_enfants,
                SUM(nombre_jeunes) as total_jeunes,
                SUM(nombre_handicapes) as total_handicapes,
                SUM(instruction_aucun) as instruction_aucun,
                SUM(instruction_primaire) as instruction_primaire,
                SUM(instruction_secondaire) as instruction_secondaire,
                SUM(instruction_superieur) as instruction_superieur,
                SUM(CASE WHEN statut = "VALIDE" THEN 1 ELSE 0 END) as fiches_validees,
                SUM(CASE WHEN statut != "VALIDE" THEN 1 ELSE 0 END) as fiches_en_attente
            ')
            ->first();

        $totalHabitats = $maisonQuery->count();
        $totalOperateurs = $opQuery->count();

        // Paiements & Taxes
        $paiementsQuery = PaiementTaxe::query()
            ->join('taxe_operateurs', 'paiement_taxes.taxe_operateur_id', '=', 'taxe_operateurs.id')
            ->join('operateurs', 'taxe_operateurs.operateur_id', '=', 'operateurs.id');

        if ($user && !$isAdmin) {
            $authorizedQuartierIds = $this->scopeService->getAuthorizedQuartierIds($user);
            $paiementsQuery->whereIn('operateurs.quartier_id', $authorizedQuartierIds);
        }

        $paiementStats = $paiementsQuery
            ->selectRaw('COUNT(paiement_taxes.id) as total_paiements, SUM(paiement_taxes.montant) as total_montant')
            ->first();

        $totalPop = (int) ($menagesAgreges->total_population ?? 0);
        $totalHommes = (int) ($menagesAgreges->total_hommes ?? 0);
        $totalFemmes = (int) ($menagesAgreges->total_femmes ?? 0);
        $totalMenages = (int) ($menagesAgreges->total_menages ?? 0);
        $fichesValidees = (int) ($menagesAgreges->fiches_validees ?? 0);
        $fichesPending = (int) ($menagesAgreges->fiches_en_attente ?? 0);
        $totalPaiements = (int) ($paiementStats->total_paiements ?? 0);
        $montantEncaisse = (float) ($paiementStats->total_montant ?? 0);

        $fichesTotal = $totalMenages + $totalHabitats + $totalOperateurs;
        $target = max(100, $fichesTotal);
        $progression = min(100, round(($fichesTotal / $target) * 100, 1));

        return [
            'scope' => $isAdmin ? 'global' : 'agent_scope',
            'total_menages' => $totalMenages,
            'total_population' => $totalPop,
            'total_hommes' => $totalHommes,
            'total_femmes' => $totalFemmes,
            'total_enfants' => (int) ($menagesAgreges->total_enfants ?? 0),
            'total_jeunes' => (int) ($menagesAgreges->total_jeunes ?? 0),
            'total_handicapes' => (int) ($menagesAgreges->total_handicapes ?? 0),
            'instruction_aucun' => (int) ($menagesAgreges->instruction_aucun ?? 0),
            'instruction_primaire' => (int) ($menagesAgreges->instruction_primaire ?? 0),
            'instruction_secondaire' => (int) ($menagesAgreges->instruction_secondaire ?? 0),
            'instruction_superieur' => (int) ($menagesAgreges->instruction_superieur ?? 0),
            'total_habitats' => $totalHabitats,
            'total_habitations' => $totalHabitats,
            'total_operateurs' => $totalOperateurs,
            'total_entreprises' => $totalOperateurs,
            'homme_ratio' => $totalPop > 0 ? round(($totalHommes / $totalPop) * 100, 1) : 0,
            'femme_ratio' => $totalPop > 0 ? round(($totalFemmes / $totalPop) * 100, 1) : 0,
            'total_fiches' => $fichesTotal,
            'fiches_validees' => $fichesValidees,
            'fiches_en_attente' => $fichesPending,
            'total_paiements' => $totalPaiements,
            'montant_encaisse' => $montantEncaisse,
            'progression' => $progression,
        ];
    }

    /**
     * Niveau 2 — Statistiques par Quartier
     */
    public function getQuartierStats(?User $user = null, string|int|null $quartierId = null): array
    {
        $isAdmin = $this->scopeService->isAdmin($user);

        if ($quartierId && $user && !$this->scopeService->canAccessQuartier($quartierId, $user)) {
            throw new AuthorizationException("Accès refusé : Le quartier #{$quartierId} ne fait pas partie de votre périmètre autorisé.");
        }

        $authorizedQuartierIds = $user ? $this->scopeService->getAuthorizedQuartierIds($user) : [];

        if (!$isAdmin && $user && empty($authorizedQuartierIds)) {
            return [
                'scope' => 'quartiers',
                'items' => [],
                'totals' => $this->emptyTotals(),
            ];
        }

        $quartiersQuery = Quartier::query();
        if (!$isAdmin && $user) {
            $quartiersQuery->whereIn('id', $authorizedQuartierIds);
        }
        if ($quartierId) {
            $quartiersQuery->where('id', $quartierId);
        }

        $quartiers = $quartiersQuery->get();

        // SQL Group By Aggregations
        $menagesPerQuartier = Recensement::query()
            ->selectRaw('quartier_id, COUNT(*) as total_menages, SUM(nombre_personnes) as total_pop, SUM(CASE WHEN statut = "VALIDE" THEN 1 ELSE 0 END) as sync_count, SUM(CASE WHEN statut != "VALIDE" THEN 1 ELSE 0 END) as pending_count')
            ->groupBy('quartier_id')
            ->get()
            ->keyBy('quartier_id');

        $operateursPerQuartier = Operateur::query()
            ->selectRaw('quartier_id, COUNT(*) as count')
            ->groupBy('quartier_id')
            ->pluck('count', 'quartier_id');

        $habitatsPerQuartier = Maison::query()
            ->join('carres', 'maisons.carre_id', '=', 'carres.id')
            ->selectRaw('carres.quartier_id, COUNT(maisons.id) as count')
            ->groupBy('carres.quartier_id')
            ->pluck('count', 'carres.quartier_id');

        $paiementsPerQuartier = PaiementTaxe::query()
            ->join('taxe_operateurs', 'paiement_taxes.taxe_operateur_id', '=', 'taxe_operateurs.id')
            ->join('operateurs', 'taxe_operateurs.operateur_id', '=', 'operateurs.id')
            ->selectRaw('operateurs.quartier_id, COUNT(paiement_taxes.id) as count, SUM(paiement_taxes.montant) as total')
            ->groupBy('operateurs.quartier_id')
            ->get()
            ->keyBy('quartier_id');

        $items = [];
        $totalMenages = 0;
        $totalHabitats = 0;
        $totalOperateurs = 0;
        $totalPop = 0;
        $totalSync = 0;
        $totalPending = 0;
        $totalPaiementsCount = 0;
        $totalMontant = 0;

        foreach ($quartiers as $q) {
            $qId = (string) $q->id;

            $m = $menagesPerQuartier->get($qId);
            $menagesCount = (int) ($m->total_menages ?? 0);
            $popCount = (int) ($m->total_pop ?? 0);
            $syncCount = (int) ($m->sync_count ?? 0);
            $pendingCount = (int) ($m->pending_count ?? 0);

            $habitatsCount = (int) ($habitatsPerQuartier->get($qId) ?? 0);
            $operateursCount = (int) ($operateursPerQuartier->get($qId) ?? 0);

            $p = $paiementsPerQuartier->get($qId);
            $paiementsCount = (int) ($p->count ?? 0);
            $montant = (float) ($p->total ?? 0);

            $fichesCollectees = $menagesCount + $habitatsCount + $operateursCount;
            $target = 100;
            $progression = min(100, round(($fichesCollectees / $target) * 100, 1));

            $items[] = [
                'id' => $q->id,
                'nom' => $q->nom,
                'code' => $q->code ?? '',
                'menages' => $menagesCount,
                'habitants' => $popCount,
                'habitats' => $habitatsCount,
                'operateurs' => $operateursCount,
                'taxes' => $operateursCount,
                'paiements' => $paiementsCount,
                'montantEncaisse' => $montant,
                'fiches_validees' => $syncCount,
                'fiches_en_attente' => $pendingCount,
                'fiches_collectees' => $fichesCollectees,
                'progression' => $progression,
            ];

            $totalMenages += $menagesCount;
            $totalPop += $popCount;
            $totalHabitats += $habitatsCount;
            $totalOperateurs += $operateursCount;
            $totalSync += $syncCount;
            $totalPending += $pendingCount;
            $totalPaiementsCount += $paiementsCount;
            $totalMontant += $montant;
        }

        $totalFiches = $totalMenages + $totalHabitats + $totalOperateurs;
        $globalProgression = count($quartiers) > 0 ? min(100, round(($totalFiches / (count($quartiers) * 100)) * 100, 1)) : 0;

        return [
            'scope' => 'quartiers',
            'items' => $items,
            'totals' => [
                'menages' => $totalMenages,
                'habitants' => $totalPop,
                'habitats' => $totalHabitats,
                'operateurs' => $totalOperateurs,
                'fiches_validees' => $totalSync,
                'fiches_en_attente' => $totalPending,
                'fiches_collectees' => $totalFiches,
                'paiements' => $totalPaiementsCount,
                'montantEncaisse' => $totalMontant,
                'progression' => $globalProgression,
            ],
        ];
    }

    /**
     * Niveau 3 — Statistiques par Carré pour un Quartier donné
     */
    public function getCarreStats(string|int $quartierId, ?User $user = null, string|int|null $carreId = null): array
    {
        if ($user && !$this->scopeService->canAccessQuartier($quartierId, $user)) {
            throw new AuthorizationException("Accès refusé : Le quartier #{$quartierId} ne fait pas partie de votre périmètre autorisé.");
        }

        $quartier = Quartier::findOrFail($quartierId);

        $carresQuery = Carre::where('quartier_id', $quartierId);
        if ($carreId) {
            $carresQuery->where('id', $carreId);
        }

        $carres = $carresQuery->get();

        // SQL Group By Aggregations par Carre
        $menagesPerCarre = Recensement::where('quartier_id', $quartierId)
            ->selectRaw('carre_id, COUNT(*) as total_menages, SUM(nombre_personnes) as total_pop, SUM(CASE WHEN statut = "VALIDE" THEN 1 ELSE 0 END) as sync_count, SUM(CASE WHEN statut != "VALIDE" THEN 1 ELSE 0 END) as pending_count')
            ->groupBy('carre_id')
            ->get()
            ->keyBy('carre_id');

        $habitatsPerCarre = Maison::query()
            ->selectRaw('carre_id, COUNT(id) as count')
            ->groupBy('carre_id')
            ->pluck('count', 'carre_id');

        $operateursPerCarre = Operateur::where('quartier_id', $quartierId)
            ->selectRaw('carre_id, COUNT(id) as count')
            ->groupBy('carre_id')
            ->pluck('count', 'carre_id');

        $paiementsPerCarre = PaiementTaxe::query()
            ->join('taxe_operateurs', 'paiement_taxes.taxe_operateur_id', '=', 'taxe_operateurs.id')
            ->join('operateurs', 'taxe_operateurs.operateur_id', '=', 'operateurs.id')
            ->where('operateurs.quartier_id', $quartierId)
            ->selectRaw('operateurs.carre_id, COUNT(paiement_taxes.id) as count, SUM(paiement_taxes.montant) as total')
            ->groupBy('operateurs.carre_id')
            ->get()
            ->keyBy('carre_id');

        $items = [];
        foreach ($carres as $c) {
            $cId = (string) $c->id;

            $m = $menagesPerCarre->get($cId);
            $menagesCount = (int) ($m->total_menages ?? 0);
            $popCount = (int) ($m->total_pop ?? 0);
            $syncCount = (int) ($m->sync_count ?? 0);
            $pendingCount = (int) ($m->pending_count ?? 0);

            $habitatsCount = (int) ($habitatsPerCarre->get($cId) ?? 0);
            $operateursCount = (int) ($operateursPerCarre->get($cId) ?? 0);

            $p = $paiementsPerCarre->get($cId);
            $paiementsCount = (int) ($p->count ?? 0);
            $montant = (float) ($p->total ?? 0);

            $fichesCollectees = $menagesCount + $habitatsCount + $operateursCount;
            $target = 30;
            $progression = min(100, round(($fichesCollectees / $target) * 100, 1));

            $items[] = [
                'id' => $c->id,
                'nom' => $c->nom,
                'code' => $c->code ?? '',
                'quartier_id' => $quartierId,
                'quartier_nom' => $quartier->nom,
                'menages' => $menagesCount,
                'habitants' => $popCount,
                'habitats' => $habitatsCount,
                'operateurs' => $operateursCount,
                'taxes' => $operateursCount,
                'paiements' => $paiementsCount,
                'montantEncaisse' => $montant,
                'fiches_validees' => $syncCount,
                'fiches_en_attente' => $pendingCount,
                'fiches_collectees' => $fichesCollectees,
                'progression' => $progression,
            ];
        }

        return [
            'scope' => 'carres',
            'quartier' => [
                'id' => $quartier->id,
                'nom' => $quartier->nom,
                'code' => $quartier->code ?? '',
            ],
            'items' => $items,
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'menages' => 0,
            'habitants' => 0,
            'habitats' => 0,
            'operateurs' => 0,
            'fiches_validees' => 0,
            'fiches_en_attente' => 0,
            'fiches_collectees' => 0,
            'paiements' => 0,
            'montantEncaisse' => 0,
            'progression' => 0,
        ];
    }
}
