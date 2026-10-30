<?php

use App\Models\Product;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function updatePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Laptop Pro 14 v2',
        'description' => 'Portátil 32GB',
        'price' => 1700.00,
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'tax_cost' => 200.00,
        'manufacturing_cost' => 950.00,
    ], $overrides);
}

test('actualiza un producto con datos válidos', function () {
    $product = Product::factory()->create(['name' => 'Laptop Pro 14']);

    $response = $this->putJson("/api/v1/products/{$product->id}", updatePayload());

    $response->assertSuccessful();
    expect($product->fresh()->name)->toBe('Laptop Pro 14 v2');
});

test('conservar el propio nombre no genera error de unicidad', function () {
    $product = Product::factory()->create(['name' => 'Laptop Pro 14']);

    $response = $this->putJson(
        "/api/v1/products/{$product->id}",
        updatePayload(['name' => 'Laptop Pro 14'])
    );

    $response->assertSuccessful();
});

test('rechaza el nombre usado por otro producto', function () {
    $product = Product::factory()->create(['name' => 'Laptop Pro 14']);
    Product::factory()->create(['name' => 'Monitor 27']);

    $response = $this->putJson(
        "/api/v1/products/{$product->id}",
        updatePayload(['name' => 'Monitor 27'])
    );

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

test('rechaza la actualización parcial', function () {
    $product = Product::factory()->create();

    $response = $this->putJson("/api/v1/products/{$product->id}", ['price' => 100]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'currency_id']);
});

test('responde 404 cuando el producto no existe', function () {
    $response = $this->putJson('/api/v1/products/9999', updatePayload());

    $response->assertNotFound();
});

test('rechaza montos con más de dos decimales o negativos', function () {
    $product = Product::factory()->create();

    $decimales = $this->putJson("/api/v1/products/{$product->id}", updatePayload(['price' => 1700.123]));
    $decimales->assertUnprocessable()->assertJsonValidationErrors(['price']);

    $negativo = $this->putJson("/api/v1/products/{$product->id}", updatePayload(['price' => -10]));
    $negativo->assertUnprocessable()->assertJsonValidationErrors(['price']);
});

test('rechaza un costo de fabricación mayor o igual al precio', function () {
    $product = Product::factory()->create();

    $response = $this->putJson("/api/v1/products/{$product->id}", updatePayload([
        'price' => 100,
        'manufacturing_cost' => 100,
        'tax_cost' => 10,
    ]));

    $response->assertUnprocessable()->assertJsonValidationErrors(['manufacturing_cost']);
});

test('valida contra el costo actual cuando el campo no se envía', function () {
    $product = Product::factory()->create(['manufacturing_cost' => 900, 'tax_cost' => 50]);

    $response = $this->putJson("/api/v1/products/{$product->id}", [
        'name' => 'Laptop Pro 14 v3',
        'price' => 800,
        'currency_id' => CurrencyFactory::resolveId('USD'),
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['manufacturing_cost']);
});

test('usa mensajes personalizados en los errores', function () {
    $product = Product::factory()->create();

    $response = $this->putJson("/api/v1/products/{$product->id}", []);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'El nombre del producto es obligatorio.')
        ->assertJsonPath('errors.price.0', 'El precio es obligatorio.')
        ->assertJsonPath('errors.currency_id.0', 'La divisa es obligatoria.');
});
