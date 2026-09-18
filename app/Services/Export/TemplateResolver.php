<?php

namespace App\Services\Export;

use App\Models\ExportTemplate;
use Illuminate\Support\Facades\Auth;

/**
 * TemplateResolver — Loads and normalizes export template configuration.
 *
 * Responsibilities:
 *  - Load a template by ID (ensuring ownership)
 *  - Fallback to the user's default template for the export type
 *  - Fallback to a sensible system default if no user template exists
 *  - Validate config integrity
 *  - Return a normalized config array so exporters never deal with missing keys
 */
class TemplateResolver
{
    /**
     * Resolve the template config for a given export context.
     *
     * Priority:
     *  1. Explicit template_id on the context
     *  2. User's default template for the export type
     *  3. System-default config (no DB record)
     *
     * @return array{template: ?ExportTemplate, config: array}
     */
    public function resolve(ExportContext $context): array
    {
        $template = null;

        // 1. Explicit template ID
        if ($context->templateId) {
            $template = ExportTemplate::where('id', $context->templateId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Template type must match export type
            if ($template->type !== $context->type) {
                abort(422, "Template type '{$template->type}' does not match export type '{$context->type}'.");
            }
        }

        // 2. User's default for this type
        if (! $template) {
            $template = ExportTemplate::where('user_id', Auth::id())
                ->where('type', $context->type)
                ->where('is_default', true)
                ->first();
        }

        // 3. System-level default (no persisted record)
        $config = $template
            ? $template->normalized_config
            : ExportTemplate::defaultConfig();

        return [
            'template' => $template,
            'config' => $config,
        ];
    }

    /**
     * Validate a config array for structural integrity.
     * Returns an array of error messages (empty = valid).
     */
    public function validateConfig(array $config): array
    {
        $errors = [];
        $defaults = ExportTemplate::defaultConfig();

        // Orientation
        if (isset($config['orientation']) && ! in_array($config['orientation'], ['portrait', 'landscape'], true)) {
            $errors[] = 'orientation must be portrait or landscape.';
        }

        // Numeric precision
        if (isset($config['numeric_precision'])) {
            $p = $config['numeric_precision'];
            if (! is_int($p) || $p < 0 || $p > 6) {
                $errors[] = 'numeric_precision must be an integer between 0 and 6.';
            }
        }

        // Boolean fields
        $boolFields = [
            'include_logo',
            'include_signature_line',
            'include_attendance_summary',
            'include_observations_summary',
            'include_category_breakdown',
        ];
        foreach ($boolFields as $field) {
            if (isset($config[$field]) && ! is_bool($config[$field])) {
                $errors[] = "{$field} must be a boolean.";
            }
        }

        // Array fields
        $arrayFields = ['included_columns', 'column_order'];
        foreach ($arrayFields as $field) {
            if (isset($config[$field]) && ! is_array($config[$field])) {
                $errors[] = "{$field} must be an array.";
            }
        }

        // String-nullable fields
        $stringNullable = ['include_header_text', 'include_footer_text', 'date_format'];
        foreach ($stringNullable as $field) {
            if (array_key_exists($field, $config) && $config[$field] !== null && ! is_string($config[$field])) {
                $errors[] = "{$field} must be a string or null.";
            }
        }

        return $errors;
    }
}
