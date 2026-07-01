# 02 — Database Schema

## ERD Singkat

```
servers ──1:N──► metrics
servers ──1:N──► services
servers ──1:N──► alerts
servers ──1:N──► logs
servers ──1:N──► ssl_certificates
servers ──1:N──► env_checks
servers ──1:N──► port_scans
servers ──1:N──► docker_containers
servers ──1:N──► python_processes
servers ──1:N──► error502_analyses
servers ──1:N──► healing_logs
servers ──1:N──► failure_predictions

services ──1:N──► service_status_history

alerts ──1:N──► alert_notifications
alert_channels ──1:N──► alert_notifications

alert_rules ──1:N──► healing_rules
alert_rules ──1:N──► alerts
healing_rules ──1:N──► healing_logs

users ──1:N──► audit_logs
```

---

## Tabel: `servers`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| name | varchar(255) | NOT NULL | Nama label server |
| host | varchar(255) | NOT NULL | IP / hostname |
| port | int unsigned | DEFAULT 22 | SSH port |
| ssh_user | varchar(100) | NOT NULL | |
| ssh_key | text | NOT NULL | Encrypted (AES-256-CBC) |
| connection_type | varchar(20) | DEFAULT 'ssh' | ssh / agent |
| status | varchar(20) | DEFAULT 'unknown' | online / offline / unknown |
| tags | json | NULL | Label kustom: `["production", "web"]` |
| metadata | json | NULL | OS, kernel, uptime baseline |
| last_checked_at | timestamp | NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |
| deleted_at | timestamp | NULL | Soft delete |

## Tabel: `metrics`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| type | varchar(50) | NOT NULL | cpu / ram / disk / network_in / network_out / io_read / io_write |
| value | decimal(12,4) | NOT NULL | |
| unit | varchar(20) | NOT NULL | percent / MB / GB / kbps / iops |
| metadata | json | NULL | Detail tambahan (per-core CPU, per-mount disk) |
| recorded_at | timestamp | NOT NULL | INDEX |
| created_at | timestamp | NOT NULL | |

Index: `(server_id, type, recorded_at)` composite.

## Tabel: `services`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| type | varchar(50) | NOT NULL | nginx / php-fpm / pm2 / redis / mysql / queue-worker / docker |
| name | varchar(100) | NOT NULL | Nama instance (pm2 app name, docker container name) |
| status | varchar(20) | DEFAULT 'unknown' | running / stopped / restarting / unknown |
| port | int unsigned | NULL | Port default service |
| pid | int unsigned | NULL | PID proses |
| uptime_seconds | int unsigned | NULL | |
| memory_mb | decimal(10,2) | NULL | |
| cpu_percent | decimal(5,2) | NULL | |
| metadata | json | NULL | Version, config path, dll |
| last_checked_at | timestamp | NULL | |
| last_status_change_at | timestamp | NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

Index: `(server_id, type)` unique.

## Tabel: `service_status_history`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| service_id | bigint unsigned | FK → services.id, NOT NULL | |
| status | varchar(20) | NOT NULL | |
| checked_at | timestamp | NOT NULL | |

## Tabel: `alert_rules`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| name | varchar(255) | NOT NULL | Label aturan |
| metric_type | varchar(50) | NOT NULL | cpu / ram / disk / service / ssl / port / custom |
| condition | varchar(20) | NOT NULL | gt / lt / eq / gte / lte |
| threshold | decimal(12,4) | NOT NULL | Nilai batas |
| duration_seconds | int unsigned | DEFAULT 0 | Berapa detik kondisi harus bertahan sebelum alert |
| severity | varchar(20) | DEFAULT 'warning' | critical / warning / info |
| message_template | text | NULL | `{server} CPU at {value}%` |
| cooldown_seconds | int unsigned | DEFAULT 300 | Jeda antar alert berulang |
| enabled | tinyint(1) | DEFAULT 1 | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `alerts`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| alert_rule_id | bigint unsigned | FK → alert_rules.id, NULL | NULL = manual alert |
| type | varchar(50) | NOT NULL | cpu / ram / disk / service / ssl / port / 502 / custom |
| severity | varchar(20) | NOT NULL | critical / warning / info |
| status | varchar(20) | DEFAULT 'open' | open / acknowledged / resolved |
| title | varchar(500) | NOT NULL | |
| message | text | NULL | Detail alert |
| context | json | NULL | Data saat alert (metric value, service status, etc) |
| resolved_at | timestamp | NULL | |
| resolved_by | bigint unsigned | NULL | FK → users.id |
| acknowledged_at | timestamp | NULL | |
| acknowledged_by | bigint unsigned | NULL | FK → users.id |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `alert_channels`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| name | varchar(100) | NOT NULL | Label |
| type | varchar(20) | NOT NULL | telegram / discord / email |
| config | json | NOT NULL | `{ "bot_token": "...", "chat_id": "..." }` atau `{ "webhook_url": "..." }` |
| enabled | tinyint(1) | DEFAULT 1 | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `alert_notifications`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| alert_id | bigint unsigned | FK → alerts.id, NOT NULL | |
| alert_channel_id | bigint unsigned | FK → alert_channels.id, NOT NULL | |
| status | varchar(20) | DEFAULT 'pending' | pending / sent / failed |
| response | text | NULL | Response dari channel API |
| sent_at | timestamp | NULL | |
| created_at | timestamp | NOT NULL | |

