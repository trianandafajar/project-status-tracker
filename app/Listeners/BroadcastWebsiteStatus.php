<?php

namespace App\Listeners;

use App\Events\WebsiteStatusChanged;
use App\Models\Alert;
use App\Services\Security\AuditLogger;

class BroadcastWebsiteStatus
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function handle(WebsiteStatusChanged $event): void
    {
        $this->auditLogger->log(
            action: 'website.status_changed',
            modelType: 'Website',
            modelId: $event->website->id,
            metadata: [
                'url' => $event->website->url,
                'previous_status' => $event->previousStatus,
                'new_status' => $event->result['status'],
                'http_code' => $event->result['http_status_code'],
            ],
        );

        // Auto-create alert if website goes down
        if ($event->result['status'] === 'down' && $event->previousStatus !== 'down') {
            Alert::create([
                'server_id' => $event->website->server_id,
                'type' => 'website',
                'severity' => 'critical',
                'status' => 'open',
                'title' => "Website {$event->website->name} is DOWN",
                'message' => "{$event->website->url} returned HTTP {$event->result['http_status_code']}. {$event->result['error_message']}",
                'context' => $event->result,
            ]);
        }

        // Alert if SSL expiring
        if ($event->result['ssl_status'] === 'expiring_soon') {
            Alert::create([
                'server_id' => $event->website->server_id,
                'type' => 'ssl',
                'severity' => 'warning',
                'status' => 'open',
                'title' => "SSL expiring soon for {$event->website->name}",
                'message' => "{$event->website->url} SSL certificate expires in {$event->result['ssl_days_remaining']} days.",
                'context' => $event->result,
            ]);
        }
    }
}
