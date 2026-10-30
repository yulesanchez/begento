<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    /**
     * Registra un producto y devuelve 201 con la cabecera Location.
     */
    #[OA\Post(
        path: '/products',
        operationId: 'storeProduct',
        tags: ['Products'],
        summary: 'Registra un producto',
        description: 'Crea un producto. Devuelve la cabecera Location con la URL del recurso creado.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductPayload'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Producto creado',
                headers: [
                    new OA\Header(header: 'Location', description: 'URL del producto creado', schema: new OA\Schema(type: 'string')),
                ],
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 415, description: 'Content-Type no soportado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
            new OA\Response(response: 422, description: 'Datos inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('products.detail', ['product' => $product->id]));
    }

    /**
     * Lista los productos de forma paginada con búsqueda y ordenamiento.
     */
    #[OA\Get(
        path: '/products',
        operationId: 'listProducts',
        tags: ['Products'],
        summary: 'Lista los productos de forma paginada',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Filtra por nombre o descripción.', schema: new OA\Schema(type: 'string', maxLength: 120)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página solicitada (0 y 1 son la primera).', schema: new OA\Schema(type: 'integer', minimum: 0, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Registros por página.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Campo de ordenamiento.', schema: new OA\Schema(type: 'string', enum: ['name', 'price', 'created_at', 'updated_at'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', description: 'Sentido del ordenamiento.', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 422, description: 'Parámetros de consulta inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function list(ProductIndexRequest $request): AnonymousResourceCollection
    {
        [$sort, $direction] = $request->orderBy();

        $products = $this->productService->paginate(
            $request->validated('search'),
            (int) $request->validated('page', 0),
            (int) $request->validated('per_page', 15),
            $sort,
            $direction,
        );

        return ProductResource::collection($products);
    }

    /**
     * Devuelve un producto con su divisa.
     */
    #[OA\Get(
        path: '/products/{product}',
        operationId: 'showProduct',
        tags: ['Products'],
        summary: 'Devuelve un producto con su divisa',
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'Identificador del producto.', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto encontrado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
        ],
    )]
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->loadMissing('currency'));
    }

    /**
     * Actualiza un producto existente.
     */
    #[OA\Put(
        path: '/products/{product}',
        operationId: 'updateProduct',
        tags: ['Products'],
        summary: 'Actualiza un producto existente',
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'Identificador del producto.', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto actualizado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
            new OA\Response(response: 409, description: 'No se puede cambiar la divisa base porque existen precios en otras divisas', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
            new OA\Response(response: 422, description: 'Datos inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse|ProductResource
    {
        if ($this->productService->blocksCurrencyChange($product, (int) $request->validated('currency_id'))) {
            return response()->json(['message' => 'No se puede cambiar la divisa base.'], 409);
        }

        return ProductResource::make(
            $this->productService->update($product, $request->validated()),
        );
    }

    /**
     * Elimina el producto junto con sus precios.
     */
    #[OA\Delete(
        path: '/products/{product}',
        operationId: 'destroyProduct',
        tags: ['Products'],
        summary: 'Elimina un producto y sus precios',
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'Identificador del producto.', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto eliminado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado.')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage')),
        ],
    )]
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json(['message' => 'Producto eliminado.'], 200);
    }
}
