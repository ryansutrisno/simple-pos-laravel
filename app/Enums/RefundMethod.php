<?php

namespace App\Enums;

enum RefundMethod: string
{
    case Cash = 'cash';
    case StoreCredit = 'store_credit';
    case OriginalPayment = 'original_payment';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::StoreCredit => 'Store Credit',
            self::OriginalPayment => 'Pembayaran Asli',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::StoreCredit => 'info',
            self::OriginalPayment => 'primary',
        };
    }
}
