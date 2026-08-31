<?php

namespace App\Models\Scopes;

use App\Services\AgentScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserRecordFilterScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole() || !auth()->check()) {
            return;
        }

        $scopeService = app(AgentScopeService::class);
        $scopeService->applyScope($builder, get_class($model));
    }
}

