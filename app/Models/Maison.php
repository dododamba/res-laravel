<?php

namespace App\Models;

use App\Enums\MaisonStatut;
use App\Models\Traits\HasUserFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Maison extends Model implements HasMedia
{
    use SoftDeletes, HasUserFilter, InteractsWithMedia;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'numero_porte',
        'adresse',
        'nombre_hommes',
        'nombre_femmes',
        'nombre_enfants',
        'carre_id',
        'recensement_id',
        'reference_cadastrale',
        'usage_principal_id',
        'type_construction_id',
        'statut_foncier_id',
        'source_eau_id',
        'source_energie_id',
        'assainissement_id',
        'gestion_dechet_id',
        'gps_latitude',
        'gps_longitude',
        'gps_altitude',
        'gps_precision',
        'gps_date_capture',
        'statut',
        'enqueteur_id',
        'controleur_id',
        'validateur_id',
        'proprietaire_nom',
        'proprietaire_telephone',
    ];

    protected $casts = [
        'statut' => MaisonStatut::class,
        'numero_porte' => 'integer',
        'nombre_hommes' => 'integer',
        'nombre_femmes' => 'integer',
        'nombre_enfants' => 'integer',
        'gps_latitude' => 'float',
        'gps_longitude' => 'float',
        'gps_altitude' => 'float',
        'gps_precision' => 'float',
        'gps_date_capture' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Maison $maison) {
            if (empty($maison->id)) {
                $maison->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos_habitation')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('documents_cadastre')
             ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(150)
             ->height(150)
             ->sharpen(10);
    }

    /**
     * Le carré géographique auquel appartient l'habitation.
     */
    public function carre()
    {
        return $this->belongsTo(Parameters\Carre::class, 'carre_id');
    }

    /**
     * Le recensement de ménage associé si existant.
     */
    public function recensement()
    {
        return $this->belongsTo(Recensement::class, 'recensement_id');
    }

    /**
     * L'enquêteur ayant créé la fiche.
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
     * Le validateur de la fiche.
     */
    public function validateur()
    {
        return $this->belongsTo(Agent::class, 'validateur_id');
    }

    /**
     * Historiques des modifications d'états d'habitations.
     */
    public function historiques()
    {
        return $this->hasMany(HistoriqueMaison::class, 'maison_id')->orderBy('created_at', 'desc');
    }

    public function usagePrincipal()
    {
        return $this->belongsTo(Parameters\CategorieActivite::class, 'usage_principal_id');
    }

    public function typeConstruction()
    {
        return $this->belongsTo(Parameters\TypeBatiment::class, 'type_construction_id');
    }

    public function statutFoncier()
    {
        return $this->belongsTo(Parameters\TypePropriete::class, 'statut_foncier_id');
    }

    public function sourceEau()
    {
        return $this->belongsTo(Parameters\SourceEau::class, 'source_eau_id');
    }

    public function sourceEnergie()
    {
        return $this->belongsTo(Parameters\SourceEnergie::class, 'source_energie_id');
    }

    public function assainissement()
    {
        return $this->belongsTo(Parameters\Assainissement::class, 'assainissement_id');
    }

    public function gestionDechet()
    {
        return $this->belongsTo(Parameters\GestionDechet::class, 'gestion_dechet_id');
    }
}
