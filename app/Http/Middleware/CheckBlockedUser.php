<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBlocked()) {
            Auth::logout();

            return redirect()->route('login')->with('error', 'Your account has been blocked. Please contact support for assistance.');
        }

        return $next($request);
    }
}
