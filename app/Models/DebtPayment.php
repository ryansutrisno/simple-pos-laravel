<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'supplier_debt_id',
        'amount',
        'payment_date',
        'payment_method',
        'note',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_number)) {
                $model->payment_number = 'PAY-'.str_pad(DebtPayment::count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            $model->debt->addPayment($model->amount);
        });
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(SupplierDebt::class, 'supplier_debt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
