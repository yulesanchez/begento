<?php

use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Currency;
use App\Models\Product;
use Database\Factories\CurrencyFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @param  class-string<FormRequest>  $requestClass
 * @param  array<string, mixed>  $payload
 */
function requestWithRouteProduct(string $requestClass, mixed $product, array $payload = []): FormRequest
{
    $request = new $requestClass($payload);
    $request->setContainer(app());

    $route = new Route(['POST'], 'api/v1/products/{product}', []);
    $route->parameters = ['product' => $product];

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}

/**
 * @param  array<int, callable>  $callbacks
 */
function runAfterCallbacks(array $callbacks, Validator $validator): Validator
{
    foreach ($callbacks as $callback) {
        $callback($validator);
    }

    return $validator;
}

test('omite la validacion del precio cuando el producto de la ruta no es numerico', function () {
    $request = requestWithRouteProduct(
        StoreProductPriceRequest::class,
        'no-numerico',
        ['currency_id' => CurrencyFactory::resolveId('USD'), 'price' => 10],
    );

    $validator = runAfterCallbacks(
        $request->after(),
        ValidatorFactory::make($request->all(), $request->rules()),
    );

    expect($validator->errors()->toArray())->toBe([]);
});

test('omite la validacion del precio cuando el producto de la ruta no existe', function () {
    $request = requestWithRouteProduct(
        StoreProductPriceRequest::class,
        999999,
        ['currency_id' => CurrencyFactory::resolveId('USD'), 'price' => 10],
    );

    $validator = runAfterCallbacks(
        $request->after(),
        ValidatorFactory::make($request->all(), $request->rules()),
    );

    expect($validator->errors()->toArray())->toBe([]);
});

test('exige un precio cuando la divisa no tiene tasa de cambio', function () {
    $product = Product::factory()->create();
    $currency = Currency::factory()->forCode('EUR')->create(['exchange_rate' => 0]);

    $request = requestWithRouteProduct(
        StoreProductPriceRequest::class,
        $product->id,
        ['currency_id' => $currency->id],
    );

    $validator = runAfterCallbacks(
        $request->after(),
        ValidatorFactory::make($request->all(), $request->rules()),
    );

    expect($validator->errors()->has('price'))->toBeTrue()
        ->and($validator->errors()->first('price'))
        ->toBe('Se requiere un precio o una tasa de cambio válida para la divisa.');
});

test('valida contra los costos enviados cuando el identificador de ruta no es numerico', function () {
    $request = requestWithRouteProduct(UpdateProductRequest::class, 'no-numerico', [
        'name' => 'Laptop Pro 14',
        'price' => 100,
        'currency_id' => CurrencyFactory::resolveId('USD'),
        'tax_cost' => 200,
        'manufacturing_cost' => 10,
    ]);

    $validator = runAfterCallbacks(
        $request->after(),
        ValidatorFactory::make($request->all(), $request->rules()),
    );

    expect($validator->errors()->has('tax_cost'))->toBeTrue()
        ->and($validator->errors()->has('manufacturing_cost'))->toBeFalse();
});
