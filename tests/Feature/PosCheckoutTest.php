<?php

use App\Models\Category;
use App\Models\FinancialRecord;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can add product to cart', function () {
    $product = Product::factory()->create();

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->assertSet('cart', function ($cart) use ($product) {
            return count($cart) === 1 && $cart[0]['product_id'] === $product->id;
        });
});

it('can update cart item quantity', function () {
    $product = Product::factory()->create();

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->call('updateQuantity', 0, 'increase')
        ->assertSet('cart.0.quantity', 2);
});

it('can remove item from cart', function () {
    $product = Product::factory()->create();

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->call('removeFromCart', 0)
        ->assertSet('cart', []);
});

it('cannot checkout with empty cart', function () {
    Livewire::test(\App\Livewire\Pos::class)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100000)
        ->call('checkout')
        ->assertNotDispatched('transaction-completed');
});

it('cannot checkout cash payment with insufficient amount', function () {
    $product = Product::factory()->create(['selling_price' => 50000]);

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 30000)
        ->call('checkout')
        ->assertNotDispatched('transaction-completed');
});

it('can checkout successfully with cash payment', function () {
    $product = Product::factory()->create([
        'selling_price' => 50000,
        'purchase_price' => 30000,
        'stock' => 10,
    ]);

    $initialStock = $product->stock;

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100000)
        ->call('checkout')
        ->assertDispatched('transaction-completed');

    $transaction = Transaction::latest()->first();
    expect((float) $transaction->total)->toBe(50000.0);
    expect($transaction->payment_method)->toBe('cash');
    expect((float) $transaction->cash_amount)->toBe(100000.0);
    expect((float) $transaction->change_amount)->toBe(50000.0);

    $product->refresh();
    expect($product->stock)->toBe($initialStock - 1);

    $financialRecord = FinancialRecord::where('transaction_id', $transaction->id)->first();
    expect($financialRecord)->not->toBeNull();
    expect($financialRecord->type)->toBe('sales');
    expect((float) $financialRecord->profit)->toBe(20000.0);
});

it('can checkout successfully with non-cash payment', function () {
    $product = Product::factory()->create([
        'selling_price' => 50000,
        'purchase_price' => 30000,
        'stock' => 10,
    ]);

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'qris')
        ->call('checkout')
        ->assertDispatched('transaction-completed');

    $transaction = Transaction::latest()->first();
    expect($transaction->payment_method)->toBe('qris');
    expect($transaction->cash_amount)->toBeNull();
});

it('calculates correct change for cash payment', function () {
    $product = Product::factory()->create(['selling_price' => 25000]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100000);

    expect((float) $component->get('change'))->toBe(50000.0);
});

it('can search products by name', function () {
    $product1 = Product::factory()->create(['name' => 'Kopi Susu']);
    $product2 = Product::factory()->create(['name' => 'Teh Manis']);

    Livewire::test(\App\Livewire\Pos::class)
        ->set('searchQuery', 'Kopi')
        ->assertSee('Kopi Susu')
        ->assertDontSee('Teh Manis');
});

it('can filter products by category', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();

    $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']);
    $product2 = Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']);

    Livewire::test(\App\Livewire\Pos::class)
        ->call('filterProductsByCategory', $category1->id)
        ->assertSee('Product 1')
        ->assertDontSee('Product 2');
});

it('only shows active products', function () {
    $activeProduct = Product::factory()->create(['name' => 'Active Product', 'is_active' => true]);
    $inactiveProduct = Product::factory()->inactive()->create(['name' => 'Inactive Product']);

    Livewire::test(\App\Livewire\Pos::class)
        ->assertSee('Active Product')
        ->assertDontSee('Inactive Product');
});

it('resets cart after successful checkout', function () {
    $product = Product::factory()->create();

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100000)
        ->call('checkout')
        ->assertSet('cart', [])
        ->assertSet('cashAmount', 0);
});
