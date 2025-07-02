<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProjectResource extends BaseResource
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
            'client' => new ClientResource($this->whenLoaded('client')),
            'time_board_id' => $this->time_board_id,
            'expenses_board_id' => $this->expenses_board_id,
            'groups' => GroupResource::collection($this->whenLoaded('groups')),
            'duration_seconds' => $this->duration_seconds,
            'duration_human' => $this->formatDuration($this->duration_seconds),
        ];
    }
}
