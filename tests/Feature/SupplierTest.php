<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierDebt;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'PT Supplier Utama',
        'address' => 'Jl. Industri No. 123',
        'phone' => '021-1234567',
        'email' => 'supplier@example.com',
        'is_active' => true,
        'notes' => 'Supplier utama untuk kopi',
    ]);

    $this->assertDatabaseHas('suppliers', [
        'name' => 'PT Supplier Utama',
        'email' => 'supplier@example.com',
    ]);
    expect($supplier->is_active)->toBeTrue();
});

it('can update a supplier', function () {
    $supplier = Supplier::factory()->create();

    $supplier->update([
        'name' => 'Supplier Updated',
        'phone' => '08123456789',
    ]);

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'Supplier Updated',
        'phone' => '08123456789',
    ]);
});

it('can delete a supplier', function () {
    $supplier = Supplier::factory()->create();

    $supplier->delete();

    $this->assertDatabaseMissing('suppliers', [
        'id' => $supplier->id,
    ]);
});

it('has many purchase orders', function () {
    $supplier = Supplier::factory()->create();
    $orders = PurchaseOrder::factory()->count(3)->create(['supplier_id' => $supplier->id]);

    expect($supplier->purchaseOrders)->toHaveCount(3)
        ->and($supplier->purchaseOrders->first())->toBeInstanceOf(PurchaseOrder::class);
});

it('has many debts', function () {
    $supplier = Supplier::factory()->create();
    SupplierDebt::factory()->count(2)->create(['supplier_id' => $supplier->id]);

    expect($supplier->debts)->toHaveCount(2)
        ->and($supplier->debts->first())->toBeInstanceOf(SupplierDebt::class);
});

it('returns correct total debt', function () {
    $supplier = Supplier::factory()->create();

    SupplierDebt::factory()->create([
        'supplier_id' => $supplier->id,
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'status' => 'pending',
    ]);

    SupplierDebt::factory()->create([
        'supplier_id' => $supplier->id,
        'total_amount' => 500000,
        'paid_amount' => 200000,
        'remaining_amount' => 300000,
        'status' => 'partial',
    ]);

    SupplierDebt::factory()->create([
        'supplier_id' => $supplier->id,
        'total_amount' => 200000,
        'paid_amount' => 200000,
        'remaining_amount' => 0,
        'status' => 'paid',
    ]);

    $totalDebt = $supplier->getTotalDebt();

    expect($totalDebt)->toBe(1300000.0);
});

it('returns zero total debt when no pending debts', function () {
    $supplier = Supplier::factory()->create();

    SupplierDebt::factory()->create([
        'supplier_id' => $supplier->id,
        'total_amount' => 200000,
        'paid_amount' => 200000,
        'remaining_amount' => 0,
        'status' => 'paid',
    ]);

    expect($supplier->getTotalDebt())->toBe(0.0);
});

it('can filter active suppliers', function () {
    Supplier::factory()->count(3)->create(['is_active' => true]);
    Supplier::factory()->count(2)->inactive()->create();

    $activeSuppliers = Supplier::active()->get();

    expect($activeSuppliers)->toHaveCount(3);
});

it('can create inactive supplier', function () {
    $supplier = Supplier::factory()->inactive()->create();

    expect($supplier->is_active)->toBeFalse();
});
