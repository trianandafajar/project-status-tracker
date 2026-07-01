<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebsiteResource;
use App\Http\Resources\WebsiteCheckResource;
use App\Models\Server;
use App\Models\Website;
use App\Services\Monitoring\WebsiteMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function index(Server $server, Request $request): JsonResponse
    {
        $query = $server->websites();

        if ($request->filled('status')) {
            $query->where('last_status', $request->status);
        }

        return response()->json(
            WebsiteResource::collection($query->paginate($request->per_page ?? 15))
        );
    }

    public function store(Server $server, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'name' => 'required|string|max:255',
            'check_interval_seconds' => 'integer|min:30|max:3600',
            'expected_status_code' => 'integer|min:100|max:599',
            'expected_keyword' => 'nullable|string|max:255',
            'timeout_seconds' => 'integer|min:1|max:60',
            'enabled' => 'boolean',
        ]);

        $website = $server->websites()->create($validated);

        return response()->json(new WebsiteResource($website), 201);
    }

    public function show(Website $website): JsonResponse
    {
        return response()->json(new WebsiteResource($website->load('checks')));
    }

    public function update(Request $request, Website $website): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'sometimes|url|max:500',
            'name' => 'sometimes|string|max:255',
            'check_interval_seconds' => 'sometimes|integer|min:30|max:3600',
            'expected_status_code' => 'sometimes|integer|min:100|max:599',
            'expected_keyword' => 'nullable|string|max:255',
            'timeout_seconds' => 'sometimes|integer|min:1|max:60',
            'enabled' => 'sometimes|boolean',
        ]);

        $website->update($validated);

        return response()->json(new WebsiteResource($website));
    }

    public function destroy(Website $website): JsonResponse
    {
        $website->delete();

        return response()->json(['message' => 'Website deleted successfully']);
    }

    public function check(Website $website, WebsiteMonitor $monitor): JsonResponse
    {
        $result = $monitor->check($website);

        return response()->json([
            'website' => new WebsiteResource($website),
            'check_result' => $result,
        ]);
    }

    public function history(Website $website, Request $request): JsonResponse
    {
        $checks = $website->checks()
            ->latest('checked_at')
            ->paginate($request->per_page ?? 50);

        return response()->json(WebsiteCheckResource::collection($checks));
    }
}
