<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SingleUserAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If local mode is enabled and no explicit lock, allow access automatically
        if (config('invoice.local_mode', true)) {
            return $next($request);
        }

        // If authenticated in session, continue
        if ($request->session()->get('authenticated') === true) {
            return $next($request);
        }

        // Allow login route itself
        if ($request->routeIs('login') || $request->routeIs('login.submit')) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
