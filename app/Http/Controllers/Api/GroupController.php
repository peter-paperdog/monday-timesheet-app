<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollection;
use App\Http\Resources\GroupResource;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Support\LoadsWithDepth;

class GroupController extends Controller
{
    use LoadsWithDepth;
    protected array $relationDepthMap = [
        1 => ['tasks'],
        2 => ['tasks.timeTrackings'],
    ];
    public function index(Project $project, Request $request)
    {
        $depth = (int) $request->query('depth', 0);
        $groups = $project->groups()->get();

        $groups->each(fn($group) => $this->loadWithDepth($group, $depth, $this->relationDepthMap));

        return new GroupCollection($groups);
    }
    public function show(Project $project, $id, Request $request)
    {
        $depth = (int) $request->query('depth', 0);
        $group = $project->groups()->findOrFail($id);
        $this->loadWithDepth($group, $depth, $this->relationDepthMap);

        return new GroupResource($group);
    }
}
