<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query();

        if ($request->has('type')) {
            switch ($request->input('type')) {
                case 'project':
                    $query->where('taskable_type', \App\Models\Project::class);
                    break;
                case 'user':
                    $query->where('taskable_type', \App\Models\UserBoard::class);
                    break;
                case 'all':
                    break;
                default:
                    return response()->json(['error' => 'Invalid type specified'], 400);
            }
        }

        return TaskResource::collection($query->get());
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

    public function indexByGroup(Project $project, string $id)
    {
        $tasks = $project->tasks()
            ->where('group_id', $id)
            ->with('group')
            ->get();

        return TaskResource::collection($tasks);    }
}
