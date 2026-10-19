<?php

use App\Http\Controllers\AltchaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BanThreadController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseOfferingController;
use App\Http\Controllers\CourseOfferingReportController;
use App\Http\Controllers\CourseOfferingReviewController;
use App\Http\Controllers\CourseOfferingReviewModerationController;
use App\Http\Controllers\CourseReportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentReportController;
use App\Http\Controllers\ModerationCountsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostCommentModerationController;
use App\Http\Controllers\PostCommentReportController;
use App\Http\Controllers\PostCommentReportReviewController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostModerationController;
use App\Http\Controllers\PostPollController;
use App\Http\Controllers\PostReportController;
use App\Http\Controllers\PostReportReviewController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\ProfessorReportController;
use App\Http\Controllers\TermsOfUseController;
use App\Http\Controllers\UserBanController;
use App\Http\Controllers\UserBanMessageController;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Http\Middleware\UserIsAdmin;
use App\Http\Middleware\UserIsModerator;
use App\Http\Middleware\UserIsNotBanned;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    // return $request->user();
    return new UserResource($request->user());
})->middleware('auth:sanctum');

Route::get('altcha/challenge', [AltchaController::class, 'challenge']);

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

    Route::middleware(['auth:sanctum', UserIsNotBanned::class])->group(function () {
        Route::post('privacy-policy/accept', [PrivacyPolicyController::class, 'accept']);
        Route::post('terms-of-use/accept', [TermsOfUseController::class, 'accept']);
    });
});

// Reachable while banned: the ban conversation only.
Route::prefix('app')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('bans/{ban}/messages', [UserBanMessageController::class, 'index']);

        Route::post('bans/{ban}/messages', [UserBanMessageController::class, 'store'])
            ->middleware('throttle:ban-messages');

        Route::middleware(UserIsAdmin::class)->group(function () {
            Route::get('bans', [UserBanController::class, 'list']);

            Route::post('bans/{ban}/thread/close', [BanThreadController::class, 'close']);

            Route::post('bans/{ban}/thread/reopen', [BanThreadController::class, 'reopen']);
        });
    });

Route::prefix('app')
    ->middleware(['auth:sanctum', UserIsNotBanned::class, 'verified', UserHasAcceptedLegalDocuments::class])
    ->group(function () {
        Route::get('posts/saved', [PostController::class, 'saved']);

        Route::get('moderation-counts', [ModerationCountsController::class, 'show'])
            ->middleware(UserIsModerator::class);

        Route::get('notifications', [NotificationController::class, 'index']);

        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);

        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);

        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);

        Route::get('posts/comments', [PostController::class, 'comments']);

        Route::get('posts/search', [PostController::class, 'search'])
            ->middleware('throttle:posts-search');

        Route::apiResource('posts', PostController::class)
            ->only(['index', 'show', 'store']);

        Route::post('posts/{post}/react', [PostController::class, 'react']);

        Route::post('posts/{post}/save', [PostController::class, 'save']);

        Route::post('posts/{post}/unsave', [PostController::class, 'unsave']);

        Route::post('posts/{post}/subscribe', [PostController::class, 'subscribe']);

        Route::post('posts/{post}/unsubscribe', [PostController::class, 'unsubscribe']);

        Route::post('posts/{post}/poll/{poll}/vote/{option}', [PostPollController::class, 'vote'])->scopeBindings();

        Route::post('posts/{post}/poll/{poll}/unvote/{option}', [PostPollController::class, 'unvote'])->scopeBindings();

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

        Route::post('posts/{post}/comments/{postComment}/subscribe', [PostCommentController::class, 'subscribe']);

        Route::post('posts/{post}/comments/{postComment}/unsubscribe', [PostCommentController::class, 'unsubscribe']);

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

        Route::apiResource('departments', DepartmentController::class)
            ->only(['index', 'show']);

        Route::apiResource('departments', DepartmentController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('departments.courses', CourseController::class)
            ->scoped()
            ->only(['index', 'show']);

        Route::apiResource('departments.courses', CourseController::class)
            ->scoped()
            ->only(['store', 'update', 'destroy'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('departments.courses.offerings', CourseOfferingController::class)
            ->scoped()
            ->only(['index', 'show']);

        Route::apiResource('departments.courses.offerings', CourseOfferingController::class)
            ->scoped()
            ->only(['store', 'update', 'destroy'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('departments.professors', ProfessorController::class)
            ->scoped()
            ->only(['index', 'show']);

        Route::apiResource('departments.professors', ProfessorController::class)
            ->scoped()
            ->only(['store', 'update', 'destroy'])
            ->middleware(UserIsModerator::class);

        Route::apiResource('departments.courses.offerings.reviews', CourseOfferingReviewController::class)
            ->scoped();

        Route::apiResource('departments.courses.offerings.reviews.moderations', CourseOfferingReviewModerationController::class)
            ->scoped()
            ->only(['store'])
            ->middleware(UserIsModerator::class);

        Route::get('departments/{department}/reports', [DepartmentReportController::class, 'index'])
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/reports/{report}/resolution', [DepartmentReportController::class, 'resolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::delete('departments/{department}/reports/{report}/resolution', [DepartmentReportController::class, 'unresolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/reports', [DepartmentReportController::class, 'store'])
            ->middleware('throttle:academic-reports');

        Route::get('departments/{department}/courses/{course}/reports', [CourseReportController::class, 'index'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/courses/{course}/reports/{report}/resolution', [CourseReportController::class, 'resolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::delete('departments/{department}/courses/{course}/reports/{report}/resolution', [CourseReportController::class, 'unresolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/courses/{course}/reports', [CourseReportController::class, 'store'])
            ->scopeBindings()
            ->middleware('throttle:academic-reports');

        Route::get('departments/{department}/professors/{professor}/reports', [ProfessorReportController::class, 'index'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/professors/{professor}/reports/{report}/resolution', [ProfessorReportController::class, 'resolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::delete('departments/{department}/professors/{professor}/reports/{report}/resolution', [ProfessorReportController::class, 'unresolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/professors/{professor}/reports', [ProfessorReportController::class, 'store'])
            ->scopeBindings()
            ->middleware('throttle:academic-reports');

        Route::get('departments/{department}/courses/{course}/offerings/{offering}/reports', [CourseOfferingReportController::class, 'index'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/courses/{course}/offerings/{offering}/reports/{report}/resolution', [CourseOfferingReportController::class, 'resolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::delete('departments/{department}/courses/{course}/offerings/{offering}/reports/{report}/resolution', [CourseOfferingReportController::class, 'unresolve'])
            ->scopeBindings()
            ->middleware(UserIsModerator::class);

        Route::post('departments/{department}/courses/{course}/offerings/{offering}/reports', [CourseOfferingReportController::class, 'store'])
            ->scopeBindings()
            ->middleware('throttle:academic-reports');

        Route::apiResource('users.bans', UserBanController::class)
            ->only(['index', 'store'])
            ->middleware(UserIsAdmin::class);

        Route::post('users/{user}/unban', [UserBanController::class, 'unban'])
            ->middleware(UserIsAdmin::class);
    });
