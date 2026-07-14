<?php

namespace App\Models\Parameters;

use App\Models\BaseParameterModel;
use App\Models\Maison;
use App\Models\Affectation;

class Carre extends BaseParameterModel
{
    protected $casts = [
        'est_chef' => 'boolean',
    ];

    /**
     * Un carré appartient à un Quartier.
     */
    public function quartier()
    {
        return $this->belongsTo(Quartier::class, 'quartier_id');
    }

    /**
     * Un carré englobe plusieurs Secteurs.
     */
    public function secteurs()
    {
        return $this->hasMany(Secteur::class, 'carre_id')->orderBy('ordre_affichage', 'asc');
    }

    /**
     * Un carré englobe plusieurs habitations (Maisons).
     */
    public function maisons()
    {
        return $this->hasMany(Maison::class, 'carre_id');
    }

    /**
     * Un carré possède des affectations temporelles.
     */
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'carre_id');
    }

    /**
     * Un carré englobe plusieurs recensements (ménages).
     */
    public function recensements()
    {
        return $this->hasMany(\App\Models\Recensement::class, 'carre_id');
    }

    /**
     * Un carré englobe plusieurs opérateurs économiques.
     */
    public function operateurs()
    {
        return $this->hasMany(\App\Models\Operateur::class, 'carre_id');
    }

    /**
     * Récupère le Chef de Carré actuellement affecté (Règle métier Symfony).
     */
    public function getChefCarreAttribute()
    {
        $activeAffectation = $this->affectations()
            ->where('statut', 'actif')
            ->whereHas('fonction', function($q) {
                $q->where('code', 'CHEF_CARRE');
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
