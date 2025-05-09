<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialRecord extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'profit',
        'transaction_id',
        'description',
        'record_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'record_date' => 'date'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
