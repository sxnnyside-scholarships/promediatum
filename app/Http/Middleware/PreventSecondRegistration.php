<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSecondRegistration
{
    /**
     * Block registration routes if a user already exists.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (User::exists()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
