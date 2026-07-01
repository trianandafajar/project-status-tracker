<?php

namespace App\Listeners;

use App\Events\MetricCollected;
use Illuminate\Support\Facades\Log;

class BroadcastMetric
{
    public function handle(MetricCollected $event): void
    {
        Log::debug("Metric collected: {$event->metric->type->value} = {$event->metric->value}{$event->metric->unit}");
    }
}
