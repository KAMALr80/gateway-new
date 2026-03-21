<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InternalApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-Internal-Key');

        if (!$key || $key !== config('services.erp.internal_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}