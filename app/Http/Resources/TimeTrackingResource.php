<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeTrackingResource extends JsonResource
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
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'user_id' => $this->user_id,
            'task_id' => $this->item_id,
            'duration_minutes' => $this->calculateDurationMinutes(),
            'duration_seconds' => $this->calculateDurationSeconds(),
            'duration_human' => $this->calculateDurationHuman(),
        ];
    }
    protected function calculateDurationMinutes()
    {
        if ($this->started_at && $this->ended_at) {
            return $this->started_at->diffInMinutes($this->ended_at);
        }

        return 0;
    }

    protected function calculateDurationSeconds()
    {
        if ($this->started_at && $this->ended_at) {
            return $this->started_at->diffInSeconds($this->ended_at);
        }

        return 0;
    }

    protected function calculateDurationHuman()
    {
        if ($this->started_at && $this->ended_at) {
            $diff = $this->started_at->diff($this->ended_at);
            return sprintf('%dh %02dm', $diff->h + ($diff->days * 24), $diff->i);
        }

        return '0h 00m';
    }
}
