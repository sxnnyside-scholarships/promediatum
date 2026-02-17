<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Observation;
use App\Services\Automation\AutomationEngine;
use App\Services\Insights\InsightEngine;

/**
 * Invalidates insight + automation cache when observations change.
 */
class ObservationObserver
{
    public function created(Observation $observation): void
    {
        $this->invalidate($observation);
    }

    public function updated(Observation $observation): void
    {
        $this->invalidate($observation);
    }

    public function deleted(Observation $observation): void
    {
        $this->invalidate($observation);
    }

    private function invalidate(Observation $observation): void
    {
        if ($observation->period_id) {
            InsightEngine::invalidateCache($observation->period_id);
            AutomationEngine::invalidateCache($observation->period_id);
        }
    }
}
