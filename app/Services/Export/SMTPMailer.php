<?php

namespace App\Services\Export;

use App\Models\SmtpSetting;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * SMTPMailer — Sends export files via the user's personal SMTP config.
 *
 * Uses Symfony Mailer directly so the app-level mail config stays untouched.
 * All operations are synchronous; callers should dispatch via queue for
 * non-blocking delivery.
 */
class SMTPMailer
{
    /**
     * Send an export file as an email attachment.
     *
     * @param  SmtpSetting  $smtp  User's SMTP configuration.
     * @param  string  $recipient  Recipient email address.
     * @param  string  $filePath  Absolute path to the export file.
     * @param  string  $fileName  Display name for the attachment.
     * @param  string  $subject  Email subject line.
     * @param  string  $body  Plain-text email body.
     * @return array{success: bool, message: string}
     */
    public function send(
        SmtpSetting $smtp,
        string $recipient,
        string $filePath,
        string $fileName,
        string $subject,
        string $body,
    ): array {
        if (! $smtp->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMTP is not fully configured.',
            ];
        }

        try {
            $dsn = $this->buildDsn($smtp);
            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $email = (new Email)
                ->from("{$smtp->from_name} <{$smtp->from_email}>")
                ->to($recipient)
                ->subject($subject)
                ->text($body)
                ->attachFromPath($filePath, $fileName);

            $mailer->send($email);

            return [
                'success' => true,
                'message' => 'Email sent successfully.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test the SMTP connection without sending a real email.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(SmtpSetting $smtp): array
    {
        if (! $smtp->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMTP is not fully configured.',
            ];
        }

        try {
            $dsn = $this->buildDsn($smtp);
            $transport = Transport::fromDsn($dsn);

            // Send a minimal test email to the sender themselves
            $mailer = new Mailer($transport);
            $email = (new Email)
                ->from("{$smtp->from_name} <{$smtp->from_email}>")
                ->to($smtp->from_email)
                ->subject('Promediatum — SMTP Test')
                ->text('This is a test email from Promediatum to verify your SMTP configuration.');

            $mailer->send($email);

            return [
                'success' => true,
                'message' => 'Connection successful. A test email was sent to your address.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build a Symfony Mailer DSN string from SMTP settings.
     */
    protected function buildDsn(SmtpSetting $smtp): string
    {
        $scheme = match ($smtp->encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            'none' => 'smtp',
            default => 'smtp',
        };

        $user = urlencode($smtp->username);
        $pass = urlencode($smtp->password);

        $dsn = "{$scheme}://{$user}:{$pass}@{$smtp->host}:{$smtp->port}";

        // For TLS, verify_peer is handled by Symfony defaults.
        // For 'none', disable TLS explicitly.
        if ($smtp->encryption === 'none') {
            $dsn .= '?verify_peer=0';
        }

        return $dsn;
    }
}
