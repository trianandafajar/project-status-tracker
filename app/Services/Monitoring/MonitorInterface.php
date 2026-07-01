<?php

namespace App\Services\Monitoring;

use App\DTO\MetricData;

interface MonitorInterface
{
    public function collect(): MetricData;
}
