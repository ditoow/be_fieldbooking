<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Auth;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


Route::get('/fields', [FieldController::class, 'index']);

Route::middleware('auth:api')->group(function () {

    // Field
    Route::post('/fields', [FieldController::class, 'store']);
    Route::get('/fields/{id}', [FieldController::class, 'show']);


    // Schedule
    Route::post('schedules', [ScheduleController::class, 'store']);
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']); 
    
    Route::get('/user', function () {
        return Auth::guard('api')->user();
    });
});