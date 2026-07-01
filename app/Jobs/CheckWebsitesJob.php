<?php

namespace App\Jobs;

use App\Events\WebsiteStatusChanged;
use App\Models\Website;
use App\Services\Monitoring\WebsiteMonitor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckWebsitesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WebsiteMonitor $monitor): void
    {
        $websites = Website::where('enabled', true)->get();

        foreach ($websites as $website) {
            $result = $monitor->check($website);
            $previousStatus = $website->last_status;

            $website->checks()->create([
                'http_status_code' => $result['http_status_code'],
                'response_time_ms' => $result['response_time_ms'],
                'ssl_days_remaining' => $result['ssl_days_remaining'],
                'ssl_status' => $result['ssl_status'],
                'is_up' => $result['is_up'],
                'error_message' => $result['error_message'],
                'checked_at' => Carbon::now(),
            ]);

            // Calculate 30-day uptime
            $recentChecks = $website->checks()
                ->where('checked_at', '>=', Carbon::now()->subDays(30))
                ->count();
            $recentUp = $website->checks()
                ->where('checked_at', '>=', Carbon::now()->subDays(30))
                ->where('is_up', true)
                ->count();
            $uptime = $recentChecks > 0 ? round(($recentUp / $recentChecks) * 100, 2) : null;

            $website->update([
                'last_checked_at' => Carbon::now(),
                'last_status' => $result['status'],
                'last_http_code' => $result['http_status_code'] ?: null,
                'last_response_ms' => $result['response_time_ms'],
                'last_uptime_percent' => $uptime,
            ]);

            if ($result['status'] !== $previousStatus) {
                event(new WebsiteStatusChanged($website, $result, $previousStatus));
            }
        }
    }
}
