<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::BankTransfer => 'Virement bancaire',
            self::Cash => 'Espèces (bureau GKAS)',
            self::Other => 'Autre',
        };
    }
}
