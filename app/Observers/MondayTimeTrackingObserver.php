<?php

namespace App\Observers;

use App\Models\MondayTimeTracking;
use Illuminate\Support\Facades\Log;

class MondayTimeTrackingObserver
{
    /**
     * Handle the MondayTimeTracking "created" event.
     */
    public function created(MondayTimeTracking $mondayTimeTracking): void
    {
        $this->updateTaskDuration($mondayTimeTracking, 'created');
    }

    /**
     * Handle the MondayTimeTracking "updated" event.
     */
    public function updated(MondayTimeTracking $mondayTimeTracking): void
    {
        $this->updateTaskDuration($mondayTimeTracking, 'updated');
    }

    /**
     * Handle the MondayTimeTracking "deleted" event.
     */
    public function deleted(MondayTimeTracking $mondayTimeTracking): void
    {
        $this->updateTaskDuration($mondayTimeTracking, 'deleted');
    }

    /**
     * Handle the MondayTimeTracking "restored" event.
     */
    public function restored(MondayTimeTracking $mondayTimeTracking): void
    {
        $this->updateTaskDuration($mondayTimeTracking, 'restored');
    }

    /**
     * Handle the MondayTimeTracking "force deleted" event.
     */
    public function forceDeleted(MondayTimeTracking $mondayTimeTracking): void
    {
        $this->updateTaskDuration($mondayTimeTracking, 'forceDeleted');
    }

    /**
     * Update related task duration summary.
     */
    protected function updateTaskDuration(MondayTimeTracking $tracking, string $event): void
    {
        $task = $tracking->item->task ?? null;

        if ($task) {
            Log::info("Observer: {$event} event triggered for tracking ID {$tracking->id}, updating task ID {$task->id}");
            $task->updateDurationSummary();
        } else {
            Log::warning("Observer: {$event} event triggered for tracking ID {$tracking->id}, but no related task found.");
        }
    }
}
