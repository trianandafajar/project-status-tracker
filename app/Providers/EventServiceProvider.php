<?php

namespace App\Providers;

use App\Events\MetricCollected;
use App\Events\ServiceDown;
use App\Events\ServiceUp;
use App\Events\WebsiteStatusChanged;
use App\Listeners\BroadcastMetric;
use App\Listeners\BroadcastServiceStatus;
use App\Listeners\BroadcastWebsiteStatus;
use App\Listeners\EvaluateAlertRules;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MetricCollected::class => [
            BroadcastMetric::class,
            EvaluateAlertRules::class,
        ],
        ServiceDown::class => [
            BroadcastServiceStatus::class,
        ],
        ServiceUp::class => [
            BroadcastServiceStatus::class,
        ],
        WebsiteStatusChanged::class => [
            BroadcastWebsiteStatus::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
