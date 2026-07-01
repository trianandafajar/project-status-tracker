<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Server::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('host', 'like', "%{$search}%");
            });
        }

        return ServerResource::collection(
            $query->paginate($request->per_page ?? 15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'auth_type' => 'required|string|in:key,password',
            'auth_key' => 'required|string',
            'connection_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'os' => 'nullable|string|max:255',
        ]);

        $validated['auth_key'] = encrypt($validated['auth_key']);

        $server = Server::create($validated);

        return response()->json(new ServerResource($server), 201);
    }

    public function show(Server $server): JsonResponse
    {
        return response()->json(new ServerResource($server));
    }

    public function update(Request $request, Server $server): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'host' => 'sometimes|string|max:255',
            'port' => 'sometimes|integer|min:1|max:65535',
            'username' => 'sometimes|string|max:255',
            'auth_type' => 'sometimes|string|in:key,password',
            'auth_key' => 'sometimes|string',
            'connection_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'os' => 'nullable|string|max:255',
            'status' => 'sometimes|string|in:online,offline,unknown',
        ]);

        if (isset($validated['auth_key'])) {
            $validated['auth_key'] = encrypt($validated['auth_key']);
        }

        $server->update($validated);

        return response()->json(new ServerResource($server));
    }

    public function destroy(Server $server): JsonResponse
    {
        $server->delete();

        return response()->json(['message' => 'Server deleted successfully']);
    }

    public function testConnection(Server $server): JsonResponse
    {
        try {
            $connection = app(\App\Services\Ssh\SshConnection::class, ['server' => $server]);
            $connected = $connection->connect();
            $connection->disconnect();

            if ($connected) {
                return response()->json(['message' => 'Connection successful', 'status' => 'online']);
            }

            return response()->json(['message' => 'Connection failed', 'status' => 'offline'], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Connection failed: ' . $e->getMessage(),
                'status' => 'offline',
            ], 500);
        }
    }
}
