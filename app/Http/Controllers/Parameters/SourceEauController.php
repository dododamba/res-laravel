<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\SourceEau;

class SourceEauController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return SourceEau::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.source_eau';
    }

    protected function getRoutePrefix(): string
    {
        return 'source-eau';
    }
}
