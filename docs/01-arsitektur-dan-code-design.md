# 01 — Arsitektur & Code Design

## Arsitektur High-Level

```
Browser (TailwindCSS + Alpine.js)
        │
        ▼
Laravel Dashboard ──read──► Redis / MySQL
        │
        ├── POST command via API ──► Queue (Redis)
        │                                   │
        │                                   ▼
        │                           Monitoring Jobs
        │                                   │
        │                                   ▼
        │                           Monitoring Services
        │                                   │
        │                                   ▼
        └─── Reverb (WebSocket) ◄── Events ◄── Linux Services
                                             (Nginx, PM2, PHP-FPM,
                                              Redis, MySQL, Docker)
```

**Prinsip:** Dashboard tidak menjalankan shell command secara langsung.
Semua command masuk Queue → Job → Service → Server target.

## Struktur Folder Lengkap

```
app/
├── Console/
│   └── Commands/
│       └── Monitor/
│           ├── CheckServices.php         # 30 detik
│           ├── CollectMetrics.php        # 1 menit
│           ├── CheckSsl.php              # 5 menit
│           ├── CheckEnv.php              # 10 menit
│           ├── ScanPorts.php             # manual / harian
│           ├── Analyze502.php            # on-demand / 5 menit
│           └── PruneOldData.php          # harian
├── Services/
│   ├── Monitoring/
│   │   ├── MonitorInterface.php          # kontrak collect() untuk semua monitor
│   │   ├── BaseMonitor.php               # abstract, shared logic
│   │   ├── CpuMonitor.php
│   │   ├── RamMonitor.php
│   │   ├── DiskMonitor.php
│   │   ├── NginxMonitor.php
│   │   ├── PhpFpmMonitor.php
│   │   ├── Pm2Monitor.php
│   │   ├── RedisMonitor.php
│   │   ├── MySqlMonitor.php
│   │   ├── QueueMonitor.php
│   │   ├── DockerMonitor.php
│   │   ├── PythonMonitor.php
│   │   └── NetworkMonitor.php
│   ├── Alerts/
│   │   ├── AlertEvaluator.php
│   │   ├── AlertDispatcher.php
│   │   ├── Channels/
│   │   │   ├── AlertChannelInterface.php
│   │   │   ├── TelegramChannel.php
│   │   │   ├── DiscordChannel.php
│   │   │   └── EmailChannel.php
│   ├── Metrics/
│   │   ├── MetricsCollector.php
│   │   ├── MetricsAggregator.php
│   │   └── MetricsQuery.php
│   ├── Security/
│   │   ├── CommandWhitelist.php
│   │   ├── CredentialEncrypter.php
│   │   └── AuditLogger.php
│   ├── Ssh/
│   │   ├── SshConnection.php
│   │   └── SshCommandRunner.php
│   ├── LogParser/
│   │   ├── LogParserService.php
│   │   ├── Parsers/
│   │   │   ├── NginxErrorParser.php
│   │   │   ├── NginxAccessParser.php
│   │   │   ├── PhpFpmParser.php
│   │   │   ├── SyslogParser.php
│   │   │   └── AppLogParser.php
│   │   └── LogPattern.php               # pola regex per service
│   ├── Analyzer/
│   │   ├── Error502Analyzer.php
│   │   └── RootCauseEngine.php
│   ├── SelfHealing/
│   │   ├── HealingRuleEngine.php
│   │   ├── Actions/
│   │   │   ├── RestartService.php
│   │   │   ├── ClearCache.php
│   │   │   ├── RunCommand.php
│   │   │   └── ScaleWorker.php
│   │   └── HealingLogger.php
│   ├── Ssl/
│   │   └── SslChecker.php
│   ├── Port/
│   │   └── PortScanner.php
│   └── Env/
│       └── EnvChecker.php
├── Jobs/
│   ├── MonitorServiceJob.php
│   ├── CollectMetricsJob.php
│   ├── CheckSslJob.php
│   ├── CheckEnvJob.php
│   ├── ScanPortJob.php
│   ├── ParseLogsJob.php
│   ├── Analyze502Job.php
│   ├── DispatchAlertJob.php
│   ├── ExecuteHealingJob.php
│   ├── ExecuteServiceActionJob.php
│   ├── PruneDataJob.php
│   ├── AggregateMetricsJob.php
│   ├── PredictFailureJob.php
│   ├── EvaluatePredictionsJob.php
│   └── TrainModelJob.php
├── Events/
│   ├── ServiceDown.php
│   ├── ServiceUp.php
│   ├── MetricCollected.php
│   ├── AlertTriggered.php
│   ├── AlertAcknowledged.php
│   ├── AlertResolved.php
│   ├── ServerStatusChanged.php
│   ├── SslExpiring.php
│   ├── HealingExecuted.php
│   └── LogsParsed.php
├── Listeners/
│   ├── BroadcastServiceStatus.php
│   ├── BroadcastMetric.php
│   ├── BroadcastAlert.php
│   ├── EvaluateAlertRules.php
│   ├── SendAlertNotification.php
│   └── LogAuditEvent.php
├── Models/
│   ├── Server.php
│   ├── Metric.php
│   ├── Service.php
│   ├── ServiceStatus.php
│   ├── Alert.php
│   ├── AlertRule.php
│   ├── AlertChannel.php
│   ├── AlertNotification.php
│   ├── LogEntry.php
│   ├── AuditLog.php
│   ├── SslCertificate.php
│   ├── EnvCheck.php
│   ├── PortScan.php
│   ├── PortScanResult.php
│   ├── Error502Analysis.php
│   ├── HealingRule.php
│   ├── HealingLog.php
│   ├── DockerContainer.php
│   ├── PythonProcess.php
│   └── User.php
├── DTO/
│   ├── MetricData.php
│   ├── ServiceStatusData.php
│   ├── AlertData.php
│   ├── LogEntryData.php
│   ├── SslData.php
│   ├── PortData.php
│   ├── HealingResultData.php
│   └── ServerHealthData.php
├── Enums/
│   ├── MetricType.php                # cpu, ram, disk, network_in, network_out
│   ├── ServiceType.php               # nginx, php-fpm, pm2, redis, mysql, queue, docker
│   ├── ServiceStatus.php             # running, stopped, restarting, unknown
│   ├── AlertSeverity.php             # critical, warning, info
│   ├── AlertStatus.php               # open, acknowledged, resolved
│   ├── AlertChannelType.php          # telegram, discord, email
│   ├── LogLevel.php                  # debug, info, notice, warning, error, critical
│   ├── HealingActionType.php         # restart_service, clear_cache, run_command, scale_worker
│   ├── HealingStatus.php             # success, failed, skipped
│   ├── SslStatus.php                 # valid, expiring_soon, expired
│   └── ServerConnectionType.php      # ssh, agent
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── DashboardController.php
│   │   │   ├── ServerController.php
│   │   │   ├── ServiceController.php
│   │   │   ├── MetricController.php
│   │   │   ├── AlertController.php
│   │   │   ├── AlertRuleController.php
│   │   │   ├── AlertChannelController.php
│   │   │   ├── AlertNotificationController.php
│   │   │   ├── LogController.php
│   │   │   ├── AuditLogController.php
│   │   │   ├── SslMonitorController.php
│   │   │   ├── EnvCheckerController.php
│   │   │   ├── PortMonitorController.php
│   │   │   ├── Error502Controller.php
│   │   │   ├── SelfHealingController.php
│   │   │   ├── DockerMonitorController.php
│   │   │   ├── PythonMonitorController.php
│   │   │   ├── NetworkMonitorController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── UserController.php
│   │   │   ├── JobController.php
│   │   │   ├── PredictionController.php
│   │   │   ├── ModelController.php
│   │   │   └── AuthController.php
│   │   └── Web/
│   │       └── DashboardController.php    # serve Blade view
│   ├── Middleware/
│   │   ├── AuditLogMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── RoleMiddleware.php
│   └── Resources/
│       ├── ServerResource.php
│       ├── MetricResource.php
│       ├── ServiceResource.php
│       ├── AlertResource.php
│       ├── AlertRuleResource.php
│       ├── AlertChannelResource.php
│       ├── LogEntryResource.php
│       ├── AuditLogResource.php
│       ├── SslCertificateResource.php
│       ├── EnvCheckResource.php
│       ├── PortScanResource.php
│       ├── Error502AnalysisResource.php
│       ├── HealingRuleResource.php
│       ├── HealingLogResource.php
│       ├── DockerContainerResource.php
│       ├── PythonProcessResource.php
│       └── UserResource.php
├── Rules/
│   └── ValidCronExpression.php
└── Notifications/
    ├── ServiceDownNotification.php
    ├── SslExpiringNotification.php
    └── AlertNotification.php
```

