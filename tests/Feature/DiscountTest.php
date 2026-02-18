<?php

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Services\DiscountService;

beforeEach(function () {
    $this->discountService = new DiscountService;
});

it('can create discount', function () {
    $discount = Discount::factory()->create([
        'name' => 'Test Discount',
        'type' => 'percentage',
        'value' => 10,
        'target_type' => 'global',
    ]);

    expect($discount)->toBeInstanceOf(Discount::class)
        ->and($discount->name)->toBe('Test Discount')
        ->and($discount->type)->toBe('percentage')
        ->and((float) $discount->value)->toBe(10.0);
});

it('can update discount', function () {
    $discount = Discount::factory()->create();

    $discount->update([
        'name' => 'Updated Discount',
        'value' => 20,
    ]);

    expect($discount->fresh()->name)->toBe('Updated Discount')
        ->and((float) $discount->fresh()->value)->toBe(20.0);
});

it('can delete discount', function () {
    $discount = Discount::factory()->create();

    $discount->delete();

    expect(Discount::find($discount->id))->toBeNull();
});

it('calculates percentage discount correctly', function () {
    $discount = Discount::factory()->percentage()->create([
        'value' => 10,
    ]);

    $discountAmount = $discount->calculateDiscount(100000);

    expect($discountAmount)->toBe(10000.0);
});

it('calculates fixed discount correctly', function () {
    $discount = Discount::factory()->fixed()->create([
        'value' => 5000,
    ]);

    $discountAmount = $discount->calculateDiscount(100000);

    expect($discountAmount)->toBe(5000.0);
});

it('respects max discount limit', function () {
    $discount = Discount::factory()->percentage()->create([
        'value' => 50,
        'max_discount' => 10000,
    ]);

    $discountAmount = $discount->calculateDiscount(100000);

    expect($discountAmount)->toBe(10000.0);
});

it('checks if discount is valid', function () {
    $validDiscount = Discount::factory()->create([
        'start_date' => now()->subDays(7),
        'end_date' => now()->addDays(7),
        'is_active' => true,
    ]);

    $expiredDiscount = Discount::factory()->expired()->create();

    $upcomingDiscount = Discount::factory()->upcoming()->create();

    $inactiveDiscount = Discount::factory()->inactive()->create();

    expect($validDiscount->isValid())->toBeTrue()
        ->and($expiredDiscount->isValid())->toBeFalse()
        ->and($upcomingDiscount->isValid())->toBeFalse()
        ->and($inactiveDiscount->isValid())->toBeFalse();
});

it('checks usage limit', function () {
    $discountWithoutLimit = Discount::factory()->create([
        'usage_limit' => null,
        'used_count' => 100,
    ]);

    $discountWithLimit = Discount::factory()->withLimit(10)->create([
        'used_count' => 10,
    ]);

    $discountBelowLimit = Discount::factory()->withLimit(10)->create([
        'used_count' => 5,
    ]);

    expect($discountWithoutLimit->hasReachedLimit())->toBeFalse()
        ->and($discountWithLimit->hasReachedLimit())->toBeTrue()
        ->and($discountBelowLimit->hasReachedLimit())->toBeFalse();
});

it('validates voucher correctly', function () {
    $voucher = Discount::factory()->voucher()->create([
        'code' => 'TEST123',
        'min_purchase' => 50000,
    ]);

    $validVoucher = $this->discountService->validateVoucher('TEST123', 60000);
    expect($validVoucher)->not->toBeNull()
        ->and($validVoucher->code)->toBe('TEST123');

    $invalidCode = $this->discountService->validateVoucher('INVALID', 60000);
    expect($invalidCode)->toBeNull();

    $belowMinPurchase = $this->discountService->validateVoucher('TEST123', 40000);
    expect($belowMinPurchase)->toBeNull();
});

