<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\Assainissement;

class AssainissementController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return Assainissement::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.assainissement';
    }

    protected function getRoutePrefix(): string
    {
        return 'assainissement';
    }
}
