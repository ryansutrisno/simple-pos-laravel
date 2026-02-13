<?php

use App\Enums\OpnameStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create stock opname', function () {
    $opname = StockOpname::create([
        'opname_date' => now(),
        'status' => OpnameStatus::Draft,
        'notes' => 'Opname bulanan',
    ]);

    expect($opname->status)->toBe(OpnameStatus::Draft)
        ->and($opname->isDraft())->toBeTrue()
        ->and($opname->isCompleted())->toBeFalse();
});

it('auto generates opname number', function () {
    $opname = StockOpname::create([
        'opname_date' => now(),
    ]);

    expect($opname->opname_number)->toStartWith('OPN-')
        ->and(strlen($opname->opname_number))->toBe(9);
});

it('auto sets user id on creation', function () {
    $opname = StockOpname::create([
        'opname_date' => now(),
    ]);

    expect($opname->user_id)->toBe($this->user->id);
});

it('can add items to opname', function () {
    $opname = StockOpname::create(['opname_date' => now()]);
    $product = Product::factory()->create(['stock' => 100]);

    $item = StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 95,
    ]);

    expect($opname->items)->toHaveCount(1)
        ->and($item->system_stock)->toBe(100)
        ->and($item->actual_stock)->toBe(95);
});

it('calculates difference correctly', function () {
    $opname = StockOpname::create(['opname_date' => now()]);
    $product = Product::factory()->create(['stock' => 100]);

    $item = StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 92,
    ]);

    expect($item->difference)->toBe(-8)
        ->and($item->hasDifference())->toBeTrue()
        ->and($item->isDeficit())->toBeTrue()
        ->and($item->isSurplus())->toBeFalse();
});

it('calculates surplus correctly', function () {
    $opname = StockOpname::create(['opname_date' => now()]);
    $product = Product::factory()->create(['stock' => 100]);

    $item = StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 105,
    ]);

    expect($item->difference)->toBe(5)
        ->and($item->isSurplus())->toBeTrue()
        ->and($item->isDeficit())->toBeFalse();
});

it('handles no difference', function () {
    $opname = StockOpname::create(['opname_date' => now()]);
    $product = Product::factory()->create(['stock' => 100]);

    $item = StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 100,
    ]);

    expect($item->difference)->toBe(0)
        ->and($item->hasDifference())->toBeFalse();
});

it('completing opname updates stock', function () {
    $product = Product::factory()->create(['stock' => 100]);
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 95,
    ]);

    $opname->complete();

    $product->refresh();
    expect($product->stock)->toBe(95)
        ->and($opname->isCompleted())->toBeTrue()
        ->and($opname->status)->toBe(OpnameStatus::Completed);
});

it('completing opname creates stock history', function () {
    $product = Product::factory()->create(['stock' => 100]);
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 97,
    ]);

    $opname->complete();

    $history = StockHistory::where('product_id', $product->id)
        ->where('type', StockMovementType::Opname)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->quantity)->toBe(-3)
        ->and($history->stock_before)->toBe(100)
        ->and($history->stock_after)->toBe(97)
        ->and($history->reference_type)->toBe(StockOpname::class)
        ->and($history->reference_id)->toBe($opname->id);
});

it('cannot complete non-draft opname', function () {
    $opname = StockOpname::factory()->completed()->create();

    $opname->complete();

    expect($opname->status)->toBe(OpnameStatus::Completed);
});

it('ignores items with no difference', function () {
    $product = Product::factory()->create(['stock' => 100]);
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 100,
    ]);

    $opname->complete();

    $product->refresh();
    expect($product->stock)->toBe(100);

    $history = StockHistory::where('product_id', $product->id)
        ->where('type', StockMovementType::Opname)
        ->first();

    expect($history)->toBeNull();
});

it('calculates total difference', function () {
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);
    $product1 = Product::factory()->create(['stock' => 100]);
    $product2 = Product::factory()->create(['stock' => 50]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product1->id,
        'system_stock' => 100,
        'actual_stock' => 95,
    ]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product2->id,
        'system_stock' => 50,
        'actual_stock' => 52,
    ]);

    expect($opname->getTotalDifference())->toBe(-3);
});

it('gets items with difference', function () {
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);
    $product1 = Product::factory()->create(['stock' => 100]);
    $product2 = Product::factory()->create(['stock' => 50]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product1->id,
        'system_stock' => 100,
        'actual_stock' => 100,
    ]);

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product2->id,
        'system_stock' => 50,
        'actual_stock' => 48,
    ]);

    $itemsWithDiff = $opname->getItemsWithDifference();

    expect($itemsWithDiff)->toHaveCount(1)
        ->and($itemsWithDiff->first()->product_id)->toBe($product2->id);
});

it('belongs to user', function () {
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);

    expect($opname->user->id)->toBe($this->user->id);
});

it('has many items', function () {
    $opname = StockOpname::create(['opname_date' => now(), 'status' => OpnameStatus::Draft]);
    $product = Product::factory()->create();

    StockOpnameItem::create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_stock' => 100,
        'actual_stock' => 100,
    ]);

    expect($opname->items)->toHaveCount(1);
});

it('can be cancelled', function () {
    $opname = StockOpname::factory()->create(['status' => OpnameStatus::Draft]);

    $opname->update(['status' => OpnameStatus::Cancelled]);

    expect($opname->status)->toBe(OpnameStatus::Cancelled);
});
