<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\SourceEnergie;

class SourceEnergieController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return SourceEnergie::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.source_energie';
    }

    protected function getRoutePrefix(): string
    {
        return 'source-energie';
    }
}
