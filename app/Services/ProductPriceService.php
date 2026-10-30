<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductPriceService
{
    /**
     * Lista paginada de los precios del producto con su divisa.
     *
     * @return LengthAwarePaginator<int, ProductPrice>
     */
    public function paginate(Product $product, int $page, int $pageSize, string $sort, string $direction): LengthAwarePaginator
    {
        return $product->prices()
            ->with('currency')
            ->orderBy($sort, $direction)
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * Registra (o reemplaza) el precio del producto en la divisa indicada.
     *
     * @param  array{currency_id: int, price?: float|string|null}  $data
     */
    public function register(Product $product, array $data): ProductPrice
    {
        return DB::transaction(function () use ($product, $data): ProductPrice {
            $currency = Currency::query()->findOrFail((int) $data['currency_id']);
            $isBase = (int) $currency->id === (int) $product->currency_id;

            $price = $this->resolvePrice($product, $currency, $data['price'] ?? null, $isBase);

            $productPrice = ProductPrice::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'currency_id' => $currency->id,
                ],
                [
                    'price' => $price,
                    'rate_used' => $isBase ? 1 : $currency->exchange_rate,
                ],
            );

            if ($isBase) {
                $product->update(['price' => $price]);
            }

            return $productPrice->load('currency');
        });
    }

    /**
     * Precio en la divisa base debe coincidir con el producto; en otra divisa se
     * calcula con la tasa de cambio cuando no se envía precio explícito.
     */
    public function resolvePrice(Product $product, Currency $currency, float|string|null $price, bool $isBase): string
    {
        if ($isBase) {
            return number_format((float) $product->price, 2, '.', '');
        }

        if ($price !== null && $price !== '') {
            return number_format($this->round((float) $price), 2, '.', '');
        }

        return number_format($this->round((float) $product->price * (float) $currency->exchange_rate), 2, '.', '');
    }

    /**
     * Redondeo HALF_UP a 2 decimales (RNF-08).
     */
    public function round(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
