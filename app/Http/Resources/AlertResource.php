<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'status' => $this->status,
            'acknowledged_by' => $this->acknowledged_by,
            'acknowledged_at' => $this->acknowledged_at,
            'resolved_by' => $this->resolved_by,
            'resolved_at' => $this->resolved_at,
            'auto_resolved' => $this->auto_resolved,
            'triggered_at' => $this->triggered_at,
            'created_at' => $this->created_at,
        ];
    }
}
