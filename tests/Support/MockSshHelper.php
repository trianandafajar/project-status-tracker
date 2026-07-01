<?php

namespace Tests\Support;

use App\Services\Ssh\SshCommandRunner;
use App\Services\Ssh\SshConnection;
use Mockery;

trait MockSshHelper
{
    protected function mockSshWithData(array $commandMap = []): void
    {
        $mockRunner = Mockery::mock(SshCommandRunner::class);

        foreach ($commandMap as $search => $output) {
            $mockRunner->shouldReceive('run')
                ->with(Mockery::any(), Mockery::on(fn ($command) => str_contains($command, $search)))
                ->andReturn($output);
        }

        $mockRunner->shouldReceive('run')
            ->with(Mockery::any(), Mockery::any())
            ->andReturn('mock output');

        $this->app->instance(SshCommandRunner::class, $mockRunner);

        $mockConnection = Mockery::mock(SshConnection::class);
        $mockConnection->shouldReceive('connect')->andReturn(true);
        $mockConnection->shouldReceive('exec')->andReturn('mock output');
        $mockConnection->shouldReceive('disconnect');

        $this->app->instance(SshConnection::class, $mockConnection);
    }

    protected function getCpuCommandMap(): array
    {
        return [
            'Cpu(s)' => '%Cpu(s): 10.5 us, 2.3 sy, 0.0 ni, 85.1 id, 0.0 wa, 0.0 hi, 0.0 si, 0.0 st',
            'loadavg' => '0.50 0.30 0.10',
        ];
    }

    protected function getRamCommandMap(): array
    {
        return [
            'free -m' => '              total        used        free      shared  buff/cache   available
Mem:           7985        1024        4096         100        2865        6657
Swap:          2048           0        2048',
        ];
    }

    protected function getDiskCommandMap(): array
    {
        return [
            'df -h /' => 'Filesystem      Size  Used Avail Use% Mounted on
/dev/sda1       100G   45G   55G  45% /',
        ];
    }

    protected function getAllMetricsCommandMap(): array
    {
        return array_merge(
            $this->getCpuCommandMap(),
            $this->getRamCommandMap(),
            $this->getDiskCommandMap(),
        );
    }

    protected function getServiceCommandMap(string $status = 'active (running)'): array
    {
        return [
            'systemctl status' => "● nginx.service - A high performance web server
   Active: {$status} since Mon 2025-01-01 00:00:00 UTC",
        ];
    }
}
