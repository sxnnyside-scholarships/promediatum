<?php

namespace App\Services;

use App\Models\RecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RecoveryCodeService
{
    /**
     * Number of recovery codes to generate.
     */
    public const CODE_COUNT = 6;

    /**
     * Length of each recovery code.
     */
    public const CODE_LENGTH = 8;

    /**
     * Generate new recovery codes for a user.
     * Returns the plain-text codes (shown once, never again).
     *
     * @return string[]
     */
    public function generate(User $user): array
    {
        // Delete any existing codes
        $user->recoveryCodes()->delete();

        $plainCodes = [];

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $code = $this->generateCode();
            $plainCodes[] = $code;

            RecoveryCode::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'used' => false,
            ]);
        }

        return $plainCodes;
    }

    /**
     * Validate a recovery code for a user.
     * Returns true if valid and unused. Does NOT consume the code.
     */
    public function validate(User $user, string $code): bool
    {
        $unusedCodes = $user->unusedRecoveryCodes()->get();

        foreach ($unusedCodes as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->code_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Consume a recovery code (mark as used).
     * Returns true if a valid code was found and consumed.
     */
    public function consume(User $user, string $code): bool
    {
        $unusedCodes = $user->unusedRecoveryCodes()->get();

        foreach ($unusedCodes as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->code_hash)) {
                $recoveryCode->markAsUsed();
                return true;
            }
        }

        return false;
    }

    /**
     * Count remaining unused codes for a user.
     */
    public function remainingCount(User $user): int
    {
        return $user->unusedRecoveryCodes()->count();
    }

    /**
     * Check if user has any unused codes left.
     */
    public function hasCodesRemaining(User $user): bool
    {
        return $this->remainingCount($user) > 0;
    }

    /**
     * Generate a single cryptographically random alphanumeric code.
     */
    private function generateCode(): string
    {
        // Use only unambiguous alphanumeric characters
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        $code = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $characters[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Format codes for download as text file.
     */
    public function formatForDownload(array $codes): string
    {
        $header = "PROMEDIATUM — RECOVERY CODES\n";
        $header .= "=============================\n\n";
        $header .= "Store these codes in a safe place.\n";
        $header .= "Each code can only be used ONCE.\n";
        $header .= "These codes will NOT be shown again.\n\n";
        $header .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        $body = '';
        foreach ($codes as $i => $code) {
            $num = $i + 1;
            $body .= "  {$num}. {$code}\n";
        }

        $footer = "\n=============================\n";
        $footer .= "If you lose all codes and forget your password,\n";
        $footer .= "you will not be able to recover your account.\n";

        return $header . $body . $footer;
    }
}
