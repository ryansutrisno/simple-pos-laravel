<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Opname = 'opname';
    case Return = 'return';

    public function getLabel(): string
    {
        return match ($this) {
            self::In => 'Masuk',
            self::Out => 'Keluar',
            self::Adjustment => 'Penyesuaian',
            self::Sale => 'Penjualan',
            self::Opname => 'Opname',
            self::Return => 'Return',
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
            self::Return => 'purple',
        };
    }
}
