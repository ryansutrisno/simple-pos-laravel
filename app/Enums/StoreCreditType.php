<?php

namespace App\Enums;

enum StoreCreditType: string
{
    case Earn = 'earn';
    case Use = 'use';
    case Adjust = 'adjust';
    case Expire = 'expire';

    public function getLabel(): string
    {
        return match ($this) {
            self::Earn => 'Diperoleh',
            self::Use => 'Digunakan',
            self::Adjust => 'Penyesuaian',
            self::Expire => 'Kedaluwarsa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Earn => 'success',
            self::Use => 'info',
            self::Adjust => 'warning',
            self::Expire => 'danger',
        };
    }
}
