<?php

namespace App\Enums;

enum RecensementStatut: string
{
    case BROUILLON = 'brouillon';
    case SOUMIS = 'soumis';
    case CONTROLE = 'controle';
    case VALIDE = 'valide';
    case REJETE = 'rejete';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMIS => 'Soumis',
            self::CONTROLE => 'Contrôlé',
            self::VALIDE => 'Validé',
            self::REJETE => 'Rejeté',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BROUILLON => 'badge-secondary bg-secondary',
            self::SOUMIS => 'badge-info bg-info text-white',
            self::CONTROLE => 'badge-warning bg-warning text-dark',
            self::VALIDE => 'badge-success bg-success',
            self::REJETE => 'badge-danger bg-danger',
        };
    }
}
