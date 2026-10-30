<?php

use App\Models\Product;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Laptop Pro 14',
        'description' => 'Portátil 16GB',
        'price' => 1500.00,
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'tax_cost' => 180.00,
        'manufacturing_cost' => 900.00,
    ], $overrides);
}

test('crea un producto válido y devuelve 201 con Location y data', function () {
    $payload = validPayload();

    $response = $this->postJson('/api/v1/products', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Laptop Pro 14')
        ->assertJsonPath('data.currency.id', $payload['currency_id'])
        ->assertHeader('Location');

    expect($response->headers->get('Location'))->toContain('/api/v1/products/');
    expect(Product::where('name', 'Laptop Pro 14')->exists())->toBeTrue();
});

test('normaliza los montos a dos decimales', function () {
    $response = $this->postJson('/api/v1/products', validPayload(['price' => 1500.5]));

    $response->assertCreated()->assertJsonPath('data.price', '1500.50');
});

test('rechaza un cuerpo vacío con 422 y no crea registros', function () {
    $response = $this->postJson('/api/v1/products', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'price', 'currency_id']);

    expect(Product::count())->toBe(0);
});

test('rechaza un precio negativo', function () {
    $response = $this->postJson('/api/v1/products', validPayload(['price' => -10]));

    $response->assertUnprocessable()->assertJsonValidationErrors(['price']);
});

test('rechaza una divisa inexistente', function () {
    $response = $this->postJson('/api/v1/products', validPayload(['currency_id' => 999]));

    $response->assertUnprocessable()->assertJsonValidationErrors(['currency_id']);
});

test('rechaza un nombre duplicado', function () {
    Product::factory()->create(['name' => 'Laptop Pro 14']);

    $response = $this->postJson('/api/v1/products', validPayload());

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

test('rechaza un costo de fabricación mayor o igual al precio', function () {
    $mayor = $this->postJson('/api/v1/products', validPayload(['price' => 100, 'manufacturing_cost' => 200]));
    $mayor->assertUnprocessable()->assertJsonValidationErrors(['manufacturing_cost']);

    $igual = $this->postJson('/api/v1/products', validPayload(['price' => 100, 'manufacturing_cost' => 100]));
    $igual->assertUnprocessable()->assertJsonValidationErrors(['manufacturing_cost']);
});

test('rechaza un impuesto mayor que el precio', function () {
    $response = $this->postJson('/api/v1/products', validPayload(['price' => 100, 'tax_cost' => 200]));

    $response->assertUnprocessable()->assertJsonValidationErrors(['tax_cost']);
});

test('responde 415 cuando el Content-Type no es soportado', function () {
    $response = $this->call(
        'POST',
        '/api/v1/products',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        'name=Laptop'
    );

    $response->assertStatus(415)->assertJsonStructure(['message']);
    expect(Product::count())->toBe(0);
});

test('rechaza campos no declarados en el payload', function () {
    $response = $this->postJson('/api/v1/products', validPayload([
        'id' => 999,
        'created_at' => '2000-01-01',
        'nameXXX' => 'Laptop Pro 14',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payload'])
        ->assertJsonPath('errors.payload.0', 'El cuerpo de la petición contiene campos no permitidos: id, created_at, nameXXX.');

    expect(Product::count())->toBe(0);
});
