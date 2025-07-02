<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Support\LoadsWithDepth;


class TaskController extends Controller
{
    use LoadsWithDepth;

    protected array $relationDepthMap = [
        1 => ['timeTrackings'],
        2 => ['timeTrackings.user']
    ];

    public function index(Request $request)
    {
        $depth = (int)$request->query('depth', 0);
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

        $tasks = $query->get();
        $tasks->each(fn($task) => $this->loadWithDepth($task, $depth, $this->relationDepthMap));

        return TaskResource::collection($tasks);
    }

    public function show($id, Request $request)
    {
        $depth = (int)$request->query('depth', 0);
        $task = Task::findOrFail($id);
        $this->loadWithDepth($task, $depth, $this->relationDepthMap);

        return new TaskResource($task);
    }

    public function indexByProject(Project $project, Request $request)
    {
        $depth = (int)$request->query('depth', 0);
        $tasks = $project->tasks()->with('group')->get();
        $tasks->each(fn($task) => $this->loadWithDepth($task, $depth, $this->relationDepthMap));

        return TaskResource::collection($tasks);
    }

    public function indexByGroup(Project $project, string $id, Request $request)
    {
        $depth = (int)$request->query('depth', 0);
        $tasks = $project->tasks()
            ->where('group_id', $id)
            ->with('group')
            ->get();

        $tasks->each(fn($task) => $this->loadWithDepth($task, $depth, $this->relationDepthMap));

        return TaskResource::collection($tasks);
    }
    public function showFromGroup(Project $project, string $group, string $taskId, Request $request)
    {
        $depth = (int) $request->query('depth', 0);

        $task = $project->tasks()
            ->where('group_id', $group)
            ->where('id', $taskId)
            ->firstOrFail();

        $this->loadWithDepth($task, $depth, $this->relationDepthMap);

        return new TaskResource($task);
    }
}
