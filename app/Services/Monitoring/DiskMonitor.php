<?php

namespace App\Services\Monitoring;

use App\DTO\MetricData;
use App\Enums\MetricType;
use Carbon\Carbon;

class DiskMonitor extends BaseMonitor
{
    public function collect(): MetricData
    {
        $output = $this->runCommand('df -h /');

        preg_match('/(\d+)%\s+/', $output, $matches);

        if (isset($matches[1])) {
            $value = (float) $matches[1];
        } else {
            preg_match('/(\d+)%/', $output, $fallback);
            $value = isset($fallback[1]) ? (float) $fallback[1] : 0;
        }

        return new MetricData(
            serverId: $this->server->id,
            type: MetricType::Disk,
            value: round($value, 2),
            unit: '%',
            recordedAt: Carbon::now(),
        );
    }
}
