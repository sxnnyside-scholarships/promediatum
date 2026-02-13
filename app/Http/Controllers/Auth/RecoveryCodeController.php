<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecoveryCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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
            return redirect()->route('workspace');
        }

        return Inertia::render('Auth/RecoveryCodes', [
            'codes' => $codes,
            'downloadContent' => $this->recoveryCodeService->formatForDownload($codes),
        ]);
    }

    /**
     * Regenerate recovery codes (authenticated users only).
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|current_password',
        ]);

        $codes = $this->recoveryCodeService->generate($request->user());

        session(['recovery_codes' => $codes]);

        return redirect()->route('recovery-codes.show');
    }

    /**
     * Clear recovery codes from session (user confirmed they saved them).
     */
    public function acknowledge(): RedirectResponse
    {
        session()->forget('recovery_codes');

        return redirect()->route('workspace');
    }
}
