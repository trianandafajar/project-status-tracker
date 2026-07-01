<?php

namespace App\Events;

use App\DTO\ServiceStatusData;
use App\Models\Service;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ServiceDown implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Service $service,
        public ServiceStatusData $data,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("private-server.{$this->service->server_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'service_id' => $this->service->id,
            'type' => $this->data->type->value,
            'name' => $this->data->name,
            'status' => $this->data->status->value,
            'previous_status' => $this->service->status,
            'checked_at' => $this->data->checkedAt->toIso8601String(),
            'output' => $this->data->output,
        ];
    }
}
