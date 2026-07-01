<?php

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

class MySqlMonitor
{
    public function __construct(protected Server $server) {}

    public function check(): array
    {
        $runner = App::make(SshCommandRunner::class);
        $pingOutput = trim($runner->run($this->server, 'mysqladmin ping'));
        $versionOutput = trim($runner->run($this->server, 'mysqladmin version'));

        preg_match('/Server version\s+([^\s]+)/', $versionOutput, $versionMatches);

        return [
            'active' => str_contains($pingOutput, 'mysqld is alive'),
            'status' => str_contains($pingOutput, 'mysqld is alive') ? 'running' : 'stopped',
            'version' => $versionMatches[1] ?? null,
        ];
    }
}
