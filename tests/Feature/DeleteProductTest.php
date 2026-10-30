<?php

use App\Models\Product;
use App\Models\ProductPrice;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('elimina el producto y sus precios asociados', function () {
    $product = Product::factory()->create();

    ProductPrice::factory()->forProduct($product)->create([
        'currency_id' => CurrencyFactory::resolveId('EUR'),
    ]);
    ProductPrice::factory()->forProduct($product)->create([
        'currency_id' => CurrencyFactory::resolveId('COP'),
    ]);
    $response = $this->deleteJson("/api/v1/products/{$product->id}");

    $response->assertOk()->assertJsonPath('message', 'Producto eliminado.');
    expect(Product::find($product->id))->toBeNull()
        ->and(ProductPrice::where('product_id', $product->id)->count())->toBe(0);
});

test('responde 404 cuando el producto no existe', function () {
    $this->deleteJson('/api/v1/products/9999')->assertNotFound();
});

test('responde 404 en el segundo intento de eliminación', function () {
    $product = Product::factory()->create();

    $this->deleteJson("/api/v1/products/{$product->id}")->assertOk();
    $this->deleteJson("/api/v1/products/{$product->id}")->assertNotFound();
});
