<?php

namespace App\Models;

use App\Enums\RecensementStatut;
use App\Models\Traits\HasUserFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recensement extends Model
{
    use SoftDeletes, HasUserFilter; // Protection automatique d'accès enquêteur

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    /**
     * Surchage de fill() pour mapper automatiquement les clés camelCase de l'API mobile vers le snake_case SQL.
     */
    public function fill(array $attributes)
    {
        $snakeAttributes = [];
        foreach ($attributes as $key => $value) {
            $snakeAttributes[\Illuminate\Support\Str::snake($key)] = $value;
        }

        // Exclure les relations pivots et relations d'uploads avant de remplir les colonnes
        unset($snakeAttributes['priorites']);
        unset($snakeAttributes['priorities']);

        return parent::fill($snakeAttributes);
    }

    protected $casts = [
        'statut' => RecensementStatut::class,
        'date_recensement' => 'datetime',
        'gps_date_capture' => 'datetime',
        'signature_date' => 'datetime',
        'nombre_personnes' => 'integer',
        'nombre_hommes' => 'integer',
        'nombre_femmes' => 'integer',
        'nombre_enfants' => 'integer',
        'nombre_jeunes' => 'integer',
        'nombre_handicapes' => 'integer',
        'instruction_aucun' => 'integer',
        'instruction_primaire' => 'integer',
        'instruction_secondaire' => 'integer',
        'instruction_superieur' => 'integer',
        'gps_latitude' => 'float',
        'gps_longitude' => 'float',
        'gps_precision' => 'float',
    ];

    /**
     * Un recensement est rattaché à un Quartier administratif.
     */
    public function quartier()
    {
        return $this->belongsTo(Parameters\Quartier::class, 'quartier_id');
    }

    /**
     * Un recensement est rattaché à un Carré administratif.
     */
    public function carre()
    {
        return $this->belongsTo(Parameters\Carre::class, 'carre_id');
    }

    /**
     * Un recensement est rattaché à un Secteur administratif (nullable).
     */
    public function secteur()
    {
        return $this->belongsTo(Parameters\Secteur::class, 'secteur_id');
    }

    /**
     * Un recensement est rattaché à une Avenue (nullable).
     */
    public function avenue()
    {
        return $this->belongsTo(Parameters\Avenue::class, 'avenue_id');
    }

    /**
     * L'enquêteur ayant effectué l'enquête de ménage.
     */
    public function enqueteur()
    {
        return $this->belongsTo(Agent::class, 'enqueteur_id');
    }

    /**
     * Le contrôleur assigné.
     */
    public function controleur()
    {
        return $this->belongsTo(Agent::class, 'controleur_id');
    }

    /**
     * Le validateur final de la fiche de recensement.
     */
    public function validateur()
    {
        return $this->belongsTo(Agent::class, 'validateur_id');
    }

    /**
     * Relation Many-to-Many vers les Besoins Prioritaires exprimés par le ménage (Max 3).
     */
    public function priorites()
    {
        return $this->belongsToMany(Parameters\BesoinPrioritaire::class, 'recensement_besoin_prioritaire', 'recensement_id', 'besoin_id');
    }

    /**
     * Liste des habitations (Maisons) éventuellement rattachées à ce recensement de ménage.
     */
    public function maisons()
    {
        return $this->hasMany(Maison::class, 'recensement_id');
    }

    /**
     * Liste des opérateurs économiques rattachés à ce recensement (ex: commerce de ménage).
     */
    public function operateurs()
    {
        return $this->hasMany(Operateur::class, 'recensement_id');
    }

    /**
     * Historique des états de la fiche.
     */
    public function historiques()
    {
        return $this->hasMany(HistoriqueRecensement::class, 'recensement_id')->orderBy('created_at', 'desc');
    }
}
