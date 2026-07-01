# 07 — Sprint v1.0: Monitoring Dasar

**Target:** Monitoring CPU, RAM, Disk, Nginx, PHP-FPM, PM2, Redis, MySQL, Queue
**Estimasi:** 4 minggu

---

## Week 1: Foundation

### 1.1 Project Setup
- [x] `laravel new sentinel-monitor` (Laravel 13)
- [x] Install packages: `laravel/sanctum`, `laravel/reverb`, `phpseclib/phpseclib`
- [x] Setup TailwindCSS + Alpine.js
- [x] Setup `.env`: DB, Redis, queue connection, Reverb credentials
- [x] Folder structure sesuai `01-arsitektur-dan-code-design.md`

### 1.2 Database
- [x] Migration: `servers`, `users`, `metrics`, `services`, `service_status_history`
- [x] Migration: `audit_logs`, `settings`
- [x] Seed: default superadmin user (`admin@sentinel.local` / `password`)
- [x] Seed: 4 user per role (superadmin, admin, operator, viewer) untuk testing RBAC
- [x] Seed: `DatabaseSeeder` panggil `php artisan db:seed --class=DevSeeder` untuk data development
- [x] Seed: default settings

### 1.2b Dev Seeder (untuk development & QA)

`DevSeeder` menyediakan data dummy yang cukup untuk seluruh UI development:

- **Server:** 3 dummy server (production, staging, development) dengan status `online`
- **Metrics:** 7 hari historical metric (CPU, RAM, Disk) per server, interval 1 menit (~10k record/server)
- **Services:** 7 service per server (nginx, php-fpm, pm2, redis, mysql, queue-worker, docker) dengan status `running`
- **Service Status History:** 24 jam history per service, interval 30 detik
- **Alerts:** Mix open (5), acknowledged (3), resolved (10) dengan berbagai severity
- **Alert Channels:** 1 Telegram, 1 Discord, 1 Email (semua disabled)
- **Audit Logs:** 50 random action entries
- **SSL Certificates:** 2 valid (60+ days), 1 expiring soon (14 days), 1 expired

Jalankan: `php artisan db:seed --class=DevSeeder`

> **Penting:** v2.0 ML pipeline butuh data 30 hari. Setelah sistem jalan ≥30 hari, gunakan data real. Untuk development, DevSeeder generate 7 hari — cukup untuk testing feature engineering pipeline, tapi model training butuh data tambahan dari production soak.

### 1.3 Base Classes
- [x] `BaseMonitor` abstract class
- [x] `SshConnection` + `SshCommandRunner`
- [x] `CommandWhitelist` service
- [x] `CredentialEncrypter` service
- [x] `AuditLogger` service
- [x] Enums: `MetricType`, `ServiceType`, `ServiceStatus`, `AlertSeverity`, `AlertStatus`, `LogLevel`

### 1.4 Auth & Middleware
- [x] `AuthController@login`, `@logout`, `@me`
- [x] Sanctum token setup
- [x] RBAC middleware + Gates
- [x] `AuditLogMiddleware`
- [x] Rate limiter config (60/min API, 5/min login)

---

## Week 2: Server & Monitoring Engine

### 2.1 Server CRUD
- [x] `ServerController@index`, `@store`, `@show`, `@update`, `@destroy`
- [x] `ServerController@testConnection`
- [x] `ServerResource`
- [x] `ServerPolicy`
- [x] Blade view: server list + form (modal CRUD, test connection)

### 2.2 CPU, RAM, Disk Monitors
- [x] `CpuMonitor`
- [x] `RamMonitor`
- [x] `DiskMonitor`
- [x] `MetricsCollector` — orchestrate collection per server
- [x] `CollectMetricsJob` — dipanggil scheduler
- [x] `MetricController@index`, `@latest`, `@history`
- [x] `MetricResource`

### 2.3 Service Monitors (Nginx, PHP-FPM, PM2, Redis, MySQL, Queue)
- [x] `NginxMonitor`
- [x] `PhpFpmMonitor`
- [x] `Pm2Monitor`
- [x] `RedisMonitor`
- [x] `MySqlMonitor`
- [x] `QueueMonitor`
- [x] `MonitorServiceJob` — check semua service per server
- [x] `ServiceController@index`, `@show`, `@status`, `@history`
- [x] `ServiceController@restart`, `@start`, `@stop`
- [x] `ServiceResource`

### 2.4 Dashboard
- [x] `DashboardController@overview` — aggregate semua server
- [x] `DashboardController@health`
- [x] Blade SPA skeleton: sidebar + main panel + realtime updates skeleton

---

## Week 3: Scheduler + Reverb

### 3.1 Scheduler
- [x] Schedule `MonitorServiceJob` setiap 30 detik
- [x] Schedule `CollectMetricsJob` setiap 1 menit
- [x] Register di `routes/console.php`

