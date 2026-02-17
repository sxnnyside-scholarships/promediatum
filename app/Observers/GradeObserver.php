<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Grade;
use App\Services\Automation\AutomationEngine;
use App\Services\Insights\InsightEngine;

/**
 * Invalidates insight + automation cache when grades are created, updated, or deleted.
 */
class GradeObserver
{
    public function created(Grade $grade): void
    {
        $this->invalidate($grade);
    }

    public function updated(Grade $grade): void
    {
        $this->invalidate($grade);
    }

    public function deleted(Grade $grade): void
    {
        $this->invalidate($grade);
    }

    private function invalidate(Grade $grade): void
    {
        if ($grade->period_id) {
            InsightEngine::invalidateCache($grade->period_id);
            AutomationEngine::invalidateCache($grade->period_id);
        }
    }
}
