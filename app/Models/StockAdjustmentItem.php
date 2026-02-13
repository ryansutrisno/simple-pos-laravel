<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'quantity',
        'stock_before',
        'stock_after',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $product = Product::find($model->product_id);
            $model->stock_before = $product->stock;

            if ($model->stockAdjustment->isIncrease()) {
                $model->stock_after = $model->stock_before + $model->quantity;
            } else {
                $model->stock_after = $model->stock_before - $model->quantity;
            }

            $product->update(['stock' => $model->stock_after]);

            StockHistory::create([
                'product_id' => $model->product_id,
                'type' => StockMovementType::Adjustment,
                'quantity' => $model->quantity,
                'stock_before' => $model->stock_before,
                'stock_after' => $model->stock_after,
                'reference_type' => StockAdjustment::class,
                'reference_id' => $model->stock_adjustment_id,
                'note' => $model->stockAdjustment->reason->getLabel(),
                'user_id' => $model->stockAdjustment->user_id,
            ]);
        });
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
