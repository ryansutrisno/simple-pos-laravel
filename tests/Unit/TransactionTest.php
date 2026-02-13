<?php

use App\Models\Transaction;

it('transaction has correct fillable attributes', function () {
    $transaction = new Transaction;

    expect($transaction->getFillable())->toEqual([
        'user_id',
        'total',
        'payment_method',
        'cash_amount',
        'change_amount',
    ]);
});
