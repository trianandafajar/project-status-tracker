<?php

namespace App\DTO;

use App\Enums\ServiceType;
use App\Enums\ServiceStatus;
use Carbon\Carbon;

readonly class ServiceStatusData
{
    public function __construct(
        public int $serverId,
        public int $serviceId,
        public ServiceType $type,
        public string $name,
        public ServiceStatus $status,
        public Carbon $checkedAt,
        public ?string $output = null,
    ) {}
}
