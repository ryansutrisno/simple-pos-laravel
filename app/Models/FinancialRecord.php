<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'profit',
        'transaction_id',
        'product_return_id',
        'description',
        'record_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'record_date' => 'date',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function isSale(): bool
    {
        return $this->type === 'sales';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }
}
