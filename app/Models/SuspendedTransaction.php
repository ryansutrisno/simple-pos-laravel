<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SuspendedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'suspension_key',
        'customer_id',
        'cart_items',
        'subtotal',
        'discount_amount',
        'total',
        'voucher_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cart_items' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function generateSuspensionKey(): string
    {
        return 'SUS-'.strtoupper(Str::random(8));
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
