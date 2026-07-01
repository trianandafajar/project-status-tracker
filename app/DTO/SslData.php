<?php

namespace App\DTO;

use App\Enums\SslStatus;
use Carbon\Carbon;

readonly class SslData
{
    public function __construct(
        public int $serverId,
        public string $domain,
        public ?Carbon $validFrom,
        public ?Carbon $validTo,
        public SslStatus $status,
        public ?string $issuer = null,
        public ?int $daysRemaining = null,
    ) {}
}