## Service Container / Dependency Injection

Semua monitor class di-binding ke interface untuk testability:

```php
// AppServiceProvider.php
$this->app->bind(MonitorInterface::class, function ($app, $params) {
    return match ($params['type']) {
        'cpu'     => new CpuMonitor($params['server']),
        'ram'     => new RamMonitor($params['server']),
        'disk'    => new DiskMonitor($params['server']),
        'nginx'   => new NginxMonitor($params['server']),
        'php-fpm' => new PhpFpmMonitor($params['server']),
        'pm2'     => new Pm2Monitor($params['server']),
        'redis'   => new RedisMonitor($params['server']),
        'mysql'   => new MySqlMonitor($params['server']),
        'queue'   => new QueueMonitor($params['server']),
        'docker'  => new DockerMonitor($params['server']),
        'python'  => new PythonMonitor($params['server']),
        'network' => new NetworkMonitor($params['server']),
    };
});
```

## Event-Driven Flow

```
Scheduler
  → Job (CollectMetricsJob / MonitorServiceJob / ParseLogsJob / CheckSslJob)
    → Service (CpuMonitor / NginxMonitor / LogParserService / SslChecker / etc)
      → Model (Metric / ServiceStatus / LogEntry / SslCertificate)
        → Event (MetricCollected / ServiceDown / ServiceUp / LogsParsed / SslExpiring)
          → Listener (Broadcast via Reverb)
          → Listener (EvaluateAlertRules)
            → Alert triggered? → Event AlertTriggered
              → Listener (BroadcastAlert via Reverb)
              → AlertDispatcher → Telegram / Discord / Email
              → Healing rule exists? → ExecuteHealingJob
                → Event HealingExecuted → Reverb broadcast
          → Listener (LogAuditEvent)

User Action (HTTP Controller)
  → Acknowledge/Resolve Alert
    → Event (AlertAcknowledged / AlertResolved)
      → Reverb broadcast → semua viewer lihat update realtime
      → LogAuditEvent

Server Connection Check
  → MonitorServiceJob ping server
    → Status berubah (online↔offline)
      → Event ServerStatusChanged → Reverb broadcast
```

