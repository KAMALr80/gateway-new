<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Public
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);
});

// Protected
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/auth/validate', [AuthController::class, 'validate']);
    Route::post('/auth/logout',   [AuthController::class, 'logout']);

    // User
    Route::get('/users/me',      [UserController::class, 'me']);
    Route::patch('/users/{id}',  [UserController::class, 'update']);

    // Products
    Route::get('/products',         [ProductController::class, 'index']);
    Route::get('/products/{id}',    [ProductController::class, 'show']);

    // Categories
    Route::get('/categories',       [CategoryController::class, 'index']);
    Route::get('/categories/{id}',  [CategoryController::class, 'show']);

    // Brands
    Route::get('/brands',      [BrandController::class, 'index']);
    Route::get('/brands/{id}', [BrandController::class, 'show']);

    // Orders
    Route::post('/orders',          [OrderController::class, 'store']);
    Route::get('/orders/{id}',      [OrderController::class, 'show']);
    Route::get('/orders',           [OrderController::class, 'index']);

    // Addresses
    Route::get('/addresses',        [AddressController::class, 'index']);
    Route::post('/addresses',       [AddressController::class, 'store']);
    Route::get('/addresses/{id}',   [AddressController::class, 'show']);
    Route::patch('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}',[AddressController::class, 'destroy']);
});