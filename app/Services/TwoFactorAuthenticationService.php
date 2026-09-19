<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationService
{
    private Google2FA $engine;

    public function __construct(?Google2FA $engine = null)
    {
        $this->engine = $engine ?? new Google2FA;
    }

    /**
     * Generate a new random Base32 secret key.
     */
    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * Get the OTPAuth URL for QR code generation.
     */
    public function getQrCodeUrl(string $company, string $holder, string $secret): string
    {
        return $this->engine->getQRCodeUrl($company, $holder, $secret);
    }

    /**
     * Generate a self-contained SVG string of the QR code.
     */
    public function getQrCodeSvg(string $company, string $holder, string $secret, int $size = 200): string
    {
        $url = $this->getQrCodeUrl($company, $holder, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        $svg = $writer->writeString($url);

        // Strip XML declaration to allow direct inline injection in HTML / Vue
        return trim(preg_replace('/^<\?xml[^>]*\?>/i', '', $svg));
    }

    /**
     * Verify a 6-digit TOTP code against a secret key with a 1-step window tolerance.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = trim($code);

        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        // Window = 1 allows 30 seconds before and after for clock drift
        return (bool) $this->engine->verifyKey($secret, $code, 1);
    }
}
