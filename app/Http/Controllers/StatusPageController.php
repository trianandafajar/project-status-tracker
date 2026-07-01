<?php

namespace App\Http\Controllers;

use App\Models\StatusMonitor;
use App\Services\Status\StatusCheckRecorder;
use App\Services\Status\StatusMonitorProbe;
use App\Services\Status\StatusSnapshotBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StatusPageController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function snapshot(StatusSnapshotBuilder $snapshotBuilder): JsonResponse
    {
        return response()->json($snapshotBuilder->build());
    }

    public function store(
        Request $request,
        StatusMonitorProbe $probe,
        StatusCheckRecorder $recorder,
    ): JsonResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'group_name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['website', 'api', 'database'])],
            'url' => ['required', 'url', 'max:500', 'unique:status_monitors,url'],
            'method' => ['required', Rule::in(['HEAD', 'GET'])],
            'expected_status_code' => ['nullable', 'integer', 'between:100,599'],
            'expected_keyword' => ['nullable', 'string', 'max:255'],
            'request_body_template' => ['nullable', 'string', 'max:5000'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        if (($validated['method'] ?? 'HEAD') === 'HEAD' && ! empty($validated['expected_keyword'])) {
            throw ValidationException::withMessages([
                'expected_keyword' => 'Keyword check requires GET method.',
            ]);
        }

        $monitor = StatusMonitor::query()->create([
            'name' => $validated['name'],
            'group_name' => $validated['group_name'],
            'type' => $validated['type'],
            'url' => $validated['url'],
            'method' => $validated['method'],
            'expected_status_code' => $validated['expected_status_code'] ?? 200,
            'expected_keyword' => $validated['expected_keyword'] ?? null,
            'request_body_template' => $validated['request_body_template'] ?? null,
            'timeout_seconds' => $validated['timeout_seconds'] ?? 10,
            'enabled' => true,
        ]);

        $recorder->record($monitor, $probe->check($monitor));

        return response()->json([
            'message' => 'Monitor created successfully.',
            'monitor_id' => $monitor->id,
        ], 201);
    }

    public function refresh(
        StatusMonitor $statusMonitor,
        StatusMonitorProbe $probe,
        StatusCheckRecorder $recorder,
    ): JsonResponse {
        $recorder->record($statusMonitor, $probe->check($statusMonitor));

        return response()->json([
            'message' => 'Monitor checked successfully.',
            'monitor_id' => $statusMonitor->id,
        ]);
    }
}
