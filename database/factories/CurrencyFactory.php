<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/** @extends Factory<Currency> */
class CurrencyFactory extends Factory
{
    /**
     * Divisas de demostración disponibles en las pruebas.
     *
     * @var array<string, array{name: string, symbol: string, exchange_rate: float}>
     */
    private const CATALOG = [
        'USD' => ['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.000000],
        'EUR' => ['name' => 'EUR', 'symbol' => '€', 'exchange_rate' => 0.890000],
        'COP' => ['name' => 'COP', 'symbol' => '$', 'exchange_rate' => 0.0002952],
        'MXN' => ['name' => 'MXN', 'symbol' => '$', 'exchange_rate' => 0.050400],
        'CNY' => ['name' => 'CNY', 'symbol' => '¥', 'exchange_rate' => 0.140300],
    ];

    protected $model = Currency::class;

    /**
     * Divisas soportadas por la fábrica.
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::CATALOG);
    }

    /**
     * Devuelve el id de la divisa, creándola con los datos del catálogo si no existe.
     */
    public static function resolveId(string $code): int
    {
        $attributes = self::attributesFor($code);

        return Currency::query()->firstOrCreate(['name' => $attributes['name']], $attributes)->id;
    }

    /**
     * @return array{name: string, symbol: string, exchange_rate: float}
     */
    protected static function attributesFor(string $code): array
    {
        return self::CATALOG[$code] ?? throw new InvalidArgumentException("Divisa no soportada: {$code}");
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return self::attributesFor('USD');
    }

    public function usd(): static
    {
        return $this->forCode('USD');
    }

    public function eur(): static
    {
        return $this->forCode('EUR');
    }

    public function cop(): static
    {
        return $this->forCode('COP');
    }

    public function mxn(): static
    {
        return $this->forCode('MXN');
    }

    public function cny(): static
    {
        return $this->forCode('CNY');
    }

    public function forCode(string $code): static
    {
        return $this->state(fn (): array => self::attributesFor($code));
    }
}
