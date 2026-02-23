<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ShieldSeeder;

beforeEach(function () {
    $seeder = new ShieldSeeder;
    $seeder->run();
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

it('can list products', function () {
    $products = Product::factory()->count(3)->create();

    $response = $this->get('/admin/products');

    $response->assertStatus(200);
    foreach ($products as $product) {
        $response->assertSee($product->name);
    }
});

it('can create a product', function () {
    $category = Category::factory()->create();

    $product = Product::create([
        'name' => 'Kopi Susu',
        'category_id' => $category->id,
        'description' => 'Kopi susu gula aren',
        'purchase_price' => 10000,
        'selling_price' => 18000,
        'stock' => 50,
        'barcode' => '1234567890123',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Kopi Susu',
        'category_id' => $category->id,
    ]);
    expect($product->selling_price)->toBe('18000.00');
});

it('can update a product', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $product->update([
        'name' => 'Kopi Susu Update',
        'category_id' => $category->id,
        'purchase_price' => 12000,
        'selling_price' => 20000,
        'stock' => 100,
    ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Kopi Susu Update',
        'selling_price' => '20000.00',
    ]);
});

it('can soft delete a product', function () {
    $product = Product::factory()->create();

    $product->delete();

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

it('can restore a soft deleted product', function () {
    $product = Product::factory()->create();
    $product->delete();

    $product->restore();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'deleted_at' => null,
    ]);
});

it('product belongs to a category', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    expect($product->category->id)->toBe($category->id);
});

it('can filter products by category', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();

    $product1 = Product::factory()->create(['category_id' => $category1->id]);
    $product2 = Product::factory()->create(['category_id' => $category2->id]);

    $response = $this->get("/admin/products?tableFilters[category][value]={$category1->id}");

    $response->assertSee($product1->name);
    $response->assertDontSee($product2->name);
});

it('barcode must be unique', function () {
    Product::factory()->create(['barcode' => '1234567890']);

    $product = Product::create([
        'name' => 'Test Product',
        'category_id' => Category::factory()->create()->id,
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 10,
        'barcode' => '1234567890',
    ]);

    expect($product)->toBeNull();
})->throws(\Illuminate\Database\QueryException::class);
