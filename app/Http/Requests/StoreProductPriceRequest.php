<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\RejectsUnknownFields;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductPriceRequest extends FormRequest
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
     * Reglas alineadas al $fillable de ProductPrice y a la tabla product_prices:
     * currency_id FK obligatoria, price opcional decimal(12,2) >= 0.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'price' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
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
            'currency_id.required' => 'La divisa es obligatoria.',
            'currency_id.integer' => 'La divisa debe ser un identificador válido.',
            'currency_id.exists' => 'La divisa seleccionada no existe.',

            'price.numeric' => 'El precio debe ser un número.',
            'price.decimal' => 'El precio debe tener como máximo 2 decimales.',
            'price.min' => 'El precio no puede ser negativo.',
            'price.max' => 'El precio supera el monto máximo permitido.',
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
            'currency_id' => 'divisa',
            'price' => 'precio',
            'payload' => 'cuerpo de la petición',
        ];
    }

    /**
     * Duplicado (product_id, currency_id), coincidencia del precio base y
     * exigencia de precio cuando la divisa no tiene tasa de cambio.
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
                if ($validator->errors()->has('currency_id')) {
                    return;
                }

                $product = $this->product();
                if (! $product) {
                    return;
                }

                $currencyId = (int) $this->input('currency_id');

                if (ProductPrice::query()
                    ->where('product_id', $product->id)
                    ->where('currency_id', $currencyId)
                    ->exists()) {
                    $validator->errors()->add('currency_id', 'La divisa ya tiene un precio registrado para este producto.');

                    return;
                }

                if ($validator->errors()->has('price')) {
                    return;
                }

                $currency = Currency::query()->find($currencyId);
                if (! $currency) {
                    return;
                }

                $hasPrice = $this->filled('price');

                if ($currencyId === (int) $product->currency_id) {
                    if ($hasPrice && abs((float) $this->input('price') - (float) $product->price) > 0.005) {
                        $validator->errors()->add('price', 'El precio de la divisa base debe coincidir con el precio del producto.');
                    }

                    return;
                }

                if (! $hasPrice && (float) $currency->exchange_rate <= 0) {
                    $validator->errors()->add('price', 'Se requiere un precio o una tasa de cambio válida para la divisa.');
                }
            },
        ];
    }

    private function product(): ?Product
    {
        $product = $this->route('product') ?? $this->route('id');

        if ($product instanceof Product) {
            return $product;
        }

        return is_numeric($product) ? Product::query()->find((int) $product) : null;
    }
}
