<?php

use App\Console\Commands\Monitor\CheckServices;
use App\Console\Commands\Monitor\CollectMetrics;
use App\Jobs\CheckStatusMonitorsJob;
use App\Jobs\CheckWebsitesJob;
use App\Jobs\PruneStatusChecksJob;
use Illuminate\Support\Facades\Schedule;

// Schedule::command(CollectMetrics::class)->everyFiveMinutes();
// Schedule::command(CheckServices::class)->everyThirtySeconds();
// Schedule::job(new CheckWebsitesJob)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new CheckStatusMonitorsJob)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new PruneStatusChecksJob)->daily();
