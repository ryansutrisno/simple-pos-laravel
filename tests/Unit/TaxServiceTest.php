<?php

use App\Services\TaxService;
use App\Models\Store;

beforeEach(function () {
    $this->taxService = new TaxService();
});

it('calculates tax correctly', function () {
    $result = $this->taxService->calculateTax(100000, 10);
    
    expect($result)->toBe(10000.0);
});

it('returns zero for zero subtotal', function () {
    $result = $this->taxService->calculateTax(0, 10);
    
    expect($result)->toBe(0.0);
});

it('returns zero for zero tax rate', function () {
    $result = $this->taxService->calculateTax(100000, 0);
    
    expect($result)->toBe(0.0);
});

it('returns zero for negative subtotal', function () {
    $result = $this->taxService->calculateTax(-100000, 10);
    
    expect($result)->toBe(0.0);
});

it('calculates total with tax correctly', function () {
    $result = $this->taxService->calculateTotalWithTax(100000, 10);
    
    expect($result)->toBe(110000.0);
});

it('rounds tax calculation correctly', function () {
    // 33333.33 * 0.10 = 3333.333
    $result = $this->taxService->calculateTax(33333.33, 10);
    
    expect($result)->toBe(3333.33);
});

it('rounds total correctly', function () {
    $result = $this->taxService->calculateTotalWithTax(33333.33, 10);
    
    expect($result)->toBe(36666.66);
});

it('returns correct tax data when tax is enabled', function () {
    // Mock store object
    $store = new Store([
        'tax_enabled' => true,
        'tax_rate' => 10,
        'tax_name' => 'PPN',
    ]);
    
    $result = $this->taxService->getTaxData($store, 100000);
    
    expect($result['enabled'])->toBe(true);
    expect($result['name'])->toBe('PPN');
    expect($result['rate'])->toBe(10.0);
    expect($result['subtotal_before_tax'])->toBe(100000.0);
    expect($result['tax_amount'])->toBe(10000.0);
    expect($result['total'])->toBe(110000.0);
});

it('returns correct tax data when tax is disabled', function () {
    // Mock store object
    $store = new Store([
        'tax_enabled' => false,
        'tax_rate' => 10,
        'tax_name' => 'PPN',
    ]);
    
    $result = $this->taxService->getTaxData($store, 100000);
    
    expect($result['enabled'])->toBe(false);
    expect($result['rate'])->toBe(0);
    expect($result['subtotal_before_tax'])->toBe(100000.0);
    expect($result['tax_amount'])->toEqual(0.0);
    expect($result['total'])->toEqual(100000.0);
});

it('calculates cart tax correctly', function () {
    $items = [
        ['selling_price' => 10000, 'quantity' => 2],
        ['selling_price' => 5000, 'quantity' => 3],
    ];
    
    $result = $this->taxService->calculateCartTax($items, 10);
    
    expect($result['subtotal'])->toBe(35000); // 20000 + 15000
    expect($result['tax_amount'])->toBe(3500.0);
    expect($result['total'])->toEqual(38500.0);
});

it('returns zero for empty cart', function () {
    $result = $this->taxService->calculateCartTax([], 10);
    
    expect($result['subtotal'])->toBe(0);
    expect($result['tax_amount'])->toEqual(0.0);
    expect($result['total'])->toEqual(0.0);
});
