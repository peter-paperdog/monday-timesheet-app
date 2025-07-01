<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateDurationSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-duration-summaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and update denormalized duration_seconds fields on tasks, groups, and projects';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔁 Recalculating task durations...");
        Log::info("Running UpdateDurationSummaries command...");

        $taskCount = 0;
        $errorCount = 0;

        foreach (Task::with('group.project')->get() as $task) {
            try {
                $task->updateDurationSummary();
                $this->line("✅ Task #{$task->id} '{$task->name}' → duration: {$task->duration_seconds} sec");
                $taskCount++;
            } catch (\Throwable $e) {
                $this->error("❌ Error updating task #{$task->id}: {$e->getMessage()}");
                Log::error("Error updating task duration", ['task_id' => $task->id, 'error' => $e]);
                $errorCount++;
            }
        }

        // Also update all projects directly (in case some have no tasks or groups)
        $projectCount = 0;
        foreach (Project::with('groups')->get() as $project) {
            try {
                $project->updateDurationSummary();
                $projectCount++;
            } catch (\Throwable $e) {
                Log::error("Error updating project duration", ['project_id' => $project->id, 'error' => $e]);
            }
        }

        $this->newLine();
        $this->info("✅ Duration summary update complete.");
        $this->info("🧩 Tasks processed: {$taskCount}");
        $this->info("📦 Projects updated: {$projectCount}");
        if ($errorCount > 0) {
            $this->warn("⚠️ Errors: {$errorCount} (see logs)");
        }

        Log::info("UpdateDurationSummaries finished", [
            'tasks_updated' => $taskCount,
            'projects_updated' => $projectCount,
            'errors' => $errorCount
        ]);
    }
}