## Tabel: `healing_rules`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| alert_rule_id | bigint unsigned | FK → alert_rules.id, NOT NULL | Trigger dari alert rule mana |
| action_type | varchar(30) | NOT NULL | restart_service / clear_cache / run_command / scale_worker |
| service_type | varchar(50) | NULL | Service yang ditarget (jika restart_service) |
| command | text | NULL | Command mentah (jika run_command) |
| max_attempts | int unsigned | DEFAULT 3 | Maksimum retry |
| cooldown_seconds | int unsigned | DEFAULT 600 | Jeda antar eksekusi |
| enabled | tinyint(1) | DEFAULT 1 | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `healing_logs`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| healing_rule_id | bigint unsigned | FK → healing_rules.id, NOT NULL | |
| alert_id | bigint unsigned | FK → alerts.id, NULL | Alert pemicu |
| action_type | varchar(30) | NOT NULL | |
| command | text | NULL | Command yang dijalankan |
| status | varchar(20) | DEFAULT 'success' | success / failed / skipped |
| output | text | NULL | Output command |
| error_message | text | NULL | Jika failed |
| executed_at | timestamp | NOT NULL | |

## Tabel: `logs`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| source | varchar(50) | NOT NULL | nginx-error / nginx-access / php-fpm / syslog / app |
| level | varchar(20) | NOT NULL | debug / info / notice / warning / error / critical |
| message | text | NOT NULL | |
| context | json | NULL | Raw log parsed |
| log_file | varchar(500) | NULL | Path file sumber |
| log_line | int unsigned | NULL | Nomor baris |
| logged_at | timestamp | NOT NULL | Timestamp asli dari log |
| created_at | timestamp | NOT NULL | |

Index: `(server_id, source, level, logged_at)`.

## Tabel: `ssl_certificates`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| domain | varchar(255) | NOT NULL | |
| issuer | varchar(500) | NULL | CA issuer |
| valid_from | date | NULL | |
| valid_to | date | NOT NULL | |
| days_remaining | int | NOT NULL | |
| status | varchar(20) | DEFAULT 'valid' | valid / expiring_soon / expired / error |
| san | json | NULL | Subject Alternative Names |
| error_message | text | NULL | |
| checked_at | timestamp | NOT NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

Index: `(server_id, domain)` unique.

## Tabel: `env_checks`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| key | varchar(255) | NOT NULL | Nama env / config key |
| expected_value | text | NOT NULL | Nilai yang diharapkan |
| actual_value | text | NULL | Nilai aktual dari server |
| status | varchar(20) | DEFAULT 'unknown' | match / mismatch / error |
| checked_at | timestamp | NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `port_scans`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| scan_type | varchar(20) | DEFAULT 'tcp' | tcp / udp / both |
| status | varchar(20) | DEFAULT 'pending' | pending / running / completed / failed |
| scanned_at | timestamp | NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `port_scan_results`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| port_scan_id | bigint unsigned | FK → port_scans.id, NOT NULL | |
| port | int unsigned | NOT NULL | |
| protocol | varchar(10) | NOT NULL | tcp / udp |
| service_name | varchar(100) | NULL | http / ssh / mysql / redis / unknown |
| state | varchar(20) | NOT NULL | open / closed / filtered |
| created_at | timestamp | NOT NULL | |

## Tabel: `error502_analyses`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| domain | varchar(255) | NULL | Domain yang kena 502 |
| trigger_event | varchar(255) | NULL | Event pemicu (deploy, restart, traffic spike) |
| probable_cause | varchar(500) | NOT NULL | PHP-FPM down / socket timeout / memory exhausted / nginx misconfig |
| confidence | decimal(5,2) | NULL | 0.00 - 100.00 |
| evidence | json | NULL | Log entries, metric snapshot |
| recommendation | text | NULL | Langkah perbaikan |
| resolved | tinyint(1) | DEFAULT 0 | |
| analyzed_at | timestamp | NOT NULL | |
| created_at | timestamp | NOT NULL | |

