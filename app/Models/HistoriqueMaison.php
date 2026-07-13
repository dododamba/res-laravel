<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HistoriqueMaison extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'maison_id',
        'action',
        'details',
        'user_identifier',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (HistoriqueMaison $hist) {
            if (empty($hist->id)) {
                $hist->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Fiche d'habitation associée.
     */
    public function maison()
    {
        return $this->belongsTo(Maison::class, 'maison_id');
    }
}
