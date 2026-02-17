<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmtpSettingsRequest;
use App\Models\SmtpSetting;
use App\Services\Export\SMTPMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SmtpSettingsController extends Controller
{
    /**
     * GET /settings/smtp — Return current SMTP config as JSON.
     */
    public function show(): JsonResponse
    {
        $smtp = SmtpSetting::where('user_id', Auth::id())->first();

        if (! $smtp) {
            return response()->json([
                'configured' => false,
                'settings'   => null,
            ]);
        }

        return response()->json([
            'configured' => $smtp->isConfigured(),
            'verified'   => $smtp->verified,
            'settings'   => [
                'host'       => $smtp->host,
                'port'       => $smtp->port,
                'username'   => $smtp->username,
                'encryption' => $smtp->encryption,
                'from_name'  => $smtp->from_name,
                'from_email' => $smtp->from_email,
                // Password is never sent back to the client
            ],
        ]);
    }

    /**
     * PUT /settings/smtp — Create or update SMTP configuration.
     */
    public function update(SmtpSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        SmtpSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            array_merge($data, [
                'verified' => false, // reset verification on config change
            ]),
        );

        return back()->with('smtp_status', 'saved');
    }

    /**
     * POST /settings/smtp/test — Test SMTP connection.
     */
    public function test(SMTPMailer $mailer): JsonResponse
    {
        $smtp = SmtpSetting::where('user_id', Auth::id())->first();

        if (! $smtp || ! $smtp->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP is not configured. Please save your settings first.',
            ], 422);
        }

        $result = $mailer->testConnection($smtp);

        if ($result['success']) {
            $smtp->update(['verified' => true]);
        }

        return response()->json($result);
    }
}
