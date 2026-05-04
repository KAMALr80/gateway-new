<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            try {
                auth('api')->authenticate();
            } catch (\Throwable) {
                // Invalid or expired token — continue as guest
            }
        }

        return $next($request);
    }
}
