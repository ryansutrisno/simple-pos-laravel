<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_stock',
        'actual_stock',
        'difference',
        'note',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->actual_stock !== null) {
                $model->difference = $model->actual_stock - $model->system_stock;
            }
        });
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasDifference(): bool
    {
        return $this->difference !== 0 && $this->difference !== null;
    }

    public function isSurplus(): bool
    {
        return $this->difference > 0;
    }

    public function isDeficit(): bool
    {
        return $this->difference < 0;
    }
}
