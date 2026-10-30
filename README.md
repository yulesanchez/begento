# Begento — API de Productos

API RESTful en Laravel para la gestión de productos y el registro de sus precios en distintas divisas.

- Listado paginado con búsqueda y ordenamiento.
- CRUD completo de productos (crear, listar, ver, actualizar, eliminar).
- Precios por producto y divisa, calculados con la tasa de cambio configurada.
- Validación con mensajes en español y respuestas de error consistentes.
- Documentación OpenAPI/Swagger generada con `darkaonline/l5-swagger`.
- Suite de pruebas con Pest.

## Requisitos

- PHP 8.3 o superior
- Composer
- SQLite (por defecto) o cualquier motor soportado por Laravel

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

La API queda disponible en `http://localhost:8000/api/v1`.

Variables relevantes del `.env`:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
L5_SWAGGER_GENERATE_ALWAYS=true
```

## Estructura

```
app/
├── Http/
│   ├── Controllers/     ProductController, ProductPriceController
│   ├── Requests/        Store/Update/ProductIndex/ProductPriceIndex...
│   └── Resources/       ProductResource, ProductPriceResource
├── Models/              Product, ProductPrice, Currency
├── OpenApi/             Definición raíz OpenAPI (info, servidores, esquemas)
└── Services/            ProductService, ProductPriceService
```

La lógica de negocio vive en los `Services`; los controladores solo orquestan petición, servicio y respuesta.

## Modelo de datos

**products**: `id`, `name` (único, 120), `description` (500), `price` decimal(12,2), `currency_id` (FK restrict), `tax_cost`, `manufacturing_cost`.

**currencies**: `id`, `name` (código ISO), `symbol`, `exchange_rate` decimal(16,6).

**product_prices**: `id`, `product_id` (FK cascade), `currency_id` (FK restrict), `price` decimal(12,2), `rate_used` decimal(16,6). Índice único `(product_id, currency_id)`.

Las divisas de demostración las carga `CurrencySeeder` con valores fijos:

| Código | `exchange_rate` |
| --- | --- |
| USD | 1.000000 |
| EUR | 0.890000 |
| COP | 0.0002952 |
| MXN | 0.050400 |
| CNY | 0.140300 |

Son valores de ejemplo, sin conexión a ningún proveedor de tasas. Las pruebas usan un catálogo equivalente definido en `CurrencyFactory`.

## Endpoints

Base: `http://localhost:8000/api/v1`

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/products` | Lista paginada de productos |
| POST | `/products` | Crea un producto |
| GET | `/products/{product}` | Obtiene un producto por ID |
| PUT | `/products/{product}` | Actualiza un producto |
| DELETE | `/products/{product}` | Elimina un producto y sus precios |
| GET | `/products/{product}/prices` | Lista los precios del producto |
| POST | `/products/{product}/prices` | Registra un precio para el producto |

Todas las peticiones con cuerpo deben enviar `Content-Type: application/json` y `Accept: application/json`.

### GET /products

Parámetros de consulta opcionales:

| Parámetro | Valores | Por defecto |
| --- | --- | --- |
| `search` | texto (máx. 120) | — |
| `page` | entero ≥ 0 | 1 |
| `per_page` | entero 1–100 | 15 |
| `sort` | `name`, `price`, `created_at`, `updated_at` | `created_at` |
| `direction` | `asc`, `desc` | `desc` |

```bash
curl "http://localhost:8000/api/v1/products?search=laptop&per_page=10&sort=price&direction=desc"
```

```json
{
  "data": [
    {
      "id": 1,
      "name": "Laptop Pro 14",
      "description": "Portátil de 14 pulgadas",
      "price": "1500.50",
      "tax_cost": "150.00",
      "manufacturing_cost": "900.00",
      "currency": { "id": 1, "name": "USD", "symbol": "$", "exchange_rate": "1.000000" },
      "created_at": "2026-10-29T12:00:00+00:00",
      "updated_at": "2026-10-29T12:00:00+00:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 10, "to": 1, "total": 1 }
}
```

### POST /products

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Laptop Pro 14",
    "description": "Portátil de 14 pulgadas",
    "price": 1500.50,
    "currency_id": 1,
    "tax_cost": 150.00,
    "manufacturing_cost": 900.00
  }'
```

Reglas principales:

- `name` obligatorio, único, máximo 120 caracteres.
- `description` obligatorio, máximo 500 caracteres.
- `price` obligatorio, numérico, máximo 2 decimales, mayor o igual a 0.
- `currency_id` obligatorio y existente en `currencies`.
- `tax_cost` y `manufacturing_cost` opcionales (por defecto 0).
- `manufacturing_cost` debe ser **estrictamente menor** que `price`.
- `tax_cost` no puede ser mayor que `price`.

Respuesta `201` con cabecera `Location: /api/v1/products/{id}`.

### PUT /products/{product}

Mismos campos que el alta. Devuelve `409` si se intenta cambiar la divisa base cuando ya existen precios registrados en otras divisas.

```bash
curl -X PUT http://localhost:8000/api/v1/products/1 \
  -H "Content-Type: application/json" \
  -d '{ "name": "Laptop Pro 15", "description": "Portátil de 15 pulgadas", "price": 1700.00, "currency_id": 1 }'
```

### DELETE /products/{product}

```bash
curl -X DELETE http://localhost:8000/api/v1/products/1 -H "Accept: application/json"
```

```json
{ "message": "Producto eliminado." }
```

### GET /products/{product}/prices

Acepta `page`, `per_page` (1–100) y `sort` (`price`, `created_at`, `id`, con prefijo `-` para descendente; por defecto `-created_at`).

```bash
curl "http://localhost:8000/api/v1/products/1/prices?sort=-price"
```

La respuesta incluye `meta.base_currency_id` con la divisa base del producto.

### POST /products/{product}/prices

```bash
curl -X POST http://localhost:8000/api/v1/products/1/prices \
  -H "Content-Type: application/json" \
  -d '{ "currency_id": 2, "price": 1422.10 }'
```

- `currency_id` obligatorio y existente. Un producto solo puede tener un precio por divisa.
- `price` opcional: si se omite se calcula como `precio base × exchange_rate` de la divisa, redondeado a 2 decimales (HALF_UP).

Respuesta `201` con cabecera `Location`.

## Errores

| Código | Cuándo |
| --- | --- |
| 404 | Recurso inexistente (`{"message": "Producto no encontrado.", "error": "NOT_FOUND", "data": null}`) |
| 409 | Cambio de divisa base bloqueado |
| 415 | `Content-Type` distinto de `application/json` |
| 422 | Fallo de validación (`message` + `errors` por campo) |
| 500 | Error inesperado |

## Documentación

Swagger UI: **http://localhost:8000/api/documentation**

Interfaz en español: **http://localhost:8000/api/documentation/es** (misma especificación, con la UI traducida por `public/js/swagger-ui-es.js`; no se modifican los archivos del paquete en `vendor/`).

El contrato OpenAPI vive en `storage/api-docs/api-docs.json` y se regenera con:

```bash
php artisan l5-swagger:generate
```

También se incluye una colección de Postman en [`docs/Begento-API.postman_collection.json`](docs/Begento-API.postman_collection.json). Importarla y ajustar la variable `base_url` si es necesario.

## Pruebas

```bash
php artisan test --compact
```

O directamente:

```bash
vendor/bin/pest
vendor/bin/pest tests/Feature/ListProductTest.php --filter="listado vacío"
```

Formato de código:

```bash
vendor/bin/pint --dirty --format agent
```

## Licencia

MIT.
