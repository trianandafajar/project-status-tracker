<?php

use App\Console\Commands\Monitor\CheckServices;
use App\Console\Commands\Monitor\CollectMetrics;
use App\Jobs\CheckWebsitesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CollectMetrics::class)->everyMinute();
Schedule::command(CheckServices::class)->everyThirtySeconds();
Schedule::job(new CheckWebsitesJob)->everyMinute()->withoutOverlapping();
