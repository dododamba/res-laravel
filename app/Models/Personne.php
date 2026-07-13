<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personne extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prenom',
        'nom',
        'telephone',
        'email',
        'role',
    ];

    /**
     * Un profil Agent lié à cette personne physique.
     */
    public function agent()
    {
        return $this->hasOne(Agent::class, 'personne_id');
    }
}
