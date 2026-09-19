<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Jobs\SendExportEmail;
use App\Models\ExportHistory;
use App\Models\ExportTemplate;
use App\Models\Group;
use App\Models\Period;
use App\Models\SmtpSetting;
use App\Models\Student;
use App\Services\Export\ExportContext;
use App\Services\Export\ExportManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(
        protected ExportManager $exportManager,
    ) {}

    /**
     * POST /exports — Generate and download/email an export file.
     */
    public function store(ExportRequest $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validated();
        $deliveryMethod = $validated['delivery_method'] ?? 'download';
        $recipientEmail = $validated['recipient_email'] ?? null;

        $context = ExportContext::fromArray($validated);
        $filePath = $this->exportManager->execute($context);

        // If sending via email, dispatch the job and return JSON
        if ($deliveryMethod === 'email' && $recipientEmail) {
            $smtp = SmtpSetting::where('user_id', Auth::id())->where('verified', true)->first();

            if (! $smtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP is not configured or not verified.',
                ], 422);
            }

            // Find the just-created history record
            $history = ExportHistory::where('user_id', Auth::id())
                ->orderByDesc('id')
                ->first();

            if ($history) {
                SendExportEmail::dispatch(
                    $history->id,
                    $smtp->id,
                    $recipientEmail,
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Export generated. Email is being sent.',
            ]);
        }

        // Default: download
        $mimeType = match ($context->format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };

        return response()->download($filePath, basename($filePath), [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * GET /exports/history — Show export history page.
     */
    public function history(Request $request): Response
    {
        $exports = ExportHistory::where('user_id', Auth::id())
            ->with(['period:id,name', 'group:id,name', 'student:id,first_name,last_name', 'template:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ExportHistory $export) => [
                'id' => $export->id,
                'type' => $export->type,
                'format' => $export->format,
                'context_label' => $export->context_label,
                'template_name' => $export->template?->name,
                'file_name' => $export->file_name,
                'sent_via_email' => $export->sent_via_email,
                'recipient_email' => $export->recipient_email,
                'created_at' => $export->created_at?->toDateTimeString(),
                'can_download' => Storage::disk('local')->exists($export->file_path),
            ]);

        // Provide reference data for the export form
        $periods = Period::orderByDesc('start_date')->get(['id', 'name']);
        $groups = Group::orderBy('name')->get(['id', 'name', 'period_id']);
        $students = Student::with('groups:id,period_id')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);
        $templates = ExportTemplate::where('user_id', Auth::id())->orderBy('name')->get(['id', 'name', 'type', 'is_default', 'config']);

        return Inertia::render('Exports/History', [
            'exports' => $exports,
            'periods' => $periods,
            'groups' => $groups,
            'students' => $students,
            'templates' => $templates,
            'smtpConfigured' => SmtpSetting::where('user_id', Auth::id())->where('verified', true)->exists(),
            'prefill' => [
                'type' => $request->query('type'),
                'period_id' => $request->query('period_id'),
                'group_id' => $request->query('group_id'),
                'student_id' => $request->query('student_id'),
                'format' => $request->query('format'),
                'template_id' => $request->query('template_id'),
            ],
        ]);
    }

    /**
     * GET /exports/{exportHistory}/download — Re-download a previous export.
     */
    public function download(ExportHistory $exportHistory): BinaryFileResponse
    {
        // Security: only the owner can download
        if ($exportHistory->user_id !== Auth::id()) {
            abort(403);
        }

        $fullPath = Storage::disk('local')->path($exportHistory->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'Export file no longer exists.');
        }

        $mimeType = match ($exportHistory->format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };

        return response()->download($fullPath, $exportHistory->file_name, [
            'Content-Type' => $mimeType,
        ]);
    }
}
