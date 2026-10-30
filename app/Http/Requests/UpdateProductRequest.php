<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\RejectsUnknownFields;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use RejectsUnknownFields;

    /**
     * Comprueba si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas alineadas al $fillable de Product y a la tabla products:
     * name varchar(120) unique, description varchar(500) nullable,
     * price/tax_cost/manufacturing_cost decimal(12,2), currency_id FK obligatoria.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('products', 'name')->ignore($this->productId()),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'tax_cost' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'manufacturing_cost' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * Mensajes personalizados por campo y tipo de regla.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // name
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.string' => 'El nombre del producto debe ser texto.',
            'name.max' => 'El nombre del producto no debe superar los :max caracteres.',
            'name.unique' => 'Ya existe un producto con ese nombre.',

            // description
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe superar los :max caracteres.',

            // price
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.decimal' => 'El precio debe tener como máximo 2 decimales.',
            'price.min' => 'El precio no puede ser negativo.',
            'price.max' => 'El precio supera el monto máximo permitido.',

            // currency_id
            'currency_id.required' => 'La divisa es obligatoria.',
            'currency_id.integer' => 'La divisa debe ser un identificador válido.',
            'currency_id.exists' => 'La divisa seleccionada no existe.',

            // tax_cost
            'tax_cost.numeric' => 'El impuesto debe ser un número.',
            'tax_cost.decimal' => 'El impuesto debe tener como máximo 2 decimales.',
            'tax_cost.min' => 'El impuesto no puede ser negativo.',
            'tax_cost.max' => 'El impuesto supera el monto máximo permitido.',

            // manufacturing_cost
            'manufacturing_cost.numeric' => 'El costo de fabricación debe ser un número.',
            'manufacturing_cost.decimal' => 'El costo de fabricación debe tener como máximo 2 decimales.',
            'manufacturing_cost.min' => 'El costo de fabricación no puede ser negativo.',
            'manufacturing_cost.max' => 'El costo de fabricación supera el monto máximo permitido.',
        ];
    }

    /**
     * Nombres legibles de los atributos usados en los mensajes.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del producto',
            'description' => 'descripción',
            'price' => 'precio',
            'currency_id' => 'divisa',
            'tax_cost' => 'impuesto',
            'manufacturing_cost' => 'costo de fabricación',
            'payload' => 'cuerpo de la petición',
        ];
    }

    /**
     * El impuesto no puede superar el precio y el costo de fabricación debe ser
     * estrictamente menor. Si el campo no se envía se compara el valor actual.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->rejectUnknownFields($validator);
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['price', 'tax_cost', 'manufacturing_cost'])) {
                    return;
                }

                $product = $this->route('product');
                $product = $product instanceof Product ? $product : Product::find($this->productId());
                $price = (float) $this->input('price');
                $taxCost = $this->filled('tax_cost')
                    ? (float) $this->input('tax_cost')
                    : (float) $product?->tax_cost;
                $manufacturingCost = $this->filled('manufacturing_cost')
                    ? (float) $this->input('manufacturing_cost')
                    : (float) $product?->manufacturing_cost;

                if ($manufacturingCost >= $price) {
                    $validator->errors()->add(
                        'manufacturing_cost',
                        'El costo de fabricación debe ser menor que el precio.'
                    );
                }

                if ($taxCost > $price) {
                    $validator->errors()->add(
                        'tax_cost',
                        'El impuesto no puede ser mayor que el precio.'
                    );
                }
            },
        ];
    }

    private function productId(): ?int
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            return $product->id;
        }

        return is_numeric($product) ? (int) $product : null;
    }
}
