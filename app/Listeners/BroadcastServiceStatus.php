<?php

namespace App\Listeners;

use App\Events\ServiceDown;
use App\Events\ServiceUp;
use App\Services\Security\AuditLogger;
use Illuminate\Support\Facades\Log;

class BroadcastServiceStatus
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function handle(ServiceDown|ServiceUp $event): void
    {
        $status = $event instanceof ServiceDown ? 'stopped' : 'running';

        Log::info("Service {$event->service->name} is now {$status}");
    }
}
