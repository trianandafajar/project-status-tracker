<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Alert::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        return AlertResource::collection(
            $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15)
        );
    }

    public function show(Alert $alert): JsonResponse
    {
        return response()->json(new AlertResource($alert));
    }

    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);

        return response()->json(new AlertResource($alert));
    }

    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return response()->json(new AlertResource($alert));
    }
}
