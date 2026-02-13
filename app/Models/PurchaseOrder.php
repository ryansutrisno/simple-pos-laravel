<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'total_amount',
        'status',
        'payment_status',
        'order_date',
        'expected_date',
        'received_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_number)) {
                $model->order_number = 'PO-'.str_pad(PurchaseOrder::count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function calculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('subtotal');
        $this->save();
    }

    public function canUpdate(): bool
    {
        return $this->status === PurchaseOrderStatus::Draft;
    }

    public function canDelete(): bool
    {
        return in_array($this->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Cancelled]);
    }

    public function canMarkAsOrdered(): bool
    {
        return $this->status === PurchaseOrderStatus::Pending;
    }

    public function canReceive(): bool
    {
        return $this->status === PurchaseOrderStatus::Ordered;
    }

    public function isReceived(): bool
    {
        return $this->status === PurchaseOrderStatus::Received;
    }
}
