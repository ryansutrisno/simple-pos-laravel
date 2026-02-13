<?php

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockHistory;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create stock adjustment increase', function () {
    $adjustment = StockAdjustment::create([
        'adjustment_date' => now(),
        'type' => AdjustmentType::Increase,
        'reason' => AdjustmentReason::Found,
        'notes' => 'Penambahan stok dari gudang',
        'user_id' => $this->user->id,
    ]);

    expect($adjustment->type)->toBe(AdjustmentType::Increase)
        ->and($adjustment->isIncrease())->toBeTrue()
        ->and($adjustment->isDecrease())->toBeFalse();
});

it('can create stock adjustment decrease', function () {
    $adjustment = StockAdjustment::create([
        'adjustment_date' => now(),
        'type' => AdjustmentType::Decrease,
        'reason' => AdjustmentReason::Damaged,
        'notes' => 'Barang rusak',
        'user_id' => $this->user->id,
    ]);

    expect($adjustment->type)->toBe(AdjustmentType::Decrease)
        ->and($adjustment->isDecrease())->toBeTrue()
        ->and($adjustment->isIncrease())->toBeFalse();
});

it('auto generates adjustment number', function () {
    $adjustment = StockAdjustment::create([
        'adjustment_date' => now(),
        'type' => AdjustmentType::Increase,
        'reason' => AdjustmentReason::Correction,
        'user_id' => $this->user->id,
    ]);

    expect($adjustment->adjustment_number)->toStartWith('ADJ-')
        ->and(strlen($adjustment->adjustment_number))->toBe(9);
});

it('updates stock correctly on increase', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->increase()->create(['user_id' => $this->user->id]);

    $item = StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity' => 20,
    ]);

    $product->refresh();

    expect($product->stock)->toBe(70)
        ->and($item->stock_before)->toBe(50)
        ->and($item->stock_after)->toBe(70);
});

it('updates stock correctly on decrease', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->decrease()->create(['user_id' => $this->user->id]);

    $item = StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity' => 15,
    ]);

    $product->refresh();

    expect($product->stock)->toBe(35)
        ->and($item->stock_before)->toBe(50)
        ->and($item->stock_after)->toBe(35);
});

it('creates stock history on adjustment item creation', function () {
    $product = Product::factory()->create(['stock' => 100]);
    $adjustment = StockAdjustment::factory()->increase()->create([
        'user_id' => $this->user->id,
        'reason' => AdjustmentReason::Found,
    ]);

    $item = StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity' => 25,
    ]);

    $history = StockHistory::where('product_id', $product->id)
        ->where('reference_type', StockAdjustment::class)
        ->where('reference_id', $adjustment->id)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe(StockMovementType::Adjustment)
        ->and($history->quantity)->toBe(25)
        ->and($history->stock_before)->toBe(100)
        ->and($history->stock_after)->toBe(125);
});

it('creates stock history on decrease adjustment', function () {
    $product = Product::factory()->create(['stock' => 100]);
    $adjustment = StockAdjustment::factory()->decrease()->create([
        'user_id' => $this->user->id,
        'reason' => AdjustmentReason::Damaged,
    ]);

    $item = StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity' => 30,
    ]);

    $history = StockHistory::where('product_id', $product->id)->first();

    expect($history->quantity)->toBe(30)
        ->and($history->stock_before)->toBe(100)
        ->and($history->stock_after)->toBe(70);
});

it('belongs to user', function () {
    $adjustment = StockAdjustment::factory()->create(['user_id' => $this->user->id]);

    expect($adjustment->user->id)->toBe($this->user->id);
});

it('has many items', function () {
    $adjustment = StockAdjustment::factory()->create();
    $product = Product::factory()->create();

    StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    expect($adjustment->items)->toHaveCount(1);
});

it('can have multiple items', function () {
    $adjustment = StockAdjustment::factory()->increase()->create(['user_id' => $this->user->id]);
    $product1 = Product::factory()->create(['stock' => 50]);
    $product2 = Product::factory()->create(['stock' => 30]);

    StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product1->id,
        'quantity' => 10,
    ]);

    StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product2->id,
        'quantity' => 5,
    ]);

    $product1->refresh();
    $product2->refresh();

    expect($product1->stock)->toBe(60)
        ->and($product2->stock)->toBe(35)
        ->and($adjustment->items)->toHaveCount(2);
});

it('can create with different reasons', function () {
    $adjustment = StockAdjustment::create([
        'adjustment_date' => now(),
        'type' => AdjustmentType::Decrease,
        'reason' => AdjustmentReason::Expired,
        'user_id' => $this->user->id,
    ]);

    expect($adjustment->reason)->toBe(AdjustmentReason::Expired);
});

it('stock history has correct reference', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->increase()->create(['user_id' => $this->user->id]);

    StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $history = StockHistory::where('product_id', $product->id)->first();

    expect($history->reference->id)->toBe($adjustment->id)
        ->and($history->reference)->toBeInstanceOf(StockAdjustment::class);
});
