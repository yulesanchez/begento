<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'tax_cost' => $this->tax_cost,
            'manufacturing_cost' => $this->manufacturing_cost,
            'currency' => [
                'id' => $this->currency->id,
                'name' => $this->currency->name,
                'symbol' => $this->currency->symbol,
                'exchange_rate' => $this->currency->exchange_rate,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
