<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecoveryCodeService;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        private TwoFactorAuthenticationService $twoFactorService,
        private RecoveryCodeService $recoveryCodeService
    ) {}

    /**
     * Initialize 2FA setup by generating a secret and QR code SVG.
     */
    public function setup(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $secret = $this->twoFactorService->generateSecretKey();
        $appName = config('app.name', 'Promediatum');
        $svg = $this->twoFactorService->getQrCodeSvg($appName, $user->email, $secret);

        session(['two_factor_setup_secret' => $secret]);

        return response()->json([
            'secret' => $secret,
            'qr_svg' => $svg,
        ]);
    }

    /**
     * Confirm and activate 2FA with a TOTP verification code.
     */
    public function confirm(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $secret = session('two_factor_setup_secret');

        if (! $secret) {
            throw ValidationException::withMessages([
                'code' => [__('auth.two_factor_setup_expired')],
            ]);
        }

        if (! $this->twoFactorService->verify($secret, $request->code)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.two_factor_code_invalid')],
            ]);
        }

        /** @var User $user */
        $user = $request->user();
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        session()->forget('two_factor_setup_secret');

        // Ensure user has recovery codes generated
        if (! $this->recoveryCodeService->hasCodesRemaining($user)) {
            $plainCodes = $this->recoveryCodeService->generate($user);
            session(['recovery_codes' => $plainCodes]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'two-factor-enabled',
                'message' => __('auth.two_factor_enabled_success'),
            ]);
        }

        return back()->with('status', 'two-factor-enabled');
    }

    /**
     * Disable 2FA for the user (requires current password).
     */
    public function disable(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'two-factor-disabled',
                'message' => __('auth.two_factor_disabled_success'),
            ]);
        }

        return back()->with('status', 'two-factor-disabled');
    }
}
