<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HistoriquePaiement extends Model
{
    protected $table = 'historique_paiements';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function taxeOperateur()
    {
        return $this->belongsTo(TaxeOperateur::class, 'taxe_operateur_id');
    }

    public function paiement()
    {
        return $this->belongsTo(PaiementTaxe::class, 'paiement_id');
    }
}
