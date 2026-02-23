<?php

namespace App\Enums;

enum ReturnType: string
{
    case Full = 'full';
    case Partial = 'partial';
    case Exchange = 'exchange';

    public function getLabel(): string
    {
        return match ($this) {
            self::Full => 'Return Penuh',
            self::Partial => 'Return Sebagian',
            self::Exchange => 'Tukar Barang',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Full => 'danger',
            self::Partial => 'warning',
            self::Exchange => 'info',
        };
    }
}
