<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class BaseParameterModel extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->uuid;
            }
            if (empty($model->slug) && !empty($model->nom)) {
                $model->slug = Str::slug($model->nom) . '-' . Str::random(5);
            }
            $model->created_by = auth()->check() ? auth()->user()->email : 'system';
        });

        static::updating(function (Model $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->user()->email;
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('nom', 'LIKE', "%{$term}%")
                     ->orWhere('code', 'LIKE', "%{$term}%")
                     ->orWhere('description', 'LIKE', "%{$term}%");
    }
}
