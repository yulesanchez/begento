# Guía de Requerimientos — API de Productos y Divisas

Documento fuente: [Prueba Backend.md](./Prueba%20Backend.md)
Arquitectura de componentes: [componentes.md](./componentes.md)

## Épicas

| Épica | Archivo | Alcance |
| --- | --- | --- |
| EP-01 | [EP-01-gestion-productos.md](./historias-usuario/EP-01-gestion-productos.md) | CRUD de productos (5 endpoints) |
| EP-02 | [EP-02-gestion-precios.md](./historias-usuario/EP-02-gestion-precios.md) | Precios por divisa (2 endpoints) |
| EP-03 | [EP-03-catalogo-divisas.md](./historias-usuario/EP-03-catalogo-divisas.md) | Catálogo de divisas (2 endpoints) |
| EP-04 | [EP-04-transversal-rnf.md](./historias-usuario/EP-04-transversal-rnf.md) | RNF transversales, documentación y pruebas |

## Convenciones

| Elemento | Formato | Ejemplo |
| --- | --- | --- |
| Épica | `EP-NN` | `EP-01` |
| Historia de Usuario | `HU-<AREA>-NN` | `HU-PROD-02` |
| Requisito Funcional | `RF-<AREA>-NN` | `RF-PROD-05` |
| Requisito No Funcional | `RNF-NN` | `RNF-03` |
| Caso de uso principal | `@principal` | Camino feliz |
| Caso alterno / error | `@alterno` | 404, 422, 409 |
| Requisito no funcional | `@rnf` | Seguridad, performance |

Regla de atomicidad: **1 endpoint = 1 HU**. Cada HU declara endpoint, RF, RNF, reglas de negocio y escenarios Gherkin (Given/When/Then) ejecutables.

Prefijo de rutas: `/api/v1`. Formato de intercambio: `application/json`.

Stack de referencia del repositorio: PHP 8.3, Laravel 13, Pest.

## Matriz de trazabilidad

| Endpoint | HU | RF | RNF |
| --- | --- | --- | --- |
| GET /api/v1/products | HU-PROD-01 | RF-PROD-01, RF-PROD-06, RF-PROD-07, RF-PROD-08 | RNF-02, RNF-03, RNF-06 |
| POST /api/v1/products | HU-PROD-02 | RF-PROD-02, RF-PROD-09..RF-PROD-12 | RNF-01, RNF-02, RNF-04 |
| GET /api/v1/products/{id} | HU-PROD-03 | RF-PROD-03, RF-PROD-13 | RNF-01, RNF-03 |
| PUT /api/v1/products/{id} | HU-PROD-04 | RF-PROD-04, RF-PROD-14..RF-PROD-16 | RNF-01, RNF-02, RNF-04 |
| DELETE /api/v1/products/{id} | HU-PROD-05 | RF-PROD-05, RF-PROD-17 | RNF-05 |
| GET /api/v1/products/{id}/prices | HU-PRICE-01 | RF-PRICE-01, RF-PRICE-03 | RNF-03 |
| POST /api/v1/products/{id}/prices | HU-PRICE-02 | RF-PRICE-02, RF-PRICE-04..RF-PRICE-08 | RNF-01, RNF-04, RNF-07, RNF-08 |
| GET /api/v1/currencies | HU-CUR-01 | RF-CUR-01 | RNF-02, RNF-09 |
| POST /api/v1/currencies | HU-CUR-02 (condicional) | RF-CUR-02 | RNF-01, RNF-10 |

## Orden de implementación sugerido

1. EP-03 (catálogo de divisas y seeder) — dependencia de todo lo demás.
2. EP-01 (CRUD de productos).
3. EP-02 (precios por producto).
4. EP-04 (contrato de errores, seguridad, performance, documentación, tests).

## Criterios de aceptación globales (Definition of Done)

- [ ] Todos los endpoints responden JSON con `Content-Type: application/json`.
- [ ] Códigos HTTP cubiertos: 200, 201, 204, 401, 403, 404, 409, 413, 415, 422, 429, 500.
- [ ] Cada HU tiene al menos 1 test Feature que cubre el escenario `@principal` y 1 por cada escenario `@alterno`.
- [ ] Documentación OpenAPI publicada y colección Postman/Insomnia exportada.
