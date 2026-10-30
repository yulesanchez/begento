<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPriceIndexRequest extends FormRequest
{
    /**
     * Columnas permitidas para ordenar los precios.
     *
     * @var array<int, string>
     */
    public const SORTABLE = ['price', 'created_at', 'id'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:0'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'regex:/^-?(price|created_at|id)$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página no puede ser negativa.',
            'per_page.integer' => 'El tamaño de página debe ser un número entero.',
            'per_page.min' => 'El tamaño de página debe ser al menos :min.',
            'per_page.max' => 'El tamaño de página no debe superar :max elementos.',
            'sort.string' => 'El criterio de orden debe ser texto.',
            'sort.regex' => 'El criterio de orden no está soportado.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'page' => 'página',
            'per_page' => 'elementos por página',
            'sort' => 'orden',
        ];
    }

    /**
     * Columna y sentido de orden a partir del formato "campo" o "-campo".
     *
     * @return array{0: string, 1: string}
     */
    public function orderBy(): array
    {
        $sort = (string) $this->query('sort', '-created_at');
        $descending = str_starts_with($sort, '-');
        $column = ltrim($sort, '-');

        return [$column, $descending ? 'desc' : 'asc'];
    }
}
