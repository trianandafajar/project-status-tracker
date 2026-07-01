<?php

namespace Database\Seeders\Dev;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // --- Users ---
        $adminId = User::create(['name' => 'John Operator', 'email' => 'john@sentinel.local', 'password' => Hash::make('password'), 'role' => 'admin'])->id;
        $opId = User::create(['name' => 'Jane Engineer', 'email' => 'jane@sentinel.local', 'password' => Hash::make('password'), 'role' => 'operator'])->id;
        $viewerId = User::create(['name' => 'Bot Viewer', 'email' => 'bot@sentinel.local', 'password' => Hash::make('password'), 'role' => 'viewer'])->id;
        $userIds = [$adminId, $opId, $viewerId];

        // --- Servers ---
        $servers = [
            ['name' => 'Production US', 'host' => 'prod-01.sentinel.local', 'port' => 22, 'username' => 'deploy', 'auth_type' => 'key', 'auth_key' => null, 'connection_type' => 'ssh', 'status' => 'online', 'health_score' => 100, 'os' => 'Ubuntu 22.04.4 LTS', 'notes' => 'Primary production server', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Staging EU', 'host' => 'staging-01.sentinel.local', 'port' => 22, 'username' => 'deploy', 'auth_type' => 'key', 'auth_key' => null, 'connection_type' => 'ssh', 'status' => 'online', 'health_score' => 95, 'os' => 'Ubuntu 22.04.4 LTS', 'notes' => 'Staging environment', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Development', 'host' => 'dev.sentinel.local', 'port' => 2222, 'username' => 'vagrant', 'auth_type' => 'password', 'auth_key' => null, 'connection_type' => 'ssh', 'status' => 'offline', 'health_score' => 0, 'os' => 'Debian 12 Bookworm', 'notes' => 'Local dev VM - currently powered off', 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('servers')->insert($servers);
        $serverIds = DB::table('servers')->pluck('id')->toArray();

        // --- Services ---
        $serviceTypes = ['nginx', 'php-fpm', 'pm2', 'redis', 'mysql', 'queue-worker', 'docker'];
        $serviceNames = [
            'nginx' => 'Nginx Web Server',
            'php-fpm' => 'PHP-FPM 8.3',
            'pm2' => 'PM2 Process Manager',
            'redis' => 'Redis Cache',
            'mysql' => 'MySQL 8.0',
            'queue-worker' => 'Queue Worker',
            'docker' => 'Docker Engine',
        ];
        $serviceRows = [];
        foreach ($serverIds as $sid) {
            foreach ($serviceTypes as $st) {
                $serviceRows[] = ['server_id' => $sid, 'type' => $st, 'name' => $serviceNames[$st], 'status' => 'running', 'current_output' => null, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('services')->insert($serviceRows);
        $allServices = DB::table('services')->whereIn('server_id', $serverIds)->get();

        // --- Metrics (7 days, 1-min interval cycling cpu/ram/disk = ~10k per server) ---
        $metricTypes = ['cpu', 'ram', 'disk'];
        $chunk = [];
        $startDate = $now->copy()->subDays(7);
        foreach ($serverIds as $sid) {
            for ($i = 0; $i < 10080; $i++) {
                $type = $metricTypes[$i % 3];
                $value = match ($type) {
                    'cpu' => round(rand(150, 950) / 10, 1),
                    'ram' => round(rand(200, 920) / 10, 1),
                    'disk' => round(rand(100, 880) / 10, 1),
                };
                $recordedAt = $startDate->copy()->addMinutes($i);
                $chunk[] = [
                    'server_id' => $sid,
                    'type' => $type,
                    'value' => $value,
                    'unit' => 'percent',
                    'metadata' => null,
                    'recorded_at' => $recordedAt,
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ];
                if (count($chunk) >= 500) {
                    DB::table('metrics')->insert($chunk);
                    $chunk = [];
                }
            }
        }
        if ($chunk) {
            DB::table('metrics')->insert($chunk);
            $chunk = [];
        }

        // --- Service Status History (24h, 30-sec interval) ---
        $historyStart = $now->copy()->subHours(24);
        foreach ($allServices as $svc) {
            for ($i = 0; $i < 2880; $i++) {
                $checkedAt = $historyStart->copy()->addSeconds($i * 30);
                $status = ($i % 100 === 0 && rand(1, 100) <= 5) ? 'restarting' : 'running';
                $chunk[] = [
                    'service_id' => $svc->id,
                    'status' => $status,
                    'output' => null,
                    'checked_at' => $checkedAt,
                ];
                if (count($chunk) >= 500) {
                    DB::table('service_status_history')->insert($chunk);
                    $chunk = [];
                }
            }
        }
        if ($chunk) {
            DB::table('service_status_history')->insert($chunk);
            $chunk = [];
        }

        // --- Alert Channels ---
        $channelData = [
            ['server_id' => null, 'type' => 'telegram', 'name' => 'Telegram Ops', 'config' => json_encode(['bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'chat_id' => '-1001234567890']), 'enabled' => false, 'created_at' => $now, 'updated_at' => $now],
            ['server_id' => null, 'type' => 'discord', 'name' => 'Discord Alerts', 'config' => json_encode(['webhook_url' => 'https://discord.com/api/webhooks/123456/abc-def']), 'enabled' => false, 'created_at' => $now, 'updated_at' => $now],
            ['server_id' => null, 'type' => 'email', 'name' => 'Email Alert', 'config' => json_encode(['email' => 'ops@sentinel.local', 'smtp_host' => 'mail.sentinel.local', 'smtp_port' => 587]), 'enabled' => false, 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('alert_channels')->insert($channelData);
        $channelIds = DB::table('alert_channels')->pluck('id')->toArray();

        // --- Alerts ---
        $severities = ['critical', 'warning', 'info'];
        $alertData = [];

        // 5 open
        for ($i = 1; $i <= 5; $i++) {
            $alertData[] = [
                'server_id' => $serverIds[array_rand($serverIds)],
                'alert_rule_id' => null,
                'title' => "Open Alert #{$i}: High CPU Usage",
                'message' => "Server CPU usage exceeded 90% threshold",
                'severity' => $severities[array_rand($severities)],
                'status' => 'open',
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'resolved_by' => null,
                'resolved_at' => null,
                'auto_resolved' => false,
                'triggered_at' => $now->copy()->subHours(rand(1, 72)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 3 acknowledged
        for ($i = 1; $i <= 3; $i++) {
            $ackAt = $now->copy()->subHours(rand(1, 48));
            $alertData[] = [
                'server_id' => $serverIds[array_rand($serverIds)],
                'alert_rule_id' => null,
                'title' => "Acknowledged Alert #{$i}: RAM Warning",
                'message' => "Server RAM usage exceeded 90% threshold",
                'severity' => 'warning',
                'status' => 'acknowledged',
                'acknowledged_by' => $adminId,
                'acknowledged_at' => $ackAt,
                'resolved_by' => null,
                'resolved_at' => null,
                'auto_resolved' => false,
                'triggered_at' => $ackAt->copy()->subMinutes(rand(10, 120)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 10 resolved
        for ($i = 1; $i <= 10; $i++) {
            $resolvedAt = $now->copy()->subHours(rand(1, 168));
            $ackAt = $resolvedAt->copy()->subMinutes(rand(10, 60));
            $alertData[] = [
                'server_id' => $serverIds[array_rand($serverIds)],
                'alert_rule_id' => null,
                'title' => "Resolved Alert #{$i}: " . ($i % 2 === 0 ? 'Disk Space' : 'Service Down'),
                'message' => ($i % 2 === 0 ? 'Disk usage exceeded 85%' : 'Service was detected as down'),
                'severity' => $severities[array_rand($severities)],
                'status' => 'resolved',
                'acknowledged_by' => $adminId,
                'acknowledged_at' => $ackAt,
                'resolved_by' => $opId,
                'resolved_at' => $resolvedAt,
                'auto_resolved' => (bool) rand(0, 1),
                'triggered_at' => $resolvedAt->copy()->subMinutes(rand(30, 240)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('alerts')->insert($alertData);
        $alertIds = DB::table('alerts')->pluck('id')->toArray();

        // --- Alert Notifications ---
        $notifData = [];
        foreach ($alertIds as $aid) {
            $alert = DB::table('alerts')->find($aid);
            foreach ($channelIds as $cid) {
                $notifData[] = [
                    'alert_id' => $aid,
                    'channel_id' => $cid,
                    'sent_at' => $alert->triggered_at,
                    'status' => 'sent',
                    'error_message' => null,
                    'created_at' => $now,
                ];
            }
        }
        DB::table('alert_notifications')->insert($notifData);

        // --- Audit Logs ---
        $actions = ['server.create', 'server.update', 'service.restart', 'alert.resolve', 'healing.execute', 'user.login', 'settings.update'];
        $resources = ['server', 'service', 'alert', 'user', 'settings'];
        $auditLogs = [];
        for ($i = 0; $i < 50; $i++) {
            $createdAt = $now->copy()->subHours(rand(1, 168));
            $auditLogs[] = [
                'user_id' => $userIds[array_rand($userIds)],
                'server_id' => $serverIds[array_rand($serverIds)],
                'action' => $actions[array_rand($actions)],
                'resource_type' => $resources[array_rand($resources)],
                'resource_id' => rand(1, 100),
                'details' => json_encode(['key' => 'value', 'description' => 'Sample audit entry']),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Sentinel CLI',
                'created_at' => $createdAt,
            ];
            if (count($auditLogs) >= 500) {
                DB::table('audit_logs')->insert($auditLogs);
                $auditLogs = [];
            }
        }
        if ($auditLogs) {
            DB::table('audit_logs')->insert($auditLogs);
        }

        // --- SSL Certificates ---
        $sslCerts = [
            [
                'server_id' => $serverIds[0],
                'domain' => 'sentinel.local',
                'issuer' => "Let's Encrypt",
                'valid_from' => $now->copy()->subDays(30)->toDateString(),
                'valid_to' => $now->copy()->addDays(60)->toDateString(),
                'days_remaining' => 60,
                'status' => 'valid',
                'san' => json_encode(['sentinel.local', 'www.sentinel.local']),
                'error_message' => null,
                'checked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'server_id' => $serverIds[0],
                'domain' => 'api.sentinel.local',
                'issuer' => "Let's Encrypt",
                'valid_from' => $now->copy()->subDays(90)->toDateString(),
                'valid_to' => $now->copy()->addDays(90)->toDateString(),
                'days_remaining' => 90,
                'status' => 'valid',
                'san' => json_encode(['api.sentinel.local']),
                'error_message' => null,
                'checked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'server_id' => $serverIds[1],
                'domain' => 'staging.sentinel.local',
                'issuer' => "Let's Encrypt",
                'valid_from' => $now->copy()->subDays(80)->toDateString(),
                'valid_to' => $now->copy()->addDays(14)->toDateString(),
                'days_remaining' => 14,
                'status' => 'expiring_soon',
                'san' => json_encode(['staging.sentinel.local']),
                'error_message' => null,
                'checked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'server_id' => $serverIds[2],
                'domain' => 'dev.sentinel.local',
                'issuer' => 'Self-Signed',
                'valid_from' => $now->copy()->subDays(400)->toDateString(),
                'valid_to' => $now->copy()->subDays(30)->toDateString(),
                'days_remaining' => -30,
                'status' => 'expired',
                'san' => json_encode(['dev.sentinel.local']),
                'error_message' => 'Certificate expired 30 days ago',
                'checked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        DB::table('ssl_certificates')->insert($sslCerts);

        // --- Websites ---
        $websiteData = [
            [
                'server_id' => $serverIds[0], 'url' => 'https://sentinel.local', 'name' => 'Sentinel Dashboard',
                'check_interval_seconds' => 60, 'expected_status_code' => 200, 'expected_keyword' => 'Sentinel',
                'timeout_seconds' => 10, 'enabled' => true, 'last_checked_at' => $now,
                'last_status' => 'up', 'last_http_code' => 200, 'last_response_ms' => 89, 'last_uptime_percent' => 99.97,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'server_id' => $serverIds[0], 'url' => 'https://api.sentinel.local/health', 'name' => 'API Health Check',
                'check_interval_seconds' => 60, 'expected_status_code' => 200, 'expected_keyword' => null,
                'timeout_seconds' => 10, 'enabled' => true, 'last_checked_at' => $now,
                'last_status' => 'up', 'last_http_code' => 200, 'last_response_ms' => 120, 'last_uptime_percent' => 99.85,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'server_id' => $serverIds[1], 'url' => 'https://staging.sentinel.local', 'name' => 'Staging App',
                'check_interval_seconds' => 60, 'expected_status_code' => 200, 'expected_keyword' => 'Staging',
                'timeout_seconds' => 10, 'enabled' => true, 'last_checked_at' => $now,
                'last_status' => 'up', 'last_http_code' => 200, 'last_response_ms' => 250, 'last_uptime_percent' => 98.2,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'server_id' => $serverIds[2], 'url' => 'https://dev.sentinel.local', 'name' => 'Dev App',
                'check_interval_seconds' => 120, 'expected_status_code' => 200, 'expected_keyword' => null,
                'timeout_seconds' => 10, 'enabled' => true, 'last_checked_at' => $now,
                'last_status' => 'down', 'last_http_code' => 0, 'last_response_ms' => 0, 'last_uptime_percent' => 45.0,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ];
        DB::table('websites')->insert($websiteData);
        $websiteIds = DB::table('websites')->pluck('id')->toArray();

        // --- Website Checks (24h history, 1-min interval for first 3, sparse for dev) ---
        $checkChunk = [];
        $checkStart = $now->copy()->subHours(24);
        foreach ($websiteIds as $idx => $wid) {
            $website = DB::table('websites')->find($wid);
            $interval = min($website->check_interval_seconds, 120);
            $totalChecks = (int) (86400 / $interval);
            for ($i = 0; $i < $totalChecks; $i++) {
                $checkedAt = $checkStart->copy()->addSeconds($i * $interval);
                $isUp = $idx !== 3 || ($i % 10 > 3);
                $httpCode = $isUp ? $website->expected_status_code : 0;
                $responseMs = $isUp ? rand(50, 400) : 0;
                $checkChunk[] = [
                    'website_id' => $wid,
                    'http_status_code' => $httpCode,
                    'response_time_ms' => $responseMs,
                    'ssl_days_remaining' => $idx === 3 ? -30 : ($idx === 2 ? 14 : rand(45, 90)),
                    'ssl_status' => $idx === 3 ? 'expired' : ($idx === 2 ? 'expiring_soon' : 'valid'),
                    'is_up' => $isUp,
                    'error_message' => $isUp ? null : 'Connection refused',
                    'checked_at' => $checkedAt,
                ];
                if (count($checkChunk) >= 500) {
                    DB::table('website_checks')->insert($checkChunk);
                    $checkChunk = [];
                }
            }
        }
        if ($checkChunk) {
            DB::table('website_checks')->insert($checkChunk);
        }
    }
}
