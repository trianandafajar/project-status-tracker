# 04 — Modul Monitoring

## Overview

13 modul monitoring. Setiap modul extends `BaseMonitor` dan punya interface `collect(): MetricData|ServiceStatusData`.

---

## 1. CPU Monitor

**Command:** `top -bn1 | grep "Cpu(s)"` atau `/proc/stat`

**Data dikumpulkan:**
- `usage_percent` — total CPU usage
- `per_core` — array usage per core
- `load_1m`, `load_5m`, `load_15m` — load average
- `context_switches` — jumlah context switch

**MetricType:** `cpu`

**Unit:** `percent`

---

## 2. RAM Monitor

**Command:** `free -m` atau `/proc/meminfo`

**Data dikumpulkan:**
- `total_mb` — total RAM
- `used_mb` — RAM terpakai
- `free_mb` — RAM bebas
- `available_mb` — RAM available
- `swap_total_mb`, `swap_used_mb`
- `buffers_mb`, `cached_mb`
- `usage_percent`

**MetricType:** `ram`

**Unit:** `MB`, `percent`

---

## 3. Disk Monitor

**Command:** `df -h` atau `df -BM`

**Data dikumpulkan:**
- Per mount point: `mount`, `total_gb`, `used_gb`, `available_gb`, `usage_percent`
- `inode_usage_percent` — inode usage (penting untuk server dengan banyak file kecil)

**MetricType:** `disk`

**Unit:** `GB`, `percent`

---

## 4. Nginx Monitor

**Command:**
- Status: `systemctl is-active nginx`
- Version: `nginx -v`
- Config test: `nginx -t`
- Active connections: `curl -s http://localhost/nginx_status` (jika stub_status enabled)
- Error log tail: `tail -100 /var/log/nginx/error.log`

**Data dikumpulkan:**
- `status` — running/stopped/dead
- `version`
- `config_valid` — bool
- `active_connections`, `accepts`, `handled`, `requests` — dari stub_status
- `reading`, `writing`, `waiting`
- `pid`, `uptime_seconds`, `memory_mb`

**ServiceType:** `nginx`

---

## 5. PHP-FPM Monitor

**Command:**
- Status: `systemctl is-active php8.3-fpm`
- Pool status: `curl -s http://localhost/php-fpm-status?pool=www` (jika pm.status_path enabled)
- Process list: `ps aux | grep php-fpm`

**Data dikumpulkan:**
- `status` — running/stopped
- `version` — `php -v`
- `pool_name`, `process_manager` — static/dynamic/ondemand
- `active_processes`, `idle_processes`, `total_processes`
- `max_children_reached` — penting: jika sering mencapai max_children → penyebab 502
- `slow_requests` — jumlah slow request
- `listen_queue` — antrian koneksi ke socket
- `pid`, `uptime_seconds`, `memory_mb`

**ServiceType:** `php-fpm`

---

## 6. PM2 Monitor

**Command:** `pm2 jlist` (JSON output)

**Data dikumpulkan per app:**
- `name` — nama app PM2
- `status` — online/stopped/errored
- `pid`, `cpu_percent`, `memory_mb`
- `uptime_seconds`, `restart_count`
- `mode` — fork/cluster
- `version` — node/python version
- `exit_code` — jika stopped

**ServiceType:** `pm2`

---

## 7. Redis Monitor

**Command:** `redis-cli INFO` atau `redis-cli -a {pass} INFO`

**Data dikumpulkan:**
- `status` — up/down (dari PING)
- `version` — redis_version
- `uptime_seconds`, `connected_clients`
- `used_memory_mb`, `used_memory_peak_mb`
- `mem_fragmentation_ratio` — >1.5 = perlu perhatian
- `total_connections_received`, `total_commands_processed`
- `keyspace_hits`, `keyspace_misses` — hit ratio
- `evicted_keys` — jika >0 = memory pressure
- `rdb_last_save_status`, `rdb_last_bgsave_time_sec`
- `rejected_connections`

**ServiceType:** `redis`

---

## 8. MySQL Monitor

**Command:** `mysqladmin -u{user} -p{pass} status` + query `SHOW GLOBAL STATUS`

