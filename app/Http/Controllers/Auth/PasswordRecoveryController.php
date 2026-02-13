<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecoveryCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PasswordRecoveryController extends Controller
{
    public function __construct(
        private RecoveryCodeService $recoveryCodeService
    ) {}

    /**
     * Show the forgot password form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Verify email and recovery code, then show reset form.
     */
    public function verify(Request $request): RedirectResponse|Response
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'recovery_code' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $this->recoveryCodeService->validate($user, $validated['recovery_code'])) {
            return back()->withErrors([
                'recovery_code' => __('auth.recovery_code_invalid'),
            ]);
        }

        // Store verified state in session for the reset form
        session([
            'password_recovery_user_id' => $user->id,
            'password_recovery_code' => $validated['recovery_code'],
        ]);

        return redirect()->route('password.reset');
    }

    /**
     * Show the password reset form (after successful code verification).
     */
    public function showReset(): Response|RedirectResponse
    {
        if (! session('password_recovery_user_id')) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPassword');
    }

    /**
     * Handle the password reset.
     */
    public function reset(Request $request): RedirectResponse
    {
        $userId = session('password_recovery_user_id');
        $code = session('password_recovery_code');

        if (! $userId || ! $code) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user = User::findOrFail($userId);

        // Consume the recovery code (mark as used permanently)
        $consumed = $this->recoveryCodeService->consume($user, $code);

        if (! $consumed) {
            return redirect()->route('password.request')->withErrors([
                'recovery_code' => __('auth.recovery_code_invalid'),
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Clear recovery session data
        session()->forget(['password_recovery_user_id', 'password_recovery_code']);

        return redirect()->route('login')->with('status', __('auth.password_reset_success'));
    }
}
