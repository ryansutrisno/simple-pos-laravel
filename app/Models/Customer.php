<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'points',
        'total_spent',
        'total_transactions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'total_spent' => 'decimal:2',
            'total_transactions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pointsHistory(): HasMany
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function addPoints(int $amount, ?int $transactionId = null, string $description = ''): CustomerPoint
    {
        $balanceAfter = $this->points + $amount;

        $pointRecord = $this->pointsHistory()->create([
            'transaction_id' => $transactionId,
            'type' => 'earn',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);

        $this->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function redeemPoints(int $amount, int $transactionId): CustomerPoint
    {
        $balanceAfter = $this->points - $amount;

        $pointRecord = $this->pointsHistory()->create([
            'transaction_id' => $transactionId,
            'type' => 'redeem',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => 'Penukaran poin untuk transaksi #'.$transactionId,
        ]);

        $this->update(['points' => $balanceAfter]);

        return $pointRecord;
    }

    public function getCurrentBalance(): int
    {
        return $this->points;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function updateStats(float $transactionTotal): void
    {
        $this->increment('total_transactions');
        $this->increment('total_spent', $transactionTotal);
    }
}
