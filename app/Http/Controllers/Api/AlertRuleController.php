<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlertRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertRuleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AlertRule::query();

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        return JsonResource::collection(
            $query->paginate($request->per_page ?? 15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'name' => 'required|string|max:255',
            'metric_type' => 'nullable|string|max:50',
            'service_type' => 'nullable|string|max:50',
            'operator' => 'required|string|in:>,<,>=,<=,==,!=',
            'threshold' => 'required|numeric',
            'severity' => 'required|string|in:critical,warning,info',
            'enabled' => 'boolean',
            'cooldown_minutes' => 'nullable|integer|min:0',
        ]);

        $rule = AlertRule::create($validated);

        return response()->json($rule, 201);
    }

    public function update(Request $request, AlertRule $alertRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'metric_type' => 'nullable|string|max:50',
            'service_type' => 'nullable|string|max:50',
            'operator' => 'sometimes|string|in:>,<,>=,<=,==,!=',
            'threshold' => 'sometimes|numeric',
            'severity' => 'sometimes|string|in:critical,warning,info',
            'enabled' => 'boolean',
            'cooldown_minutes' => 'nullable|integer|min:0',
        ]);

        $alertRule->update($validated);

        return response()->json($alertRule);
    }

    public function destroy(AlertRule $alertRule): JsonResponse
    {
        $alertRule->delete();

        return response()->json(['message' => 'Alert rule deleted successfully']);
    }

    public function toggle(AlertRule $alertRule): JsonResponse
    {
        $alertRule->update(['enabled' => !$alertRule->enabled]);

        return response()->json($alertRule);
    }
}
