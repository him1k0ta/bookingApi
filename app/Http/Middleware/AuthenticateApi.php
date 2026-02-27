<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please provide a valid authentication token'
            ], 401);
        }

        return $next($request);
    }
}