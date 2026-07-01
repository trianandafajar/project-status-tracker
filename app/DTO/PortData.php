<?php

namespace App\DTO;

readonly class PortData
{
    public function __construct(
        public int $serverId,
        public int $port,
        public string $protocol,
        public string $service,
        public string $status,
    ) {}
}
