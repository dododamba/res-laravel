<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\BesoinPrioritaire;

class BesoinPrioritaireController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return BesoinPrioritaire::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.besoin_prioritaire';
    }

    protected function getRoutePrefix(): string
    {
        return 'besoin-prioritaire';
    }
}
