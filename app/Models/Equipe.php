<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Equipe extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'nom',
        'description',
        'chef_id',
        'campagne_id',
    ];

    protected static function booted()
    {
        static::creating(function (Equipe $equipe) {
            if (empty($equipe->id)) {
                $equipe->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Une équipe appartient à une campagne de recensement.
     */
    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id');
    }

    /**
     * Le chef d'équipe (Agent territorial).
     */
    public function chef()
    {
        return $this->belongsTo(Agent::class, 'chef_id');
    }

    /**
     * Les agents membres de cette équipe (ManyToMany via equipe_agent).
     */
    public function membres()
    {
        return $this->belongsToMany(Agent::class, 'equipe_agent', 'equipe_id', 'agent_id');
    }
}
