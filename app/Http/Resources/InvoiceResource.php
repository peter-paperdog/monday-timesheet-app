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
            'client_id' => $this->client_id,
            'customer' => new ContactResource($this->client->contact ?? null),
            'currency' => $this->currency,
            'number' => $this->number,
            'issueDate' => $this->issue_date,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
