<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecoveryCodeService;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private TwoFactorAuthenticationService $twoFactorService,
        private RecoveryCodeService $recoveryCodeService
    ) {}

    /**
     * Show the two-factor authentication challenge view.
     */
    public function create(): Response|RedirectResponse
    {
        if (! session('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verify the two-factor code or recovery code and log the user in.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = session('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        if ($request->filled('recovery_code')) {
            $request->validate([
                'recovery_code' => ['required', 'string'],
            ]);

            $consumed = $this->recoveryCodeService->consume($user, $request->recovery_code);

            if (! $consumed) {
                throw ValidationException::withMessages([
                    'recovery_code' => [__('auth.recovery_code_invalid')],
                ]);
            }
        } else {
            $request->validate([
                'code' => ['required', 'string'],
            ]);

            if (! $user->two_factor_secret || ! $this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
                throw ValidationException::withMessages([
                    'code' => [__('auth.two_factor_code_invalid')],
                ]);
            }
        }

        Auth::login($user, session('login.remember', false));

        session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        $user->update(['is_locked' => false]);

        return redirect()->intended(route('workspace', absolute: false));
    }

    /**
     * Cancel the two-factor challenge and return to login.
     */
    public function destroy(): RedirectResponse
    {
        session()->forget(['login.id', 'login.remember']);

        return redirect()->route('login');
    }
}
