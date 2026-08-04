<?php

namespace App\Enums;

enum PeriodiciteTaxe: string
{
    case MENSUELLE = 'mensuelle';
    case TRIMESTRIELLE = 'trimestrielle';
    case ANNUELLE = 'annuelle';
    case PONCTUELLE = 'ponctuelle';

    public function label(): string
    {
        return match ($this) {
            self::MENSUELLE => 'Mensuelle',
            self::TRIMESTRIELLE => 'Trimestrielle',
            self::ANNUELLE => 'Annuelle',
            self::PONCTUELLE => 'Ponctuelle',
        };
    }
}
