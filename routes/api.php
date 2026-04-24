<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FieldController;
use Illuminate\Support\Facades\Auth;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


Route::get('/fields', [FieldController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('/fields', [FieldController::class, 'store']);
    Route::get('/user', function () {
        return Auth::guard('api')->user();
    });
});