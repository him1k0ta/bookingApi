<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);

// Защищенные маршруты - используем наш новый middleware
Route::middleware('auth.api')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Bookings
    Route::get('/my-bookings', [BookingController::class, 'userBookings']);
    Route::apiResource('/bookings', BookingController::class)->except(['create', 'edit']);
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    
    // Reviews
    Route::apiResource('/reviews', ReviewController::class)->except(['index', 'create', 'edit']);
    
    // Room schedule
    Route::get('/rooms/{room}/schedule', [RoomController::class, 'schedule']);
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::apiResource('/rooms', RoomController::class)->except(['index', 'show']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
    });
});