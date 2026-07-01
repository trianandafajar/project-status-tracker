<?php

namespace App\Jobs;

use App\DTO\ServiceStatusData;
use App\Enums\ServiceStatus;
use App\Enums\ServiceType;
use App\Events\ServiceDown;
use App\Events\ServiceUp;
use App\Models\Server;
use App\Models\Service;
use App\Services\Ssh\SshCommandRunner;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitorServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $serverId,
    ) {}

    public function handle(SshCommandRunner $runner): void
    {
        $server = Server::findOrFail($this->serverId);
        $services = $server->services;

        foreach ($services as $service) {
            $command = $this->buildCommand($service);
            $output = $runner->run($server, $command);

            $isActive = str_contains($output, 'active (running)')
                || str_contains($output, 'up')
                || str_contains($output, 'running');
            $newStatus = $isActive ? ServiceStatus::Running : ServiceStatus::Stopped;
            $previousStatus = $service->status;

            if ($newStatus->value !== $previousStatus) {
                $service->update(['status' => $newStatus->value, 'current_output' => $output]);

                $service->statusHistory()->create([
                    'status' => $newStatus->value,
                    'output' => $output,
                    'checked_at' => Carbon::now(),
                ]);

                $data = new ServiceStatusData(
                    serverId: $server->id,
                    serviceId: $service->id,
                    type: ServiceType::from($service->type),
                    name: $service->name,
                    status: $newStatus,
                    checkedAt: Carbon::now(),
                    output: $output,
                );

                if ($newStatus === ServiceStatus::Running) {
                    event(new ServiceUp($service, $data));
                } else {
                    event(new ServiceDown($service, $data));
                }
            }
        }
    }

    private function buildCommand(Service $service): string
    {
        return match ($service->type) {
            'nginx' => 'systemctl status nginx',
            'php-fpm' => 'systemctl status php8.3-fpm',
            'redis' => 'systemctl status redis',
            'mysql' => 'systemctl status mysql',
            'docker' => 'systemctl status docker',
            default => "systemctl status {$service->type}",
        };
    }
}
