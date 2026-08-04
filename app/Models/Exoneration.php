<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Exoneration extends Model
{
    use SoftDeletes;

    protected $table = 'exonerations';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'date_exoneration' => 'date',
        'montant_exonere' => 'float',
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
        });
    }

    public function taxeOperateur()
    {
        return $this->belongsTo(TaxeOperateur::class, 'taxe_operateur_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
