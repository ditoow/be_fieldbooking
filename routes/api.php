<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AdminBookingController;
use Illuminate\Support\Facades\Auth;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


Route::get('/fields', [FieldController::class, 'index']);
Route::get('/schedules', [ScheduleController::class, 'index']);

Route::middleware('auth:api')->group(function () {

    // Field
    Route::post('/fields', [FieldController::class, 'store']);
    Route::get('/fields/{id}', [FieldController::class, 'show']);

    // Booking - User
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/upload', [BookingController::class, 'upload']);

    // Booking - Admin
    Route::get('/admin/bookings', [AdminBookingController::class, 'index']);
    Route::patch('/admin/bookings/{id}/approve', [AdminBookingController::class, 'approve']);
    Route::patch('/admin/bookings/{id}/reject', [AdminBookingController::class, 'reject']);
    Route::patch('/admin/bookings/{id}/attend', [AdminBookingController::class, 'attend']);

    Route::get('/user', function () {
        return Auth::guard('api')->user();
    });
});