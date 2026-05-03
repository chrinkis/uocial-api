<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostCommentModerationController;
use App\Http\Controllers\PostCommentReportController;
use App\Http\Controllers\PostCommentReportReviewController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostModerationController;
use App\Http\Controllers\PostReportController;
use App\Http\Controllers\PostReportReviewController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfUseController;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Http\Middleware\UserIsModerator;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    // return $request->user();
    return new UserResource($request->user());
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

Route::prefix('legal')->group(function () {
    Route::get('privacy-policy', [PrivacyPolicyController::class, 'show']);
    Route::get('terms-of-use', [TermsOfUseController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('privacy-policy/accept', [PrivacyPolicyController::class, 'accept']);
        Route::post('terms-of-use/accept', [TermsOfUseController::class, 'accept']);
    });
});

Route::prefix('app')
    ->middleware(['auth:sanctum', 'verified', UserHasAcceptedLegalDocuments::class])
    ->group(function () {
        Route::get('posts/saved', [PostController::class, 'saved']);

        Route::get('posts/comments', [PostController::class, 'comments']);

        Route::apiResource('posts', PostController::class)
            ->only(['index', 'show', 'store']);

        Route::post('posts/{post}/react', [PostController::class, 'react']);

        Route::post('posts/{post}/save', [PostController::class, 'save']);

        Route::post('posts/{post}/unsave', [PostController::class, 'unsave']);

        Route::get('posts/{post}/reports', [PostReportController::class, 'index'])
            ->middleware(UserIsModerator::class);

        Route::post('posts/{post}/reports', [PostReportController::class, 'store']);

        Route::apiResource('posts.reports.reviews', PostReportReviewController::class)
            ->only(['store'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('posts.moderations', PostModerationController::class)
            ->only(['store'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('posts.comments', PostCommentController::class)
            ->only(['index', 'store']);

        Route::get('posts/{post}/comments/{postComment}/replies', [PostCommentController::class, 'replies']);

        Route::post('posts/{post}/comments/{postComment}/react', [PostCommentController::class, 'react']);

        Route::get('posts/{post}/comments/{postComment}/reports', [PostCommentReportController::class, 'index'])
            ->middleware(UserIsModerator::class);

        Route::post('posts/{post}/comments/{postComment}/reports', [PostCommentReportController::class, 'store']);

        Route::get('posts/{post}/comments/{postComment}/trace', [PostCommentController::class, 'trace'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('posts.comments.reports.reviews', PostCommentReportReviewController::class)
            ->only(['store'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('posts.comments.moderations', PostCommentModerationController::class)
            ->only(['store'])
            ->middleware(UserIsModerator::class);
    });
