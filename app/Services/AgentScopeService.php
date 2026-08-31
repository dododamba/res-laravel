<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;
use App\Models\Affectation;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Quartier;
use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\TaxeOperateur;
use App\Models\PaiementTaxe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AgentScopeService
{
    /**
     * Obtenir l'agent associé à l'utilisateur connecté ou spécifié.
     */
    public function getCurrentAgent(?User $user = null): ?Agent
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return null;
        }

        if ($user->relationLoaded('agent')) {
            return $user->agent;
        }

        return $user->agent;
    }

    /**
     * Vérifier si l'utilisateur possède les privilèges Administrateur (Scope global).
     */
    public function isAdmin(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return false;
        }

        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return $user->hasRole(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ADMINISTRATEUR', 'ADMIN']);
    }

    /**
     * Récupérer la liste des affectations actives de l'agent.
     */
    public function getActiveAssignments(?User $user = null): Collection
    {
        $agent = $this->getCurrentAgent($user);
        if (!$agent) {
            return collect();
        }

        return Affectation::where('agent_id', $agent->id)
            ->where(function ($q) {
                $q->where('statut', 'Active')
                  ->orWhere('statut', 'actif');
            })
            ->where(function ($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            })
            ->get();
    }

    /**
     * Obtenir la liste des IDs de Quartiers autorisés pour le recenseur.
     * Retourne null si l'utilisateur est administrateur (accès illimité).
     * Retourne un tableau vide [] si aucune affectation n'est active.
     */
    public function getAuthorizedQuartierIds(?User $user = null): ?array
    {
        if ($this->isAdmin($user)) {
            return null; // Null signifie pas de restriction géographique
        }

        $assignments = $this->getActiveAssignments($user);
        if ($assignments->isEmpty()) {
            return [];
        }

        $quartierIds = [];
        $carreIds = [];

        foreach ($assignments as $aff) {
            if ($aff->quartier_id) {
                $quartierIds[] = (string) $aff->quartier_id;
            }
            if ($aff->carre_id) {
                $carreIds[] = (string) $aff->carre_id;
            }
        }

        if (!empty($carreIds)) {
            $quartierIdsFromCarres = Carre::whereIn('id', $carreIds)
                ->whereNotNull('quartier_id')
                ->pluck('quartier_id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $quartierIds = array_merge($quartierIds, $quartierIdsFromCarres);
        }

        return array_values(array_unique(array_filter($quartierIds)));
    }

    /**
     * Obtenir la liste des IDs de Carrés autorisés pour le recenseur.
     * Retourne null si l'utilisateur est administrateur.
     */
    public function getAuthorizedCarreIds(?User $user = null): ?array
    {
        if ($this->isAdmin($user)) {
            return null;
        }

        $quartierIds = $this->getAuthorizedQuartierIds($user);
        if ($quartierIds === null) {
            return null;
        }

        $assignments = $this->getActiveAssignments($user);
        $directCarreIds = $assignments->pluck('carre_id')->filter()->map(fn($id) => (string) $id)->toArray();

        $carreIdsFromQuartiers = [];
        if (!empty($quartierIds)) {
            $carreIdsFromQuartiers = Carre::whereIn('quartier_id', $quartierIds)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        }

        $allCarreIds = array_merge($directCarreIds, $carreIdsFromQuartiers);
        return array_values(array_unique(array_filter($allCarreIds)));
    }

    /**
     * Obtenir les campagnes actives auxquelles l'agent est affecté.
     */
    public function getAuthorizedCampagneIds(?User $user = null): ?array
    {
        if ($this->isAdmin($user)) {
            return null;
        }

        $assignments = $this->getActiveAssignments($user);
        $campagneIds = $assignments->pluck('campagne_id')->filter()->map(fn($id) => (string) $id)->toArray();

        return array_values(array_unique($campagneIds));
    }

    /**
     * Vérifie si le quartier spécifié fait partie du périmètre autorisé.
     */
    public function canAccessQuartier($quartierId, ?User $user = null): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (empty($quartierId)) {
            return false;
        }

        $authorized = $this->getAuthorizedQuartierIds($user);
        if ($authorized === null) {
            return true;
        }

        return in_array((string) $quartierId, $authorized, true);
    }

    /**
     * Vérifie si le carré spécifié fait partie du périmètre autorisé.
     */
    public function canAccessCarre($carreId, ?User $user = null): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (empty($carreId)) {
            return false;
        }

        $authorized = $this->getAuthorizedCarreIds($user);
        if ($authorized === null) {
            return true;
        }

        return in_array((string) $carreId, $authorized, true);
    }

    /**
     * Applique la restriction du scope géographique à une requête Eloquent.
     */
    public function applyScope(Builder $query, ?string $modelClass = null, ?User $user = null): Builder
    {
        if ($this->isAdmin($user)) {
            return $query;
        }

        $quartierIds = $this->getAuthorizedQuartierIds($user);
        $carreIds = $this->getAuthorizedCarreIds($user);

        if ($quartierIds === [] && $carreIds === []) {
            // L'agent n'a aucune affectation active -> Accès bloqué
            return $query->whereRaw('1 = 0');
        }

        $model = $modelClass ?: get_class($query->getModel());

        switch ($model) {
            case Recensement::class:
                return $query->where(function ($q) use ($quartierIds, $carreIds) {
                    if (!empty($quartierIds)) {
                        $q->whereIn('quartier_id', $quartierIds);
                    }
                    if (!empty($carreIds)) {
                        $q->orWhereIn('carre_id', $carreIds);
                    }
                });

            case Maison::class:
                return $query->where(function ($q) use ($quartierIds, $carreIds) {
                    if (!empty($carreIds)) {
                        $q->whereIn('carre_id', $carreIds);
                    }
                    if (!empty($quartierIds)) {
                        $q->orWhereHas('carre', function ($cq) use ($quartierIds) {
                            $cq->whereIn('quartier_id', $quartierIds);
                        });
                    }
                });

            case Operateur::class:
                return $query->where(function ($q) use ($quartierIds, $carreIds) {
                    if (!empty($quartierIds)) {
                        $q->whereIn('quartier_id', $quartierIds);
                    }
                    if (!empty($carreIds)) {
                        $q->orWhereIn('carre_id', $carreIds);
                    }
                });

            case TaxeOperateur::class:
                return $query->whereHas('operateur', function ($opQ) use ($quartierIds, $carreIds) {
                    if (!empty($quartierIds)) {
                        $opQ->whereIn('quartier_id', $quartierIds);
                    }
                    if (!empty($carreIds)) {
                        $opQ->orWhereIn('carre_id', $carreIds);
                    }
                });

            case PaiementTaxe::class:
                return $query->whereHas('taxeOperateur.operateur', function ($opQ) use ($quartierIds, $carreIds) {
                    if (!empty($quartierIds)) {
                        $opQ->whereIn('quartier_id', $quartierIds);
                    }
                    if (!empty($carreIds)) {
                        $opQ->orWhereIn('carre_id', $carreIds);
                    }
                });

            default:
                // Pour tout autre modèle possédant quartier_id ou carre_id
                return $query->where(function ($q) use ($quartierIds, $carreIds) {
                    if (!empty($quartierIds)) {
                        $q->whereIn('quartier_id', $quartierIds);
                    }
                    if (!empty($carreIds)) {
                        $q->orWhereIn('carre_id', $carreIds);
                    }
                });
        }
    }
}
