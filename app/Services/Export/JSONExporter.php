<?php

namespace App\Services\Export;

use Illuminate\Support\Facades\Storage;

/**
 * JSONExporter — Generates structured JSON files.
 *
 * Output follows a consistent envelope:
 *   { meta, context, data }
 */
class JSONExporter implements ExporterInterface
{
    public function export(array $data, ExportContext $context, array $templateConfig = []): string
    {
        $output = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'type' => $context->type,
                'format' => 'json',
                'version' => '1.0',
            ],
            'context' => $this->buildContextBlock($context),
            'data' => $data,
        ];

        // Add template info to meta if a template was used
        if ($context->templateId) {
            $output['meta']['template_id'] = $context->templateId;
        }
        if (! empty($templateConfig)) {
            $output['meta']['template_config'] = array_intersect_key($templateConfig, array_flip([
                'orientation', 'date_format', 'numeric_precision',
                'include_attendance_summary', 'include_observations_summary', 'include_category_breakdown',
            ]));
        }

        $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode export data as JSON.');
        }

        $fileName = $context->generateFileName();
        $directory = 'exports';

        Storage::disk('local')->makeDirectory($directory);

        $filePath = storage_path("app/{$directory}/{$fileName}");

        file_put_contents($filePath, $json);

        return $filePath;
    }

    protected function buildContextBlock(ExportContext $context): array
    {
        $block = [
            'period_id' => $context->periodId,
        ];

        if ($context->groupId) {
            $block['group_id'] = $context->groupId;
        }

        if ($context->studentId) {
            $block['student_id'] = $context->studentId;
        }

        if (! empty($context->filters)) {
            $block['filters'] = $context->filters;
        }

        return $block;
    }
}
