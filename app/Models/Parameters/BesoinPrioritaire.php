<?php

namespace App\Models\Parameters;

use App\Models\BaseParameterModel;

class BesoinPrioritaire extends BaseParameterModel
{
    /**
     * Correction explicite du nom de table lié aux pluriels franco-anglais.
     */
    protected $table = 'besoins_prioritaires';
}
