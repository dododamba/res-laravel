<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\Secteur;

class SecteurController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return Secteur::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.secteur';
    }

    protected function getRoutePrefix(): string
    {
        return 'secteur';
    }
}
