<?php

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

class Pm2Monitor
{
    public function __construct(protected Server $server) {}

    public function check(): array
    {
        $runner = App::make(SshCommandRunner::class);
        $active = trim($runner->run($this->server, 'systemctl is-active pm2-root'));
        $processesJson = trim($runner->run($this->server, 'pm2 jlist'));
        $processList = json_decode($processesJson, true);

        return [
            'active' => $active === 'active',
            'status' => $active === 'active' ? 'running' : 'stopped',
            'processes' => is_array($processList) ? count($processList) : null,
        ];
    }
}
