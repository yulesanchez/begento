<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Crea un producto con los valores por defecto de los costos.
     *
     * @param  array<string, mixed>  $data  Datos ya validados por StoreProductRequest.
     */
    public function create(array $data): Product
    {
        return Product::create($data + [
            'tax_cost' => 0,
            'manufacturing_cost' => 0,
        ])->load('currency');
    }

    /**
     * Lista paginada de productos con búsqueda y ordenamiento.
     */
    public function paginate(?string $search, int $page, int $pageSize, string $sort, string $direction): LengthAwarePaginator
    {
        return Product::search($search, $sort, $direction)
            ->with('currency')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * Actualiza el producto y devuelve el estado persistido.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh()->load('currency');
    }

    /**
     * Elimina el producto junto con sus precios.
     */
    public function delete(Product $product): void
    {
        $product->prices()->delete();
        $product->delete();
    }

    /**
     * La divisa base no puede cambiarse si ya hay precios en otras divisas
     */
    public function blocksCurrencyChange(Product $product, int $currencyId): bool
    {
        if ($currencyId === (int) $product->currency_id) {
            return false;
        }

        return $product->prices()
            ->where('currency_id', '!=', $product->currency_id)
            ->exists();
    }
}
