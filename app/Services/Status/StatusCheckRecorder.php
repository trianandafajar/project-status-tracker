<?php

namespace App\Services\Status;

use App\Jobs\PruneStatusChecksJob;
use App\Models\StatusCheck;
use App\Models\StatusMonitor;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class StatusCheckRecorder
{
    public function record(StatusMonitor $monitor, array $result, CarbonInterface|string|null $checkedAt = null): StatusMonitor
    {
        $checkedAt = $checkedAt ? Carbon::parse($checkedAt) : now();

        $monitor->checks()->create([
            'status_monitor_id' => $monitor->id,
            'status' => $result['status'],
            'http_status_code' => $result['http_status_code'],
            'response_time_ms' => $result['response_time_ms'],
            'error_message' => $result['error_message'],
            'checked_at' => $checkedAt,
        ]);

        $recentChecks = StatusCheck::query()
            ->where('status_monitor_id', $monitor->id)
            ->where('checked_at', '>=', $checkedAt->copy()->subHours(PruneStatusChecksJob::RETENTION_HOURS));

        $totalChecks = (clone $recentChecks)->count();
        $operationalChecks = (clone $recentChecks)
            ->where('status', 'operational')
            ->count();

        $monitor->forceFill([
            'last_checked_at' => $checkedAt,
            'last_status' => $result['status'],
            'last_http_code' => $result['http_status_code'],
            'last_response_ms' => $result['response_time_ms'],
            'last_uptime_percent' => $totalChecks > 0
                ? round(($operationalChecks / $totalChecks) * 100, 2)
                : null,
            'last_error_message' => $result['error_message'],
        ])->save();

        return $monitor->refresh();
    }
}
