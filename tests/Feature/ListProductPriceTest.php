<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('lista los precios del producto con su divisa y la divisa base', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);
    $eur = Currency::factory()->eur()->create();
    ProductPrice::factory()->convertedFrom($product, $eur)->create();

    $response = $this->getJson("/api/v1/products/{$product->id}/prices");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.currency_id', $eur->id)
        ->assertJsonPath('meta.base_currency_id', $product->currency_id)
        ->assertJsonStructure([
            'data' => [['id', 'product_id', 'currency_id', 'price', 'rate_used', 'currency' => ['id', 'name', 'symbol', 'exchange_rate']]],
        ]);
});

test('devuelve data vacío y la divisa base cuando el producto no tiene precios', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);

    $response = $this->getJson("/api/v1/products/{$product->id}/prices");

    $response->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.base_currency_id', $product->currency_id);
});

test('ordena por precio descendente con sort=-price', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);
    $eur = Currency::factory()->eur()->create();
    $cop = Currency::factory()->cop()->create();

    ProductPrice::factory()->forProduct($product)->inCurrency($eur)->create(['price' => 100]);
    ProductPrice::factory()->forProduct($product)->inCurrency($cop)->create(['price' => 900]);

    $response = $this->getJson("/api/v1/products/{$product->id}/prices?sort=-price");

    $response->assertOk()
        ->assertJsonPath('data.0.price', '900.00')
        ->assertJsonPath('data.1.price', '100.00');
});

test('rechaza un per_page fuera de rango', function () {
    $product = Product::factory()->create();

    $this->getJson("/api/v1/products/{$product->id}/prices?per_page=0")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

test('responde 404 cuando el producto no existe', function () {
    $this->getJson('/api/v1/products/9999/prices')->assertNotFound();
});

test('no genera consultas N+1 al listar precios', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);

    foreach (['EUR', 'COP', 'MXN'] as $code) {
        ProductPrice::factory()->forProduct($product)->create([
            'currency_id' => CurrencyFactory::resolveId($code),
        ]);
    }

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->getJson("/api/v1/products/{$product->id}/prices")->assertOk();

    expect($queries)->toBeLessThanOrEqual(4);
});
