<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('devuelve el producto con su divisa', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.name', $product->name)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'description', 'price', 'tax_cost', 'manufacturing_cost', 'currency' => ['id', 'name', 'symbol', 'exchange_rate'], 'created_at', 'updated_at'],
        ]);
});

test('responde 404 cuando el producto no existe', function () {
    $response = $this->getJson('/api/v1/products/9999');

    $response->assertNotFound()->assertJsonPath('message', 'Producto no encontrado.');
});

test('responde 404 con identificador no numérico', function () {
    $this->getJson('/api/v1/products/abc')->assertNotFound();
});

test('no genera consultas N+1 al consultar el producto', function () {
    $product = Product::factory()->create();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->getJson("/api/v1/products/{$product->id}")->assertOk();

    expect($queries)->toBeLessThanOrEqual(2);
});
