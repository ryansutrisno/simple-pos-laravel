<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\StockService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->stockService = app(StockService::class);
});

it('adds stock correctly', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->addStock($product, 25);

    $product->refresh();
    expect($product->stock)->toBe(75)
        ->and($history->type)->toBe(StockMovementType::In)
        ->and($history->quantity)->toBe(25)
        ->and($history->stock_before)->toBe(50)
        ->and($history->stock_after)->toBe(75);
});

it('subtracts stock correctly', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->subtractStock($product, 20);

    $product->refresh();
    expect($product->stock)->toBe(30)
        ->and($history->type)->toBe(StockMovementType::Out)
        ->and($history->quantity)->toBe(-20)
        ->and($history->stock_before)->toBe(50)
        ->and($history->stock_after)->toBe(30);
});

it('sets stock correctly', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->setStock($product, 75);

    $product->refresh();
    expect($product->stock)->toBe(75)
        ->and($history->type)->toBe(StockMovementType::Opname)
        ->and($history->quantity)->toBe(25)
        ->and($history->stock_before)->toBe(50)
        ->and($history->stock_after)->toBe(75);
});

it('sets stock with decrease', function () {
    $product = Product::factory()->create(['stock' => 100]);

    $history = $this->stockService->setStock($product, 80);

    $product->refresh();
    expect($product->stock)->toBe(80)
        ->and($history->quantity)->toBe(-20);
});

it('sets stock to same value', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->setStock($product, 50);

    $product->refresh();
    expect($product->stock)->toBe(50)
        ->and($history->quantity)->toBe(0);
});

it('isLowStock returns true when stock is at threshold', function () {
    $product = Product::factory()->create([
        'stock' => 10,
        'low_stock_threshold' => 10,
    ]);

    expect($this->stockService->isLowStock($product))->toBeTrue();
});

it('isLowStock returns true when stock is below threshold', function () {
    $product = Product::factory()->create([
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);

    expect($this->stockService->isLowStock($product))->toBeTrue();
});

it('isLowStock returns false when stock is above threshold', function () {
    $product = Product::factory()->create([
        'stock' => 20,
        'low_stock_threshold' => 10,
    ]);

    expect($this->stockService->isLowStock($product))->toBeFalse();
});

it('getLowStockProducts returns correct collection', function () {
    $lowStock1 = Product::factory()->create([
        'stock' => 5,
        'low_stock_threshold' => 10,
        'is_active' => true,
    ]);

    $lowStock2 = Product::factory()->create([
        'stock' => 10,
        'low_stock_threshold' => 10,
        'is_active' => true,
    ]);

    $normalStock = Product::factory()->create([
        'stock' => 50,
        'low_stock_threshold' => 10,
        'is_active' => true,
    ]);

    $inactiveLowStock = Product::factory()->create([
        'stock' => 5,
        'low_stock_threshold' => 10,
        'is_active' => false,
    ]);

    $lowStockProducts = $this->stockService->getLowStockProducts();

    expect($lowStockProducts)->toHaveCount(2)
        ->and($lowStockProducts->contains('id', $lowStock1->id))->toBeTrue()
        ->and($lowStockProducts->contains('id', $lowStock2->id))->toBeTrue()
        ->and($lowStockProducts->contains('id', $normalStock->id))->toBeFalse()
        ->and($lowStockProducts->contains('id', $inactiveLowStock->id))->toBeFalse();
});

it('adjustStock increases stock when isIncrease is true', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->create();

    $history = $this->stockService->adjustStock($product, 20, true, $adjustment, 'Adjustment test');

    $product->refresh();
    expect($product->stock)->toBe(70)
        ->and($history->type)->toBe(StockMovementType::Adjustment)
        ->and($history->quantity)->toBe(20);
});

it('adjustStock decreases stock when isIncrease is false', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->create();

    $history = $this->stockService->adjustStock($product, 15, false, $adjustment, 'Adjustment test');

    $product->refresh();
    expect($product->stock)->toBe(35)
        ->and($history->type)->toBe(StockMovementType::Adjustment)
        ->and($history->quantity)->toBe(-15);
});

it('creates history with reference', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->create();

    $history = $this->stockService->addStock(
        $product,
        10,
        StockMovementType::Adjustment,
        $adjustment,
        'Test note'
    );

    expect($history->reference_type)->toBe(StockAdjustment::class)
        ->and($history->reference_id)->toBe($adjustment->id)
        ->and($history->note)->toBe('Test note');
});

it('creates history with user id', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->addStock($product, 10);

    expect($history->user_id)->toBe($this->user->id);
});

it('allows stock to go negative', function () {
    $product = Product::factory()->create(['stock' => 5]);

    $history = $this->stockService->subtractStock($product, 10);

    $product->refresh();
    expect($product->stock)->toBe(-5)
        ->and($history->stock_after)->toBe(-5);
});
