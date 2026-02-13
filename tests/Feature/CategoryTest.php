<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can list categories', function () {
    $categories = Category::factory()->count(3)->create();

    $response = $this->get('/admin/categories');

    $response->assertStatus(200);
    foreach ($categories as $category) {
        $response->assertSee($category->name);
    }
});

it('can create a category', function () {
    $categoryData = [
        'name' => 'Makanan',
        'description' => 'Kategori makanan',
    ];

    $category = Category::create($categoryData);

    $this->assertDatabaseHas('categories', $categoryData);
    expect($category->name)->toBe('Makanan');
});

it('can update a category', function () {
    $category = Category::factory()->create();

    $category->update([
        'name' => 'Minuman',
        'description' => 'Kategori minuman',
    ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Minuman',
        'description' => 'Kategori minuman',
    ]);
});

it('can delete a category', function () {
    $category = Category::factory()->create();

    $category->delete();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('category has many products', function () {
    $category = Category::factory()->create();
    $products = Product::factory()->count(3)->create(['category_id' => $category->id]);

    expect($category->products)->toHaveCount(3);
});
