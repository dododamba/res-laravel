<?php

namespace App\Models;

use App\Enums\ModeCalculTaxe;
use App\Enums\PeriodiciteTaxe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Taxe extends Model
{
    use SoftDeletes;

    protected $table = 'taxes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'montant' => 'float',
        'pourcentage' => 'float',
        'surface' => 'float',
        'volume' => 'float',
        'actif' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'ordre' => 'integer',
        'regles_affectation' => 'array',
        'mode_calcul' => ModeCalculTaxe::class,
        'periodicite' => PeriodiciteTaxe::class,
    ];

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->uuid;
            }
            if (empty($model->code)) {
                $model->code = 'TAX-' . strtoupper(Str::random(6));
            }
        });
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function affectations()
    {
        return $this->hasMany(TaxeOperateur::class, 'taxe_id');
    }
}
