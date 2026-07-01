<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Server;
use App\Models\Website;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $servers = Server::all();
        $websites = Website::all();

        return response()->json([
            'total_servers' => $servers->count(),
            'online_servers' => $servers->where('status', 'online')->count(),
            'offline_servers' => $servers->where('status', 'offline')->count(),
            'unknown_servers' => $servers->where('status', 'unknown')->count(),
            'average_health_score' => round($servers->avg('health_score') ?? 0, 2),
            'total_alerts_open' => Alert::whereIn('status', ['open', 'acknowledged'])->count(),
            'total_alerts_critical' => Alert::where('status', 'open')->where('severity', 'critical')->count(),
            'total_alerts_warning' => Alert::where('status', 'open')->where('severity', 'warning')->count(),
            'total_websites' => $websites->count(),
            'websites_up' => $websites->where('last_status', 'up')->count(),
            'websites_down' => $websites->where('last_status', 'down')->count(),
            'websites_degraded' => $websites->where('last_status', 'degraded')->count(),
            'servers' => $servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'status' => $s->status,
                'health_score' => $s->health_score,
            ]),
            'websites' => $websites->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'url' => $w->url,
                'last_status' => $w->last_status,
                'last_http_code' => $w->last_http_code,
                'last_response_ms' => $w->last_response_ms,
                'last_uptime_percent' => $w->last_uptime_percent,
            ]),
        ]);
    }

    public function health(): JsonResponse
    {
        $servers = Server::all()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'host' => $s->host,
            'status' => $s->status,
            'health_score' => $s->health_score,
            'last_checked_at' => $s->last_checked_at,
            'os' => $s->os,
        ]);

        return response()->json([
            'servers' => $servers,
            'healthy_count' => $servers->where('health_score', '>=', 80)->count(),
            'warning_count' => $servers->where('health_score', '>=', 50)->where('health_score', '<', 80)->count(),
            'critical_count' => $servers->where('health_score', '<', 50)->count(),
        ]);
    }
}