it('calculates product discount', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'selling_price' => 100000,
    ]);

    $discount = Discount::factory()->percentage()->productTarget()->create([
        'value' => 10,
    ]);
    $discount->products()->attach($product->id);

    $result = $this->discountService->calculateProductDiscount($product);

    expect((float) $result['original_price'])->toBe(100000.0)
        ->and((float) $result['discount_amount'])->toBe(10000.0)
        ->and((float) $result['final_price'])->toBe(90000.0)
        ->and($result['discount'])->not->toBeNull();
});

it('calculates category discount', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'selling_price' => 100000,
    ]);

    $discount = Discount::factory()->percentage()->categoryTarget()->create([
        'value' => 15,
    ]);
    $discount->categories()->attach($category->id);

    $result = $this->discountService->calculateProductDiscount($product);

    expect($result['discount_amount'])->toBe(15000.0)
        ->and($result['final_price'])->toBe(85000.0);
});

it('calculates global discount', function () {
    Discount::factory()->percentage()->globalTarget()->create([
        'value' => 5,
    ]);

    $result = $this->discountService->calculateTransactionDiscount(100000);

    expect($result['global_discount_amount'])->toBe(5000.0)
        ->and($result['total_discount'])->toBe(5000.0)
        ->and($result['final_total'])->toBe(95000.0);
});

it('calculates voucher discount on transaction', function () {
    Discount::factory()->voucher()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
    ]);

    $result = $this->discountService->calculateTransactionDiscount(100000, 'SAVE10');

    expect($result['voucher_discount_amount'])->toBe(10000.0)
        ->and($result['voucher_discount'])->not->toBeNull();
});

it('selects best discount for product', function () {
    $product = Product::factory()->create([
        'selling_price' => 100000,
    ]);

    $smallerDiscount = Discount::factory()->percentage()->productTarget()->create([
        'name' => 'Small Discount',
        'value' => 5,
    ]);
    $smallerDiscount->products()->attach($product->id);

    $biggerDiscount = Discount::factory()->percentage()->productTarget()->create([
        'name' => 'Big Discount',
        'value' => 20,
    ]);
    $biggerDiscount->products()->attach($product->id);

    $result = $this->discountService->calculateProductDiscount($product);

    expect($result['discount']->name)->toBe('Big Discount')
        ->and($result['discount_amount'])->toBe(20000.0);
});

it('stacks global and voucher discounts', function () {
    Discount::factory()->percentage()->globalTarget()->create([
        'value' => 5,
    ]);

    Discount::factory()->voucher()->create([
        'code' => 'EXTRA10',
        'type' => 'percentage',
        'value' => 10,
    ]);

    $result = $this->discountService->calculateTransactionDiscount(100000, 'EXTRA10');

    expect($result['global_discount_amount'])->toBe(5000.0)
        ->and($result['voucher_discount_amount'])->toBe(9500.0)
        ->and($result['total_discount'])->toBe(14500.0)
        ->and($result['final_total'])->toBe(85500.0);
});

it('increments discount usage', function () {
    $discount = Discount::factory()->create([
        'used_count' => 0,
    ]);

    $this->discountService->incrementUsage($discount);

    expect($discount->fresh()->used_count)->toBe(1);
});

it('scope active returns only active discounts', function () {
    Discount::factory()->count(3)->create(['is_active' => true]);
    Discount::factory()->count(2)->inactive()->create();

    $activeDiscounts = Discount::active()->get();

    expect($activeDiscounts)->toHaveCount(3);
});

it('scope valid returns only currently valid discounts', function () {
    Discount::factory()->create([
        'is_active' => true,
        'start_date' => now()->subDays(7),
        'end_date' => now()->addDays(7),
    ]);

    Discount::factory()->expired()->create();
    Discount::factory()->upcoming()->create();
    Discount::factory()->inactive()->create();

    $validDiscounts = Discount::valid()->get();

    expect($validDiscounts)->toHaveCount(1);
});
