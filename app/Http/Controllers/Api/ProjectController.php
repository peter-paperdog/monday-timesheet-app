<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Support\LoadsWithDepth;

class ProjectController extends Controller
{
    use LoadsWithDepth;

    protected array $relationDepthMap = [
        1 => ['client', 'groups'],
        2 => ['client', 'groups.tasks'],
        3 => ['client', 'groups.tasks.timeTrackings'],
    ];

    /**
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $depth = (int) $request->query('depth', 1);

        $projects = Project::with($this->getRelationsByDepth($depth))->get();

        return ProjectResource::collection($projects);
    }

    public function indexByClient(Request $request, Client $client)
    {
        $depth = (int) $request->query('depth', 1);

        $client->load(['projects' => function ($query) use ($depth) {
            $query->with($this->getRelationsByDepth($depth));
        }]);

        return ProjectResource::collection($client->projects);
    }

    /**
     * @param Project $project
     * @return ProjectResource
     */
    public function show(Request $request, Project $project)
    {
        $depth = (int) $request->query('depth', 1);

        $this->loadWithDepth($project, $depth, $this->relationDepthMap);

        return new ProjectResource($project);
    }
}
