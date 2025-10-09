<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')
    ->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('token/generate', [AuthController::class, 'getToken']);
        Route::post('token/revoke', [AuthController::class, 'revokeToken'])
            ->middleware('auth:sanctum');
        Route::post('register', [AuthController::class, 'register']);
        Route::post('password/forgot', [AuthController::class, 'forgotPassword'])
            ->middleware('guest')
            ->name('password.email');
        Route::post('password/reset', [AuthController::class, 'resetPassword'])
            ->middleware('guest')
            ->name('password.update');
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['auth', 'signed'])
            ->name('verification.verify');
        Route::post('/email/resend-verification', [AuthController::class, 'resendVerification'])
            ->middleware(['auth', 'throttle:email-verification'])
            ->name('verification.send');
    });
