<?php

namespace App\Services\Export;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ExcelExporter — Generates XLSX files with multiple sheets.
 *
 * Uses Maatwebsite/Excel under the hood.
 * Keeps formatting minimal and clean — no heavy styling.
 */
class ExcelExporter implements ExporterInterface
{
    public function export(array $data, ExportContext $context, array $templateConfig = []): string
    {
        $fileName = $context->generateFileName();
        $directory = 'exports';

        Storage::disk('local')->makeDirectory($directory);

        $relativePath = "{$directory}/{$fileName}";
        $sheets = $this->buildSheets($data, $context, $templateConfig);

        Excel::store(
            new MultiSheetExport($sheets),
            $relativePath,
            'local'
        );

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * Build an array of sheet descriptors based on export type.
     */
    protected function buildSheets(array $data, ExportContext $context, array $templateConfig = []): array
    {
        return match ($context->type) {
            'group' => $this->groupSheets($data, $templateConfig),
            'student' => $this->studentSheets($data, $templateConfig),
            'period' => $this->periodSheets($data, $templateConfig),
            default => [],
        };
    }

    // ────────────────────────────────────────────────
    // GROUP SHEETS
    // ────────────────────────────────────────────────

    protected function groupSheets(array $data, array $templateConfig = []): array
    {
        $sheets = [];

        // Sheet 1: Students Summary
        $headings = ['Student', 'Weighted Average', 'Attendance Rate', 'Present', 'Absent', 'Justified', 'Total Sessions', 'At Risk', 'Absence Streak'];
        $rows = array_map(fn ($s) => [
            $s['full_name'],
            $s['weighted_average'] ?? '',
            $s['attendance_rate'] !== null ? $s['attendance_rate'].'%' : '',
            $s['present'],
            $s['absent'],
            $s['justified'],
            $s['total_sessions'],
            $s['at_risk'] ? 'Yes' : 'No',
            $s['absence_streak'],
        ], $data['students']);

        $sheets[] = ['title' => 'Students Summary', 'headings' => $headings, 'rows' => $rows];

        // Sheet 2: Categories (controlled by template)
        $includeCategories = $templateConfig['include_category_breakdown'] ?? true;
        if ($includeCategories && ! empty($data['categories'])) {
            $catHeadings = ['Category', 'Weight (%)'];
            $catRows = array_map(fn ($c) => [$c['name'], $c['weight']], $data['categories']);
            $sheets[] = ['title' => 'Grade Categories', 'headings' => $catHeadings, 'rows' => $catRows];
        }

        return $sheets;
    }

    // ────────────────────────────────────────────────
    // STUDENT SHEETS
    // ────────────────────────────────────────────────

    protected function studentSheets(array $data, array $templateConfig = []): array
    {
        $sheets = [];
        $student = $data['student'];
        $academic = $data['academic'];
        $attendance = $data['attendance'];

        // Sheet 1: Summary
        $summaryHeadings = ['Field', 'Value'];
        $summaryRows = [
            ['Student', $student['full_name']],
            ['Group', $data['group']['name']],
            ['Period', $data['period']['name']],
            ['Weighted Average', $academic['weighted_average'] ?? 'N/A'],
            ['At Risk', $academic['at_risk'] ? 'Yes' : 'No'],
            ['Absence Streak', $academic['absence_streak']],
            ['Attendance Rate', $attendance['rate'] !== null ? $attendance['rate'].'%' : 'N/A'],
            ['Present', $attendance['present']],
            ['Absent', $attendance['absent']],
            ['Justified', $attendance['justified']],
            ['Total Sessions', $attendance['total']],
        ];
        $sheets[] = ['title' => 'Summary', 'headings' => $summaryHeadings, 'rows' => $summaryRows];

        // Sheet 2: Grades Breakdown
        $gradeHeadings = ['Category', 'Weight (%)', 'Grade Title', 'Score', 'Max Score', 'Percentage', 'Date'];
        $gradeRows = [];
        foreach ($data['categories'] as $category) {
            foreach ($category['grades'] as $grade) {
                $gradeRows[] = [
                    $category['name'],
                    $category['weight'],
                    $grade['title'],
                    $grade['score'],
                    $grade['max_score'],
                    $grade['percentage'].'%',
                    $grade['date'],
                ];
            }
        }
        $sheets[] = ['title' => 'Grades Breakdown', 'headings' => $gradeHeadings, 'rows' => $gradeRows];

        // Sheet 3: Observations (controlled by template)
        $includeObs = $templateConfig['include_observations_summary'] ?? true;
        if ($includeObs && ! empty($data['observations'])) {
            $obsHeadings = ['Type', 'Content', 'Status', 'Date'];
            $obsRows = array_map(fn ($o) => [
                $o['type'],
                $o['content'],
                $o['status'],
                $o['created_at'],
            ], $data['observations']);
            $sheets[] = ['title' => 'Observations', 'headings' => $obsHeadings, 'rows' => $obsRows];
        }

        return $sheets;
    }

    // ────────────────────────────────────────────────
    // PERIOD SHEETS
    // ────────────────────────────────────────────────

    protected function periodSheets(array $data, array $templateConfig = []): array
    {
        $sheets = [];

        // Sheet 1: Period Info
        $period = $data['period'];
        $summary = $data['summary'];
        $infoHeadings = ['Field', 'Value'];
        $infoRows = [
            ['Period', $period['name']],
            ['Start Date', $period['start_date']],
            ['End Date', $period['end_date']],
            ['Active', $period['is_active'] ? 'Yes' : 'No'],
            ['Total Groups', $summary['total_groups']],
            ['Total Students', $summary['total_students']],
            ['Students At Risk', $summary['total_at_risk']],
        ];
        $sheets[] = ['title' => 'Period Info', 'headings' => $infoHeadings, 'rows' => $infoRows];

        // Sheet 2: Groups Summary
        $groupHeadings = ['Group', 'Subject', 'Level', 'Students', 'Average', 'Attendance', 'At Risk', 'Categories', 'Total Weight'];
        $groupRows = array_map(fn ($g) => [
            $g['group_name'],
            $g['subject'] ?? '',
            $g['educational_level'] ?? '',
            $g['students_count'],
            $g['group_average'] ?? 'N/A',
            $g['group_attendance'] !== null ? $g['group_attendance'].'%' : 'N/A',
            $g['students_at_risk'],
            $g['categories_count'],
            $g['total_weight'].'%',
        ], $data['groups']);
        $sheets[] = ['title' => 'Groups Summary', 'headings' => $groupHeadings, 'rows' => $groupRows];

        return $sheets;
    }
}

// ────────────────────────────────────────────────────
// Internal helper classes used by Maatwebsite\Excel
// ────────────────────────────────────────────────────

/**
 * MultiSheetExport — Wraps an array of sheet descriptors.
 */
class MultiSheetExport implements WithMultipleSheets
{
    public function __construct(protected array $sheetDescriptors) {}

    public function sheets(): array
    {
        return array_map(
            fn ($desc) => new SimpleSheet($desc['title'], $desc['headings'], $desc['rows']),
            $this->sheetDescriptors
        );
    }
}

/**
 * SimpleSheet — Single sheet with headings and data rows.
 */
class SimpleSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected string $title,
        protected array $headings,
        protected array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
