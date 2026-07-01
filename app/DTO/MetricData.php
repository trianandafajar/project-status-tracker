<?php

namespace App\DTO;

use App\Enums\MetricType;
use Carbon\Carbon;

readonly class MetricData
{
    public function __construct(
        public int $serverId,
        public MetricType $type,
        public float $value,
        public string $unit,
        public Carbon $recordedAt,
        public array $metadata = [],
    ) {}
}
