<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    /**
     * Switch application locale.
     * Persists to DB for authenticated users, session for guests.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:es,en',
        ]);

        $locale = $validated['locale'];

        // Always store in session
        session(['locale' => $locale]);

        // Persist to DB if authenticated
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }

        return back();
    }
}
