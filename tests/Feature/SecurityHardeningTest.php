<?php

use App\Models\Product;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function securityPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Laptop Pro 14',
        'description' => 'Portatil 16GB',
        'price' => 1500.00,
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'tax_cost' => 180.00,
        'manufacturing_cost' => 900.00,
    ], $overrides);
}

function assertProductsTableIntact(int $expectedRows): void
{
    expect(Schema::hasTable('products'))->toBeTrue()
        ->and(DB::table('products')->count())->toBe($expectedRows);
}

test('neutraliza la inyeccion SQL en el parametro de busqueda', function (string $payload) {
    Product::factory()->count(3)->create();

    $this->getJson('/api/v1/products?'.http_build_query(['search' => $payload]))
        ->assertOk()
        ->assertJsonPath('meta.total', 0);

    assertProductsTableIntact(3);
})->with([
    "' OR 1=1 --",
    "'; DROP TABLE products; --",
    '" OR ""="',
    '1; DELETE FROM products',
    "admin'--",
    "' UNION SELECT id, name FROM products --",
]);

test('rechaza la inyeccion SQL en los parametros de orden y paginacion', function (string $query) {
    Product::factory()->count(2)->create();

    $this->getJson('/api/v1/products?'.$query)
        ->assertUnprocessable()
        ->assertJsonPath('error', 'VALIDATION_ERROR');

    assertProductsTableIntact(2);
})->with([
    'sort=price;DROP TABLE products',
    'sort=(SELECT 1)',
    'sort=price%20desc',
    'direction=asc,(SELECT 1)',
    'direction=ASC',
    'page=-1',
    'per_page=0',
]);

test('rechaza la inyeccion SQL en el orden de los precios', function (string $query) {
    $product = Product::factory()->create();

    $this->getJson("/api/v1/products/{$product->id}/prices?{$query}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
})->with([
    'sort=id;DROP TABLE product_prices',
    'sort=(SELECT 1)',
    'sort=price--',
]);

test('responde 404 ante identificadores de ruta manipulados', function (string $path) {
    Product::factory()->create();

    $this->getJson($path)->assertNotFound();

    assertProductsTableIntact(1);
})->with([
    '/api/v1/products/1%20OR%201=1',
    "/api/v1/products/1'%20OR%20'1'='1",
    '/api/v1/products/999999999999',
    '/api/v1/products/1;DROP%20TABLE%20products',
]);

test('almacena literalmente el texto con carga SQL sin ejecutarla', function () {
    $name = "Laptop'; DROP TABLE products; --";
    $description = "Descripcion'); DELETE FROM products; --";

    $this->postJson('/api/v1/products', securityPayload([
        'name' => $name,
        'description' => $description,
    ]))
        ->assertCreated()
        ->assertJsonPath('data.name', $name)
        ->assertJsonPath('data.description', $description);

    assertProductsTableIntact(1);
    expect(Product::query()->where('name', $name)->count())->toBe(1);
});

test('responde 422 y no 500 cuando el cuerpo JSON esta malformado', function () {
    $this->call(
        'POST',
        '/api/v1/products',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        '{"name":'
    )
        ->assertStatus(422)
        ->assertJsonPath('error', 'VALIDATION_ERROR');
});

test('respeta los limites de longitud del nombre', function () {
    $this->postJson('/api/v1/products', securityPayload(['name' => str_repeat('A', 120)]))
        ->assertCreated();

    $this->postJson('/api/v1/products', securityPayload([
        'name' => str_repeat('B', 121),
        'price' => 100,
        'tax_cost' => 10,
        'manufacturing_cost' => 50,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
