<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductPrice> */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'currency_id' => CurrencyFactory::resolveId('USD'),
            'price' => fake()->randomFloat(2, 1, 10000),
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (): array => [
            'product_id' => $product->id,
        ]);
    }

    public function inCurrency(Currency $currency): static
    {
        return $this->state(fn (): array => [
            'currency_id' => $currency->id,
        ]);
    }

    /**
     * Precio convertido desde el precio base del producto (USD) usando la tasa de la divisa.
     * `exchange_rate` = unidades de la divisa por 1 USD, por lo que el precio se multiplica.
     */
    public function convertedFrom(Product $product, Currency $currency): static
    {
        return $this->state(fn (): array => [
            'product_id' => $product->id,
            'currency_id' => $currency->id,
            'price' => round((float) $product->price * (float) $currency->exchange_rate, 2),
            'rate_used' => $currency->exchange_rate,
        ]);
    }
}
