<?php

namespace App\DTO;

use App\Enums\LogLevel;
use Carbon\Carbon;

readonly class LogEntryData
{
    public function __construct(
        public int $serverId,
        public string $source,
        public LogLevel $level,
        public string $message,
        public array $context = [],
        public Carbon $timestamp,
    ) {}
}
