<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
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
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:0'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in(Product::SORTABLE)],
            'direction' => ['sometimes', 'string', Rule::in(Product::DIRECTIONS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.string' => 'El término de búsqueda debe ser texto.',
            'search.max' => 'El término de búsqueda no debe superar los :max caracteres.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página no puede ser negativa.',
            'per_page.integer' => 'El tamaño de página debe ser un número entero.',
            'per_page.min' => 'El tamaño de página debe ser al menos :min.',
            'per_page.max' => 'El tamaño de página no debe superar :max elementos.',
            'sort.string' => 'El criterio de orden debe ser texto.',
            'sort.in' => 'El criterio de orden no está soportado.',
            'direction.string' => 'La dirección de orden debe ser texto.',
            'direction.in' => 'La dirección de orden debe ser "asc" o "desc".',
        ];
    }

    /**
     * Columna y sentido de orden del listado (por defecto -created_at).
     *
     * @return array{0: string, 1: string}
     */
    public function orderBy(): array
    {
        return [
            (string) $this->validated('sort', 'created_at'),
            (string) $this->validated('direction', 'desc'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'search' => 'búsqueda',
            'page' => 'página',
            'per_page' => 'elementos por página',
            'sort' => 'orden',
            'direction' => 'dirección',
        ];
    }
}
