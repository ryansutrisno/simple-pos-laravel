<?php

namespace App\Enums;

enum DebtStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Belum Dibayar',
            self::Partial => 'Sebagian',
            self::Paid => 'Lunas',
            self::Overdue => 'Jatuh Tempo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Partial => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
        };
    }
}
