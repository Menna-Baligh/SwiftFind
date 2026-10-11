<?php

use App\Http\Controllers\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    Route::get('/search', ProductSearchController::class)->name('products.search');
});
