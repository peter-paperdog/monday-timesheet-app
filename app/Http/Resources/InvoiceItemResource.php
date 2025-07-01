<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'qty' => $this->qty,
            'price' => $this->price,
            'projectId' => $this->project_id,
            'taskId' => $this->task_id,
            'TAX' => $this->TAX,
            'discount' => $this->discount,
        ];
    }
}
