<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\TypeBatiment;

class TypeBatimentController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return TypeBatiment::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.type_batiment';
    }

    protected function getRoutePrefix(): string
    {
        return 'type-batiment';
    }
}
