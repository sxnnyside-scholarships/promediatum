<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response|RedirectResponse
    {
        // If authenticated and locked, redirect to unlock page
        if (Auth::check() && Auth::user()->is_locked) {
            return redirect()->route('unlock');
        }

        // If authenticated and not locked, go to workspace
        if (Auth::check()) {
            return redirect()->intended(route('workspace', absolute: false));
        }

        // Show login form — pass whether a user account exists
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'userExists' => User::exists(),
        ]);
    }

    /**
     * Display the unlock screen.
     */
    public function showUnlock(): Response|RedirectResponse
    {
        // If not authenticated, redirect to login
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // If authenticated but not locked, redirect to workspace
        if (! Auth::user()->is_locked) {
            return redirect()->intended(route('workspace', absolute: false));
        }

        // Show unlock screen
        return Inertia::render('Auth/Unlock', [
            'user' => [
                'first_name' => Auth::user()->first_name,
                'greeting' => Auth::user()->localized_greeting,
            ],
        ]);
    }

    /**
     * Handle an incoming authentication request (login).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Ensure session is unlocked on login
        $request->user()->update(['is_locked' => false]);

        return redirect()->intended(route('workspace', absolute: false));
    }

    /**
     * Handle session unlock (password re-entry while session persists).
     */
    public function unlock(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (! $user || ! Auth::validate(['email' => $user->email, 'password' => $request->password])) {
            return back()->withErrors([
                'password' => __('auth.password'),
            ]);
        }

        $user->update(['is_locked' => false]);

        return redirect()->intended(route('workspace', absolute: false));
    }

    /**
     * Lock the current session (keep session alive, require password to unlock).
     */
    public function lock(Request $request): RedirectResponse
    {
        $request->user()->update(['is_locked' => true]);

        return redirect()->route('unlock');
    }

    /**
     * Destroy an authenticated session (full logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
