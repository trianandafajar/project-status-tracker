<?php

namespace App\Services\Metrics;

use App\DTO\MetricData;
use App\Enums\MetricType;
use App\Events\MetricCollected;
use App\Models\Metric;
use App\Models\Server;
use App\Services\Monitoring\MonitorInterface;

class MetricsCollector
{
    private const TYPES = ['cpu', 'ram', 'disk'];

    public function collect(int $serverId): array
    {
        $server = Server::findOrFail($serverId);
        $results = [];

        foreach (self::TYPES as $type) {
            $results[] = $this->collectForType($serverId, MetricType::from($type));
        }

        return $results;
    }

    public function collectForType(int $serverId, MetricType $type): MetricData
    {
        $server = Server::findOrFail($serverId);

        $monitor = app()->make(MonitorInterface::class, [
            'type' => $type->value,
            'server' => $server,
        ]);

        $data = $monitor->collect();

        Metric::create([
            'server_id' => $server->id,
            'type' => $data->type->value,
            'value' => $data->value,
            'unit' => $data->unit,
            'metadata' => $data->metadata,
            'recorded_at' => $data->recordedAt,
        ]);

        event(new MetricCollected($server, $data));

        return $data;
    }
}
