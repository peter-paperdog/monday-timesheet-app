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
        Log::debug("Observer triggered: created tracking {$mondayTimeTracking->id} for item {$mondayTimeTracking->item_id}");
        $this->updateTaskDuration($mondayTimeTracking, 'created');
    }

    /**
     * Handle the MondayTimeTracking "updated" event.
     */
    public function updated(MondayTimeTracking $mondayTimeTracking): void
    {
        Log::debug("Observer triggered: updated tracking {$mondayTimeTracking->id}");
        $this->updateTaskDuration($mondayTimeTracking, 'updated');
    }

    /**
     * Handle the MondayTimeTracking "deleted" event.
     */
    public function deleted(MondayTimeTracking $mondayTimeTracking): void
    {
        Log::debug("Observer triggered: deleted tracking {$mondayTimeTracking->id}");
        $this->updateTaskDuration($mondayTimeTracking, 'deleted');
    }

    /**
     * Handle the MondayTimeTracking "restored" event.
     */
    public function restored(MondayTimeTracking $mondayTimeTracking): void
    {
        Log::debug("Observer triggered: restored tracking {$mondayTimeTracking->id}");
        $this->updateTaskDuration($mondayTimeTracking, 'restored');
    }

    /**
     * Handle the MondayTimeTracking "force deleted" event.
     */
    public function forceDeleted(MondayTimeTracking $mondayTimeTracking): void
    {
        Log::debug("Observer triggered: forceDeleted tracking {$mondayTimeTracking->id}");
        $this->updateTaskDuration($mondayTimeTracking, 'forceDeleted');
    }

    /**
     * Update related task duration summary.
     */
    protected function updateTaskDuration(MondayTimeTracking $tracking, string $event): void
    {
        $task = $tracking->task;

        if ($task) {
            Log::info("Observer: {$event} event triggered for tracking ID {$tracking->id}, updating task ID {$task->id}");
            $task->updateDurationSummary();
        } else {
            Log::warning("Observer: {$event} event triggered for tracking ID {$tracking->id}, but no related task found.");
        }
    }
}
