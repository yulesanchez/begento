<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<ProductPrice, $this> */
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