### 3.2 Events & Listeners
- [x] Events: `ServiceDown`, `ServiceUp`, `MetricCollected`
- [x] Listeners: `BroadcastServiceStatus`, `BroadcastMetric`, `EvaluateAlertRules`

### 3.3 Reverb WebSocket
- [x] Setup Reverb config
- [x] Channel: `private-server.{id}`
- [x] Channel auth: `ServerPolicy::view`
- [x] Frontend: subscribe to Reverb channel (Echo + polling fallback)
- [x] Frontend: live update service status badge + metric sparkline

### 3.4 UI Dashboard
- [x] Server card: name, status, CPU/RAM/Disk bars
- [x] Service grid: per-service status indicator (detail view)
- [x] Metric line charts (Chart.js) — CPU, RAM, Disk timeseries (1H/24H/7D)
- [x] Auto-refresh fallback (polling 30s jika WebSocket disconnect)

---

## Week 4: Polish & Testing

### 4.1 Alert Rules (basic)
- [x] `AlertRuleController@index`, `@store`, `@show`, `@update`, `@destroy`, `@toggle`
- [x] `AlertEvaluator` (EvaluateAlertRules listener)
- [x] Default alert rules seed (CPU > 90%, RAM > 90%, service down, disk > 85%)
- [x] Alert list UI di dashboard (table + filter + acknowledge/resolve)

### 4.2 Audit Log
- [x] `AuditLogController@index`, `@show`
- [x] AuditLogMiddleware — log semua action

### 4.3 Settings
- [x] `SettingsController@index`, `@update`

### 4.4 Testing
- [x] Unit test: `CommandWhitelist`
- [x] Unit test: `CredentialEncrypter`
- [x] Feature test: Auth (login, logout)
- [x] Feature test: Server CRUD
- [x] Feature test: Metrics endpoints
- [x] Feature test: Service status endpoints
- [x] Mock SSH connection untuk semua monitor test (MockSshHelper trait + 24 test)

### 4.5 User Management
- [x] `UserController@index`, `@store`, `@show`, `@update`, `@destroy`, `@resetPassword`

---

## Acceptance Criteria v1.0

- [x] Bisa menambah server via UI + test SSH connection
- [x] Dashboard API endpoint — overview + health
- [x] Metrics history chart (1 jam, 6 jam, 24 jam, 7 hari) + Chart.js
- [x] Status service realtime: Nginx, PHP-FPM, PM2, Redis, MySQL, Queue
- [x] Bisa restart/start/stop service dari dashboard
- [x] Alert rules + evaluator (EvaluateAlertRules listener)
- [x] WebSocket update status service tanpa refresh (Echo + polling fallback)
- [x] Audit log mencatat semua action
- [x] Role-based access: viewer tidak bisa restart service
- [x] 100 tests pass (228 assertions)

---

## File Checklist v1.0

```
Models (12)   : Server, Metric, Service, ServiceStatus, Alert, AlertRule, AlertChannel,
                AlertNotification, AuditLog, Setting, User, SslCertificate
Controllers (10): Dashboard, Server, Service, Metric, Alert, AlertRule, AuditLog, Settings, Auth, User
Services (12) : BaseMonitor, CpuMonitor, RamMonitor, DiskMonitor,
                SshConnection, SshCommandRunner, CommandWhitelist, CredentialEncrypter, AuditLogger,
                MetricsCollector, MonitorInterface, AlertEvaluator (via Listener)
Jobs (3)      : CollectMetricsJob, MonitorServiceJob, ExecuteServiceActionJob
Events (3)    : ServiceDown, ServiceUp, MetricCollected
Listeners (3) : BroadcastServiceStatus, BroadcastMetric, EvaluateAlertRules
DTO (8)       : MetricData, ServiceStatusData, AlertData, LogEntryData,
                SslData, PortData, HealingResultData, ServerHealthData
Enums (11)    : MetricType, ServiceType, ServiceStatus, AlertSeverity, AlertStatus,
                AlertChannelType, LogLevel, HealingActionType, HealingStatus, SslStatus, ServerConnectionType
Migrations (16): users, cache, jobs, personal_access_tokens, servers, metrics, services,
                 service_status_history, alert_rules, alerts, audit_logs, settings,
                 alert_channels, alert_notifications, ssl_certificates (+ role/avatar to users)
Seeders (5)   : DatabaseSeeder, DefaultUser, DefaultSettings, DefaultAlertRules, Dev/DevSeeder
Blade (2)     : app.blade.php (SPA), welcome.blade.php
Config (3)    : reverb.php, sanctum.php, queue.php
Tests (7)     : AuthTest, ServerTest, MetricTest, ServiceTest, AlertTest,
                CommandWhitelistTest, CredentialEncrypterTest
```
