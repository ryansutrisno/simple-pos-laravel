<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Menunggu',
            self::Ordered => 'Dipesan',
            self::Received => 'Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Ordered => 'info',
            self::Received => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Pending, self::Cancelled]),
            self::Pending => in_array($status, [self::Ordered, self::Cancelled]),
            self::Ordered => in_array($status, [self::Received, self::Cancelled]),
            self::Received => false,
            self::Cancelled => false,
        };
    }
}
