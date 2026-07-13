<?php

namespace App\Models\Traits;

use App\Models\Scopes\UserRecordFilterScope;

trait HasUserFilter
{
    protected static function bootHasUserFilter()
    {
        static::addGlobalScope(new UserRecordFilterScope());
    }
}
