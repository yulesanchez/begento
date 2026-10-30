<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Interfaz de Swagger UI en español. Reutiliza el spec generado y los assets
 * del paquete, sin modificar sus archivos: el único añadido es el traductor
 * public/js/swagger-ui-es.js.
 */
Route::view('api/documentation/es', 'swagger.index')->name('swagger.es');
