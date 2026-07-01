<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'server_name' => $this->whenLoaded('server', fn () => $this->server->name),
            'url' => $this->url,
            'name' => $this->name,
            'check_interval_seconds' => $this->check_interval_seconds,
            'expected_status_code' => $this->expected_status_code,
            'expected_keyword' => $this->expected_keyword,
            'timeout_seconds' => $this->timeout_seconds,
            'enabled' => $this->enabled,
            'last_checked_at' => $this->last_checked_at,
            'last_status' => $this->last_status,
            'last_http_code' => $this->last_http_code,
            'last_response_ms' => $this->last_response_ms,
            'last_uptime_percent' => $this->last_uptime_percent,
            'checks_count' => $this->whenCounted('checks'),
            'checks' => WebsiteCheckResource::collection($this->whenLoaded('checks')),
            'created_at' => $this->created_at,
        ];
    }
}
