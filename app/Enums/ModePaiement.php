<?php

namespace App\Enums;

enum ModePaiement: string
{
    case ESPECES = 'Espèces';
    case MOBILE_MONEY = 'Mobile Money';
    case BANQUE = 'Banque';
    case CHEQUE = 'Chèque';

    public function label(): string
    {
        return match ($this) {
            self::ESPECES => 'Espèces / Enveloppe',
            self::MOBILE_MONEY => 'Mobile Money (Wave/MTN/Moov/Orange)',
            self::BANQUE => 'Virement Bancaire',
            self::CHEQUE => 'Chèque Certifié',
        };
    }
}
