<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'group_id' => $this->group_id,
            'project_id' => $this->taskable_type === Project::class ? $this->taskable_id : null,
            'user_board_id' => $this->taskable_type === \App\Models\UserBoard::class ? $this->taskable_id : null,
        ];
    }
}
