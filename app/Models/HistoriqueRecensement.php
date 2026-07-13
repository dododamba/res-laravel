<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HistoriqueRecensement extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'recensement_id',
        'action',
        'details',
        'user_identifier',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (HistoriqueRecensement $hist) {
            if (empty($hist->id)) {
                $hist->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Fiche de recensement associée.
     */
    public function recensement()
    {
        return $this->belongsTo(Recensement::class, 'recensement_id');
    }
}
