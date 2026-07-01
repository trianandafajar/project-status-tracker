<?php

namespace App\Services\Monitoring;

use App\DTO\MetricData;
use App\Enums\MetricType;
use Carbon\Carbon;

class CpuMonitor extends BaseMonitor
{
    public function collect(): MetricData
    {
        $output = $this->runCommand("top -bn1 | grep \"Cpu(s)\"");

        preg_match('/(\d+\.?\d*)\s*%?\s*id/', $output, $matches);

        if (isset($matches[1])) {
            $idle = (float) $matches[1];
            $value = 100 - $idle;
        } else {
            $loadOutput = $this->runCommand('cat /proc/loadavg');
            $parts = explode(' ', $loadOutput);
            $value = (float) ($parts[0] ?? 0) * 100;
        }

        return new MetricData(
            serverId: $this->server->id,
            type: MetricType::Cpu,
            value: round($value, 2),
            unit: '%',
            recordedAt: Carbon::now(),
        );
    }
}
