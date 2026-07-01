<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'website_id' => $this->website_id,
            'http_status_code' => $this->http_status_code,
            'response_time_ms' => $this->response_time_ms,
            'ssl_days_remaining' => $this->ssl_days_remaining,
            'ssl_status' => $this->ssl_status,
            'is_up' => $this->is_up,
            'error_message' => $this->error_message,
            'checked_at' => $this->checked_at,
        ];
    }
}
