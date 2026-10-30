1. Modelos: `Product`, `Currency`, `ProductPrice`.
2. Migraciones: tablas, claves foráneas, índices y restricciones únicas.
3. Controladores API: `ProductController`, `ProductPriceController`.
4. Form Requests: `StoreProductRequest`, `UpdateProductRequest`, `StoreProductPriceRequest`.
5. API Resources: `ProductResource`, `CurrencyResource`, `ProductPriceResource`.
6. Relaciones Eloquent: `hasMany`, `belongsTo`.
7. Rutas API: `routes/api.php`.
8. Middleware: manejo de autenticación y acceso, si aplica.
9. Policies: autorización sobre productos y precios, si aplica.
10. Servicios: `ProductPriceService` para la lógica de precios y conversiones entre divisas.
11. Transacciones de base de datos: `DB::transaction()` para operaciones relacionadas.
12. Excepciones: manejo centralizado de errores y respuestas JSON.
13. Tests: pruebas Feature para endpoints, validaciones, relaciones y errores.
14. Documentación API: Swagger/OpenAPI.
15. Seeders: datos iniciales de divisas.
16. Paginación: listado de productos.
17. Git: repositorio con commits organizados.