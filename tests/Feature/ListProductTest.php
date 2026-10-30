<?php

use App\Models\Product;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('lista los productos de forma paginada con su divisa', function () {
    Product::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/products?per_page=2');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'price', 'currency' => ['id', 'name', 'symbol']]],
            'links',
            'meta',
        ]);
});

test('devuelve data vacío cuando no hay productos', function () {
    $response = $this->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0);
});

test('filtra por término de búsqueda', function () {
    Product::factory()->create(['name' => 'Laptop Pro 14']);
    Product::factory()->create(['name' => 'Monitor 27']);

    $response = $this->getJson('/api/v1/products?search=Laptop');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Laptop Pro 14');
});

test('ordena por defecto con los más recientes primero', function () {
    $old = Product::factory()->create(['created_at' => now()->subDay()]);
    $new = Product::factory()->create(['created_at' => now()]);

    $response = $this->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $new->id)
        ->assertJsonPath('data.1.id', $old->id);
});

test('acepta ordenamiento por precio descendente', function () {
    Product::factory()->create(['price' => 100, 'manufacturing_cost' => 10, 'tax_cost' => 5]);
    Product::factory()->create(['price' => 900, 'manufacturing_cost' => 10, 'tax_cost' => 5]);

    $response = $this->getJson('/api/v1/products?sort=price&direction=desc');

    $response->assertOk()->assertJsonPath('data.0.price', '900.00');
});

test('acepta el ordenamiento por nombre descendente en el límite de per_page', function () {
    Product::factory()->create(['name' => 'Alfa']);
    Product::factory()->create(['name' => 'Zulu']);

    $response = $this->getJson('/api/v1/products?page=1&per_page=100&sort=name&direction=desc');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Zulu')
        ->assertJsonPath('data.1.name', 'Alfa')
        ->assertJsonPath('meta.per_page', 100);
});

test('ordena por nombre ascendente con direction=asc', function () {
    Product::factory()->create(['name' => 'Alfa']);
    Product::factory()->create(['name' => 'Zulu']);

    $response = $this->getJson('/api/v1/products?sort=name&direction=asc');

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Alfa')
        ->assertJsonPath('data.1.name', 'Zulu');
});

test('devuelve una página vacía cuando la página está fuera de rango', function () {
    Product::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/products?page=99');

    $response->assertOk()->assertJsonPath('data', []);
});

test('rechaza un per_page fuera de rango', function () {
    $response = $this->getJson('/api/v1/products?per_page=500');

    $response->assertUnprocessable()->assertJsonValidationErrors(['per_page']);
});

test('rechaza un criterio de orden no soportado', function () {
    $response = $this->getJson('/api/v1/products?sort=password');

    $response->assertUnprocessable()->assertJsonValidationErrors(['sort']);
});

test('no genera consultas N+1 al listar con divisa', function () {
    Product::factory()->count(5)->create(['currency_id' => CurrencyFactory::resolveId('USD')]);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->getJson('/api/v1/products')->assertOk();

    expect($queries)->toBeLessThanOrEqual(3);
});
