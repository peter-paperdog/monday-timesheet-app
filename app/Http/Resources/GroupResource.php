<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GroupResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_id' => $this->project_id,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'duration_seconds' => $this->duration_seconds,
            'duration_human' => $this->formatDuration($this->duration_seconds),
        ];
    }
}
