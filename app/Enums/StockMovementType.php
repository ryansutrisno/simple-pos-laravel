<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Opname = 'opname';

    public function getLabel(): string
    {
        return match ($this) {
            self::In => 'Masuk',
            self::Out => 'Keluar',
            self::Adjustment => 'Penyesuaian',
            self::Sale => 'Penjualan',
            self::Opname => 'Opname',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'danger',
            self::Adjustment => 'warning',
            self::Sale => 'info',
            self::Opname => 'primary',
        };
    }
}
