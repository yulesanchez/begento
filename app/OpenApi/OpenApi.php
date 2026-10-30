<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Begento Products API',
    description: 'API REST para la gestión de productos y el registro de precios por divisa.',
)]
#[OA\Server(url: '/api/v1', description: 'Servidor local')]
#[OA\Tag(name: 'Products', description: 'Registro, listado, consulta, actualización y eliminación de productos')]
#[OA\Tag(name: 'Prices', description: 'Precios de un producto expresados en cada divisa')]
#[OA\Schema(
    schema: 'Currency',
    description: 'Divisa asociada al producto o al precio.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'USD'),
        new OA\Property(property: 'symbol', type: 'string', example: '$'),
        new OA\Property(property: 'exchange_rate', type: 'string', example: '1.000000'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Product',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Laptop Pro 14'),
        new OA\Property(property: 'description', type: 'string', example: 'Portátil de 14 pulgadas'),
        new OA\Property(property: 'price', type: 'string', example: '1500.50'),
        new OA\Property(property: 'tax_cost', type: 'string', example: '150.00'),
        new OA\Property(property: 'manufacturing_cost', type: 'string', example: '900.00'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/Currency'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ProductPrice',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'currency_id', type: 'integer', example: 2),
        new OA\Property(property: 'price', type: 'string', example: '1422.10'),
        new OA\Property(property: 'rate_used', type: 'string', example: '0.947688'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/Currency'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'path', type: 'string'),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 42),
        new OA\Property(property: 'base_currency_id', type: 'integer', description: 'Solo en el listado de precios.', example: 1),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'El nombre del producto es obligatorio. (and 1 more error)'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['name' => ['El nombre del producto es obligatorio.']],
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ErrorMessage',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Producto no encontrado.'),
        new OA\Property(property: 'error', type: 'string', example: 'NOT_FOUND'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ProductPayload',
    required: ['name', 'description', 'price', 'currency_id'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Laptop Pro 14'),
        new OA\Property(property: 'description', type: 'string', maxLength: 500, example: 'Portátil de 14 pulgadas'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1500.50),
        new OA\Property(property: 'currency_id', type: 'integer', example: 1),
        new OA\Property(property: 'tax_cost', type: 'number', format: 'float', nullable: true, example: 150.00),
        new OA\Property(property: 'manufacturing_cost', type: 'number', format: 'float', nullable: true, description: 'Debe ser estrictamente menor que price.', example: 900.00),
    ],
    additionalProperties: false,
    type: 'object',
)]
#[OA\Schema(
    schema: 'ProductUpdatePayload',
    description: 'Cuerpo del PUT. La divisa base no es editable: se conserva la del producto.',
    required: ['name', 'description', 'price', 'currency_id'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Laptop Pro 14'),
        new OA\Property(property: 'description', type: 'string', maxLength: 500, example: 'Portátil de 14 pulgadas'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1500.50),
        new OA\Property(property: 'currency_id', type: 'integer', description: 'Divisa base del producto. No es editable: debe coincidir con la actual o la petición se rechaza con 409.', example: 1),
        new OA\Property(property: 'tax_cost', type: 'number', format: 'float', nullable: true, example: 150.00),
        new OA\Property(property: 'manufacturing_cost', type: 'number', format: 'float', nullable: true, description: 'Debe ser estrictamente menor que price.', example: 900.00),
    ],
    additionalProperties: false,
    type: 'object',
)]
#[OA\Schema(
    schema: 'ProductPricePayload',
    required: ['currency_id'],
    properties: [
        new OA\Property(property: 'currency_id', type: 'integer', example: 2),
        new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true, description: 'Si se omite se calcula con la tasa de la divisa.', example: 1422.10),
    ],
    additionalProperties: false,
    type: 'object',
)]
class OpenApi {}
