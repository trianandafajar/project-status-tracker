<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Server;
use App\Models\Service;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteServiceActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $serverId,
        public int $serviceId,
        public string $action,
    ) {}

    public function handle(SshCommandRunner $runner): void
    {
        $server = Server::findOrFail($this->serverId);
        $service = Service::findOrFail($this->serviceId);

        $command = "systemctl {$this->action} {$service->type}";
        $output = $runner->run($server, $command);

        AuditLog::create([
            'server_id' => $server->id,
            'action' => "service.{$this->action}",
            'resource_type' => 'service',
            'resource_id' => $service->id,
            'details' => ['output' => $output, 'command' => $command],
        ]);
    }
}
