<?php

use Illuminate\Support\Facades\Route;

// All routes here are protected by InternalApiKey middleware
// AND should be firewall-restricted to ERP server IP at nginx/server level
Route::middleware('internal.key')->group(function () {
    Route::post('/products/sync',  [App\Http\Controllers\Internal\ProductSyncController::class, 'sync']);
    Route::post('/contacts/sync',  [App\Http\Controllers\Internal\ContactSyncController::class, 'sync']);
});