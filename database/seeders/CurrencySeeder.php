<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $currencies = [
            ['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.000000],
            ['name' => 'EUR', 'symbol' => '€', 'exchange_rate' => 0.89],
            ['name' => 'COP', 'symbol' => '$', 'exchange_rate' => 0.0002952],
            ['name' => 'MXN', 'symbol' => '$', 'exchange_rate' => 0.0504],
            ['name' => 'CNY', 'symbol' => '¥', 'exchange_rate' => 0.1403],
        ];

        Currency::upsert(
            array_map(fn (array $currency): array => $currency + ['created_at' => $now, 'updated_at' => $now], $currencies),
            ['name'],
            ['symbol', 'exchange_rate', 'updated_at'],
        );
    }
}
