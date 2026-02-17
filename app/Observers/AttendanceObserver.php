<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Attendance;
use App\Services\Automation\AutomationEngine;
use App\Services\Insights\InsightEngine;

/**
 * Invalidates insight + automation cache when attendance records change.
 */
class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        $this->invalidate($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        $this->invalidate($attendance);
    }

    public function deleted(Attendance $attendance): void
    {
        $this->invalidate($attendance);
    }

    private function invalidate(Attendance $attendance): void
    {
        if ($attendance->period_id) {
            InsightEngine::invalidateCache($attendance->period_id);
            AutomationEngine::invalidateCache($attendance->period_id);
        }
    }
}
