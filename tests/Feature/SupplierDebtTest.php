<?php

use App\Enums\DebtStatus;
use App\Models\DebtPayment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierDebt;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create debt from purchase order', function () {
    $supplier = Supplier::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->received()->create([
        'supplier_id' => $supplier->id,
        'total_amount' => 5000000,
    ]);

    $debt = SupplierDebt::create([
        'supplier_id' => $supplier->id,
        'purchase_order_id' => $purchaseOrder->id,
        'total_amount' => 5000000,
        'paid_amount' => 0,
        'remaining_amount' => 5000000,
        'debt_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => DebtStatus::Pending,
        'user_id' => $this->user->id,
    ]);

    expect($debt->total_amount)->toBe('5000000.00')
        ->and($debt->remaining_amount)->toBe('5000000.00')
        ->and($debt->status)->toBe(DebtStatus::Pending);
});

it('auto generates debt number', function () {
    $debt = SupplierDebt::create([
        'supplier_id' => Supplier::factory()->create()->id,
        'purchase_order_id' => PurchaseOrder::factory()->create()->id,
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'debt_date' => now(),
        'due_date' => now()->addDays(30),
        'user_id' => $this->user->id,
    ]);

    expect($debt->debt_number)->toStartWith('HUT-')
        ->and(strlen($debt->debt_number))->toBe(9);
});

it('can record payment for debt', function () {
    $debt = SupplierDebt::factory()->create([
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'status' => DebtStatus::Pending,
    ]);

    $payment = DebtPayment::create([
        'supplier_debt_id' => $debt->id,
        'amount' => 500000,
        'payment_date' => now(),
        'payment_method' => 'transfer',
        'user_id' => $this->user->id,
    ]);

    $debt->refresh();

    expect($debt->paid_amount)->toBe('500000.00')
        ->and($debt->remaining_amount)->toBe('500000.00')
        ->and($debt->status)->toBe(DebtStatus::Partial);
});

it('updates status to partial after partial payment', function () {
    $debt = SupplierDebt::factory()->create([
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'status' => DebtStatus::Pending,
    ]);

    $debt->addPayment(300000);

    expect($debt->status)->toBe(DebtStatus::Partial)
        ->and((float) $debt->paid_amount)->toBe(300000.0)
        ->and((float) $debt->remaining_amount)->toBe(700000.0);
});

it('updates status to paid after full payment', function () {
    $debt = SupplierDebt::factory()->create([
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'status' => DebtStatus::Pending,
    ]);

    $debt->addPayment(1000000);

    expect($debt->status)->toBe(DebtStatus::Paid)
        ->and((float) $debt->paid_amount)->toBe(1000000.0)
        ->and((float) $debt->remaining_amount)->toBe(0.0)
        ->and($debt->isPaid())->toBeTrue();
});

it('handles overpayment correctly', function () {
    $debt = SupplierDebt::factory()->create([
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'status' => DebtStatus::Pending,
    ]);

    $debt->addPayment(1200000);

    expect($debt->status)->toBe(DebtStatus::Paid)
        ->and((float) $debt->remaining_amount)->toBe(0.0);
});

it('transitions from pending to partial', function () {
    $debt = SupplierDebt::factory()->create([
        'status' => DebtStatus::Pending,
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
    ]);

    $debt->addPayment(500000);

    expect($debt->status)->toBe(DebtStatus::Partial);
});

it('transitions from partial to paid', function () {
    $debt = SupplierDebt::factory()->create([
        'status' => DebtStatus::Partial,
        'total_amount' => 1000000,
        'paid_amount' => 500000,
        'remaining_amount' => 500000,
    ]);

    $debt->addPayment(500000);

    expect($debt->status)->toBe(DebtStatus::Paid);
});

it('detects overdue debt', function () {
    $debt = SupplierDebt::factory()->create([
        'due_date' => now()->subDays(5),
        'status' => DebtStatus::Pending,
    ]);

    expect($debt->isOverdue())->toBeTrue();
});

it('does not mark paid debt as overdue', function () {
    $debt = SupplierDebt::factory()->create([
        'due_date' => now()->subDays(5),
        'status' => DebtStatus::Paid,
        'paid_amount' => 1000000,
        'remaining_amount' => 0,
    ]);

    expect($debt->isOverdue())->toBeFalse();
});

it('can query overdue debts', function () {
    SupplierDebt::factory()->create([
        'due_date' => now()->subDays(5),
        'status' => DebtStatus::Pending,
    ]);

    SupplierDebt::factory()->create([
        'due_date' => now()->addDays(10),
        'status' => DebtStatus::Pending,
    ]);

    $overdueDebts = SupplierDebt::overdue()->get();

    expect($overdueDebts)->toHaveCount(1);
});

it('can query pending debts', function () {
    SupplierDebt::factory()->count(2)->create(['status' => DebtStatus::Pending]);
    SupplierDebt::factory()->paid()->create();

    $pendingDebts = SupplierDebt::pending()->get();

    expect($pendingDebts)->toHaveCount(2);
});

it('belongs to supplier', function () {
    $supplier = Supplier::factory()->create();
    $debt = SupplierDebt::factory()->create(['supplier_id' => $supplier->id]);

    expect($debt->supplier->id)->toBe($supplier->id);
});

it('belongs to purchase order', function () {
    $order = PurchaseOrder::factory()->create();
    $debt = SupplierDebt::factory()->create(['purchase_order_id' => $order->id]);

    expect($debt->purchaseOrder->id)->toBe($order->id);
});

it('has many payments', function () {
    $debt = SupplierDebt::factory()->create();
    DebtPayment::factory()->count(2)->create(['supplier_debt_id' => $debt->id]);

    expect($debt->payments)->toHaveCount(2);
});

it('payment auto updates debt on creation', function () {
    $debt = SupplierDebt::factory()->create([
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'remaining_amount' => 1000000,
        'status' => DebtStatus::Pending,
    ]);

    DebtPayment::create([
        'supplier_debt_id' => $debt->id,
        'amount' => 400000,
        'payment_date' => now(),
        'payment_method' => 'cash',
        'user_id' => $this->user->id,
    ]);

    $debt->refresh();

    expect($debt->paid_amount)->toBe('400000.00')
        ->and($debt->remaining_amount)->toBe('600000.00')
        ->and($debt->status)->toBe(DebtStatus::Partial);
});
