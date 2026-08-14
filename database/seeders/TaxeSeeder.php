<?php

namespace Database\Seeders;

use App\Enums\ModeCalculTaxe;
use App\Enums\PeriodiciteTaxe;
use App\Models\Taxe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxeSeeder extends Seeder
{
    /**
     * Enregistre le tableau officiel des taxes municipales.
     */
    public function run(): void
    {
        $taxes = [
            [
                'code' => 'PAT-COMM',
                'nom' => 'Patente Commerciale et Industrielle',
                'description' => 'Taxe communale obligatoire sur les établissements commerciaux et industriels exerçant sur le territoire municipal.',
                'categorie' => 'Patente Commerciale',
                'montant' => 50000,
                'mode_calcul' => ModeCalculTaxe::FIXE->value,
                'periodicite' => PeriodiciteTaxe::ANNUELLE->value,
                'actif' => true,
                'ordre' => 1,
            ],
            [
                'code' => 'ODP-TERR',
                'nom' => 'Taxe d\'Occupation du Domaine Public (ODP)',
                'description' => 'Redevance pour occupation temporaire du domaine public routier, terrasses ou étalages extérieurs.',
                'categorie' => 'Domaine Public',
                'montant' => 1500,
                'mode_calcul' => ModeCalculTaxe::SURFACE->value,
                'surface' => 10,
                'periodicite' => PeriodiciteTaxe::MENSUELLE->value,
                'actif' => true,
                'ordre' => 2,
            ],
            [
                'code' => 'LIC-BOIS',
                'nom' => 'Licence de Débit de Boissons et Établissements de Nuit',
                'description' => 'Droit de licence annuel dû par les maquis, bars, restaurants et débits de boissons.',
                'categorie' => 'Licences & Boissons',
                'montant' => 75000,
                'mode_calcul' => ModeCalculTaxe::FIXE->value,
                'periodicite' => PeriodiciteTaxe::ANNUELLE->value,
                'actif' => true,
                'ordre' => 3,
            ],
            [
                'code' => 'TAX-SALU',
                'nom' => 'Taxe d\'Assainissement et de Salubrité Publique',
                'description' => 'Contribution municipale aux services de collecte, traitement des déchets et propreté urbaine.',
                'categorie' => 'Hygiène & Salubrité',
                'montant' => 10000,
                'mode_calcul' => ModeCalculTaxe::FIXE->value,
                'periodicite' => PeriodiciteTaxe::TRIMESTRIELLE->value,
                'actif' => true,
                'ordre' => 4,
            ],
            [
                'code' => 'PUB-ENSI',
                'nom' => 'Taxe sur les Enseignes et Publicités',
                'description' => 'Taxe sur les panneaux publicitaires, enseignes lumineuses et affichages commerciaux visuels.',
                'categorie' => 'Publicité & Enseignes',
                'montant' => 25000,
                'mode_calcul' => ModeCalculTaxe::FIXE->value,
                'periodicite' => PeriodiciteTaxe::ANNUELLE->value,
                'actif' => true,
                'ordre' => 5,
            ],
            [
                'code' => 'TAX-STAT',
                'nom' => 'Droit de Place et Stationnement Commercial',
                'description' => 'Taxe d\'emprise pour véhicules commerciaux, parkings privés d\'entreprises et baies de livraison.',
                'categorie' => 'Droits de Place',
                'montant' => 5000,
                'mode_calcul' => ModeCalculTaxe::FIXE->value,
                'periodicite' => PeriodiciteTaxe::MENSUELLE->value,
                'actif' => true,
                'ordre' => 6,
            ],
        ];

        foreach ($taxes as $taxeData) {
            $taxeData['date_debut'] = now()->startOfYear();
            $uuid = (string) Str::uuid();
            
            $taxe = Taxe::firstOrCreate(
                ['code' => $taxeData['code']],
                array_merge($taxeData, [
                    'id' => $uuid,
                    'uuid' => $uuid,
                ])
            );
            
            // Si la taxe existait déjà, mettre à jour les autres champs sauf l'ID
            if (!$taxe->wasRecentlyCreated) {
                $updateData = array_merge($taxeData, [
                    'updated_at' => now(),
                ]);
                unset($updateData['id'], $updateData['uuid']);
                $taxe->update($updateData);
            }
        }
    }
}
