<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RentalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/* ================= AUTH ================= */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){

    Route::post('/logout', [AuthController::class, 'logout']);

    /* ================= ADMIN ================= */
    Route::prefix('admin')->middleware('role:admin')->group(function(){

        // Store & Camera
        Route::apiResource('/store', StoreController::class);
        Route::apiResource('/camera', CameraController::class)->except(['show', 'create']);
        
        // Monitoring
        Route::get('/rentals', [RentalController::class, 'index']);
        Route::get('/rentals/{rental}', [RentalController::class, 'show']);
        Route::post('/rentals/{rental}/approve', [RentalController::class, 'approve']);
        Route::post('/rentals/{rental}/return', [RentalController::class, 'returnCamera']);
        
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    });

    /* ================= USER ================= */
    Route::middleware('role:user')->group(function(){

        // Lihat semua store
        Route::get('/all/store', [StoreController::class, 'index']);

        Route::get('/all/camera', [CameraController::class, 'index']);

        Route::get('/count/camera', [CameraController::class, 'count']);

        // Rental
        Route::post('/rentals', [RentalController::class, 'store']);
        Route::get('/rentals', [RentalController::class, 'index']);
        Route::get('/rentals/{rental}', [RentalController::class, 'show']);
        Route::post('/rentals/return/{rental}', [RentalController::class, 'returnCamera']);


        // Payment
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        Route::put('/payments/{payment}/pay', [PaymentController::class, 'pay']);
    });
});
