<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        if (! Currency::query()->exists()) {
            $this->call(CurrencySeeder::class);
        }

        $usd = Currency::query()->where('name', 'USD')->firstOrFail();

        $quoteCurrencies = Currency::query()
            ->where('name', '!=', 'USD')
            ->orderBy('name')
            ->get();

        $products = Product::factory()
            ->count(20)
            ->inCurrency($usd)
            ->create();

        foreach ($products as $product) {
            foreach ($quoteCurrencies as $currency) {
                ProductPrice::factory()
                    ->convertedFrom($product, $currency)
                    ->create();
            }
        }
    }
}
