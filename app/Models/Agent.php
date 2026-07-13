<?php

namespace App\Models;

use App\Enums\AgentStatut;
use App\Models\Parameters\Fonction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'personne_id',
        'fonction_id',
        'user_id',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'nationalite',
        'telephone_secondaire',
        'adresse',
        'profession',
        'matricule',
        'cni',
        'statut',
        'date_nomination',
        'date_fin_fonction',
        'observations',
        'qr_code',
        'photo',
    ];

    protected $casts = [
        'statut' => AgentStatut::class,
        'date_naissance' => 'date',
        'date_nomination' => 'date',
        'date_fin_fonction' => 'date',
    ];

    /**
     * Liaison One-to-One avec la fiche de Personne physique.
     */
    public function personne()
    {
        return $this->belongsTo(Personne::class, 'personne_id');
    }

    /**
     * Liaison Many-to-One vers la Fonction de paramétrage associée.
     */
    public function fonction()
    {
        return $this->belongsTo(Fonction::class, 'fonction_id');
    }

    /**
     * Liaison One-to-One facultative vers le Compte Utilisateur d'authentification.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Les affectations géographiques de l'agent.
     */
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'agent_id');
    }
}
