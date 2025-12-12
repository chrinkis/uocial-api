<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostContoller;
use App\Http\Controllers\PostReportController;
use App\Http\Controllers\PostReportReviewController;
use App\Http\Middleware\UserIsModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')
    ->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login');
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('token/generate', [AuthController::class, 'getToken'])
            ->middleware('throttle:login');
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

Route::prefix('app')
    ->middleware(['auth:sanctum', 'verified'])
    ->group(function () {
        Route::get('posts/saved', [PostContoller::class, 'saved']);

        Route::get('posts/reported', [PostContoller::class, 'reported'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('posts', PostContoller::class)
            ->only(['index', 'show', 'store']);

        Route::post('posts/{post}/react', [PostContoller::class, 'react']);

        Route::post('posts/{post}/save', [PostContoller::class, 'save']);

        Route::post('posts/{post}/unsave', [PostContoller::class, 'unsave']);

        Route::get('posts/{post}/reports', [PostReportController::class, 'index'])
            ->middleware(UserIsModerator::class);

        Route::post('posts/{post}/reports', [PostReportController::class, 'store']);

        Route::apiResource('posts.reports.reviews', PostReportReviewController::class)
            ->only(['store'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('posts.comments', PostCommentController::class)
            ->only(['index', 'store']);

        Route::get('posts/{post}/comments/{postComment}/replies', [PostCommentController::class, 'replies']);

        Route::post('posts/{post}/comments/{postComment}/react', [PostCommentController::class, 'react']);

        Route::post('posts/{post}/comments/{postComment}/report', [PostCommentController::class, 'report']);
    });
