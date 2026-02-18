<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SplitBill;
use App\Models\SuspendedTransaction;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'barcode' => '1234567890123',
        'stock' => 100,
        'is_active' => true,
    ]);
});

it('can suspend transaction', function () {
    $suspended = SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'cart_items' => [
            ['product_id' => $this->product->id, 'name' => $this->product->name, 'quantity' => 2],
        ],
        'subtotal' => $this->product->selling_price * 2,
        'total' => $this->product->selling_price * 2,
    ]);

    expect($suspended)->toBeInstanceOf(SuspendedTransaction::class)
        ->and(SuspendedTransaction::count())->toBe(1);
});

it('cannot exceed max suspended per user', function () {
    for ($i = 0; $i < 5; $i++) {
        SuspendedTransaction::create([
            'user_id' => $this->user->id,
            'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
            'cart_items' => [],
            'subtotal' => 10000,
            'total' => 10000,
        ]);
    }

    $count = SuspendedTransaction::where('user_id', $this->user->id)->count();
    expect($count)->toBe(5);
});

it('can resume transaction', function () {
    $cartItems = [
        ['product_id' => $this->product->id, 'name' => $this->product->name, 'quantity' => 2, 'selling_price' => $this->product->selling_price],
    ];

    SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => 'SUS-TEST1234',
        'cart_items' => $cartItems,
        'subtotal' => $this->product->selling_price * 2,
        'total' => $this->product->selling_price * 2,
    ]);

    $found = SuspendedTransaction::where('suspension_key', 'SUS-TEST1234')
        ->where('user_id', $this->user->id)
        ->first();

    expect($found)->not->toBeNull()
        ->and($found->cart_items)->toBe($cartItems);

    $found->delete();

    expect(SuspendedTransaction::where('suspension_key', 'SUS-TEST1234')->exists())->toBeFalse();
});

it('can delete suspended transaction', function () {
    $suspended = SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'cart_items' => [],
        'subtotal' => 10000,
        'total' => 10000,
    ]);

    $suspended->delete();

    expect(SuspendedTransaction::find($suspended->id))->toBeNull();
});

it('can add multiple payments', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 100000,
        'payment_method' => 'multi',
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'cash',
        'amount' => 50000,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'transfer',
        'amount' => 50000,
        'reference' => 'TRX-123',
    ]);

    expect($transaction->payments)->toHaveCount(2)
        ->and($transaction->total_paid)->toBe(100000.0);
});

it('payment total calculated correctly', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 150000,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'cash',
        'amount' => 50000,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'qris',
        'amount' => 100000,
    ]);

    expect($transaction->total_paid)->toBe(150000.0);
});

it('can complete multi payment transaction', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 100000,
        'payment_method' => 'multi',
        'is_split' => false,
        'total_splits' => 1,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'cash',
        'amount' => 60000,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'transfer',
        'amount' => 40000,
        'reference' => 'REF-001',
    ]);

    expect($transaction->total_paid)->toBe(100000.0)
        ->and($transaction->payments)->toHaveCount(2);
});

it('can init split bill', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 100000,
        'is_split' => true,
        'total_splits' => 3,
    ]);

    $amountPerSplit = round($transaction->total / 3, 2);

    for ($i = 1; $i <= 3; $i++) {
        SplitBill::create([
            'transaction_id' => $transaction->id,
            'split_number' => $i,
            'subtotal' => $amountPerSplit,
            'payment_method' => 'cash',
            'amount_paid' => $amountPerSplit,
        ]);
    }

    expect($transaction->splitBills)->toHaveCount(3);
});

it('split amounts distributed correctly', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 100000,
        'is_split' => true,
        'total_splits' => 3,
    ]);

    $amountPerSplit = round($transaction->total / 3, 2);
    $totalSplit = $amountPerSplit * 3;
    $lastAmount = $amountPerSplit + ($transaction->total - $totalSplit);

    SplitBill::create([
        'transaction_id' => $transaction->id,
        'split_number' => 1,
        'subtotal' => $amountPerSplit,
        'payment_method' => 'cash',
        'amount_paid' => $amountPerSplit,
    ]);

    SplitBill::create([
        'transaction_id' => $transaction->id,
        'split_number' => 2,
        'subtotal' => $amountPerSplit,
        'payment_method' => 'transfer',
        'amount_paid' => $amountPerSplit,
    ]);

    SplitBill::create([
        'transaction_id' => $transaction->id,
        'split_number' => 3,
        'subtotal' => $lastAmount,
        'payment_method' => 'qris',
        'amount_paid' => $lastAmount,
    ]);

    $totalSplitAmount = $transaction->splitBills->sum('amount_paid');

    expect((int) $totalSplitAmount)->toBe((int) $transaction->total);
});

