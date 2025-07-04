<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'customer' => new ContactResource(optional($this->client)->contact),
            'currency' => $this->currency,
            'number' => $this->number,
            'issueDate' => $this->issue_date,
            'tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->map(fn($task) => ['id' => $task->id]);
            }),
            'invoice_groups' => InvoiceGroupResource::collection($this->whenLoaded('groups')),
        ];
    }
}
