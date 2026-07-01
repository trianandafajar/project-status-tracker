<?php

namespace App\Services\Status;

use App\Models\StatusCheck;
use App\Models\StatusMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class StatusSnapshotBuilder
{
    private const DISPLAY_WINDOW_MINUTES = 120;
    private const BAR_COUNT = self::DISPLAY_WINDOW_MINUTES;

    public function build(): array
    {
        $now = CarbonImmutable::now();
        $displaySince = $now->subMinutes(self::DISPLAY_WINDOW_MINUTES);

        $monitors = StatusMonitor::query()
            ->select([
                'id',
                'name',
                'group_name',
                'type',
                'url',
                'last_status',
                'last_http_code',
                'last_response_ms',
                'last_checked_at',
                'last_uptime_percent',
                'last_error_message',
            ])
            ->where('enabled', true)
            ->orderBy('group_name')
            ->orderBy('name')
            ->get();

        if ($monitors->isEmpty()) {
            return [
                'generated_at' => $now->toIso8601String(),
                'poll_interval_seconds' => 60,
                'display_window_minutes' => self::DISPLAY_WINDOW_MINUTES,
                'overall_status' => 'unknown',
                'overall_message' => 'No monitors have been added yet.',
                'summary' => [
                    'total' => 0,
                    'operational' => 0,
                    'degraded' => 0,
                    'down' => 0,
                ],
                'groups' => [],
                'types' => [],
                'window_label' => $displaySince->format('H:i').' - '.$now->format('H:i'),
            ];
        }

        $checksByMonitor = StatusCheck::query()
            ->select([
                'status_monitor_id',
                'status',
                'http_status_code',
                'response_time_ms',
                'error_message',
                'checked_at',
            ])
            ->whereIn('status_monitor_id', $monitors->pluck('id'))
            ->where('checked_at', '>=', $displaySince)
            ->orderBy('checked_at')
            ->get()
            ->groupBy('status_monitor_id');

        $monitorPayload = $monitors->map(function (StatusMonitor $monitor) use ($checksByMonitor, $displaySince) {
            $checks = $checksByMonitor->get($monitor->id, collect());
            $normalizedType = $monitor->type === 'database' ? 'api' : $monitor->type;

            return [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'group_name' => $monitor->group_name,
                'type' => $normalizedType,
                'url' => $monitor->url,
                'last_status' => $monitor->last_status,
                'last_http_code' => $monitor->last_http_code,
                'last_response_ms' => $monitor->last_response_ms,
                'last_checked_at' => $monitor->last_checked_at?->toIso8601String(),
                'last_uptime_percent' => $monitor->last_uptime_percent !== null ? (float) $monitor->last_uptime_percent : null,
                'last_error_message' => $monitor->last_error_message,
                'spark_bars' => $this->buildSparkBars($checks, $displaySince),
            ];
        });

        $groups = $monitorPayload
            ->groupBy('group_name')
            ->map(function (Collection $groupMonitors, string $groupName) {
                $statuses = $groupMonitors->pluck('last_status')->filter()->all();
                $uptimeValues = $groupMonitors
                    ->pluck('last_uptime_percent')
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (float) $value);

                return [
                    'name' => $groupName,
                    'status' => $this->aggregateStatus($statuses),
                    'uptime_percent' => $uptimeValues->isNotEmpty() ? round($uptimeValues->avg(), 2) : null,
                    'component_count' => $groupMonitors->count(),
                    'tooltip_urls' => $groupMonitors->pluck('url')->values()->all(),
                    'spark_bars' => $this->aggregateSparkBars($groupMonitors),
                    'monitors' => $groupMonitors->values()->all(),
                ];
            })
            ->values()
            ->all();

        $summary = [
            'total' => $monitorPayload->count(),
            'operational' => $monitorPayload->where('last_status', 'operational')->count(),
            'degraded' => $monitorPayload->where('last_status', 'degraded')->count(),
            'down' => $monitorPayload->where('last_status', 'down')->count(),
        ];

        $overallStatus = $summary['total'] === 0
            ? 'unknown'
            : $this->aggregateStatus($monitorPayload->pluck('last_status')->all());

        return [
            'generated_at' => $now->toIso8601String(),
            'poll_interval_seconds' => 60,
            'display_window_minutes' => self::DISPLAY_WINDOW_MINUTES,
            'overall_status' => $overallStatus,
            'overall_message' => $this->overallMessage($overallStatus, $summary['total']),
            'summary' => $summary,
            'groups' => $groups,
            'types' => $monitorPayload->pluck('type')->unique()->values()->all(),
            'window_label' => $displaySince->format('H:i').' - '.$now->format('H:i'),
        ];
    }

    private function buildSparkBars(Collection $checks, CarbonImmutable $since): array
    {
        $bars = [];
        $bucketStatuses = [];
        $bucketMeta = [];

        for ($i = 0; $i < self::BAR_COUNT; $i++) {
            $start = $since->addMinutes($i);

            $bars[$i] = [
                'status' => 'unknown',
                'checked_at' => $start->toIso8601String(),
                'label' => $start->format('d/m/Y H:i'),
                'http_status_code' => null,
                'response_time_ms' => null,
                'error_message' => null,
            ];
        }

        foreach ($checks as $check) {
            if (! $check->checked_at) {
                continue;
            }

            $checkedAt = CarbonImmutable::parse($check->checked_at);
            $index = (int) floor($since->diffInSeconds($checkedAt, false) / 60);

            if ($index < 0 || $index >= self::BAR_COUNT) {
                continue;
            }

            $bucketStatuses[$index][] = $check->status;
            $bucketMeta[$index] = [
                'checked_at' => $checkedAt->toIso8601String(),
                'label' => $checkedAt->format('d/m/Y H:i'),
                'http_status_code' => $check->http_status_code,
                'response_time_ms' => $check->response_time_ms,
                'error_message' => $check->error_message,
            ];
        }

        foreach ($bucketStatuses as $index => $statuses) {
            $bars[$index] = array_merge(
                $bars[$index],
                $bucketMeta[$index],
                ['status' => $this->aggregateStatus($statuses)],
            );
        }

        return array_values($bars);
    }

    private function aggregateSparkBars(Collection $groupMonitors): array
    {
        $bars = [];

        for ($i = 0; $i < self::BAR_COUNT; $i++) {
            $monitorBars = $groupMonitors
                ->map(fn (array $monitor) => $monitor['spark_bars'][$i] ?? null)
                ->filter();

            $statuses = $monitorBars->pluck('status')->filter()->all();
            $lastBar = $monitorBars->last();

            $bars[] = [
                'status' => $statuses === [] ? 'unknown' : $this->aggregateStatus($statuses),
                'checked_at' => $lastBar['checked_at'] ?? null,
                'label' => $lastBar['label'] ?? null,
                'http_status_code' => $lastBar['http_status_code'] ?? null,
                'response_time_ms' => $lastBar['response_time_ms'] ?? null,
                'error_message' => $lastBar['error_message'] ?? null,
            ];
        }

        return $bars;
    }

    private function aggregateStatus(array $statuses): string
    {
        if (in_array('down', $statuses, true)) {
            return 'down';
        }

        if (in_array('degraded', $statuses, true)) {
            return 'degraded';
        }

        if (in_array('operational', $statuses, true)) {
            return 'operational';
        }

        return 'unknown';
    }

    private function overallMessage(string $status, int $totalMonitors): string
    {
        if ($totalMonitors === 0) {
            return 'No monitors have been added yet.';
        }

        return match ($status) {
            'operational' => 'All monitored endpoints are responding normally.',
            'degraded' => 'Some monitored endpoints are responding, but with degraded behavior.',
            'down' => 'One or more monitored endpoints are currently unavailable.',
            default => 'Status is still being collected.',
        };
    }
}
