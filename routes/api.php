<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('users')->group(function(){
    Route::options('/user', [WelcomeController::class, 'apiResponse']);
    Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'fetchUser']);
    Route::post('/create', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);
    Route::middleware('auth:sanctum')->post('/update/upi', [UserController::class, 'updatePaymentUpi']);
    Route::post('/otp/send', [OtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
    Route::middleware('auth:sanctum')->post('/password/reset', [UserController::class, 'updatePassword']);
    Route::middleware('auth:sanctum')->get('/refer/list', [UserController::class, 'listReferredUsers']);
});
