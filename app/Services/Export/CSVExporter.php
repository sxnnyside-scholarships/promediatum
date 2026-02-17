<?php

namespace App\Services\Export;

use Illuminate\Support\Facades\Storage;

/**
 * CSVExporter — Generates UTF-8 CSV files.
 *
 * Flattens structured data into tabular rows. Compatible with
 * Excel, Google Sheets, and LibreOffice Calc.
 */
class CSVExporter implements ExporterInterface
{
    public function __construct(
        protected string $delimiter = ',',
    ) {}

    public function export(array $data, ExportContext $context, array $templateConfig = []): string
    {
        $rows = $this->flatten($data, $context);

        if (empty($rows)) {
            throw new \RuntimeException('No data to export.');
        }

        // Apply template column filtering and ordering
        if (! empty($templateConfig['included_columns'])) {
            $rows = $this->filterColumns($rows, $templateConfig['included_columns']);
        }
        if (! empty($templateConfig['column_order'])) {
            $rows = $this->reorderColumns($rows, $templateConfig['column_order']);
        }

        $fileName = $context->generateFileName();
        $directory = 'exports';

        Storage::disk('local')->makeDirectory($directory);

        $filePath = storage_path("app/{$directory}/{$fileName}");

        $handle = fopen($filePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to create export file: {$filePath}");
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Header row
        fputcsv($handle, array_keys($rows[0]), $this->delimiter);

        // Data rows
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), $this->delimiter);
        }

        fclose($handle);

        return $filePath;
    }

    /**
     * Flatten the resolved data into an array of flat associative rows.
     */
    protected function flatten(array $data, ExportContext $context): array
    {
        return match ($context->type) {
            'group'   => $this->flattenGroup($data),
            'student' => $this->flattenStudent($data),
            'period'  => $this->flattenPeriod($data),
            default   => [],
        };
    }

    protected function flattenGroup(array $data): array
    {
        $rows = [];
        $group = $data['group'];
        $period = $data['period'];

        foreach ($data['students'] as $student) {
            $rows[] = [
                'Group'            => $group['name'],
                'Subject'          => $group['subject'] ?? '',
                'Period'           => $period['name'],
                'Student'          => $student['full_name'],
                'Weighted Average' => $student['weighted_average'] ?? '',
                'Attendance Rate'  => $student['attendance_rate'] !== null ? $student['attendance_rate'] . '%' : '',
                'Present'          => $student['present'],
                'Absent'           => $student['absent'],
                'Justified'        => $student['justified'],
                'Total Sessions'   => $student['total_sessions'],
                'At Risk'          => $student['at_risk'] ? 'Yes' : 'No',
                'Absence Streak'   => $student['absence_streak'],
            ];
        }

        return $rows;
    }

    protected function flattenStudent(array $data): array
    {
        $rows = [];
        $student = $data['student'];
        $group = $data['group'];
        $period = $data['period'];
        $academic = $data['academic'];
        $attendance = $data['attendance'];

        // Summary row
        $rows[] = [
            'Student'            => $student['full_name'],
            'Group'              => $group['name'],
            'Subject'            => $group['subject'] ?? '',
            'Period'             => $period['name'],
            'Category'           => '— SUMMARY —',
            'Grade Title'        => '',
            'Score'              => '',
            'Max Score'          => '',
            'Percentage'         => '',
            'Weighted Average'   => $academic['weighted_average'] ?? '',
            'Attendance Rate'    => $attendance['rate'] !== null ? $attendance['rate'] . '%' : '',
            'At Risk'            => $academic['at_risk'] ? 'Yes' : 'No',
        ];

        // Grade rows per category
        foreach ($data['categories'] as $category) {
            foreach ($category['grades'] as $grade) {
                $rows[] = [
                    'Student'            => $student['full_name'],
                    'Group'              => $group['name'],
                    'Subject'            => $group['subject'] ?? '',
                    'Period'             => $period['name'],
                    'Category'           => $category['name'] . ' (' . $category['weight'] . '%)',
                    'Grade Title'        => $grade['title'],
                    'Score'              => $grade['score'],
                    'Max Score'          => $grade['max_score'],
                    'Percentage'         => $grade['percentage'] . '%',
                    'Weighted Average'   => '',
                    'Attendance Rate'    => '',
                    'At Risk'            => '',
                ];
            }
        }

        return $rows;
    }

    protected function flattenPeriod(array $data): array
    {
        $rows = [];
        $period = $data['period'];

        foreach ($data['groups'] as $group) {
            $rows[] = [
                'Period'            => $period['name'],
                'Group'             => $group['group_name'],
                'Subject'           => $group['subject'] ?? '',
                'Educational Level' => $group['educational_level'] ?? '',
                'Students'          => $group['students_count'],
                'Group Average'     => $group['group_average'] ?? '',
                'Attendance Rate'   => $group['group_attendance'] !== null ? $group['group_attendance'] . '%' : '',
                'Students At Risk'  => $group['students_at_risk'],
                'Categories'        => $group['categories_count'],
                'Total Weight'      => $group['total_weight'] . '%',
            ];
        }

        return $rows;
    }

    // ────────────────────────────────────────────────
    // TEMPLATE COLUMN HELPERS
    // ────────────────────────────────────────────────

    /**
     * Filter rows to only include specified columns.
     */
    protected function filterColumns(array $rows, array $includedColumns): array
    {
        if (empty($rows) || empty($includedColumns)) {
            return $rows;
        }

        return array_map(function (array $row) use ($includedColumns) {
            return array_intersect_key($row, array_flip($includedColumns));
        }, $rows);
    }

    /**
     * Reorder columns according to the template's column_order.
     * Columns not in the order list appear at the end.
     */
    protected function reorderColumns(array $rows, array $columnOrder): array
    {
        if (empty($rows) || empty($columnOrder)) {
            return $rows;
        }

        return array_map(function (array $row) use ($columnOrder) {
            $ordered = [];

            foreach ($columnOrder as $col) {
                if (array_key_exists($col, $row)) {
                    $ordered[$col] = $row[$col];
                }
            }

            // Append any remaining columns not in the order list
            foreach ($row as $key => $value) {
                if (! array_key_exists($key, $ordered)) {
                    $ordered[$key] = $value;
                }
            }

            return $ordered;
        }, $rows);
    }
}
