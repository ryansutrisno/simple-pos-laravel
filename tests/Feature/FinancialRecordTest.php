<?php

use App\Models\FinancialRecord;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can list financial records', function () {
    $records = FinancialRecord::factory()->count(3)->create();

    $response = $this->get('/admin/financial-records');

    $response->assertStatus(200);
});

it('financial record belongs to transaction', function () {
    $transaction = Transaction::factory()->create();
    $record = FinancialRecord::factory()->create(['transaction_id' => $transaction->id]);

    expect($record->transaction->id)->toBe($transaction->id);
});

it('can create financial record', function () {
    $transaction = Transaction::factory()->create();

    $record = FinancialRecord::create([
        'type' => 'sales',
        'amount' => 50000,
        'profit' => 20000,
        'transaction_id' => $transaction->id,
        'description' => 'Penjualan produk',
        'record_date' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('financial_records', [
        'type' => 'sales',
        'amount' => 50000,
        'transaction_id' => $transaction->id,
    ]);
});

it('can create expense record without transaction', function () {
    $record = FinancialRecord::factory()->expense()->create();

    expect($record->type)->toBe('expense');
    expect($record->transaction_id)->toBeNull();
});

it('can filter records by type', function () {
    FinancialRecord::factory()->sales()->count(2)->create();
    FinancialRecord::factory()->expense()->count(3)->create();

    $salesRecords = FinancialRecord::where('type', 'sales')->get();
    $expenseRecords = FinancialRecord::where('type', 'expense')->get();

    expect($salesRecords)->toHaveCount(2);
    expect($expenseRecords)->toHaveCount(3);
});

it('can filter records by date range', function () {
    FinancialRecord::factory()->create(['record_date' => '2025-01-01']);
    FinancialRecord::factory()->create(['record_date' => '2025-01-15']);
    FinancialRecord::factory()->create(['record_date' => '2025-02-01']);

    $records = FinancialRecord::whereBetween('record_date', ['2025-01-01', '2025-01-31'])->get();

    expect($records)->toHaveCount(2);
});

it('calculates total profit correctly', function () {
    FinancialRecord::factory()->create(['type' => 'sales', 'profit' => 10000]);
    FinancialRecord::factory()->create(['type' => 'sales', 'profit' => 15000]);
    FinancialRecord::factory()->create(['type' => 'sales', 'profit' => 5000]);

    $totalProfit = FinancialRecord::where('type', 'sales')->sum('profit');

    expect($totalProfit)->toBe(30000);
});
