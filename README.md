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
- Node 18 o superior (solo para las pruebas de estrés)

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
API_RATE_LIMIT=60
L5_SWAGGER_GENERATE_ALWAYS=true
```

`API_RATE_LIMIT` es el máximo de peticiones por minuto y por IP que aplica el limitador `api` ([`AppServiceProvider`](app/Providers/AppServiceProvider.php)); en producción se deja en 60 y solo se eleva en el proceso que ejecuta la prueba de estrés.

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
scripts/
└── stress.mjs           Prueba de carga sin dependencias externas
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

Toda respuesta de error bajo `/api/v1` usa el mismo sobre:

```json
{ "message": "...", "error": "CODIGO", "data": null }
```

| Código | `error` | Cuándo |
| --- | --- | --- |
| 400 | `BAD_REQUEST` | Petición mal formada |
| 404 | `NOT_FOUND` | Recurso inexistente (`Producto no encontrado.` en `/products/*`, `Recurso no encontrado.` en el resto) |
| 405 | `METHOD_NOT_ALLOWED` | Verbo HTTP no soportado (incluye cabecera `Allow`) |
| 409 | `CONFLICT` | Cambio de divisa base bloqueado (`{"message": "No se puede cambiar la divisa..."}`) |
| 415 | `UNSUPPORTED_MEDIA_TYPE` | `Content-Type` distinto de `application/json` |
| 422 | `VALIDATION_ERROR` | Fallo de validación (`errors` por campo, además del sobre) |
| 429 | `TOO_MANY_REQUESTS` | Límite de 60 peticiones por minuto y por IP (incluye `Retry-After`) |
| 500 | `SERVER_ERROR` | Error inesperado (sin traza en la respuesta; queda en el log) |

## Documentación

| URL | Contenido |
|---|---|
| **http://localhost:8000/api/documentation** | Swagger UI (plantilla del paquete `l5-swagger`) |
| **http://localhost:8000/api/documentation/es** | Misma especificación con la UI traducida (`public/js/swagger-ui-es.js`); no se modifican archivos de `vendor/` |
| **http://localhost:8000/docs** | Contrato OpenAPI en JSON crudo |

El contrato vive en `storage/api-docs/api-docs.json`. Los scripts de Composer lo mantienen al día:

```bash
composer docs          # regenera el contrato (php artisan l5-swagger:generate)
composer docs:serve    # regenera el contrato y levanta el servidor en http://127.0.0.1:8000
```

`/api/documentation/es` es una vista del grupo `web`, por lo que abre sesión y necesita una base de datos alcanzable: usa `DB_CONNECTION=sqlite` con `SESSION_DRIVER=file` (MySQL detenido produce 500 en esa ruta). `/docs` y `/api/documentation` responden sin base de datos porque su middleware no abre sesión.

También se incluye una colección de Postman en [`docs/Begento-API.postman_collection.json`](docs/Begento-API.postman_collection.json). Importarla y ajustar la variable `base_url` si es necesario.

## Pruebas

```bash
php artisan test --compact      # suite completa: Unit + Feature + E2E
vendor/bin/pest                 # equivalente directo
vendor/bin/pest tests/Feature/ListProductTest.php --filter="listado vacío"
vendor/bin/pest --testsuite=E2E # solo HTTP real (php -S + Guzzle), ~35 s
```

Cobertura de líneas sobre `app/` (105 tests, 100 %; requiere Xdebug instalado en `ext/` del PHP):

```bash
php -d zend_extension=php_xdebug-3.5.3-8.4-ts-vs17-x86_64.dll -d xdebug.mode=coverage vendor/bin/pest --coverage
```

`app/Models/User.php` queda excluido en `phpunit.xml`: el enunciado no pide autenticación. La suite `E2E` corre en un proceso hijo, así que sus líneas no se suman al reporte; el 100 % lo sostienen Unit y Feature.

Formato de código:

```bash
vendor/bin/pint --dirty --format agent
```

## Pruebas de estrés

[`scripts/stress.mjs`](scripts/stress.mjs) mide throughput y latencia sin dependencias externas (usa `fetch` nativo de Node 18+).

El limitador `api` fija 60 req/min por IP. Para estresar de verdad hay que elevarlo solo en ese proceso:

```bash
API_RATE_LIMIT=100000 php artisan serve
```

En PowerShell:

```powershell
$env:DB_CONNECTION='sqlite'; $env:SESSION_DRIVER='file'; $env:CACHE_STORE='file'; $env:API_RATE_LIMIT='100000'
php artisan serve
```

Carga cerrada (N peticiones):

```bash
node scripts/stress.mjs --url="http://127.0.0.1:8000/api/v1/products?page=1&per_page=100&sort=name&direction=desc" --requests=500 --concurrency=20
```

Carga abierta (duración en segundos):

```bash
node scripts/stress.mjs --duration=30 --concurrency=50 --expect=200
```

Opciones: `--method`, `--header="K: V"` (repetible), `--body`, `--timeout`, `--expect=200,422`, `--json`.

| Salida | Significado |
| --- | --- |
| `Rendimiento` | peticiones completadas por segundo |
| `Latencia ms` | percentiles p50/p90/p99, mínimo y máximo |
| `Estados` | histograma de códigos HTTP recibidos |
| `Fuera de lo esperado` | respuestas que no están en `--expect` |

El proceso termina con código 1 si hay fallos de red o estados inesperados, así que sirve como puerta en CI. Para observar el `429` basta dejar el límite en 60 y enviar más de 60 peticiones:

```bash
node scripts/stress.mjs --requests=90 --concurrency=5 --expect=200
```

Advertencia: `php artisan serve` es mono-proceso y `PHP_CLI_SERVER_WORKERS` no funciona en Windows. Los números miden el servidor de desarrollo, no la aplicación. Para cifras representativas se necesita nginx + php-fpm (Laragon/WAMP) detrás de la misma base de código.

## Licencia

MIT.
