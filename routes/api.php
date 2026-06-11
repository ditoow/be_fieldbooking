<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Field\FieldController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Admin\AdminFieldController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminMaintenanceController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Auth;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('/payment/midtrans-callback', [PaymentController::class, 'handleWebhook']);

Route::get('/fields', [FieldController::class, 'index']);
Route::get('/schedules', [ScheduleController::class, 'index']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/fields/{id}', [FieldController::class, 'show']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/upload', [BookingController::class, 'upload']);
    // Route::post('/upload/dokumen', [UploadController::class, 'uploadDokumen']);
    Route::patch('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/fields', [AdminFieldController::class, 'storeField']);
        Route::patch('/admin/fields/{id}', [AdminFieldController::class, 'updateField']);
        Route::delete('/admin/fields/{id}', [AdminFieldController::class, 'destroyField']);
        Route::get('/admin/bookings', [AdminBookingController::class, 'indexBookings']);
        Route::patch('/admin/bookings/{id}/approve', [AdminBookingController::class, 'approveBooking']);
        Route::patch('/admin/bookings/{id}/reject', [AdminBookingController::class, 'rejectBooking']);
        Route::patch('/admin/bookings/{id}/attend', [AdminBookingController::class, 'attendBooking']);
        Route::patch('/admin/users/{id}/status', [AdminUserController::class, 'updateUserStatus']);
        Route::get('/admin/stats', [AdminStatsController::class, 'getStats']);
        Route::get('/admin/users', [AdminUserController::class, 'indexUsers']);

        // Maintenance routes
        Route::get('/admin/fields/{id}/maintenances', [AdminMaintenanceController::class, 'index']);
        Route::post('/admin/fields/{id}/maintenances', [AdminMaintenanceController::class, 'store']);
        Route::delete('/admin/maintenances/{id}', [AdminMaintenanceController::class, 'destroy']);

        // Route::post('/upload/foto', [UploadController::class, 'uploadFoto']);
    });

    Route::get('/user', function () {
        return Auth::guard('api')->user();
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
});