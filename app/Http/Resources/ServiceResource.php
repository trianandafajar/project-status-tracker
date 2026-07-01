<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->status,
            'current_output' => $this->current_output,
            'created_at' => $this->created_at,
        ];
    }
}
