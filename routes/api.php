<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Field\FieldController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Admin\AdminFieldController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminMaintenanceController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Auth;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:auth');
Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:auth');
Route::post('/payment/midtrans-callback', [PaymentController::class, 'handleWebhook'])->middleware('throttle:20,1');

Route::get('/fields', [FieldController::class, 'index']);
Route::get('/fields/{id}', [FieldController::class, 'show']);
Route::get('/fields/{id}/ratings', [\App\Http\Controllers\Rating\RatingController::class, 'indexFieldRatings']);
Route::get('/schedules', [ScheduleController::class, 'index']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/payment/simulate', [PaymentController::class, 'simulateSuccess']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/upload', [BookingController::class, 'upload']);
    Route::post('/bookings/{id}/notify-payment', [BookingController::class, 'notifyPayment']);
    Route::patch('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/rating', [\App\Http\Controllers\Rating\RatingController::class, 'store']);

    Route::get('/user', function () {
        return Auth::guard('api')->user()->load('roles');
    });

    Route::match(['put', 'post'], '/user/profile', [AuthController::class, 'updateProfile']);
    Route::match(['put', 'post'], '/user/password', [AuthController::class, 'updatePassword']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/reports/pdf', [AdminReportController::class, 'exportPdf']);
        Route::post('/admin/fields', [AdminFieldController::class, 'storeField']);
        Route::patch('/admin/fields/{id}', [AdminFieldController::class, 'updateField']);
        Route::delete('/admin/fields/{id}', [AdminFieldController::class, 'destroyField']);
        Route::get('/admin/bookings', [AdminBookingController::class, 'indexBookings']);
        Route::patch('/admin/bookings/{id}/approve', [AdminBookingController::class, 'approveBooking']);
        Route::patch('/admin/bookings/{id}/reject', [AdminBookingController::class, 'rejectBooking']);
        Route::patch('/admin/bookings/{id}/attend', [AdminBookingController::class, 'attendBooking']);
        Route::patch('/admin/bookings/{id}/cancel', [\App\Http\Controllers\Booking\BookingController::class, 'cancel']);
        Route::patch('/admin/users/{id}/status', [AdminUserController::class, 'updateUserStatus']);
        Route::get('/admin/stats', [AdminStatsController::class, 'getStats']);
        Route::get('/admin/stats/revenue-trend', [AdminStatsController::class, 'getRevenueTrend']);
        Route::get('/admin/activity-logs', [AdminActivityLogController::class, 'index']);
        Route::get('/admin/reports/transactions', [AdminReportController::class, 'getReportTransactions']);
        Route::get('/admin/reports/demographics', [AdminReportController::class, 'getReportDemographics']);
        Route::get('/admin/reports/pdf-data', [AdminReportController::class, 'getPdfReportData']);
        Route::get('/admin/users', [AdminUserController::class, 'indexUsers']);

        Route::get('/admin/fields/{id}/maintenances', [AdminMaintenanceController::class, 'index']);
        Route::post('/admin/fields/{id}/maintenances', [AdminMaintenanceController::class, 'store']);
        Route::delete('/admin/maintenances/{id}', [AdminMaintenanceController::class, 'destroy']);

        Route::post('/upload/foto', [FieldController::class, 'uploadFoto']);
    });
});
