<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\RecoveryCodeController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ExportTemplateController;
use App\Http\Controllers\GradeCategoryController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SmtpSettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->is_locked) {
            return redirect()->route('unlock');
        }

        return redirect()->route('workspace');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Locale Switch
|--------------------------------------------------------------------------
*/
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

/*
|--------------------------------------------------------------------------
| Workspace
|--------------------------------------------------------------------------
*/
Route::get('/workspace', [WorkspaceController::class, 'index'])
    ->middleware(['auth', 'session.unlocked'])
    ->name('workspace');

/*
|--------------------------------------------------------------------------
| Recovery Codes (authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/recovery-codes', [RecoveryCodeController::class, 'show'])
        ->name('recovery-codes.show');
    Route::post('/recovery-codes/regenerate', [RecoveryCodeController::class, 'regenerate'])
        ->name('recovery-codes.regenerate');
    Route::post('/recovery-codes/acknowledge', [RecoveryCodeController::class, 'acknowledge'])
        ->name('recovery-codes.acknowledge');
});

/*
|--------------------------------------------------------------------------
| Periods
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/periods', [PeriodController::class, 'index'])->name('periods.index');
    Route::get('/periods/create', [PeriodController::class, 'create'])->name('periods.create');
    Route::post('/periods', [PeriodController::class, 'store'])->name('periods.store');
    Route::get('/periods/{period}', [PeriodController::class, 'show'])->name('periods.show');
    Route::post('/periods/{period}/toggle-active', [PeriodController::class, 'toggleActive'])->name('periods.toggle-active');
});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
});

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // SMTP Settings
    Route::get('/settings/smtp', [SmtpSettingsController::class, 'show'])->name('settings.smtp.show');
    Route::put('/settings/smtp', [SmtpSettingsController::class, 'update'])->name('settings.smtp.update');
    Route::post('/settings/smtp/test', [SmtpSettingsController::class, 'test'])->name('settings.smtp.test');
});

/*
|--------------------------------------------------------------------------
| Groups
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::post('/groups/{group}/toggle-archive', [GroupController::class, 'toggleArchive'])->name('groups.toggle-archive');
    Route::patch('/groups/{group}/move-period', [GroupController::class, 'movePeriod'])->name('groups.move-period');
    Route::post('/groups/{group}/students', [GroupController::class, 'addStudent'])->name('groups.add-student');
    Route::post('/groups/{group}/students/bulk', [GroupController::class, 'bulkAddStudents'])->name('groups.bulk-add-students');
    Route::delete('/groups/{group}/students/{student}', [GroupController::class, 'removeStudent'])->name('groups.remove-student');
});

/*
|--------------------------------------------------------------------------
| Students
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::patch('/students/{student}', [StudentController::class, 'update'])->name('students.update');
});

/*
|--------------------------------------------------------------------------
| Attendance
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/groups/{group}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/groups/{group}/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
});

/*
|--------------------------------------------------------------------------
| Grade Categories & Grades
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::post('/groups/{group}/categories', [GradeCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [GradeCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [GradeCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('/groups/{group}/grades', [GradeController::class, 'store'])->name('grades.store');
    Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
    Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
});

/*
|--------------------------------------------------------------------------
| Observations
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::get('/observations', [ObservationController::class, 'index'])->name('observations.index');
    Route::post('/observations', [ObservationController::class, 'store'])->name('observations.store');
    Route::post('/observations/{observation}/toggle-resolved', [ObservationController::class, 'toggleResolved'])->name('observations.toggle-resolved');
    Route::delete('/observations/{observation}', [ObservationController::class, 'destroy'])->name('observations.destroy');
});

Route::middleware(['auth', 'session.unlocked'])->group(function () {
    Route::post('/exports', [ExportController::class, 'store'])->name('exports.store');
    Route::get('/exports/history', [ExportController::class, 'history'])->name('exports.history');
    Route::get('/exports/{exportHistory}/download', [ExportController::class, 'download'])->name('exports.download');

    // Template management
    Route::get('/exports/templates', [ExportTemplateController::class, 'index'])->name('exports.templates.index');
    Route::get('/exports/templates/create', [ExportTemplateController::class, 'create'])->name('exports.templates.create');
    Route::post('/exports/templates', [ExportTemplateController::class, 'store'])->name('exports.templates.store');
    Route::get('/exports/templates/{exportTemplate}/edit', [ExportTemplateController::class, 'edit'])->name('exports.templates.edit');
    Route::put('/exports/templates/{exportTemplate}', [ExportTemplateController::class, 'update'])->name('exports.templates.update');
    Route::delete('/exports/templates/{exportTemplate}', [ExportTemplateController::class, 'destroy'])->name('exports.templates.destroy');
});

/*
|--------------------------------------------------------------------------
| Backups (Desktop)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.unlocked'])->prefix('backups')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/', [BackupController::class, 'store'])->name('backup.store');
    Route::post('/restore', [BackupController::class, 'restore'])->name('backup.restore');
    Route::post('/validate', [BackupController::class, 'validate'])->name('backup.validate');
    Route::delete('/', [BackupController::class, 'destroy'])->name('backup.destroy');
    Route::post('/prune', [BackupController::class, 'prune'])->name('backup.prune');
    Route::get('/download', [BackupController::class, 'download'])->name('backup.download');
});

require __DIR__.'/auth.php';
