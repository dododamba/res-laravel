<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\CategorieOperateur;

class CategorieOperateurController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return CategorieOperateur::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.categorie_operateur';
    }

    protected function getRoutePrefix(): string
    {
        return 'categorie-operateur';
    }
}
