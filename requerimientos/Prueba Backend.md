## Prueba Backend

IMPORTANTE: El propósito de la siguiente prueba es que puedas plasmar los conocimientos que tienes en Laravel, como es una prueba sencilla con una estructura pequeña se presta para hacerse rápido, ejecute las funcionalidades descritas, lo que se busca es que puedas implementar los conocimientos, estructuras que conozcas de Laravel. No solo hacerlo con lo mínimo.

## Prueba

Se requiere desarrollar una API RESTful utilizando Laravel que gestione productos

con las siguientes características:

## Requisitos

- La API debe permitir crear, leer, actualizar y eliminar productos.

- Cada producto debe tener un nombre, descripción, precio, costo de

impuestos y costo de fabricación.

- La API debe permitir registrar precios de los productos en diferentes divisas.

- La API debe devolver los datos en formato JSON.

- La API debe utilizar Eloquent para interactuar con la base de datos.

## Modelo de datos

Se proporciona el siguiente modelo de datos:

## Productos

| Campo | Tipo | Descripción |
| --- | --- | --- |
| id | integer | Identificador único del producto |
| name | string | Nombre del producto |
| description | string | Descripción del producto |
| Price | decimal | Precio del producto en la divisa base |
| currency_id | integer | Identificador de la divisa base |
| tax_cost | decimal | Costo de impuestos del producto |
| manufacturing_cost decimal |   | Costo de fabricación del producto |


## Divisas

## Campo Tipo Descripción

| Campo | Tipo | Descripción |
| --- | --- | --- |
| id | integer | Identificador único de la divisa |
| name | string | Identificador único de la divisa |
| symbol | string | Símbolo de la divisa |
| exchange_rate decimal |   | Tasa de cambio de la divisa |

## Precios de productos

| Campo | Tipo | Descripción |
| --- | --- | --- |
| id | integer | Identificador único de la divisa |
| product_id integer |   | Identificador del producto |
| currency_id Integer |   | Identificador de la divisa |
| price | decimal | Precio del producto en la divisa especificada |

## Endpoints

La API debe tener los siguientes endpoints:

- GET /products: Obtener lista de productos

- POST /products: Crear un nuevo producto

- GET /products/{id}: Obtener un producto por ID

- PUT /products/{id}: Actualizar un producto

- DELETE /products/{id}: Eliminar un producto

- GET /products/{id}/prices: Obtener lista de precios de un producto

- POST /products/{id}/prices: Crear un nuevo precio para un producto

## Notas

- La API debe utilizar Laravel 8 o superior.

- La API debe utilizar Eloquent para interactuar con la base de datos.

- La API debe devolver los datos en formato JSON.


• La API debe tener una documentación clara y concisa.

Evaluación

La prueba será evaluada según los siguientes criterios:

• Correctitud: La API debe funcionar correctamente y cumplir con los

requisitos especificados.

• Claridad: La documentación de la API debe ser clara y concisa.

• Organización: La estructura del código debe ser organizada y fácil de

entender.

• Seguridad: La API debe tener medidas de seguridad adecuadas para

proteger los datos.

Entrega

La prueba debe ser entregada en un repositorio de su preferencia.

Entregar una documentación en una de estas tecnologías u otra que conozcas:

Swagger

Json Postman

Json Insomnia

Documento
