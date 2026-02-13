<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the profile page.
     */
    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile/Index', [
            'user' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'pronoun' => $user->pronoun,
                'institution' => $user->institution,
                'educational_area' => $user->educational_area,
                'educational_level' => $user->educational_level,
            ],
        ]);
    }

    /**
     * Update account profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/',
            ],
            'institution' => 'nullable|string|max:255',
            'pronoun' => 'nullable|string|in:él,ella,elle',
            'educational_area' => 'nullable|string|max:255',
            'educational_level' => 'nullable|string|max:255',
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'profile-updated');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string|current_password',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-zA-Z]/',
                'regex:/[0-9]/',
            ],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
