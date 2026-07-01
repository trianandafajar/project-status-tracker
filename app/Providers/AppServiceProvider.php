<?php

namespace App\Providers;

use App\Services\Monitoring\CpuMonitor;
use App\Services\Monitoring\DiskMonitor;
use App\Services\Monitoring\MonitorInterface;
use App\Services\Monitoring\RamMonitor;
use App\Services\Ssh\SshConnection;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MonitorInterface::class, function ($app, $params) {
            return match ($params['type']) {
                'cpu'     => new CpuMonitor($params['server']),
                'ram'     => new RamMonitor($params['server']),
                'disk'    => new DiskMonitor($params['server']),
                default   => throw new \InvalidArgumentException("Unknown monitor type: {$params['type']}"),
            };
        });

        $this->app->bind(SshConnection::class, function ($app, $params) {
            $server = $params['server'] ?? throw new \InvalidArgumentException('Missing server parameter');
            return new SshConnection($server, $app->make(\App\Services\Security\CredentialEncrypter::class));
        });
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
