<?php

use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can list transactions', function () {
    $transactions = Transaction::factory()->count(3)->create();

    $response = $this->get('/admin/transactions');

    $response->assertStatus(200);
});

it('transaction belongs to a user', function () {
    $transaction = Transaction::factory()->create();

    expect($transaction->user)->not->toBeNull();
    expect($transaction->user->id)->toBe($transaction->user_id);
});

it('transaction has many items', function () {
    $transaction = Transaction::factory()->create();
    $items = \App\Models\TransactionItem::factory()->count(3)->create([
        'transaction_id' => $transaction->id,
    ]);

    expect($transaction->items)->toHaveCount(3);
});

it('can create cash transaction', function () {
    $transaction = Transaction::factory()->create([
        'payment_method' => 'cash',
        'total' => 50000,
        'cash_amount' => 100000,
        'change_amount' => 50000,
    ]);

    expect($transaction->payment_method)->toBe('cash');
    expect((float) $transaction->change_amount)->toBe(50000.0);
});

it('can create non-cash transaction without cash amount', function () {
    $transaction = Transaction::factory()->qris()->create();

    expect($transaction->payment_method)->toBe('qris');
    expect($transaction->cash_amount)->toBeNull();
    expect($transaction->change_amount)->toBeNull();
});

it('can filter transactions by date range', function () {
    $oldTransaction = Transaction::factory()->create([
        'created_at' => now()->subDays(10),
    ]);
    $newTransaction = Transaction::factory()->create([
        'created_at' => now(),
    ]);

    $from = now()->subDays(5)->format('Y-m-d');
    $to = now()->addDay()->format('Y-m-d');

    $response = $this->get("/admin/transactions?tableFilters[created_at][from]={$from}&tableFilters[created_at][to]={$to}");

    $response->assertStatus(200);
});
