<?php

namespace App\Http\Middleware;

use App\Exceptions\UserBannedException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserIsNotBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ban = Auth::user()?->activeBan();

        if ($ban !== null) {
            throw new UserBannedException($ban);
        }

        return $next($request);
    }
}
