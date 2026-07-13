<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\Avenue;

class AvenueController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return Avenue::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.avenue';
    }

    protected function getRoutePrefix(): string
    {
        return 'avenue';
    }
}
