<?php

namespace App\Models\Parameters;

use App\Models\BaseParameterModel;
use App\Models\Affectation;

class Quartier extends BaseParameterModel
{
    /**
     * Un quartier englobe plusieurs carrés.
     */
    public function carres()
    {
        return $this->hasMany(Carre::class, 'quartier_id')->orderBy('ordre_affichage', 'asc');
    }

    /**
     * Un quartier possède plusieurs affectations temporelles d'agents.
     */
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'quartier_id');
    }

    /**
     * Un quartier englobe plusieurs recensements (ménages).
     */
    public function recensements()
    {
        return $this->hasMany(\App\Models\Recensement::class, 'quartier_id');
    }

    /**
     * Un quartier englobe plusieurs opérateurs économiques.
     */
    public function operateurs()
    {
        return $this->hasMany(\App\Models\Operateur::class, 'quartier_id');
    }

    /**
     * Récupère le Délégué communal actuellement affecté au Quartier (Règle métier Symfony).
     */
    public function getDelegueAttribute()
    {
        $activeAffectation = $this->affectations()
            ->where('statut', 'actif')
            ->whereHas('fonction', function($q) {
                $q->where('code', 'DELEGUE');
            })
            ->where(function($q) {
                $q->whereNull('date_debut')->orWhere('date_debut', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now());
            })
            ->with('agent')
            ->first();

        return $activeAffectation ? $activeAffectation->agent : null;
    }
}
