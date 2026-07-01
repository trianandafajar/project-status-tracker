<?php

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

class RedisMonitor
{
    public function __construct(protected Server $server) {}

    public function check(): array
    {
        $runner = App::make(SshCommandRunner::class);
        $ping = trim($runner->run($this->server, 'redis-cli ping'));
        $versionOutput = trim($runner->run($this->server, 'redis-cli INFO server'));
        $memoryOutput = trim($runner->run($this->server, 'redis-cli INFO memory'));

        preg_match('/redis_version:([^\r\n]+)/', $versionOutput, $versionMatches);
        preg_match('/used_memory_human:([^\r\n]+)/', $memoryOutput, $memoryMatches);

        return [
            'active' => $ping === 'PONG',
            'status' => $ping === 'PONG' ? 'running' : 'stopped',
            'version' => $versionMatches[1] ?? null,
            'used_memory' => $memoryMatches[1] ?? null,
        ];
    }
}
