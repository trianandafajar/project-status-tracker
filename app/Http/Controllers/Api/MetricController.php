<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MetricResource;
use App\Models\Metric;
use App\Models\Server;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MetricController extends Controller
{
    public function index(Request $request, Server $server): AnonymousResourceCollection
    {
        $query = Metric::where('server_id', $server->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return MetricResource::collection(
            $query->orderBy('recorded_at', 'desc')->paginate($request->per_page ?? 50)
        );
    }

    public function latest(Server $server): JsonResponse
    {
        $types = Metric::where('server_id', $server->id)
            ->selectRaw('DISTINCT type')
            ->pluck('type');

        $latest = $types->map(fn ($type) => Metric::where('server_id', $server->id)
            ->where('type', $type)
            ->orderBy('recorded_at', 'desc')
            ->first()
        )->filter();

        return response()->json([
            'data' => MetricResource::collection($latest),
        ]);
    }

    public function history(Request $request, Server $server): AnonymousResourceCollection
    {
        $request->validate([
            'type' => 'required|string',
            'interval' => 'nullable|string|in:1m,5m,15m,1h,1d',
        ]);

        $interval = $request->interval ?? '1h';
        $now = Carbon::now();

        $from = match ($interval) {
            '1m' => $now->copy()->subMinute(),
            '5m' => $now->copy()->subMinutes(5),
            '15m' => $now->copy()->subMinutes(15),
            '1h' => $now->copy()->subHour(),
            '1d' => $now->copy()->subDay(),
            '7d' => $now->copy()->subDays(7),
            default => $now->copy()->subHour(),
        };

        $query = Metric::where('server_id', $server->id)
            ->where('type', $request->type)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at', 'asc');

        return MetricResource::collection($query->get());
    }
}
