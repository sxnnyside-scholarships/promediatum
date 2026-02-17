<?php

namespace App\Services\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * PDFExporter — Generates formal academic PDF documents via DomPDF.
 *
 * Uses a dedicated Blade view — NOT the UI layout.
 * Respects template config: orientation, header/footer text,
 * signature line, date format, numeric precision, section toggles.
 */
class PDFExporter implements ExporterInterface
{
    public function export(array $data, ExportContext $context, array $templateConfig = []): string
    {
        $config = array_merge(\App\Models\ExportTemplate::defaultConfig(), $templateConfig);

        $viewData = [
            'data'    => $data,
            'context' => $context,
            'config'  => $config,
            'meta'    => [
                'generated_at' => now()->format($config['date_format'] . ' H:i:s'),
                'type'         => $context->type,
            ],
        ];

        $view = match ($context->type) {
            'group'   => 'exports.pdf.group',
            'student' => 'exports.pdf.student',
            'period'  => 'exports.pdf.period',
            default   => throw new \InvalidArgumentException("Unknown export type: {$context->type}"),
        };

        $orientation = $config['orientation'] === 'landscape' ? 'landscape' : 'portrait';

        $pdf = Pdf::loadView($view, $viewData)
            ->setPaper('letter', $orientation);

        $fileName = $this->generateFileName($context);
        $directory = 'exports';

        Storage::disk('local')->makeDirectory($directory);

        $filePath = storage_path("app/{$directory}/{$fileName}");

        $pdf->save($filePath);

        return $filePath;
    }

    protected function generateFileName(ExportContext $context): string
    {
        $parts = [
            'export',
            $context->type,
            $context->periodId,
        ];

        if ($context->groupId) {
            $parts[] = 'g' . $context->groupId;
        }

        if ($context->studentId) {
            $parts[] = 's' . $context->studentId;
        }

        $parts[] = now()->format('Ymd_His');

        return implode('_', $parts) . '.pdf';
    }
}
