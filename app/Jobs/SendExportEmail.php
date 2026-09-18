<?php

namespace App\Jobs;

use App\Models\ExportHistory;
use App\Models\SmtpSetting;
use App\Services\Export\SMTPMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendExportEmail — Queued job to email an export file.
 *
 * Dispatched after the export pipeline generates a file.
 * Uses the user's personal SMTP settings (not app mailer).
 * Retries up to 3 times with exponential backoff.
 */
class SendExportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $exportHistoryId,
        public readonly int $smtpSettingId,
        public readonly string $recipientEmail,
    ) {}

    public function handle(SMTPMailer $mailer): void
    {
        $history = ExportHistory::find($this->exportHistoryId);
        $smtp = SmtpSetting::find($this->smtpSettingId);

        if (! $history || ! $smtp) {
            Log::warning('SendExportEmail: missing history or SMTP config', [
                'export_history_id' => $this->exportHistoryId,
                'smtp_setting_id' => $this->smtpSettingId,
            ]);

            return;
        }

        $fullPath = storage_path('app/'.$history->file_path);

        if (! file_exists($fullPath)) {
            Log::warning('SendExportEmail: file not found', [
                'file_path' => $history->file_path,
            ]);

            return;
        }

        $subject = "Promediatum — Export: {$history->file_name}";
        $body = "Attached is your export file generated on {$history->created_at->toDateTimeString()}.\n\nContext: {$history->context_label}";

        $result = $mailer->send(
            smtp: $smtp,
            recipient: $this->recipientEmail,
            filePath: $fullPath,
            fileName: $history->file_name,
            subject: $subject,
            body: $body,
        );

        if ($result['success']) {
            $history->update([
                'sent_via_email' => true,
                'recipient_email' => $this->recipientEmail,
            ]);
        } else {
            Log::error('SendExportEmail: failed to send', [
                'export_history_id' => $this->exportHistoryId,
                'error' => $result['message'],
            ]);

            // Re-throw so the queue can retry
            throw new \RuntimeException("SMTP send failed: {$result['message']}");
        }
    }
}
