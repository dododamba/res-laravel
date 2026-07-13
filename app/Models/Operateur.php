<?php

namespace App\Models;

use App\Enums\OperateurStatut;
use App\Enums\EntrepriseTaille;
use App\Models\Traits\HasUserFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Operateur extends Model implements HasMedia
{
    use SoftDeletes, HasUserFilter, InteractsWithMedia;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'statut' => OperateurStatut::class,
        'taille' => EntrepriseTaille::class,
        'gps_latitude' => 'float',
        'gps_longitude' => 'float',
        'gps_precision' => 'float',
        'gps_date_capture' => 'datetime',
        'effectif_hommes' => 'integer',
        'effectif_femmes' => 'integer',
        'effectif_total' => 'integer',
        'effectif_permanents' => 'integer',
        'effectif_temporaires' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos_commerce')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('documents_commerciaux')
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
     * Rattachement facultatif au Recensement de ménage d'origine.
     */
    public function recensement()
    {
        return $this->belongsTo(Recensement::class, 'recensement_id');
    }

    /**
     * Catégorie d'activité ou secteur économique de l'opérateur.
     */
    public function categorie()
    {
        return $this->belongsTo(Parameters\CategorieOperateur::class, 'categorie_id');
    }

    /**
     * Campagne de recensement associée.
     */
    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id');
    }

    /**
     * Localisation administrative fine.
     */
    public function quartier()
    {
        return $this->belongsTo(Parameters\Quartier::class, 'quartier_id');
    }

    public function carre()
    {
        return $this->belongsTo(Parameters\Carre::class, 'carre_id');
    }

    public function secteur()
    {
        return $this->belongsTo(Parameters\Secteur::class, 'secteur_id');
    }

    public function avenue()
    {
        return $this->belongsTo(Parameters\Avenue::class, 'avenue_id');
    }

    /**
     * Agents impliqués (enquêteur de saisie et validateur final).
     */
    public function enqueteur()
    {
        return $this->belongsTo(Agent::class, 'enqueteur_id');
    }

    public function validateur()
    {
        return $this->belongsTo(Agent::class, 'validateur_id');
    }

    /**
     * Historique d'états d'instruction.
     */
    public function historiques()
    {
        return $this->hasMany(HistoriqueOperateur::class, 'operateur_id')->orderBy('created_at', 'desc');
    }
}
