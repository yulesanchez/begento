<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $manufacturingCost = fake()->randomFloat(2, 10, 800);
        $taxCost = fake()->randomFloat(2, 1, 100);

        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(12),
            'price' => round($manufacturingCost + $taxCost + fake()->randomFloat(2, 10, 500), 2),
            'currency_id' => CurrencyFactory::resolveId('USD'),
            'tax_cost' => $taxCost,
            'manufacturing_cost' => $manufacturingCost,
        ];
    }

    public function inCurrency(Currency $currency): static
    {
        return $this->state(fn (): array => ['currency_id' => $currency->id]);
    }

    public function withPrices(int $count = 1): static
    {
        return $this->has(
            ProductPrice::factory()->count($count),
            'prices'
        );
    }
}
