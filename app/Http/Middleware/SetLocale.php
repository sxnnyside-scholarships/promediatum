<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set the application locale from DB (auth) or session (guest).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // Authenticated user: prefer DB-stored locale
        if ($request->user()) {
            $locale = $request->user()->locale;
        }

        // Fallback to session, then config default
        $locale = $locale ?: session('locale', config('app.locale'));

        if (in_array($locale, ['es', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
