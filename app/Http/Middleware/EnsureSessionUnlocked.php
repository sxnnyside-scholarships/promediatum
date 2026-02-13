<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionUnlocked
{
    /**
     * Redirect to unlock screen if session is locked.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_locked) {
            return redirect()->route('unlock');
        }

        return $next($request);
    }
}
