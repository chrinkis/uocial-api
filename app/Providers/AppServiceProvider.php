<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(function (mixed $notifiable, string $url) {
            $spaUrl = config('app.spa_url');

            assert(is_string($spaUrl));

            $url = $spaUrl.'/auth/email/verify?url='.rawurlencode($url);

            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $url);
        });
    }
}
