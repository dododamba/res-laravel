<?php

namespace App\Models;

use App\Enums\CampagneStatut;

class Campagne extends BaseParameterModel
{
    protected $casts = [
        'statut' => CampagneStatut::class,
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
    ];

    /**
     * Une campagne regroupe plusieurs équipes de collecte.
     */
    public function equipes()
    {
        return $this->hasMany(Equipe::class, 'campagne_id');
    }

    /**
     * Une campagne contient plusieurs affectations territoriales d'agents.
     */
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'campagne_id');
    }

    /**
     * Les recensements (Ménages) réalisés durant cette campagne.
     */
    public function recensements()
    {
        return $this->hasMany(Recensement::class, 'campagne_id');
    }

    /**
     * Les opérateurs économiques recensés durant cette campagne.
     */
    public function operateurs()
    {
        return $this->hasMany(Operateur::class, 'campagne_id');
    }
}
