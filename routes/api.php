<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('/fields', [FieldController::class, 'index']);
Route::get('/schedules', [ScheduleController::class, 'index']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/fields/{id}', [FieldController::class, 'show']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/upload', [BookingController::class, 'upload']);
    Route::patch('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/fields', [AdminController::class, 'storeField']);
        Route::patch('/admin/fields/{id}', [AdminController::class, 'updateField']);
        Route::delete('/admin/fields/{id}', [AdminController::class, 'destroyField']);
        Route::get('/admin/bookings', [AdminController::class, 'indexBookings']);
        Route::patch('/admin/bookings/{id}/approve', [AdminController::class, 'approveBooking']);
        Route::patch('/admin/bookings/{id}/reject', [AdminController::class, 'rejectBooking']);
        Route::patch('/admin/bookings/{id}/attend', [AdminController::class, 'attendBooking']);
        Route::patch('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
        Route::get('/admin/stats', [AdminController::class, 'getStats']);
        Route::get('/admin/users', [AdminController::class, 'indexUsers']);
    });

    Route::get('/user', function () {
        return Auth::guard('api')->user();
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
});