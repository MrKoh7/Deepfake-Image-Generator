<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'You must login first.');
        }

        if (Auth::user()->role !== 'user') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}
