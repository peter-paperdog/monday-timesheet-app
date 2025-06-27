<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * @return AnonymousResourceCollection
     */
    public function index()
    {
        return ProjectResource::collection(Project::with('client')->get());
    }

    public function indexByClient(Client $client)
    {
        return ProjectResource::collection($client->projects);
    }

    /**
     * @param $id
     * @return ProjectResource
     */
    public function show($id)
    {
        $project = Project::with('client')->findOrFail($id);
        return new ProjectResource($project);
    }
}
