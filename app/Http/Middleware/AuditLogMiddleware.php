<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        $route = $request->route();
        $params = $route?->parameters();
        $paramNames = $route?->parameterNames();

        $resourceType = null;
        $resourceId = null;
        $serverId = null;

        if ($paramNames && $params) {
            foreach ($paramNames as $name) {
                if ($name === 'server') {
                    $serverId = $params[$name] instanceof \App\Models\Server
                        ? $params[$name]->id
                        : $params[$name];
                }
                if ($name !== 'server') {
                    $resourceType = match ($name) {
                        'alert', 'audit_log', 'alert_rule', 'service', 'metric', 'user', 'setting' => str_replace('_', '-', $name),
                        default => $name,
                    };
                    if (isset($params[$name])) {
                        $resourceId = $params[$name] instanceof \App\Models\Model
                            ? $params[$name]->id
                            : $params[$name];
                    }
                }
            }
        }

        if (!$resourceType) {
            $path = $request->path();
            $resourceType = match (true) {
                str_contains($path, 'servers') => 'server',
                str_contains($path, 'auth') => 'auth',
                str_contains($path, 'settings') => 'setting',
                str_contains($path, 'users') => 'user',
                default => 'other',
            };
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'server_id' => $serverId,
            'action' => $request->method() . ' ' . $request->path(),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'details' => $request->except(['password', 'ssh_key', 'auth_key', 'password_confirmation']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