## Tabel: `docker_containers`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| container_id | varchar(100) | NOT NULL | Docker container ID |
| name | varchar(255) | NOT NULL | |
| image | varchar(500) | NULL | |
| status | varchar(50) | NULL | running / exited / paused |
| ports | json | NULL | Port mapping |
| cpu_percent | decimal(5,2) | NULL | |
| memory_mb | decimal(10,2) | NULL | |
| checked_at | timestamp | NOT NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `python_processes`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| pid | int unsigned | NOT NULL | |
| command | text | NULL | Full command line |
| cpu_percent | decimal(5,2) | NULL | |
| memory_mb | decimal(10,2) | NULL | |
| uptime_seconds | int unsigned | NULL | |
| checked_at | timestamp | NOT NULL | |
| created_at | timestamp | NOT NULL | |

## Tabel: `audit_logs`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| user_id | bigint unsigned | FK → users.id, NULL | NULL = system action |
| action | varchar(100) | NOT NULL | server.create / service.restart / alert.resolve / healing.execute |
| model_type | varchar(100) | NULL | |
| model_id | bigint unsigned | NULL | |
| ip_address | varchar(45) | NULL | |
| user_agent | varchar(500) | NULL | |
| metadata | json | NULL | Data tambahan |
| created_at | timestamp | NOT NULL | |

Index: `(user_id, action, created_at)`.

## Tabel: `metric_aggregates` (v2.0)

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| type | varchar(50) | NOT NULL | cpu / ram / disk / network_in / network_out |
| window | varchar(10) | NOT NULL | 5m / 15m / 1h / 6h / 1d |
| avg | decimal(12,4) | NOT NULL | |
| min | decimal(12,4) | NOT NULL | |
| max | decimal(12,4) | NOT NULL | |
| stddev | decimal(12,4) | NULL | |
| recorded_at | timestamp | NOT NULL | |

Index: `(server_id, type, window, recorded_at)`.

## Tabel: `failure_predictions` (v2.0)

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| server_id | bigint unsigned | FK → servers.id, NOT NULL | |
| predicted_at | timestamp | NOT NULL | |
| target_timestamp | timestamp | NOT NULL | 1 jam dari predicted_at |
| probability | decimal(5,2) | NOT NULL | 0.00 - 100.00 |
| features | json | NULL | Feature vector saat prediksi |
| was_correct | tinyint(1) | NULL | Diisi retrospektif |
| actual_incident | tinyint(1) | NULL | Diisi retrospektif |
| created_at | timestamp | NOT NULL | |

## Tabel: `users`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| name | varchar(255) | NOT NULL | |
| email | varchar(255) | NOT NULL, UNIQUE | |
| password | varchar(255) | NOT NULL | |
| role | varchar(50) | DEFAULT 'viewer' | superadmin / admin / operator / viewer |
| avatar_url | varchar(500) | NULL | |
| last_login_at | timestamp | NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

## Tabel: `settings`

| Kolom | Type | Constraint | Keterangan |
|-------|------|-----------|------------|
| id | bigint unsigned | PK, auto_increment | |
| key | varchar(100) | NOT NULL, UNIQUE | |
| value | text | NULL | |
| created_at | timestamp | NOT NULL | |
| updated_at | timestamp | NOT NULL | |

Settings keys standar:
- `metrics_retention_days` (default 30)
- `logs_retention_days` (default 14)
- `alert_history_retention_days` (default 90)
- `audit_logs_retention_days` (default 180)
- `dashboard_refresh_interval_seconds` (default 30)
- `data_collection_enabled` (default true)

---

## Migration Plan

```
001_create_servers_table
002_create_users_table
003_create_metrics_table
004_create_services_table
005_create_service_status_history_table
006_create_alert_rules_table
007_create_alerts_table
008_create_alert_channels_table
009_create_alert_notifications_table
010_create_healing_rules_table
011_create_healing_logs_table
012_create_logs_table
013_create_ssl_certificates_table
014_create_env_checks_table
015_create_port_scans_table
016_create_port_scan_results_table
017_create_error502_analyses_table
018_create_docker_containers_table
019_create_python_processes_table
020_create_audit_logs_table
021_create_settings_table
022_create_metric_aggregates_table
023_create_failure_predictions_table
024_add_foreign_keys
025_create_indexes
```
