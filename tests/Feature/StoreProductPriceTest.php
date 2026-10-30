<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registra un precio con monto explícito y devuelve 201 con Location', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);
    $eur = Currency::factory()->eur()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", [
        'currency_id' => $eur->id,
        'price' => 1380.00,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.currency_id', $eur->id)
        ->assertJsonPath('data.price', '1380.00')
        ->assertJsonPath('data.rate_used', $eur->exchange_rate)
        ->assertHeader('Location');

    expect($response->headers->get('Location'))
        ->toContain("/api/v1/products/{$product->id}/prices/");

    expect(ProductPrice::where('product_id', $product->id)->where('currency_id', $eur->id)->exists())->toBeTrue();
});

test('calcula el precio con la tasa de cambio cuando no se envía precio', function () {
    $product = Product::factory()->create([
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'price' => 1500.00,
    ]);
    $eur = Currency::factory()->eur()->create();
    $expected = number_format(round(1500 * (float) $eur->exchange_rate, 2, PHP_ROUND_HALF_UP), 2, '.', '');

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", [
        'currency_id' => $eur->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.price', $expected)
        ->assertJsonPath('data.rate_used', $eur->exchange_rate);
});

test('rechaza una divisa duplicada para el mismo producto', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);
    $eur = Currency::factory()->eur()->create();
    ProductPrice::factory()->forProduct($product)->inCurrency($eur)->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", ['currency_id' => $eur->id]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['currency_id']);
    expect(ProductPrice::where('product_id', $product->id)->count())->toBe(1);
});

test('rechaza una divisa inexistente', function () {
    $product = Product::factory()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", ['currency_id' => 999]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['currency_id']);
});

test('responde 404 cuando el producto no existe', function () {
    $eur = Currency::factory()->eur()->create();

    $response = $this->postJson('/api/v1/products/9999/prices', ['currency_id' => $eur->id]);

    $response->assertNotFound();
});

test('rechaza un precio en divisa base distinto al del producto', function () {
    $product = Product::factory()->create([
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'price' => 1500.00,
    ]);

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", [
        'currency_id' => $product->currency_id,
        'price' => 1200.00,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['price']);
    expect($response->json('errors.price.0'))->toContain('coincidir');
});

test('sincroniza el precio del producto cuando la divisa es la base', function () {
    $product = Product::factory()->create([
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'price' => 1500.00,
    ]);

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", [
        'currency_id' => $product->currency_id,
        'price' => 1500.00,
    ]);

    $response->assertCreated()->assertJsonPath('data.price', '1500.00');
});

test('rechaza un precio no numérico', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", [
        'currency_id' => $eur->id,
        'price' => 'abc',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['price']);
});

test('rechaza una divisa sin tasa de cambio y sin precio', function () {
    $product = Product::factory()->create(['currency_id' => CurrencyFactory::resolveId('USD')]);
    $currency = Currency::create(['name' => 'XXX', 'symbol' => 'X', 'exchange_rate' => 0]);

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", [
        'currency_id' => $currency->id,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['price']);
    expect($response->json('errors.price.0'))->toContain('tasa de cambio');
});

test('redondea a dos decimales con HALF_UP', function () {
    $product = Product::factory()->create([
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'price' => 1500.00,
    ]);
    $currency = Currency::create(['name' => 'TST', 'symbol' => 'T', 'exchange_rate' => 0.920304]);

    $response = $this->postJson("/api/v1/products/{$product->id}/prices", ['currency_id' => $currency->id]);

    $response->assertCreated()->assertJsonPath('data.price', '1380.46');
});
