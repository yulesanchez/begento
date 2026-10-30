<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('api')->prefix('api/v1')->group(function (): void {
        Route::get('_bad-request', fn () => throw new BadRequestHttpException('Cabecera inválida.'));
        Route::get('_server-error', fn () => throw new RuntimeException('Fallo simulado.'));
    });
});

dataset('errores', [
    'bad request' => [400, 'BAD_REQUEST', 'Solicitud incorrecta.'],
    'method not allowed' => [405, 'METHOD_NOT_ALLOWED', 'Método no permitido para esta ruta.'],
]);

test('devuelve el sobre JSON uniforme para los errores controlados', function (int $status, string $code, string $message) {
    $response = $status === 400
        ? $this->getJson('/api/v1/_bad-request')
        : $this->patchJson('/api/v1/products');

    $response->assertStatus($status)
        ->assertJsonPath('message', $message)
        ->assertJsonPath('error', $code)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['message', 'error', 'data']);
})->with('errores');

test('el 405 conserva la cabecera Allow', function () {
    $this->patchJson('/api/v1/products')->assertStatus(405)->assertHeader('Allow');
});

test('el 404 de ruta inexistente usa un mensaje genérico', function () {
    $this->getJson('/api/v1/no-existe')
        ->assertNotFound()
        ->assertJsonPath('message', 'Recurso no encontrado.')
        ->assertJsonPath('error', 'NOT_FOUND')
        ->assertJsonPath('data', null);
});

test('el 422 mantiene los errores por campo y añade el código', function () {
    $this->postJson('/api/v1/products', [])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Los datos enviados no son válidos.')
        ->assertJsonPath('error', 'VALIDATION_ERROR')
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['message', 'error', 'data', 'errors' => ['name', 'description', 'price', 'currency_id']]);
});

test('el 500 responde con un sobre genérico sin filtrar la traza', function () {
    $this->getJson('/api/v1/_server-error')
        ->assertStatus(500)
        ->assertJsonPath('message', 'Error interno del servidor.')
        ->assertJsonPath('error', 'SERVER_ERROR')
        ->assertJsonPath('data', null)
        ->assertJsonMissingPath('trace');
});

test('el 415 usa el sobre JSON uniforme', function () {
    $this->call('POST', '/api/v1/products', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'name=Laptop')
        ->assertStatus(415)
        ->assertJsonPath('error', 'UNSUPPORTED_MEDIA_TYPE')
        ->assertJsonPath('data', null);
});

test('deja pasar las escrituras sin cabecera Content-Type', function () {
    $this->call('POST', '/api/v1/products', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => ''])
        ->assertStatus(422)
        ->assertJsonPath('error', 'VALIDATION_ERROR');
});

test('el 409 usa el sobre JSON uniforme', function () {
    $product = Product::factory()->create();
    ProductPrice::factory()->convertedFrom($product, Currency::factory()->eur()->create())->create();

    $this->putJson("/api/v1/products/{$product->id}", [
        'name' => $product->name,
        'description' => $product->description,
        'price' => (float) $product->price,
        'currency_id' => CurrencyFactory::resolveId('COP'),
    ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'No se puede cambiar la divisa base.')
        ->assertJsonPath('error', 'CONFLICT')
        ->assertJsonPath('data', null);
});
