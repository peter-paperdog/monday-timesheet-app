<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollection;
use App\Http\Resources\GroupResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Project $project)
    {
        return new GroupCollection($project->groups()->get());
    }
    public function show(Project $project, $id)
    {
        $group = $project->groups()->findOrFail($id);

        return new GroupResource($group);
    }
}
