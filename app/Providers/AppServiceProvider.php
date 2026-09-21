<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
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
        User::observe(UserObserver::class);

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
                ->action('Verify Email Address', $url)
                ->line('If you didn\'t signed up for uocial.gr, please ignore this email!');
        });

        RateLimiter::for('email-verification', function (Request $request) {
            assert($request->user() !== null);

            return Limit::perMinutes(3, 1)
                ->by($request->user()->id);
        });

        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email', '');
            assert(is_string($email));
            $email = strtolower(trim($email));

            return [
                Limit::perMinutes(15, 5)
                    ->by('email:'.$email),
                Limit::perMinute(20)
                    ->by('ip:'.$request->ip()),
            ];
        });
    }
}
