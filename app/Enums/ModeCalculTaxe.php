<?php

namespace App\Enums;

enum ModeCalculTaxe: string
{
    case FIXE = 'fixe';
    case POURCENTAGE = 'pourcentage';
    case SURFACE = 'surface';
    case VOLUME = 'volume';
    case EFFECTIF = 'effectif';

    public function label(): string
    {
        return match ($this) {
            self::FIXE => 'Montant Fixe',
            self::POURCENTAGE => 'Pourcentage du CA',
            self::SURFACE => 'Par Surface (m²)',
            self::VOLUME => 'Par Volume (m³)',
            self::EFFECTIF => 'Par Effectif salarié',
        };
    }
}