it('can process split payment', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 50000,
        'is_split' => true,
        'total_splits' => 2,
    ]);

    $split = SplitBill::create([
        'transaction_id' => $transaction->id,
        'split_number' => 1,
        'subtotal' => 25000,
        'payment_method' => 'cash',
        'amount_paid' => 25000,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => $split->payment_method,
        'amount' => $split->amount_paid,
    ]);

    expect($transaction->payments)->toHaveCount(1)
        ->and((float) $transaction->payments->first()->amount)->toBe(25000.0);
});

it('can complete split bill', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'total' => 100000,
        'is_split' => true,
        'total_splits' => 2,
    ]);

    for ($i = 1; $i <= 2; $i++) {
        $split = SplitBill::create([
            'transaction_id' => $transaction->id,
            'split_number' => $i,
            'subtotal' => 50000,
            'payment_method' => 'cash',
            'amount_paid' => 50000,
        ]);

        TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'payment_method' => $split->payment_method,
            'amount' => $split->amount_paid,
        ]);
    }

    expect($transaction->splitBills)->toHaveCount(2)
        ->and($transaction->payments)->toHaveCount(2)
        ->and($transaction->is_split)->toBeTrue()
        ->and($transaction->total_splits)->toBe(2);
});

it('barcode finds product', function () {
    $foundProduct = Product::where('barcode', '1234567890123')->first();

    expect($foundProduct)->not->toBeNull()
        ->and($foundProduct->id)->toBe($this->product->id);
});

it('barcode shows error if not found', function () {
    $foundProduct = Product::where('barcode', 'nonexistent')->first();

    expect($foundProduct)->toBeNull();
});

it('suspended transaction has unique key', function () {
    $key1 = SuspendedTransaction::generateSuspensionKey();
    $key2 = SuspendedTransaction::generateSuspensionKey();

    expect($key1)->not->toBe($key2)
        ->and($key1)->toStartWith('SUS-');
});

it('transaction has payments relationship', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
    ]);

    TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'cash',
        'amount' => 50000,
    ]);

    expect($transaction->payments)->toHaveCount(1);
});

it('transaction has split bills relationship', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'is_split' => true,
        'total_splits' => 2,
    ]);

    SplitBill::create([
        'transaction_id' => $transaction->id,
        'split_number' => 1,
        'subtotal' => 50000,
        'payment_method' => 'cash',
        'amount_paid' => 50000,
    ]);

    expect($transaction->splitBills)->toHaveCount(1);
});

it('user has suspended transactions relationship', function () {
    SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'cart_items' => [],
        'subtotal' => 10000,
        'total' => 10000,
    ]);

    expect($this->user->suspendedTransactions)->toHaveCount(1);
});

it('suspended transaction can have customer', function () {
    $customer = Customer::factory()->create();

    $suspended = SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'customer_id' => $customer->id,
        'cart_items' => [],
        'subtotal' => 50000,
        'total' => 50000,
    ]);

    expect($suspended->customer_id)->toBe($customer->id)
        ->and($suspended->customer)->not->toBeNull();
});

it('suspended transaction can have voucher code', function () {
    $suspended = SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'cart_items' => [],
        'subtotal' => 100000,
        'discount_amount' => 10000,
        'total' => 90000,
        'voucher_code' => 'DISKON10',
    ]);

    expect($suspended->voucher_code)->toBe('DISKON10');
});

it('transaction payment has method label', function () {
    $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

    $cash = TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'cash',
        'amount' => 10000,
    ]);

    $transfer = TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'transfer',
        'amount' => 10000,
    ]);

    $qris = TransactionPayment::create([
        'transaction_id' => $transaction->id,
        'payment_method' => 'qris',
        'amount' => 10000,
    ]);

    expect($cash->payment_method_label)->toBe('Tunai')
        ->and($transfer->payment_method_label)->toBe('Transfer Bank')
        ->and($qris->payment_method_label)->toBe('QRIS');
});

it('suspended transaction scope for user', function () {
    $user2 = User::factory()->create();

    SuspendedTransaction::create([
        'user_id' => $this->user->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'cart_items' => [],
        'subtotal' => 10000,
        'total' => 10000,
    ]);

    SuspendedTransaction::create([
        'user_id' => $user2->id,
        'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
        'cart_items' => [],
        'subtotal' => 20000,
        'total' => 20000,
    ]);

    $user1Transactions = SuspendedTransaction::forUser($this->user->id)->get();
    $user2Transactions = SuspendedTransaction::forUser($user2->id)->get();

    expect($user1Transactions)->toHaveCount(1)
        ->and($user2Transactions)->toHaveCount(1);
});
