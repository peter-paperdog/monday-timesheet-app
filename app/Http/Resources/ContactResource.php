<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => intval($this->id),
            'name'       => $this->name,
            'type'       => $this->type,
            'company'    => $this->company,
            'title'      => $this->title,
            'email'      => $this->email,
            'mobile'     => $this->mobile,
            'work_phone' => $this->work_phone,
            'address'    => $this->address,
        ];
    }
}
