<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(
        private Request $request,
    ) {}

    public function log(
        User $user,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?array $details = null,
        ?Server $server = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user->id,
            'server_id' => $server?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'details' => $details,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
