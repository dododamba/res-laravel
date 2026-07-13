<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\TypePropriete;

class TypeProprieteController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return TypePropriete::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.type_propriete';
    }

    protected function getRoutePrefix(): string
    {
        return 'type-propriete';
    }
}
