<?php

namespace App\Models\Scopes;

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

        $user = auth()->user();

        if ($user->hasRole(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return;
        }

        if ($user->agent) {
            $builder->where('enqueteur_id', $user->agent->id);
        } else {
            $builder->whereRaw('1 = 0');
        }
    }
}
