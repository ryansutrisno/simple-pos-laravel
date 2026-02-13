<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\StockService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->stockService = app(StockService::class);
});

it('creates stock history when stock is added', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->addStock($product, 10, StockMovementType::In);

    expect($history)->toBeInstanceOf(StockHistory::class)
        ->and($history->product_id)->toBe($product->id)
        ->and($history->type)->toBe(StockMovementType::In)
        ->and($history->quantity)->toBe(10)
        ->and($history->stock_before)->toBe(50)
        ->and($history->stock_after)->toBe(60);
});

it('creates stock history when stock is subtracted', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->subtractStock($product, 10, StockMovementType::Out);

    expect($history->quantity)->toBe(-10)
        ->and($history->stock_before)->toBe(50)
        ->and($history->stock_after)->toBe(40);
});

it('records correct before and after values', function () {
    $product = Product::factory()->create(['stock' => 100]);

    $history = $this->stockService->setStock($product, 75, StockMovementType::Opname);

    expect($history->stock_before)->toBe(100)
        ->and($history->stock_after)->toBe(75)
        ->and($history->quantity)->toBe(-25);
});

it('has correct type in', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->addStock($product, 10, StockMovementType::In);

    expect($history->type)->toBe(StockMovementType::In)
        ->and($history->isIn())->toBeTrue()
        ->and($history->isOut())->toBeFalse();
});

it('has correct type out', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->subtractStock($product, 10, StockMovementType::Out);

    expect($history->type)->toBe(StockMovementType::Out)
        ->and($history->isIn())->toBeFalse()
        ->and($history->isOut())->toBeTrue();
});

it('has correct type adjustment', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->create();

    $history = $this->stockService->addStock($product, 10, StockMovementType::Adjustment, $adjustment);

    expect($history->type)->toBe(StockMovementType::Adjustment)
        ->and($history->isIn())->toBeFalse()
        ->and($history->isOut())->toBeFalse();
});

it('has correct type sale', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->subtractStock($product, 5, StockMovementType::Sale);

    expect($history->type)->toBe(StockMovementType::Sale)
        ->and($history->isOut())->toBeTrue();
});

it('has correct type opname', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $opname = StockOpname::factory()->create();

    $history = $this->stockService->setStock($product, 45, StockMovementType::Opname, $opname);

    expect($history->type)->toBe(StockMovementType::Opname);
});

it('has correct polymorphic reference', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $adjustment = StockAdjustment::factory()->create();

    $history = $this->stockService->addStock($product, 10, StockMovementType::Adjustment, $adjustment, 'Test note');

    expect($history->reference_type)->toBe(StockAdjustment::class)
        ->and($history->reference_id)->toBe($adjustment->id)
        ->and($history->reference)->toBeInstanceOf(StockAdjustment::class)
        ->and($history->reference->id)->toBe($adjustment->id);
});

it('has null reference when not provided', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->addStock($product, 10);

    expect($history->reference_type)->toBeNull()
        ->and($history->reference_id)->toBeNull()
        ->and($history->reference)->toBeNull();
});

it('belongs to a product', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $history = $this->stockService->addStock($product, 10);

    expect($history->product->id)->toBe($product->id);
});

it('belongs to a user', function () {
    $product = Product::factory()->create(['stock' => 50]);
    $history = $this->stockService->addStock($product, 10);

    expect($history->user->id)->toBe($this->user->id);
});

it('can have a note', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $history = $this->stockService->addStock($product, 10, StockMovementType::In, null, 'Restock from supplier');

    expect($history->note)->toBe('Restock from supplier');
});
