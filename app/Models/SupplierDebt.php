<?php

namespace App\Models;

use App\Enums\DebtStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_number',
        'supplier_id',
        'purchase_order_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'debt_date',
        'due_date',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'status' => DebtStatus::class,
        'debt_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->debt_number)) {
                $model->debt_number = 'HUT-'.str_pad(SupplierDebt::count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->remaining_amount = $this->total_amount - $this->paid_amount;

        if ($this->remaining_amount <= 0) {
            $this->status = DebtStatus::Paid;
            $this->remaining_amount = 0;
        } elseif ($this->paid_amount > 0) {
            $this->status = DebtStatus::Partial;
        }

        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->status === DebtStatus::Paid;
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && ! $this->isPaid();
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [DebtStatus::Paid]);
    }

    public function scopePending($query)
    {
        return $query->where('status', DebtStatus::Pending);
    }
}
