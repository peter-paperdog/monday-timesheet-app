<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
        ];
    }
}
