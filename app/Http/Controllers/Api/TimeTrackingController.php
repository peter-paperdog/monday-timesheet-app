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
        return TimeTrackingResource::collection($task->timeTrackings);
    }
}
