<?php

namespace App\Console\Commands\Monitor;

use App\Jobs\MonitorServiceJob;
use App\Models\Server;
use Illuminate\Console\Command;

class CheckServices extends Command
{
    protected $signature = 'monitor:check-services {server?}';
    protected $description = 'Check services status on all servers';

    public function handle(): void
    {
        $servers = $this->argument('server')
            ? Server::where('id', $this->argument('server'))->get()
            : Server::all();

        foreach ($servers as $server) {
            MonitorServiceJob::dispatch($server->id);
        }
    }
}
