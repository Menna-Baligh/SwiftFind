<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

uses(TestCase::class, DatabaseMigrations::class);

test('can search products by name or description using fulltext search', function () {
    $categoryA = Category::factory()->create(['name' => 'Laptops']);
    $categoryB = Category::factory()->create(['name' => 'Phones']);

    $laptop = Product::factory()->create([
        'category_id' => $categoryA->id,
        'name'        => 'Gaming Laptop Pro',
        'description' => 'Sleek device for heavy usage',
    ]);

    $phone = Product::factory()->create([
        'category_id' => $categoryB->id,
        'name'        => 'Smart Phone Device',
        'description' => 'Compact mobile phone with laptop stand accessory',
    ]);

    $other = Product::factory()->create([
        'category_id' => $categoryB->id,
        'name'        => 'Office Chair',
        'description' => 'Ergonomic seat',
    ]);

    // نبعث كلمة واضحة مطابقة للـ FULLTEXT Index
    $response = $this->getJson('/api/products/search?q=Gaming');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $laptop->id])
        ->assertJsonMissing(['id' => $other->id]);
});

test('can filter products by price range', function () {
    $category = Category::factory()->create();

    $cheap     = Product::factory()->create(['price' => 500, 'category_id' => $category->id]);
    $mid       = Product::factory()->create(['price' => 1500, 'category_id' => $category->id]);
    $expensive = Product::factory()->create(['price' => 5000, 'category_id' => $category->id]);

    $response = $this->getJson('/api/products/search?min_price=1000&max_price=2000');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $mid->id])
        ->assertJsonMissing(['id' => $cheap->id])
        ->assertJsonMissing(['id' => $expensive->id]);
});

test('can filter products by category and stock status', function () {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    $inStockLaptop = Product::factory()->create([
        'category_id'    => $categoryA->id,
        'stock_quantity' => 10,
    ]);

    $outOfStockLaptop = Product::factory()->create([
        'category_id'    => $categoryA->id,
        'stock_quantity' => 0,
    ]);

    $inStockPhone = Product::factory()->create([
        'category_id'    => $categoryB->id,
        'stock_quantity' => 5,
    ]);

    $response = $this->getJson("/api/products/search?category_id={$categoryA->id}&in_stock=1");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $inStockLaptop->id])
        ->assertJsonMissing(['id' => $outOfStockLaptop->id])
        ->assertJsonMissing(['id' => $inStockPhone->id]);
});

test('can apply combined filters simultaneously', function () {
    $category = Category::factory()->create();

    $matchingProduct = Product::factory()->create([
        'name'           => 'Pro Wireless Headset',
        'description'    => 'High quality audio equipment',
        'price'          => 1200,
        'category_id'    => $category->id,
        'stock_quantity' => 15,
    ]);

    $nonMatchingProduct = Product::factory()->create([
        'name'           => 'Pro Wireless Headset',
        'description'    => 'High quality audio equipment',
        'price'          => 8000,
        'category_id'    => $category->id,
        'stock_quantity' => 15,
    ]);

    $response = $this->getJson("/api/products/search?q=Headset&min_price=1000&max_price=2000&category_id={$category->id}&in_stock=1");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $matchingProduct->id]);
});

test('can sort products by price ascending and descending', function () {
    $category = Category::factory()->create();

    $p1 = Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
    $p2 = Product::factory()->create(['price' => 500, 'category_id' => $category->id]);

    $ascResponse = $this->getJson('/api/products/search?sort_by=price&sort_order=asc');
    $ascResponse->assertOk();
    expect($ascResponse->json('data.0.id'))->toBe($p1->id);

    $descResponse = $this->getJson('/api/products/search?sort_by=price&sort_order=desc');
    $descResponse->assertOk();
    expect($descResponse->json('data.0.id'))->toBe($p2->id);
});

test('enforces per_page limit and validates maximum 50', function () {
    $category = Category::factory()->create();

    Product::factory()->count(60)->create(['category_id' => $category->id]);

    $validResponse = $this->getJson('/api/products/search?per_page=50');
    $validResponse->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.per_page', 50);

    $invalidResponse = $this->getJson('/api/products/search?per_page=100');
    $invalidResponse->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});

test('validates invalid query inputs properly', function () {
    $response = $this->getJson('/api/products/search?min_price=2000&max_price=1000&category_id=99999&sort_by=invalid_column');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['max_price', 'category_id', 'sort_by']);
});

test('returns empty array with 200 status when no products match filters', function () {
    $category = Category::factory()->create();

    Product::factory()->create(['name' => 'Simple Table', 'category_id' => $category->id]);

    $response = $this->getJson('/api/products/search?q=NonExistentKeyword');

    $response->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('returns all products when search term is empty', function () {
    $category = Category::factory()->create();

    Product::factory()->count(3)->create(['category_id' => $category->id]);

    $response = $this->getJson('/api/products/search');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});
