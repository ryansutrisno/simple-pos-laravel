<?php

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->stockService = app(StockService::class);
});

it('can create purchase order with items', function () {
    $supplier = Supplier::factory()->create();
    $category = Category::factory()->create();
    $product1 = Product::factory()->create(['category_id' => $category->id, 'purchase_price' => 10000]);
    $product2 = Product::factory()->create(['category_id' => $category->id, 'purchase_price' => 15000]);

    $order = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseOrderStatus::Draft,
        'payment_status' => PaymentStatus::Unpaid,
        'user_id' => $this->user->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 10,
        'quantity_received' => 0,
        'purchase_price' => 10000,
        'subtotal' => 100000,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 5,
        'quantity_received' => 0,
        'purchase_price' => 15000,
        'subtotal' => 75000,
    ]);

    $order->calculateTotal();

    expect($order->items)->toHaveCount(2)
        ->and($order->total_amount)->toBe('175000.00');
});

it('auto generates order number', function () {
    $order = PurchaseOrder::create([
        'supplier_id' => Supplier::factory()->create()->id,
        'user_id' => $this->user->id,
    ]);

    expect($order->order_number)->toStartWith('PO-')
        ->and(strlen($order->order_number))->toBe(8);
});

it('can transition from draft to pending', function () {
    $order = PurchaseOrder::factory()->draft()->create();

    expect($order->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($order->canUpdate())->toBeTrue();

    $order->update(['status' => PurchaseOrderStatus::Pending]);

    expect($order->status)->toBe(PurchaseOrderStatus::Pending);
});

it('can transition from pending to ordered', function () {
    $order = PurchaseOrder::factory()->pending()->create();

    expect($order->canMarkAsOrdered())->toBeTrue();

    $order->update(['status' => PurchaseOrderStatus::Ordered]);

    expect($order->status)->toBe(PurchaseOrderStatus::Ordered);
});

it('can transition from ordered to received', function () {
    $order = PurchaseOrder::factory()->ordered()->create();

    expect($order->canReceive())->toBeTrue();

    $order->update([
        'status' => PurchaseOrderStatus::Received,
        'received_date' => now(),
    ]);

    expect($order->isReceived())->toBeTrue();
});

it('cannot transition from received', function () {
    $order = PurchaseOrder::factory()->received()->create();

    expect($order->status->canTransitionTo(PurchaseOrderStatus::Ordered))->toBeFalse();
});

it('receiving items updates product stock', function () {
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['stock' => 50]);
    $order = PurchaseOrder::factory()->ordered()->create(['supplier_id' => $supplier->id]);

    $item = PurchaseOrderItem::create([
        'purchase_order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'quantity_received' => 0,
        'purchase_price' => $product->purchase_price,
        'subtotal' => 20 * $product->purchase_price,
    ]);

    $this->stockService->addStock($product, 20, StockMovementType::In, $order, "PO: {$order->order_number}");

    $product->refresh();
    expect($product->stock)->toBe(70);
});

it('creates stock history on receive', function () {
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['stock' => 50]);
    $order = PurchaseOrder::factory()->ordered()->create(['supplier_id' => $supplier->id]);

    $history = $this->stockService->addStock($product, 20, StockMovementType::In, $order);

    expect($history->type)->toBe(StockMovementType::In)
        ->and($history->reference_type)->toBe(PurchaseOrder::class)
        ->and($history->reference_id)->toBe($order->id)
        ->and($history->quantity)->toBe(20);
});

it('can delete draft order', function () {
    $order = PurchaseOrder::factory()->draft()->create();

    expect($order->canDelete())->toBeTrue();

    $order->delete();

    $this->assertDatabaseMissing('purchase_orders', ['id' => $order->id]);
});

it('cannot delete received order', function () {
    $order = PurchaseOrder::factory()->received()->create();

    expect($order->canDelete())->toBeFalse();
});

it('belongs to supplier', function () {
    $supplier = Supplier::factory()->create();
    $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);

    expect($order->supplier->id)->toBe($supplier->id);
});

it('belongs to user', function () {
    $order = PurchaseOrder::factory()->create(['user_id' => $this->user->id]);

    expect($order->user->id)->toBe($this->user->id);
});

it('has many items', function () {
    $order = PurchaseOrder::factory()->create();
    PurchaseOrderItem::factory()->count(3)->create(['purchase_order_id' => $order->id]);

    expect($order->items)->toHaveCount(3);
});

it('can be cancelled', function () {
    $order = PurchaseOrder::factory()->pending()->create();

    $order->update(['status' => PurchaseOrderStatus::Cancelled]);

    expect($order->status)->toBe(PurchaseOrderStatus::Cancelled);
});
