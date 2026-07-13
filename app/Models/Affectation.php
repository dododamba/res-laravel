<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Affectation extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'agent_id',
        'fonction_id',
        'quartier_id',
        'carre_id',
        'secteur_id',
        'campagne_id',
        'date_debut',
        'date_fin',
        'motif',
        'statut',
        'responsable',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Affectation $aff) {
            if (empty($aff->id)) {
                $aff->id = (string) Str::uuid();
            }
        });
    }

    /**
     * L'agent affecté.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * La fonction de l'agent durant cette affectation.
     */
    public function fonction()
    {
        return $this->belongsTo(Parameters\Fonction::class, 'fonction_id');
    }

    /**
     * Quartier d'affectation facultatif.
     */
    public function quartier()
    {
        return $this->belongsTo(Parameters\Quartier::class, 'quartier_id');
    }

    /**
     * Carré d'affectation facultatif.
     */
    public function carre()
    {
        return $this->belongsTo(Parameters\Carre::class, 'carre_id');
    }

    /**
     * Secteur d'affectation facultatif.
     */
    public function secteur()
    {
        return $this->belongsTo(Parameters\Secteur::class, 'secteur_id');
    }

    /**
     * Campagne d'affectation.
     */
    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id');
    }
}
