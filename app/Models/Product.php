<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Columnas permitidas para ordenar el listado.
     *
     * @var array<int, string>
     */
    public const SORTABLE = ['name', 'price', 'created_at', 'updated_at'];

    /** @var array<int, string> */
    public const DIRECTIONS = ['asc', 'desc'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency_id',
        'tax_cost',
        'manufacturing_cost',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tax_cost' => 'decimal:2',
            'manufacturing_cost' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Aplica la búsqueda por texto y el ordenamiento del listado.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeSearch(Builder $query, ?string $term, string $sort = 'created_at', string $direction = 'desc'): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $pattern = "%{$term}%";

            $query->where(function (Builder $query) use ($pattern): void {
                $query->where('name', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern);
            });
        }

        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'created_at';
        $direction = in_array($direction, self::DIRECTIONS, true) ? $direction : 'desc';

        $query->orderBy($sort, $direction);
    }
}
