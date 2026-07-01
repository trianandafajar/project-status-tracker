<?php

namespace App\Events;

use App\DTO\MetricData;
use App\Models\Server;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MetricCollected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Server $server,
        public MetricData $metric,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("private-server.{$this->server->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->metric->type->value,
            'value' => $this->metric->value,
            'unit' => $this->metric->unit,
            'recorded_at' => $this->metric->recordedAt->toIso8601String(),
        ];
    }
}
