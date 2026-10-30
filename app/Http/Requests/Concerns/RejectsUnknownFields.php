<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

/**
 * Rechaza cualquier clave que no esté declarada en rules().
 * Los campos permitidos se derivan de las propias reglas para no duplicarlos.
 */
trait RejectsUnknownFields
{
    protected function rejectUnknownFields(Validator $validator): void
    {
        $allowed = array_map(
            static fn(string $key): string => explode('.', $key)[0],
            array_keys($this->rules()),
        );

        $unknown = array_values(array_diff(array_keys($this->request->all()), $allowed));

        if ($unknown === []) {
            return;
        }

        $validator->errors()->add(
            'payload',
            'El cuerpo de la petición contiene campos no permitidos: ' . implode(', ', $unknown) . '.'
        );
    }
}
