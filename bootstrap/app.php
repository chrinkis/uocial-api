<?php

use App\Exceptions\UserBannedException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->trustHosts(at: fn () => config('app.trusted_hosts'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(UserBannedException::class);

        $exceptions->render(function (UserBannedException $exception) {
            return response()->json([
                'message' => 'Your account has been banned.',
                'reason' => $exception->ban->reason,
                'expires_at' => $exception->ban->expires_at,
            ], 403);
        });
    })->create();
