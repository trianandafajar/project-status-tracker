<?php

namespace App\Events;

use App\Models\Website;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class WebsiteStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Website $website,
        public array $result,
        public string $previousStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("private-server.{$this->website->server_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'website_id' => $this->website->id,
            'name' => $this->website->name,
            'url' => $this->website->url,
            'status' => $this->result['status'],
            'previous_status' => $this->previousStatus,
            'http_status_code' => $this->result['http_status_code'],
            'response_time_ms' => $this->result['response_time_ms'],
            'ssl_days_remaining' => $this->result['ssl_days_remaining'],
            'last_uptime_percent' => $this->website->last_uptime_percent,
        ];
    }
}
