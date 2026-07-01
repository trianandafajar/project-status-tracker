<?php

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

class PhpFpmMonitor
{
    public function __construct(protected Server $server) {}

    public function check(): array
    {
        $runner = App::make(SshCommandRunner::class);
        $active = trim($runner->run($this->server, 'systemctl is-active php8.3-fpm'));
        $version = trim($runner->run($this->server, 'php -v | head -1'));

        return [
            'active' => $active === 'active',
            'status' => $active === 'active' ? 'running' : 'stopped',
            'version' => $version ?: null,
        ];
    }
}
