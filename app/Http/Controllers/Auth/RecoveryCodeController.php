<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecoveryCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecoveryCodeController extends Controller
{
    public function __construct(
        private RecoveryCodeService $recoveryCodeService
    ) {}

    /**
     * Show recovery codes (only accessible immediately after generation).
     */
    public function show(): Response|RedirectResponse
    {
        $codes = session('recovery_codes');

        if (! $codes) {
            return redirect()->route('profile.index');
        }

        return Inertia::render('Auth/RecoveryCodes', [
            'codes' => $codes,
            'downloadContent' => $this->recoveryCodeService->formatForDownload($codes),
        ]);
    }

    /**
     * Regenerate recovery codes (authenticated users only).
     */
    public function regenerate(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'password' => 'required|string|current_password',
        ]);

        /** @var User $user */
        $user = $request->user();
        $codes = $this->recoveryCodeService->generate($user);
        $downloadContent = $this->recoveryCodeService->formatForDownload($codes);

        session(['recovery_codes' => $codes]);

        if ($request->wantsJson()) {
            return response()->json([
                'codes' => $codes,
                'download_content' => $downloadContent,
                'remaining_count' => count($codes),
                'message' => __('auth.recovery_codes_regenerated_success'),
            ]);
        }

        return redirect()->route('recovery-codes.show');
    }

    /**
     * Clear recovery codes from session (user confirmed they saved them).
     */
    public function acknowledge(Request $request): RedirectResponse
    {
        session()->forget('recovery_codes');

        return redirect()->route('profile.index');
    }
}
