<?php

namespace App\Enums;

enum AdjustmentReason: string
{
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Lost = 'lost';
    case Theft = 'theft';
    case Sample = 'sample';
    case Correction = 'correction';
    case Found = 'found';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Damaged => 'Rusak',
            self::Expired => 'Kedaluwarsa',
            self::Lost => 'Hilang',
            self::Theft => 'Pencurian',
            self::Sample => 'Sampel',
            self::Correction => 'Koreksi',
            self::Found => 'Ditemukan',
            self::Other => 'Lainnya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Damaged => 'danger',
            self::Expired => 'warning',
            self::Lost => 'danger',
            self::Theft => 'danger',
            self::Sample => 'info',
            self::Correction => 'primary',
            self::Found => 'success',
            self::Other => 'gray',
        };
    }
}
