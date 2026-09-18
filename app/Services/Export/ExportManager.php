<?php

namespace App\Services\Export;

use App\Models\ExportHistory;
use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

/**
 * ExportManager — Orchestrates the full export pipeline.
 *
 * 1. Validates ownership / security
 * 2. Resolves dataset via ExportDataResolver
 * 3. Delegates to the appropriate format exporter
 * 4. Records the export in history
 * 5. Returns the file path for download
 *
 * Controllers stay thin — they create an ExportContext and hand it here.
 * Extensible: adding PDF later means adding a PDFExporter + registering it.
 */
class ExportManager
{
    protected array $exporters = [];

    public function __construct(
        protected ExportDataResolver $resolver,
        protected TemplateResolver $templateResolver,
    ) {
        $this->registerDefaultExporters();
    }

    // ────────────────────────────────────────────────
    // PUBLIC API
    // ────────────────────────────────────────────────

    /**
     * Execute the full export pipeline.
     *
     * @return string Absolute path to the generated file.
     *
     * @throws \InvalidArgumentException If format is unsupported.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If IDs are invalid.
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException If ownership check fails.
     */
    public function execute(ExportContext $context): string
    {
        $this->validateOwnership($context);

        // Resolve template configuration
        $templateResult = $this->templateResolver->resolve($context);
        $templateConfig = $templateResult['config'];
        $template = $templateResult['template'];

        $exporter = $this->resolveExporter($context->format);

        $data = $this->resolver->resolve($context);

        $filePath = $exporter->export($data, $context, $templateConfig);

        $this->recordHistory($context, $filePath, $template);

        return $filePath;
    }

    /**
     * Register a custom exporter for a format.
     */
    public function registerExporter(string $format, ExporterInterface $exporter): void
    {
        $this->exporters[$format] = $exporter;
    }

    // ────────────────────────────────────────────────
    // INTERNAL
    // ────────────────────────────────────────────────

    protected function registerDefaultExporters(): void
    {
        $this->exporters['csv'] = new CSVExporter;
        $this->exporters['json'] = new JSONExporter;
        $this->exporters['xlsx'] = new ExcelExporter;
        $this->exporters['pdf'] = new PDFExporter;
    }

    protected function resolveExporter(string $format): ExporterInterface
    {
        if (! isset($this->exporters[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        return $this->exporters[$format];
    }

    /**
     * Validate that the authenticated user owns the requested data.
     *
     * In this single-user system, we verify that referenced IDs exist
     * and are logically consistent (e.g., group belongs to period,
     * student belongs to group in that period).
     */
    protected function validateOwnership(ExportContext $context): void
    {
        // Period must exist
        $period = Period::findOrFail($context->periodId);

        // If group_id specified, it must belong to the period
        if ($context->groupId) {
            $group = Group::where('id', $context->groupId)
                ->where('period_id', $context->periodId)
                ->firstOrFail();

            // If student_id specified, student must belong to the group in that period
            if ($context->studentId) {
                $enrolled = $group->students()
                    ->wherePivot('period_id', $context->periodId)
                    ->where('students.id', $context->studentId)
                    ->exists();

                if (! $enrolled) {
                    abort(403, 'Student is not enrolled in this group for the specified period.');
                }
            }
        }

        // Type-specific validation
        match ($context->type) {
            'group' => $context->groupId ?: abort(422, 'group_id is required for group exports.'),
            'student' => ($context->groupId && $context->studentId) ?: abort(422, 'group_id and student_id are required for student exports.'),
            'period' => true,
            default => abort(422, "Invalid export type: {$context->type}"),
        };
    }

    protected function recordHistory(ExportContext $context, string $filePath, ?\App\Models\ExportTemplate $template = null): void
    {
        $fileName = basename($filePath);
        $relativePath = 'exports/'.$fileName;

        ExportHistory::create([
            'user_id' => Auth::id(),
            'type' => $context->type,
            'format' => $context->format,
            'period_id' => $context->periodId,
            'group_id' => $context->groupId,
            'student_id' => $context->studentId,
            'template_id' => $template?->id,
            'file_name' => $fileName,
            'file_path' => $relativePath,
        ]);
    }
}
