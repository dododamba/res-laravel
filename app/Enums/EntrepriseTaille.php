<?php

namespace App\Enums;

enum EntrepriseTaille: string
{
    case MICRO = 'micro';
    case PETITE = 'petite';
    case MOYENNE = 'moyenne';
    case GRANDE = 'grande';

    public function label(): string
    {
        return match ($this) {
            self::MICRO => 'Micro-entreprise (1-9 employés)',
            self::PETITE => 'Petite entreprise (10-49 employés)',
            self::MOYENNE => 'Moyenne entreprise (50-249 employés)',
            self::GRANDE => 'Grande entreprise (250+ employés)',
        };
    }
}
