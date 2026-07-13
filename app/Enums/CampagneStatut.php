<?php

namespace App\Enums;

enum CampagneStatut: string
{
    case BROUILLON = 'brouillon';
    case OUVERTE = 'ouverte';
    case SUSPENDUE = 'suspendue';
    case TERMINEE = 'terminee';
    case ARCHIVEE = 'archivee';
    case CLOTUREE = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::OUVERTE => 'Ouverte',
            self::SUSPENDUE => 'Suspendue',
            self::TERMINEE => 'Terminée',
            self::ARCHIVEE => 'Archivée',
            self::CLOTUREE => 'Clôturée',
        };
    }
}
