<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_return_id',
        'transaction_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'is_exchange',
        'exchange_product_id',
        'exchange_quantity',
        'exchange_unit_price',
        'exchange_subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'is_exchange' => 'boolean',
            'exchange_unit_price' => 'decimal:2',
            'exchange_subtotal' => 'decimal:2',
        ];
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function exchangeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'exchange_product_id');
    }

    public function hasExchange(): bool
    {
        return $this->is_exchange && $this->exchange_product_id !== null;
    }

    public function getPriceDifference(): float
    {
        if (! $this->hasExchange()) {
            return 0;
        }

        return (float) ($this->exchange_subtotal - $this->subtotal);
    }
}
