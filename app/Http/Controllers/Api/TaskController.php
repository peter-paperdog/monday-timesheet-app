<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return TaskResource::collection(Task::all());
    }

    public function show(Project $project, $id)
    {
        $task = $project->tasks()->findOrFail($id);

        return new TaskResource($task);
    }

    public function indexByProject(Project $project)
    {
        return TaskResource::collection($project->tasks()->with('group')->get());
    }
}
