<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollection;
use App\Models\Project;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Project $project)
    {
        return new GroupCollection($project->groups()->get());
    }
}
