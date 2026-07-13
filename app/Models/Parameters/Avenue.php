<?php

namespace App\Models\Parameters;

use App\Models\BaseParameterModel;

class Avenue extends BaseParameterModel
{
    /**
     * Une avenue appartient à un Secteur.
     */
    public function secteur()
    {
        return $this->belongsTo(Secteur::class, 'secteur_id');
    }
}
