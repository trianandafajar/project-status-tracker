<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'connection_type' => $this->connection_type,
            'status' => $this->status,
            'health_score' => $this->health_score,
            'os' => $this->os,
            'last_checked_at' => $this->last_checked_at,
            'notes' => $this->notes,
            'metrics_count' => $this->whenLoaded('metrics', fn () => $this->metrics->count()),
            'services_count' => $this->whenLoaded('services', fn () => $this->services->count()),
            'created_at' => $this->created_at,
        ];
    }
}
