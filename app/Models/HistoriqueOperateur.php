<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HistoriqueOperateur extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'operateur_id',
        'action',
        'details',
        'user_identifier',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (HistoriqueOperateur $hist) {
            if (empty($hist->id)) {
                $hist->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Fiche d'opérateur associée.
     */
    public function operateur()
    {
        return $this->belongsTo(Operateur::class, 'operateur_id');
    }
}
