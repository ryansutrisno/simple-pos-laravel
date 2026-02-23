<?php

namespace App\Models;

use App\Enums\StoreCreditType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_return_id',
        'amount',
        'balance_after',
        'type',
        'description',
        'expiry_date',
        'is_expired',
        'expired_at',
        'is_used',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StoreCreditType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'expiry_date' => 'date',
            'is_expired' => 'boolean',
            'expired_at' => 'datetime',
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function isEarned(): bool
    {
        return $this->type === StoreCreditType::Earn;
    }

    public function isUsed(): bool
    {
        return $this->is_used;
    }

    public function isExpired(): bool
    {
        return $this->is_expired;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiry_date || $this->is_expired || $this->is_used) {
            return false;
        }

        return $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function markAsExpired(): void
    {
        $this->update([
            'is_expired' => true,
            'expired_at' => now(),
        ]);
    }

    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }
}
