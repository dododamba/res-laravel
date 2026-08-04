<?php

namespace App\Models;

use App\Enums\TaxeStatut;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TaxeOperateur extends Model
{
    use SoftDeletes;

    protected $table = 'taxe_operateurs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'annee_fiscale' => 'integer',
        'montant_attendu' => 'float',
        'montant_paye' => 'float',
        'reste_a_payer' => 'float',
        'date_limite' => 'date',
        'statut' => TaxeStatut::class,
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

    public function operateur()
    {
        return $this->belongsTo(Operateur::class, 'operateur_id');
    }

    public function taxe()
    {
        return $this->belongsTo(Taxe::class, 'taxe_id');
    }

    public function paiements()
    {
        return $this->hasMany(PaiementTaxe::class, 'taxe_operateur_id')->orderBy('date_paiement', 'desc');
    }

    public function exonerations()
    {
        return $this->hasMany(Exoneration::class, 'taxe_operateur_id')->orderBy('date_exoneration', 'desc');
    }

    public function recouvrements()
    {
        return $this->hasMany(Recouvrement::class, 'taxe_operateur_id')->orderBy('date_relance', 'desc');
    }

    public function historiques()
    {
        return $this->hasMany(HistoriquePaiement::class, 'taxe_operateur_id')->orderBy('created_at', 'desc');
    }

    public function getEstSoldeAttribute(): bool
    {
        return $this->statut === TaxeStatut::SOLDE;
    }

    public function getJoursRetardAttribute(): int
    {
        if ($this->statut === TaxeStatut::SOLDE || $this->statut === TaxeStatut::EXONERE) {
            return 0;
        }
        if ($this->date_limite && $this->date_limite->isPast() && $this->reste_a_payer > 0) {
            return (int) now()->diffInDays($this->date_limite);
        }
        return 0;
    }

    public function getTauxPaiementAttribute(): float
    {
        if ($this->montant_attendu <= 0) {
            return 100.0;
        }
        return round(($this->montant_paye / $this->montant_attendu) * 100, 2);
    }
}
