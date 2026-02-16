<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'total',
        'payment_method',
        'cash_amount',
        'change_amount',
        'points_earned',
        'points_redeemed',
        'discount_from_points',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'points_earned' => 'integer',
            'points_redeemed' => 'integer',
            'discount_from_points' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pointsHistory(): HasMany
    {
        return $this->hasMany(CustomerPoint::class);
    }
}
