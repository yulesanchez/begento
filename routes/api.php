<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('products', [ProductController::class, 'list'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product')->name('products.detail');
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->whereNumber('product')->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->whereNumber('product')->name('products.destroy');

    Route::get('products/{product}/prices', [ProductPriceController::class, 'index'])
        ->whereNumber('product')->name('products.prices.index');
    Route::post('products/{product}/prices', [ProductPriceController::class, 'store'])
        ->whereNumber('product')->name('products.prices.store');
});
