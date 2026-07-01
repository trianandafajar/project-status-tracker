<?php

namespace App\DTO;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Carbon\Carbon;

readonly class AlertData
{
    public function __construct(
        public int $alertRuleId,
        public int $serverId,
        public AlertSeverity $severity,
        public string $title,
        public string $message,
        public AlertStatus $status,
        public ?int $acknowledgedBy = null,
        public ?Carbon $acknowledgedAt = null,
    ) {}
}
