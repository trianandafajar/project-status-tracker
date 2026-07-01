<?php

namespace App\DTO;

use App\Enums\HealingActionType;
use App\Enums\HealingStatus;
use Carbon\Carbon;

readonly class HealingResultData
{
    public function __construct(
        public int $serverId,
        public HealingActionType $actionType,
        public HealingStatus $status,
        public ?string $output = null,
        public Carbon $executedAt,
    ) {}
}
