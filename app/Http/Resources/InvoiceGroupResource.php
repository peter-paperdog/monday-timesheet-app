<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceGroupResource extends JsonResource
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
            'invoice_project' => new InvoiceProjectResource($this->whenLoaded('invoiceProject')),
            'invoice_items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
