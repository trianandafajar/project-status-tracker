<?php

namespace App\DTO;

readonly class ServerHealthData
{
    public function __construct(
        public int $serverId,
        public float $healthScore,
        public float $cpuPercent,
        public float $ramPercent,
        public float $diskPercent,
        public int $criticalAlertCount,
        public int $warningAlertCount,
        public int $servicesRunning,
        public int $servicesTotal,
    ) {}
}
