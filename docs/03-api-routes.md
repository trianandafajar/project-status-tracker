# 03 — API Routes

Semua route di-prefix `/api/v1`. Middleware default: `auth:sanctum`, `audit.log`, `rate.limit`.

---

## Auth Routes (no auth middleware)

```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
PUT    /api/v1/auth/profile
POST   /api/v1/auth/refresh
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| POST | `/auth/login` | `AuthController@login` | Return token |
| POST | `/auth/logout` | `AuthController@logout` | Revoke token |
| GET | `/auth/me` | `AuthController@me` | Current user |
| PUT | `/auth/profile` | `AuthController@updateProfile` | Update name, email, password |
| POST | `/auth/refresh` | `AuthController@refresh` | Refresh token |

---

## Dashboard Routes

```
GET    /api/v1/dashboard/overview
GET    /api/v1/dashboard/health
GET    /api/v1/dashboard/history?from=&to=&server_id=
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/dashboard/overview` | `DashboardController@overview` | Summary semua server + alert count |
| GET | `/dashboard/health` | `DashboardController@health` | Health score per server (0-100) |
| GET | `/dashboard/history` | `DashboardController@history` | Timeline status server |

---

## Server Routes

```
GET    /api/v1/servers
POST   /api/v1/servers
GET    /api/v1/servers/{server}
PUT    /api/v1/servers/{server}
DELETE /api/v1/servers/{server}
POST   /api/v1/servers/{server}/test-connection
POST   /api/v1/servers/{server}/refresh
GET    /api/v1/servers/{server}/summary
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers` | `ServerController@index` | List server, filter tag |
| POST | `/servers` | `ServerController@store` | Tambah server |
| GET | `/servers/{server}` | `ServerController@show` | Detail server |
| PUT | `/servers/{server}` | `ServerController@update` | Update server |
| DELETE | `/servers/{server}` | `ServerController@destroy` | Soft delete server |
| POST | `/servers/{server}/test-connection` | `ServerController@testConnection` | Test SSH koneksi |
| POST | `/servers/{server}/refresh` | `ServerController@refresh` | Trigger refresh semua data server |
| GET | `/servers/{server}/summary` | `ServerController@summary` | Aggregated metrics + service status |

---

## Service Routes

```
GET    /api/v1/servers/{server}/services
GET    /api/v1/servers/{server}/services/{service}
POST   /api/v1/servers/{server}/services/{service}/restart
POST   /api/v1/servers/{server}/services/{service}/start
POST   /api/v1/servers/{server}/services/{service}/stop
POST   /api/v1/servers/{server}/services/{service}/reload
GET    /api/v1/servers/{server}/services/{service}/status
GET    /api/v1/servers/{server}/services/{service}/history
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/services` | `ServiceController@index` | List semua service di server |
| GET | `/servers/{server}/services/{service}` | `ServiceController@show` | Detail satu service |
| POST | `/servers/{server}/services/{service}/restart` | `ServiceController@restart` | Restart service |
| POST | `/servers/{server}/services/{service}/start` | `ServiceController@start` | Start service |
| POST | `/servers/{server}/services/{service}/stop` | `ServiceController@stop` | Stop service |
| POST | `/servers/{server}/services/{service}/reload` | `ServiceController@reload` | Reload config (nginx -s reload) |
| GET | `/servers/{server}/services/{service}/status` | `ServiceController@status` | Status realtime |
| GET | `/servers/{server}/services/{service}/history` | `ServiceController@history` | Riwayat status service |

---

## Metric Routes

```
GET    /api/v1/servers/{server}/metrics
GET    /api/v1/servers/{server}/metrics/latest
GET    /api/v1/servers/{server}/metrics/history?type=&from=&to=&interval=
GET    /api/v1/servers/{server}/metrics/export?type=&from=&to=&format=
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/metrics` | `MetricController@index` | Filter by type, from, to |
| GET | `/servers/{server}/metrics/latest` | `MetricController@latest` | Nilai terbaru semua type |
| GET | `/servers/{server}/metrics/history` | `MetricController@history` | Timeseries untuk chart (interval: 1m/5m/15m/1h/1d) |
| GET | `/servers/{server}/metrics/export` | `MetricController@export` | Export CSV/JSON |

---

## Alert Routes

```
GET    /api/v1/alerts/summary
GET    /api/v1/alerts
GET    /api/v1/alerts/{alert}
PUT    /api/v1/alerts/{alert}
DELETE /api/v1/alerts/{alert}
POST   /api/v1/alerts/{alert}/acknowledge
POST   /api/v1/alerts/{alert}/resolve
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/alerts/summary` | `AlertController@summary` | Count by severity + status |
| GET | `/alerts` | `AlertController@index` | Filter: status, severity, server_id, from, to |
| GET | `/alerts/{alert}` | `AlertController@show` | Detail alert + notifikasi + healing log |
| PUT | `/alerts/{alert}` | `AlertController@update` | Update note/message |
| DELETE | `/alerts/{alert}` | `AlertController@destroy` | Delete alert |
| POST | `/alerts/{alert}/acknowledge` | `AlertController@acknowledge` | Ack alert |
| POST | `/alerts/{alert}/resolve` | `AlertController@resolve` | Resolve alert |

---

## Alert Rule Routes

```
GET    /api/v1/alert-rules
POST   /api/v1/alert-rules
GET    /api/v1/alert-rules/{alert_rule}
PUT    /api/v1/alert-rules/{alert_rule}
DELETE /api/v1/alert-rules/{alert_rule}
POST   /api/v1/alert-rules/{alert_rule}/toggle
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/alert-rules` | `AlertRuleController@index` | |
| POST | `/alert-rules` | `AlertRuleController@store` | |
| GET | `/alert-rules/{alert_rule}` | `AlertRuleController@show` | |
| PUT | `/alert-rules/{alert_rule}` | `AlertRuleController@update` | |
| DELETE | `/alert-rules/{alert_rule}` | `AlertRuleController@destroy` | |
| POST | `/alert-rules/{alert_rule}/toggle` | `AlertRuleController@toggle` | Enable/disable |

---

## Alert Channel Routes

```
GET    /api/v1/alert-channels
POST   /api/v1/alert-channels
GET    /api/v1/alert-channels/{alert_channel}
PUT    /api/v1/alert-channels/{alert_channel}
DELETE /api/v1/alert-channels/{alert_channel}
POST   /api/v1/alert-channels/{alert_channel}/test
POST   /api/v1/alert-channels/{alert_channel}/toggle
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/alert-channels` | `AlertChannelController@index` | |
| POST | `/alert-channels` | `AlertChannelController@store` | |
| GET | `/alert-channels/{alert_channel}` | `AlertChannelController@show` | |
| PUT | `/alert-channels/{alert_channel}` | `AlertChannelController@update` | |
| DELETE | `/alert-channels/{alert_channel}` | `AlertChannelController@destroy` | |
| POST | `/alert-channels/{alert_channel}/test` | `AlertChannelController@test` | Kirim test alert |
| POST | `/alert-channels/{alert_channel}/toggle` | `AlertChannelController@toggle` | Enable/disable |

---

## Alert Notification Routes (read-only)

```
GET    /api/v1/alert-notifications?alert_id=&channel_id=&status=
GET    /api/v1/alert-notifications/{alert_notification}
POST   /api/v1/alert-notifications/{alert_notification}/retry
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/alert-notifications` | `AlertNotificationController@index` | Riwayat notifikasi |
| GET | `/alert-notifications/{alert_notification}` | `AlertNotificationController@show` | |
| POST | `/alert-notifications/{alert_notification}/retry` | `AlertNotificationController@retry` | Kirim ulang |

---

## Log Routes

```
GET    /api/v1/servers/{server}/logs
GET    /api/v1/servers/{server}/logs/{log}
GET    /api/v1/servers/{server}/logs/stats
GET    /api/v1/servers/{server}/logs/export?source=&level=&from=&to=&format=
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/logs` | `LogController@index` | Filter: source, level, from, to, search |
| GET | `/servers/{server}/logs/{log}` | `LogController@show` | Detail satu log entry |
| GET | `/servers/{server}/logs/stats` | `LogController@stats` | Distribusi level per source |
| GET | `/servers/{server}/logs/export` | `LogController@export` | Export CSV/JSON |

---

## Audit Log Routes

```
GET    /api/v1/audit-logs
GET    /api/v1/audit-logs/{audit_log}
GET    /api/v1/audit-logs/stats
GET    /api/v1/audit-logs/export?user_id=&action=&from=&to=&format=
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/audit-logs` | `AuditLogController@index` | Filter: user_id, action, model_type, from, to |
| GET | `/audit-logs/{audit_log}` | `AuditLogController@show` | Detail |
| GET | `/audit-logs/stats` | `AuditLogController@stats` | Action frequency |
| GET | `/audit-logs/export` | `AuditLogController@export` | Export CSV/JSON |

---

## SSL Monitor Routes

```
GET    /api/v1/servers/{server}/ssl-certificates
GET    /api/v1/servers/{server}/ssl-certificates/{ssl_certificate}
POST   /api/v1/servers/{server}/ssl-certificates/check
GET    /api/v1/servers/{server}/ssl-certificates/expiring
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/ssl-certificates` | `SslMonitorController@index` | List SSL per domain |
| GET | `/servers/{server}/ssl-certificates/{ssl_certificate}` | `SslMonitorController@show` | Detail SSL |
| POST | `/servers/{server}/ssl-certificates/check` | `SslMonitorController@check` | Trigger manual check |
| GET | `/servers/{server}/ssl-certificates/expiring` | `SslMonitorController@expiring` | Hanya yang expiring (≤30 hari) |

---

## Env Checker Routes

```
GET    /api/v1/servers/{server}/env-checks
POST   /api/v1/servers/{server}/env-checks
GET    /api/v1/servers/{server}/env-checks/{env_check}
PUT    /api/v1/servers/{server}/env-checks/{env_check}
DELETE /api/v1/servers/{server}/env-checks/{env_check}
POST   /api/v1/servers/{server}/env-checks/check-all
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/env-checks` | `EnvCheckerController@index` | List semua env check |
| POST | `/servers/{server}/env-checks` | `EnvCheckerController@store` | Tambah rule env check |
| GET | `/servers/{server}/env-checks/{env_check}` | `EnvCheckerController@show` | |
| PUT | `/servers/{server}/env-checks/{env_check}` | `EnvCheckerController@update` | |
| DELETE | `/servers/{server}/env-checks/{env_check}` | `EnvCheckerController@destroy` | |
| POST | `/servers/{server}/env-checks/check-all` | `EnvCheckerController@checkAll` | Jalankan semua check |

---

## Port Monitor Routes

```
GET    /api/v1/servers/{server}/port-scans
POST   /api/v1/servers/{server}/port-scans
GET    /api/v1/servers/{server}/port-scans/{port_scan}
GET    /api/v1/servers/{server}/port-scans/{port_scan}/results
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/port-scans` | `PortMonitorController@index` | Riwayat scan |
| POST | `/servers/{server}/port-scans` | `PortMonitorController@scan` | Trigger scan baru |
| GET | `/servers/{server}/port-scans/{port_scan}` | `PortMonitorController@show` | Detail scan |
| GET | `/servers/{server}/port-scans/{port_scan}/results` | `PortMonitorController@results` | Hasil per port |

---

## 502 Error Analyzer Routes

```
GET    /api/v1/servers/{server}/error502-analyses
POST   /api/v1/servers/{server}/error502-analyses
GET    /api/v1/servers/{server}/error502-analyses/{error502_analysis}
GET    /api/v1/servers/{server}/error502-analyses/latest
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/error502-analyses` | `Error502Controller@index` | Riwayat analisis |
| POST | `/servers/{server}/error502-analyses` | `Error502Controller@analyze` | Trigger analisis baru |
| GET | `/servers/{server}/error502-analyses/{error502_analysis}` | `Error502Controller@show` | Detail + evidence + rekomendasi |
| GET | `/servers/{server}/error502-analyses/latest` | `Error502Controller@latest` | Analisis terbaru |

---

## Self Healing Routes

```
GET    /api/v1/healing-rules
POST   /api/v1/healing-rules
GET    /api/v1/healing-rules/{healing_rule}
PUT    /api/v1/healing-rules/{healing_rule}
DELETE /api/v1/healing-rules/{healing_rule}
POST   /api/v1/healing-rules/{healing_rule}/toggle
POST   /api/v1/healing-rules/{healing_rule}/execute
GET    /api/v1/healing-logs
GET    /api/v1/healing-logs/{healing_log}
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/healing-rules` | `SelfHealingController@indexRules` | List aturan |
| POST | `/healing-rules` | `SelfHealingController@storeRule` | Tambah aturan |
| GET | `/healing-rules/{healing_rule}` | `SelfHealingController@showRule` | Detail aturan |
| PUT | `/healing-rules/{healing_rule}` | `SelfHealingController@updateRule` | Update aturan |
| DELETE | `/healing-rules/{healing_rule}` | `SelfHealingController@destroyRule` | Delete aturan |
| POST | `/healing-rules/{healing_rule}/toggle` | `SelfHealingController@toggleRule` | Enable/disable |
| POST | `/healing-rules/{healing_rule}/execute` | `SelfHealingController@executeRule` | Eksekusi manual |
| GET | `/healing-logs` | `SelfHealingController@indexLogs` | Riwayat healing |
| GET | `/healing-logs/{healing_log}` | `SelfHealingController@showLog` | Detail log healing |

---

## Docker Monitor Routes

```
GET    /api/v1/servers/{server}/docker/containers
GET    /api/v1/servers/{server}/docker/containers/{docker_container}
POST   /api/v1/servers/{server}/docker/containers/{docker_container}/restart
POST   /api/v1/servers/{server}/docker/containers/{docker_container}/start
POST   /api/v1/servers/{server}/docker/containers/{docker_container}/stop
GET    /api/v1/servers/{server}/docker/containers/{docker_container}/logs?tail=
GET    /api/v1/servers/{server}/docker/stats
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/docker/containers` | `DockerMonitorController@index` | List container |
| GET | `/servers/{server}/docker/containers/{container}` | `DockerMonitorController@show` | Detail container |
| POST | `/servers/{server}/docker/containers/{container}/restart` | `DockerMonitorController@restart` | |
| POST | `/servers/{server}/docker/containers/{container}/start` | `DockerMonitorController@start` | |
| POST | `/servers/{server}/docker/containers/{container}/stop` | `DockerMonitorController@stop` | |
| GET | `/servers/{server}/docker/containers/{container}/logs` | `DockerMonitorController@logs` | Tail logs |
| GET | `/servers/{server}/docker/stats` | `DockerMonitorController@stats` | Docker daemon stats |

---

## Python Monitor Routes

```
GET    /api/v1/servers/{server}/python/processes
GET    /api/v1/servers/{server}/python/processes/{python_process}
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/python/processes` | `PythonMonitorController@index` | List python process |
| GET | `/servers/{server}/python/processes/{python_process}` | `PythonMonitorController@show` | Detail process |

---

## Network Monitor Routes

```
GET    /api/v1/servers/{server}/network
GET    /api/v1/servers/{server}/network/interfaces
GET    /api/v1/servers/{server}/network/connections
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/network` | `NetworkMonitorController@overview` | Bandwidth, packet loss |
| GET | `/servers/{server}/network/interfaces` | `NetworkMonitorController@interfaces` | List network interface |
| GET | `/servers/{server}/network/connections` | `NetworkMonitorController@connections` | Established connections |

---

## Failure Prediction Routes (v2.0)

```
GET    /api/v1/servers/{server}/predictions
GET    /api/v1/servers/{server}/predictions/latest
GET    /api/v1/predictions/accuracy
POST   /api/v1/models/train
GET    /api/v1/models/status
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/servers/{server}/predictions` | `PredictionController@index` | Riwayat prediksi |
| GET | `/servers/{server}/predictions/latest` | `PredictionController@latest` | Prediksi terbaru |
| GET | `/predictions/accuracy` | `PredictionController@accuracy` | Akurasi model (precision, recall, f1) |
| POST | `/models/train` | `ModelController@train` | Trigger training manual |
| GET | `/models/status` | `ModelController@status` | Metadata model terakhir |

---

## Settings Routes

```
GET    /api/v1/settings
PUT    /api/v1/settings
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/settings` | `SettingsController@index` | Semua settings key-value |
| PUT | `/settings` | `SettingsController@update` | Batch update settings |

---

## User Management Routes (RBAC: superadmin/admin only)

```
GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/users/{user}
PUT    /api/v1/users/{user}
DELETE /api/v1/users/{user}
POST   /api/v1/users/{user}/reset-password
```

| Method | URI | Controller@Method | Role | Keterangan |
|--------|-----|-------------------|------|-------------|
| GET | `/users` | `UserController@index` | admin+ | List user |
| POST | `/users` | `UserController@store` | superadmin | Tambah user |
| GET | `/users/{user}` | `UserController@show` | admin+ | Detail user |
| PUT | `/users/{user}` | `UserController@update` | superadmin | Update user + role |
| DELETE | `/users/{user}` | `UserController@destroy` | superadmin | Delete user |
| POST | `/users/{user}/reset-password` | `UserController@resetPassword` | superadmin | Reset password |

---

## Web Routes (Blade View)

```
GET    /                 → DashboardController@index          (Blade SPA)
GET    /login            → AuthController@showLogin            (Blade login)
```

Semua route `/api/v1/*` return JSON. Route `/` return Blade view yang me-load SPA (TailwindCSS + Alpine.js + Chart.js).

---

## Bulk Operation Routes

```
POST   /api/v1/bulk/services/restart
POST   /api/v1/bulk/alerts/acknowledge
POST   /api/v1/bulk/alerts/resolve
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| POST | `/bulk/services/restart` | `ServiceController@bulkRestart` | Body: `{ server_ids: [1,2], service_types: ["nginx"] }` |
| POST | `/bulk/alerts/acknowledge` | `AlertController@bulkAcknowledge` | Body: `{ alert_ids: [1,2,3] }` |
| POST | `/bulk/alerts/resolve` | `AlertController@bulkResolve` | Body: `{ alert_ids: [1,2,3] }` |

---

## Job Status Polling

```
GET    /api/v1/jobs/{jobId}/status
```

| Method | URI | Controller@Method | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/jobs/{jobId}/status` | `JobController@status` | Return `{ status: "pending"\|"running"\|"completed"\|"failed", output: string? }` |

Setiap POST action (restart/start/stop/scan) return header `X-Job-Id` untuk polling.

---

## Standard Error Response

Semua error return format JSON yang sama:

```json
{
    "error": {
        "code": "SSH_TIMEOUT",
        "message": "SSH connection to 192.168.1.1 timed out after 30 seconds",
        "details": {
            "server_id": 1,
            "host": "192.168.1.1"
        }
    }
}
```

| HTTP Status | code | Kapan |
|-------------|------|------|
| 401 | `UNAUTHENTICATED` | Token expired / invalid |
| 403 | `FORBIDDEN` | Role tidak punya akses |
| 404 | `NOT_FOUND` | Resource tidak ditemukan |
| 422 | `VALIDATION_ERROR` | Input validation gagal (Laravel default) |
| 429 | `RATE_LIMITED` | Rate limit exceeded, header `Retry-After` |
| 500 | `SSH_TIMEOUT` | SSH connection timeout |
| 500 | `SSH_ERROR` | SSH command gagal |
| 500 | `COMMAND_NOT_WHITELISTED` | Command tidak diizinkan |
| 500 | `SERVER_UNREACHABLE` | Server tidak bisa dijangkau |
| 500 | `QUEUE_ERROR` | Job queue gagal |

---

## Reverb WebSocket Channel Authorization

```
POST   /broadcasting/auth
```

Channels:
- `private-server.{id}` — auth via `ServerPolicy::view`
- `presence-dashboard` — user presence

---

## Rate Limit Default

| Group | Limit | Window |
|-------|-------|--------|
| api (default) | 120 req | 1 menit |
| auth | 10 req | 1 menit |
| export | 5 req | 1 menit |
| scan (port/healing) | 10 req | 1 menit |

---

## Ringkasan Semua Route (Quick Ref)

Total: **~92 route endpoints**

```
Auth               : login, logout, me, profile, refresh                (5)
Dashboard          : overview, health, history                           (3)
Servers            : index, store, show, update, destroy,
                     testConnection, refresh, summary                    (8)
Services           : index, show, restart, start, stop, reload,
                     status, history, bulkRestart                        (9)
Metrics            : index, latest, history, export                      (4)
Alerts             : summary, index, show, update, destroy,
                     acknowledge, resolve, bulkAcknowledge, bulkResolve  (9)
Alert Rules        : index, store, show, update, destroy, toggle        (6)
Alert Channels     : index, store, show, update, destroy, test, toggle  (7)
Alert Notifications: index, show, retry                                  (3)
Logs               : index, show, stats, export                          (4)
Audit Logs         : index, show, stats, export                          (4)
SSL Certificates   : index, show, check, expiring                        (4)
Env Checks         : index, store, show, update, destroy, checkAll      (6)
Port Scans         : index, scan, show, results                          (4)
502 Analyzer       : index, analyze, show, latest                        (4)
Self Healing Rules : indexRules, storeRule, showRule, updateRule,
                     destroyRule, toggleRule, executeRule                (7)
Healing Logs       : indexLogs, showLog                                  (2)
Docker             : index, show, restart, start, stop, logs, stats     (7)
Python             : index, show                                         (2)
Network            : overview, interfaces, connections                   (3)
Predictions        : index, latest, accuracy                             (3)
Models             : train, status                                       (2)
Jobs               : status                                              (1)
Settings           : index, update                                       (2)
Users              : index, store, show, update, destroy, resetPassword (6)
Web (Blade)        : index, showLogin                                    (2)
Broadcast          : auth                                                (1)
```
