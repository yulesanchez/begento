<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductPriceIndexRequest;
use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Models\Product;
use App\Services\ProductPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ProductPriceController extends Controller
{
    public function __construct(private readonly ProductPriceService $productPriceService) {}

    /**
     * Lista paginada de los precios del producto en cada divisa.
     */
    #[OA\Get(
        path: '/products/{product}/prices',
        operationId: 'listProductPrices',
        tags: ['Prices'],
        summary: 'Lista los precios del producto por divisa',
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'Identificador del producto.', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 0, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Campo de ordenamiento; el prefijo "-" indica descendente.', schema: new OA\Schema(type: 'string', enum: ['price', 'created_at', 'id', '-price', '-created_at', '-id'], default: '-created_at')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Precios paginados del producto',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductPrice')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
            new OA\Response(response: 422, description: 'Parámetros de consulta inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function index(ProductPriceIndexRequest $request, Product $product): AnonymousResourceCollection
    {
        [$sort, $direction] = $request->orderBy();

        $prices = $this->productPriceService->paginate(
            $product,
            (int) $request->validated('page', 0),
            (int) $request->validated('per_page', 15),
            $sort,
            $direction,
        );

        return ProductPriceResource::collection($prices)->additional([
            'meta' => ['base_currency_id' => (int) $product->currency_id],
        ]);
    }

    /**
     * Registra el precio de un producto en una divisa y devuelve 201 con Location.
     */
    #[OA\Post(
        path: '/products/{product}/prices',
        operationId: 'storeProductPrice',
        tags: ['Prices'],
        summary: 'Registra el precio del producto en una divisa',
        description: 'Si no se envía price, se calcula multiplicando el precio base por la tasa de la divisa. Devuelve 201 con la cabecera Location.',
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'Identificador del producto.', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductPricePayload'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Precio registrado',
                headers: [
                    new OA\Header(header: 'Location', description: 'URL del precio creado', schema: new OA\Schema(type: 'string')),
                ],
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ProductPrice')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
            new OA\Response(response: 422, description: 'Datos inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function store(StoreProductPriceRequest $request, Product $product): JsonResponse
    {
        $price = $this->productPriceService->register($product, $request->validated());

        return ProductPriceResource::make($price)
            ->response()
            ->setStatusCode(201)
            ->header('Location', url("/api/v1/products/{$product->id}/prices/{$price->id}"));
    }
}
