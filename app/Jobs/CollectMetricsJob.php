<?php

namespace App\Jobs;

use App\Services\Metrics\MetricsCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CollectMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $serverId,
    ) {}

    public function handle(MetricsCollector $collector): void
    {
        $collector->collect($this->serverId);
    }
}
