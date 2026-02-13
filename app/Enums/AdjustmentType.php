<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';

    public function getLabel(): string
    {
        return match ($this) {
            self::Increase => 'Penambahan',
            self::Decrease => 'Pengurangan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Increase => 'success',
            self::Decrease => 'danger',
        };
    }
}
