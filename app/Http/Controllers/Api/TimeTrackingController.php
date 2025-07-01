<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeTrackingResource;
use App\Models\Project;
use App\Models\Task;

class TimeTrackingController extends Controller
{

    public function indexByTask(Project $project, $id)
    {
        $task = $project->tasks()->findOrFail($id);

        if ($task->taskable_type !== Project::class || $task->taskable_id !== $project->id) {
            abort(404, "Task {$task->name} (ID: {$id}) does not belong to project {$project->name} (ID: {$project->id}).");
        }

        // Eager-load user relation
        $trackings = $task->timeTrackings()->with('user')->get();

        return TimeTrackingResource::collection($trackings);
    }
}
