<?php

namespace App\Models;

use App\Enums\RefundMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'transaction_id',
        'customer_id',
        'user_id',
        'type',
        'reason_category',
        'reason_note',
        'refund_method',
        'total_refund',
        'total_exchange_value',
        'selisih_amount',
        'selisih_payment_method',
        'store_credit_id',
        'points_reversed',
        'points_returned',
        'return_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReturnType::class,
            'reason_category' => ReturnReason::class,
            'refund_method' => RefundMethod::class,
            'total_refund' => 'decimal:2',
            'total_exchange_value' => 'decimal:2',
            'selisih_amount' => 'decimal:2',
            'points_reversed' => 'integer',
            'points_returned' => 'integer',
            'return_date' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductReturnItem::class);
    }

    public function storeCredit(): HasOne
    {
        return $this->hasOne(StoreCredit::class);
    }

    public function isFullReturn(): bool
    {
        return $this->type === ReturnType::Full;
    }

    public function isPartialReturn(): bool
    {
        return $this->type === ReturnType::Partial;
    }

    public function isExchange(): bool
    {
        return $this->type === ReturnType::Exchange;
    }

    public function hasSelisih(): bool
    {
        return $this->selisih_amount != 0;
    }

    public function needsPayment(): bool
    {
        return $this->selisih_amount > 0;
    }

    public function needsRefund(): bool
    {
        return $this->selisih_amount < 0;
    }

    public function getSelisihType(): string
    {
        if ($this->selisih_amount > 0) {
            return 'pay';
        }
        if ($this->selisih_amount < 0) {
            return 'refund';
        }

        return 'none';
    }
}
