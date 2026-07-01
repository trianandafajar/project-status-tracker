<?php

namespace App\Services\Monitoring;

use App\DTO\MetricData;
use App\Enums\MetricType;
use Carbon\Carbon;

class RamMonitor extends BaseMonitor
{
    public function collect(): MetricData
    {
        $output = $this->runCommand('free -m');

        preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $output, $matches);

        if (isset($matches[1], $matches[2])) {
            $total = (float) $matches[1];
            $used = (float) $matches[2];
            $value = $total > 0 ? ($used / $total) * 100 : 0;
        } else {
            $value = 0;
        }

        return new MetricData(
            serverId: $this->server->id,
            type: MetricType::Ram,
            value: round($value, 2),
            unit: '%',
            recordedAt: Carbon::now(),
        );
    }
}
