<?php

namespace App\Jobs;

use App\Models\StatusMonitor;
use App\Services\Status\StatusCheckRecorder;
use App\Services\Status\StatusMonitorProbe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckStatusMonitorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StatusMonitorProbe $probe, StatusCheckRecorder $recorder): void
    {
        StatusMonitor::query()
            ->where('enabled', true)
            ->orderBy('id')
            ->chunkById(50, function ($monitors) use ($probe, $recorder) {
                foreach ($monitors as $monitor) {
                    $result = $probe->check($monitor);
                    $recorder->record($monitor, $result);
                }
            });
    }
}
