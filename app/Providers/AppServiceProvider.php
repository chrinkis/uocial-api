<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (mixed $user, string $token) {
            $spaUrl = config('app.spa_url');

            assert(is_string($spaUrl));

            return $spaUrl.'/auth/password/reset/'.$token;
        });
    }
}