**Data dikumpulkan:**
- `status` — up/down (dari ping)
- `version`
- `uptime_seconds`
- `threads_connected`, `threads_running`
- `max_connections`, `connection_usage_percent`
- `questions`, `slow_queries`
- `aborted_connections`, `aborted_clients`
- `innodb_buffer_pool_hit_ratio` — <95% = warning
- `open_files_limit`, `open_files`
- `replication_lag_seconds` — jika replica

**ServiceType:** `mysql`

---

## 9. Queue Monitor

**Command:** `php artisan queue:monitor` atau query Redis langsung

**Data dikumpulkan:**
- `default_queue_size`
- `failed_job_count` — dari `failed_jobs` table
- `workers` — list worker process (pid, status, processed_count)
- `queue_latency_seconds` — waktu sejak job pertama di queue
- `throughput_per_minute`

**ServiceType:** `queue-worker`

---

## 10. Docker Monitor

**Command:** `docker stats --no-stream --format "{{json .}}"` + `docker ps -a --format "{{json .}}"`

**Data dikumpulkan per container:**
- `container_id`, `name`, `image`
- `status` — running/exited/paused
- `cpu_percent`, `memory_mb`, `memory_limit_mb`
- `ports` — port mapping
- `restart_count` — jika restart policy enabled
- `health_status` — healthy/unhealthy/starting

**ServiceType:** `docker`

---

## 11. Python Monitor

**Command:** `ps aux | grep python` (filtered)

**Data dikumpulkan per process:**
- `pid`, `command` — full command line
- `cpu_percent`, `memory_mb`
- `uptime_seconds`
- `user` — user yang menjalankan

---

## 12. Network Monitor

**Command:**
- Interface: `ip -s link` atau `cat /proc/net/dev`
- Connections: `ss -tuln` atau `netstat -tuln`
- Bandwidth: kalkulasi delta dari `/proc/net/dev`

**Data dikumpulkan:**
- Per interface: `name`, `rx_bytes`, `tx_bytes`, `rx_packets`, `tx_packets`, `rx_errors`, `tx_errors`, `rx_dropped`
- `bandwidth_rx_kbps` — dihitung dari delta between check
- `bandwidth_tx_kbps`
- Established connections: `tcp_count`, `udp_count`, `listening_ports`
- `packet_loss_percent` — jika tersedia

**MetricType:** `network_in`, `network_out`

---

## 13. SSL Monitor

**Command:** `openssl s_client -connect {domain}:443 -servername {domain} </dev/null 2>/dev/null | openssl x509 -noout -dates -issuer`

**Data dikumpulkan:**
- `domain`
- `issuer`
- `valid_from`, `valid_to`
- `days_remaining`
- `status` — valid / expiring_soon (≤30 hari) / expired
- `san` — list Subject Alternative Names
- `error_message` — jika gagal check

---

## Flow Monitoring Per Siklus

```
Scheduler tick
  → CollectMetricsJob (dispatched per server, per type)
    → Service::collect()
      → SshCommandRunner::run(whitelisted_command)
        → Server responds with output
          → Service parses output → MetricData DTO
            → Metric model saved
              → Event MetricCollected
                → Reverb broadcast
                → EvaluateAlertRules listener
```

## Flow Service Check (30 detik)

```
Scheduler tick (30 detik)
  → MonitorServiceJob (dispatched per server)
    → For each ServiceType:
      → ServiceMonitor::check()
        → Jika status berubah (up→down atau down→up):
          → Event ServiceDown / ServiceUp
            → Reverb broadcast
            → EvaluateAlertRules
            → Jika ada healing rule enabled → ExecuteHealingJob
    → Update server.last_checked_at
```

**Penting:** Semua scheduled job HARUS pakai `->withoutOverlapping()` untuk mencegah dua instance job berjalan bersamaan. Untuk multi-server, gunakan lock per server: `->withoutOverlapping($server->id)`.

```php
// routes/console.php
Schedule::job(new MonitorServiceJob)->everyThirtySeconds()->withoutOverlapping();
Schedule::job(new CollectMetricsJob)->everyMinute()->withoutOverlapping();
Schedule::job(new CheckSslJob)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new ParseLogsJob)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new CheckEnvJob)->everyTenMinutes()->withoutOverlapping();
```
