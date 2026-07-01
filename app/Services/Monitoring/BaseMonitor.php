<?php

namespace App\Services\Monitoring;

use App\DTO\MetricData;
use App\Models\Server;
use App\Services\Security\CommandWhitelist;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Support\Facades\App;

abstract class BaseMonitor
{
    public function __construct(protected Server $server) {}

    abstract public function collect(): MetricData;

    protected function runCommand(string $command): string
    {
        $whitelist = App::make(CommandWhitelist::class);

        if (!$whitelist->validate($command)) {
            throw new \RuntimeException("Command not whitelisted: $command");
        }

        $runner = App::make(SshCommandRunner::class);

        return $runner->run($this->server, $command);
    }

    protected function parseNumber(string $output): float
    {
        return (float) filter_var($output, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
    }
}
