<?php

namespace App\Services\Export;

/**
 * Contracts\ExporterInterface — Common contract for all format exporters.
 *
 * Each exporter receives resolved data + context and returns
 * a full file path on disk. The ExportManager orchestrates the
 * lifecycle: resolve → export → record history → return download.
 */
interface ExporterInterface
{
    /**
     * Generate the export file and return its absolute path.
     *
     * @param  array        $data           Resolved dataset from ExportDataResolver.
     * @param  ExportContext $context        Export parameters.
     * @param  array        $templateConfig Normalized template configuration (optional).
     */
    public function export(array $data, ExportContext $context, array $templateConfig = []): string;
}
