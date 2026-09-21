<?php

use App\Http\Controllers\Internal\BrandSyncController;
use App\Http\Controllers\Internal\CategorySyncController;
use App\Http\Controllers\Internal\ProductSyncController;
use App\Http\Controllers\Internal\ContactSyncController;
use App\Http\Controllers\Internal\OrderStatusSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.key')->group(function () {
    Route::post('/brands/sync',        [BrandSyncController::class,       'sync']);
    Route::post('/categories/sync',    [CategorySyncController::class,    'sync']);
    Route::post('/products/sync',      [ProductSyncController::class,     'sync']);
    Route::post('/contacts/sync',      [ContactSyncController::class,     'sync']);
    Route::post('/orders/status/sync', [OrderStatusSyncController::class, 'sync']);
});