<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// JWT auth routes (for mobile app)
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);
});

// JWT protected routes
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/validate',  [AuthController::class, 'validate']);
    Route::post('/auth/logout',    [AuthController::class, 'logout']);

    Route::get('/users/me',        [UserController::class, 'me']);
    Route::patch('/users/{id}',    [UserController::class, 'update']);

    Route::get('/products',        [ProductController::class, 'index']);
    Route::get('/products/{id}',   [ProductController::class, 'show']);

    Route::get('/categories',      [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::post('/orders',         [OrderController::class, 'store']);
    Route::get('/orders/{id}',     [OrderController::class, 'show']);
});

