<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('la divisa relaciona los productos que la usan como precio base', function () {
    $currency = Currency::factory()->eur()->create();
    $product = Product::factory()->create(['currency_id' => $currency->id]);
    Product::factory()->create();

    expect($currency->products()->pluck('id')->all())->toBe([$product->id]);
});

test('la divisa relaciona los precios alternativos registrados', function () {
    $currency = Currency::factory()->eur()->create();
    $price = ProductPrice::factory()->create(['currency_id' => $currency->id]);
    ProductPrice::factory()->create();

    expect($currency->productPrices()->pluck('id')->all())->toBe([$price->id]);
});

test('el precio alternativo resuelve su divisa', function () {
    $price = ProductPrice::factory()->create();

    expect($price->currency)->toBeInstanceOf(Currency::class)
        ->and($price->currency->id)->toBe($price->currency_id);
});

test('el precio alternativo resuelve su producto', function () {
    $price = ProductPrice::factory()->create();

    expect($price->product)->toBeInstanceOf(Product::class)
        ->and($price->product->id)->toBe($price->product_id);
});
