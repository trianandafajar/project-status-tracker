<?php

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

class NginxMonitor
{
    public function __construct(protected Server $server) {}

    public function check(): array
    {
        $runner = App::make(SshCommandRunner::class);
        $active = trim($runner->run($this->server, 'systemctl is-active nginx'));
        $version = trim($runner->run($this->server, 'nginx -v 2>&1'));

        return [
            'active' => $active === 'active',
            'status' => $active === 'active' ? 'running' : 'stopped',
            'version' => $version ?: null,
            'memory' => null,
        ];
    }
}
