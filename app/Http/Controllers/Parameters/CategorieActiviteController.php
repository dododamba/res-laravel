<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\CategorieActivite;

class CategorieActiviteController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return CategorieActivite::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.categorie_activite';
    }

    protected function getRoutePrefix(): string
    {
        return 'categorie-activite';
    }
}
