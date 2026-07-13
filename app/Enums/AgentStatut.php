<?php

namespace App\Enums;

enum AgentStatut: string
{
    case ACTIF = 'actif';
    case SUSPENDU = 'suspendu';
    case INACTIF = 'inactif';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::SUSPENDU => 'Suspendu',
            self::INACTIF => 'Inactif',
        };
    }
}
