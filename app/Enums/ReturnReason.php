<?php

namespace App\Enums;

enum ReturnReason: string
{
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case NotAsExpected = 'not_as_expected';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Damaged => 'Produk Rusak/Cacat',
            self::WrongItem => 'Salah Kirim',
            self::NotAsExpected => 'Tidak Sesuai Harapan',
            self::Other => 'Lainnya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Damaged => 'danger',
            self::WrongItem => 'warning',
            self::NotAsExpected => 'info',
            self::Other => 'gray',
        };
    }
}
