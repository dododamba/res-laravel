<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\Fonction;

class FonctionController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return Fonction::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.fonction';
    }

    protected function getRoutePrefix(): string
    {
        return 'fonction';
    }
}