## Reverb Channel Map

| Channel | Event | Payload | Trigger |
|---------|-------|---------|---------|
| `private-server.{id}` | `ServerHealthUpdated` | `{ health_score: int, cpu_percent: float, ram_percent: float, disk_percent: float, alert_count: { critical: int, warning: int }, services: { running: int, total: int } }` | Selesai CollectMetricsJob |
| `private-server.{id}` | `ServerStatusChanged` | `{ server_id: int, status: "online"\|"offline", checked_at: string }` | Server ping berubah status |
| `private-server.{id}` | `ServiceStatusChanged` | `{ service_id: int, type: string, name: string, status: "running"\|"stopped"\|"restarting", previous_status: string, checked_at: string }` | MonitorServiceJob deteksi perubahan |
| `private-server.{id}` | `MetricUpdated` | `{ type: string, value: float, unit: string, recorded_at: string }` | Per metric type setelah collect |
| `private-server.{id}` | `AlertNew` | `{ alert_id: int, rule_name: string, severity: "critical"\|"warning"\|"info", title: string, message: string, created_at: string }` | AlertTriggered event |
| `private-server.{id}` | `AlertAcknowledged` | `{ alert_id: int, acknowledged_by: { id: int, name: string }, acknowledged_at: string }` | User ack alert |
| `private-server.{id}` | `AlertResolved` | `{ alert_id: int, resolved_by: { id: int, name: string }, resolved_at: string, auto_resolved: bool }` | User / auto resolve alert |
| `private-server.{id}` | `HealingExecuted` | `{ healing_log_id: int, rule_name: string, action_type: string, status: "success"\|"failed", output: string?, executed_at: string }` | ExecuteHealingJob selesai |
| `private-server.{id}` | `SslExpiring` | `{ domain: string, days_remaining: int, valid_to: string, status: "expiring_soon"\|"expired" }` | CheckSslJob deteksi <30 hari |
| `private-server.{id}` | `LogsParsed` | `{ source: string, new_entries: int, levels: { error: int, warning: int, info: int } }` | ParseLogsJob selesai |
| `presence-dashboard` | `user.joined` | `{ user: { id: int, name: string, role: string } }` | User subscribe presence channel |
| `presence-dashboard` | `user.left` | `{ user: { id: int, name: string } }` | User unsubscribe |

## BaseMonitor (Abstract)

```php
abstract class BaseMonitor
{
    public function __construct(protected Server $server) {}

    abstract public function collect(): MetricData;

    protected function runCommand(string $command): string
    {
        // validate via CommandWhitelist
        // execute via SshCommandRunner
        // return output
    }

    protected function parseNumber(string $output): float
    {
        return (float) filter_var($output, FILTER_SANITIZE_NUMBER_FLOAT);
    }
}
```

## DTO Pattern

Semua data antar layer pakai DTO, bukan array.

```php
readonly class MetricData
{
    public function __construct(
        public int $serverId,
        public MetricType $type,
        public float $value,
        public string $unit,
        public Carbon $recordedAt,
        public array $metadata = [],
    ) {}
}
```

## Policy

- Semua service class `final` kecuali `BaseMonitor`
- Semua DTO `readonly`
- Controller hanya: validate input → panggil service → return Resource

## Frontend Data Flow

- Blade view di `/` adalah shell SPA kosong — load Alpine.js + Chart.js + Echo, lalu fetch `/api/v1/dashboard/overview` via JS
- **Bukan SSR**: Blade tidak pre-render data. Semua data diambil client-side via API call ke `/api/v1/*`
- Token Sanctum: login via `/api/v1/auth/login` return token → simpan di `localStorage` → attach di header `Authorization: Bearer {token}` untuk semua API call
- WebSocket: Laravel Echo subscribe `private-server.{id}` setelah token tersedia, auto-reconnect dengan exponential backoff
- Fallback polling: jika Echo disconnect >10 detik → polling `GET /servers/{server}/summary` setiap 30 detik
