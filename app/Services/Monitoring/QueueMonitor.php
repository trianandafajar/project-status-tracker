<?php

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

class QueueMonitor
{
    public function __construct(protected Server $server) {}

    public function check(): array
    {
        $runner = App::make(SshCommandRunner::class);

        $activeOutput = trim($runner->run($this->server, 'systemctl is-active laravel-worker'));
        $active = $activeOutput === 'active';

        $queueOutput = trim($runner->run($this->server, 'php artisan queue:status'));
        $queues = $queueOutput ? [$queueOutput] : [];

        return [
            'active' => $active,
            'status' => $active ? 'running' : 'stopped',
            'queues' => $queues,
        ];
    }
}
