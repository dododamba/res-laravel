<?php

namespace App\Enums;

enum TaxeStatut: string
{
    case A_PAYER = 'A payer';
    case PARTIELLEMENT_PAYE = 'Partiellement payé';
    case SOLDE = 'Soldé';
    case EN_RETARD = 'En retard';
    case EXONERE = 'Exonéré';
    case ANNULE = 'Annulé';

    public function label(): string
    {
        return match ($this) {
            self::A_PAYER => 'À payer',
            self::PARTIELLEMENT_PAYE => 'Partiellement payé',
            self::SOLDE => 'Soldé',
            self::EN_RETARD => 'En retard',
            self::EXONERE => 'Exonéré',
            self::ANNULE => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::A_PAYER => 'badge-light-primary bg-light-primary text-primary',
            self::PARTIELLEMENT_PAYE => 'badge-light-warning bg-light-warning text-warning',
            self::SOLDE => 'badge-light-success bg-light-success text-success',
            self::EN_RETARD => 'badge-light-danger bg-light-danger text-danger',
            self::EXONERE => 'badge-light-info bg-light-info text-info',
            self::ANNULE => 'badge-light-dark bg-light-dark text-muted',
        };
    }
}
