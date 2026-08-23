<?php

namespace App\Models;

use App\Enums\ModePaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PaiementTaxe extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'paiement_taxes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'date_paiement'  => 'datetime',
        'montant'        => 'float',
        'mode_paiement'  => ModePaiement::class,
        // GPS
        'gps_latitude'   => 'float',
        'gps_longitude'  => 'float',
        'gps_altitude'   => 'float',
        'gps_accuracy'   => 'float',
    ];

    public function getLatitudeAttribute() { return $this->gps_latitude; }
    public function setLatitudeAttribute($val) { $this->attributes['gps_latitude'] = $val; }

    public function getLongitudeAttribute() { return $this->gps_longitude; }
    public function setLongitudeAttribute($val) { $this->attributes['gps_longitude'] = $val; }

    public function getAltitudeAttribute() { return $this->gps_altitude; }
    public function setAltitudeAttribute($val) { $this->attributes['gps_altitude'] = $val; }

    public function getPrecisionGpsAttribute() { return $this->gps_accuracy; }
    public function setPrecisionGpsAttribute($val) { $this->attributes['gps_accuracy'] = $val; }

    public function getPeriodeFiscaleAttribute() { return $this->periode; }
    public function setPeriodeFiscaleAttribute($val) { $this->attributes['periode'] = $val; }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->uuid;
            }
            if (empty($model->numero_recu)) {
                $model->numero_recu = 'REC-' . date('Ymd') . '-' . strtoupper(Str::random(6));
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('justificatifs_paiement')
             ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }
}
