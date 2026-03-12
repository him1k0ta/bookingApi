<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.api')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Rooms
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/rooms/{room}/schedule', [RoomController::class, 'schedule']);
    
    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/my', [BookingController::class, 'userBookings']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    
    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/my', [ReviewController::class, 'userReviews']);
    Route::get('/reviews/can-review/{booking}', [ReviewController::class, 'canReview']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    
    // Room reviews
    Route::get('/rooms/{room}/reviews', [ReviewController::class, 'roomReviews']);
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
    });
});