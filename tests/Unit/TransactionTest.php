<?php

use App\Models\Transaction;

it('transaction has correct fillable attributes', function () {
    $transaction = new Transaction;

    expect($transaction->getFillable())->toEqual([
        'user_id',
        'customer_id',
        'discount_id',
        'total',
        'subtotal_before_discount',
        'discount_amount',
        'voucher_code',
        'payment_method',
        'cash_amount',
        'change_amount',
        'points_earned',
        'points_redeemed',
        'discount_from_points',
        'is_split',
        'total_splits',
    ]);
});
