<?php

namespace App\Console\Commands\Monitor;

use App\Jobs\CollectMetricsJob;
use App\Models\Server;
use Illuminate\Console\Command;

class CollectMetrics extends Command
{
    protected $signature = 'monitor:collect-metrics {server?}';
    protected $description = 'Collect CPU/RAM/Disk metrics from all servers';

    public function handle(): void
    {
        $servers = $this->argument('server')
            ? Server::where('id', $this->argument('server'))->get()
            : Server::all();

        foreach ($servers as $server) {
            CollectMetricsJob::dispatch($server->id);
        }
    }
}
