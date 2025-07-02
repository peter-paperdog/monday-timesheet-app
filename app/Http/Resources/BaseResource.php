<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    protected function getDepth(Request $request): int
    {
        return (int) $request->query('depth', 0);
    }
}
