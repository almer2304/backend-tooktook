<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->group(function(){
        Route::apiResource('/store', StoreController::class);
        Route::apiResource('/camera', CameraController::class)->except('show', 'create');
    });

    Route::middleware('role:user')->group(function(){
        Route::apiResource('/all/store', StoreController::class)->only('index');
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);

        Route::put('/payments/{payment}/pay', [PaymentController::class, 'pay']);
    });
});