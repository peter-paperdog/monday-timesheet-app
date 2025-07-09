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
            'contact' => [
                'id' => intval($this->contact_id)
            ],
            'client' => [
                'id' => $this->client_id
            ],
            'currency' => $this->currency,
            'purchaseOrder' => $this->purchaseOrder ?? null,
            'invoice_projects' => InvoiceProjectResource::collection($this->whenLoaded('invoiceProjects')),
        ];
    }
}
