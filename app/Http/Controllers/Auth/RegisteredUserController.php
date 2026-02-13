<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecoveryCodeService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private RecoveryCodeService $recoveryCodeService
    ) {}

    /**
     * Display the registration view.
     * Blocked if a user already exists (single-user system).
     */
    public function create(): Response|RedirectResponse
    {
        if (User::exists()) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Single-user guard
        if (User::exists()) {
            abort(403, 'Registration is disabled. A user already exists.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'institution' => 'nullable|string|max:255',
            'pronoun' => 'nullable|string|in:él,ella,elle',
            'educational_area' => 'nullable|string|max:255',
            'educational_level' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'institution' => $validated['institution'] ?? null,
            'pronoun' => $validated['pronoun'] ?? null,
            'educational_area' => $validated['educational_area'] ?? null,
            'educational_level' => $validated['educational_level'] ?? null,
        ]);

        // Generate recovery codes
        $plainCodes = $this->recoveryCodeService->generate($user);

        event(new Registered($user));

        Auth::login($user);

        // Store codes in session temporarily for the recovery codes display page
        session(['recovery_codes' => $plainCodes]);

        return redirect()->route('recovery-codes.show');
    }
}
