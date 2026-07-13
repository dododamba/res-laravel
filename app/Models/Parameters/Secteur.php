<?php

namespace App\Models\Parameters;

use App\Models\BaseParameterModel;

class Secteur extends BaseParameterModel
{
    /**
     * Un secteur appartient à un Carré.
     */
    public function carre()
    {
        return $this->belongsTo(Carre::class, 'carre_id');
    }

    /**
     * Un secteur possède plusieurs Avenues.
     */
    public function avenues()
    {
        return $this->hasMany(Avenue::class, 'secteur_id')->orderBy('ordre_affichage', 'asc');
    }
}
