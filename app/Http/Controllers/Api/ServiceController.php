<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
use App\Models\Service;
use App\Models\ServiceStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(Server $server): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            $server->services()->paginate(15)
        );
    }

    public function show(Server $server, Service $service): JsonResponse
    {
        if ($service->server_id !== $server->id) {
            abort(404);
        }

        return response()->json(new ServiceResource($service));
    }

    public function history(Server $server, Service $service): JsonResponse
    {
        if ($service->server_id !== $server->id) {
            abort(404);
        }

        $history = $service->statusHistory()
            ->orderBy('checked_at', 'desc')
            ->paginate(30);

        return response()->json($history);
    }

    public function restart(Server $server, Service $service): JsonResponse
    {
        if ($service->server_id !== $server->id) {
            abort(404);
        }

        $connection = app(\App\Services\Ssh\SshConnection::class, ['server' => $server]);
        $connection->connect();
        $output = $connection->exec("systemctl restart {$service->name}");
        $connection->disconnect();

        $service->update(['current_output' => $output]);

        return response()->json(['message' => 'Service restart queued', 'service' => new ServiceResource($service)]);
    }

    public function start(Server $server, Service $service): JsonResponse
    {
        if ($service->server_id !== $server->id) {
            abort(404);
        }

        $connection = app(\App\Services\Ssh\SshConnection::class, ['server' => $server]);
        $connection->connect();
        $output = $connection->exec("systemctl start {$service->name}");
        $connection->disconnect();

        $service->update(['current_output' => $output]);

        return response()->json(['message' => 'Service start queued', 'service' => new ServiceResource($service)]);
    }

    public function stop(Server $server, Service $service): JsonResponse
    {
        if ($service->server_id !== $server->id) {
            abort(404);
        }

        $connection = app(\App\Services\Ssh\SshConnection::class, ['server' => $server]);
        $connection->connect();
        $output = $connection->exec("systemctl stop {$service->name}");
        $connection->disconnect();

        $service->update(['current_output' => $output]);

        return response()->json(['message' => 'Service stop queued', 'service' => new ServiceResource($service)]);
    }
}
