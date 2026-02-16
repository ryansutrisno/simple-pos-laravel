<?php

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PointService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->pointService = new PointService;
});

it('can create customer', function () {
    $customer = Customer::create([
        'name' => 'John Doe',
        'phone' => '081234567890',
        'email' => 'john@example.com',
        'address' => 'Jl. Test No. 123',
    ]);

    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
        'phone' => '081234567890',
        'email' => 'john@example.com',
    ]);
    expect($customer->fresh()->points)->toBe(0)
        ->and((float) $customer->fresh()->total_spent)->toBe(0.0)
        ->and($customer->fresh()->total_transactions)->toBe(0)
        ->and($customer->fresh()->is_active)->toBeTrue();
});

it('can update customer', function () {
    $customer = Customer::factory()->create();

    $customer->update([
        'name' => 'Jane Doe',
        'phone' => '089876543210',
    ]);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Jane Doe',
        'phone' => '089876543210',
    ]);
});

it('can delete customer', function () {
    $customer = Customer::factory()->create();

    $customer->delete();

    $this->assertDatabaseMissing('customers', [
        'id' => $customer->id,
    ]);
});

it('can earn points', function () {
    $customer = Customer::factory()->create(['points' => 0]);

    $pointRecord = $customer->addPoints(50, null, 'Test points');

    expect($customer->fresh()->points)->toBe(50)
        ->and($pointRecord->type)->toBe('earn')
        ->and($pointRecord->amount)->toBe(50)
        ->and($pointRecord->balance_after)->toBe(50);

    $this->assertDatabaseHas('customer_points', [
        'customer_id' => $customer->id,
        'type' => 'earn',
        'amount' => 50,
        'balance_after' => 50,
    ]);
});

it('can redeem points', function () {
    $customer = Customer::factory()->create(['points' => 100]);
    $transaction = Transaction::factory()->create();

    $pointRecord = $customer->redeemPoints(30, $transaction->id);

    expect($customer->fresh()->points)->toBe(70)
        ->and($pointRecord->type)->toBe('redeem')
        ->and($pointRecord->amount)->toBe(30);

    $this->assertDatabaseHas('customer_points', [
        'customer_id' => $customer->id,
        'transaction_id' => $transaction->id,
        'type' => 'redeem',
        'amount' => 30,
        'balance_after' => 70,
    ]);
});

it('points balance is correct after multiple transactions', function () {
    $customer = Customer::factory()->create(['points' => 0]);

    $customer->addPoints(100, null, 'Initial');
    expect($customer->fresh()->points)->toBe(100);

    $customer->addPoints(50, null, 'Bonus');
    expect($customer->fresh()->points)->toBe(150);

    $transaction = Transaction::factory()->create();
    $customer->redeemPoints(30, $transaction->id);
    expect($customer->fresh()->points)->toBe(120);
});

it('customer stats update after transaction', function () {
    $customer = Customer::factory()->create([
        'total_spent' => 0,
        'total_transactions' => 0,
    ]);

    $customer->updateStats(150000);

    expect($customer->fresh()->total_transactions)->toBe(1)
        ->and((float) $customer->fresh()->total_spent)->toBe(150000.0);

    $customer->updateStats(50000);
    expect($customer->fresh()->total_transactions)->toBe(2)
        ->and((float) $customer->fresh()->total_spent)->toBe(200000.0);
});

it('cannot redeem more than balance', function () {
    $customer = Customer::factory()->create(['points' => 50]);
    $transaction = Transaction::factory()->create();

    expect(fn () => $this->pointService->redeemPoints($customer, 60, $transaction->id))
        ->toThrow(\InvalidArgumentException::class, 'Poin tidak mencukupi');
});

it('cannot redeem less than minimum', function () {
    $customer = Customer::factory()->create(['points' => 100]);
    $transaction = Transaction::factory()->create();

    expect(fn () => $this->pointService->redeemPoints($customer, 5, $transaction->id))
        ->toThrow(\InvalidArgumentException::class, 'Minimum poin untuk ditukar adalah 10 poin');
});

it('calculates earned points correctly', function () {
    expect($this->pointService->calculateEarnedPoints(50000))->toBe(5)
        ->and($this->pointService->calculateEarnedPoints(45000))->toBe(4)
        ->and($this->pointService->calculateEarnedPoints(100000))->toBe(10)
        ->and($this->pointService->calculateEarnedPoints(9999))->toBe(0);
});

it('calculates redeem value correctly', function () {
    expect($this->pointService->calculateRedeemValue(10))->toBe(10000.0)
        ->and($this->pointService->calculateRedeemValue(50))->toBe(50000.0)
        ->and($this->pointService->calculateRedeemValue(100))->toBe(100000.0);
});

it('calculates max redeemable points correctly', function () {
    $transactionTotal = 100000;

    expect($this->pointService->getMaxRedeemablePoints(100, $transactionTotal))->toBe(50)
        ->and($this->pointService->getMaxRedeemablePoints(30, $transactionTotal))->toBe(30)
        ->and($this->pointService->getMaxRedeemablePoints(200, $transactionTotal))->toBe(50);
});

it('cannot redeem more than max percentage of transaction', function () {
    $customer = Customer::factory()->create(['points' => 200]);
    $transaction = Transaction::factory()->create(['total' => 100000]);

    $maxRedeemable = $this->pointService->getMaxRedeemablePoints(200, 100000);
    expect($maxRedeemable)->toBe(50);
});

it('can filter active customers', function () {
    Customer::factory()->count(3)->create(['is_active' => true]);
    Customer::factory()->count(2)->inactive()->create();

    $activeCustomers = Customer::active()->get();

    expect($activeCustomers)->toHaveCount(3);
});

it('can create inactive customer', function () {
    $customer = Customer::factory()->inactive()->create();

    expect($customer->is_active)->toBeFalse();
});

it('can create customer with points', function () {
    $customer = Customer::factory()->withPoints(500)->create();

    expect($customer->points)->toBe(500);
});

it('phone must be unique', function () {
    Customer::factory()->create(['phone' => '081234567890']);

    expect(fn () => Customer::factory()->create(['phone' => '081234567890']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('email must be unique when provided', function () {
    Customer::factory()->create(['email' => 'test@example.com']);

    expect(fn () => Customer::factory()->create(['email' => 'test@example.com']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('email can be null', function () {
    $customer = Customer::factory()->create(['email' => null]);

    expect($customer->email)->toBeNull();
});

it('has many points history', function () {
    $customer = Customer::factory()->create();

    CustomerPoint::factory()->count(3)->create(['customer_id' => $customer->id]);

    expect($customer->pointsHistory)->toHaveCount(3)
        ->and($customer->pointsHistory->first())->toBeInstanceOf(CustomerPoint::class);
});

it('has many transactions', function () {
    $customer = Customer::factory()->create();

    Transaction::factory()->count(2)->create(['customer_id' => $customer->id]);

    expect($customer->transactions)->toHaveCount(2)
        ->and($customer->transactions->first())->toBeInstanceOf(Transaction::class);
});
