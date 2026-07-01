# 05 — Alert & Self Healing

## Alert Flow

```
MetricCollected / ServiceDown / ServiceUp / SslExpiring
        │
        ▼
EvaluateAlertRules Listener
        │
        ├── Load all enabled AlertRules matching event type
        ├── Evaluate: condition + threshold + duration
        │     │
        │     ├── PASS → Create Alert
        │     │            │
        │     │            ├── Check cooldown (jangan spam)
        │     │            ├── Save to alerts table
        │     │            ├── Event AlertTriggered → Reverb broadcast
        │     │            ├── AlertDispatcher → semua AlertChannel enabled
        │     │            │     ├── TelegramChannel
        │     │            │     ├── DiscordChannel
        │     │            │     └── EmailChannel
        │     │            │
        │     │            └── Jika ada HealingRule terhubung:
        │     │                  ExecuteHealingJob
        │     │
        │     └── FAIL → skip
        │
        └── Log to AuditLog
```

---

## Alert Rule Definition

```php
// Contoh rule: "CPU > 90% selama 5 menit = critical alert"
AlertRule {
    name: "CPU Critical",
    metric_type: "cpu",
    condition: "gt",
    threshold: 90.00,
    duration_seconds: 300,
    severity: "critical",
    message_template: "Server {server} CPU usage at {value}% for 5 minutes",
    cooldown_seconds: 600,  // jangan kirim ulang dalam 10 menit
    enabled: true,
}
```

### Rule Condition Types

| Condition | Keterangan |
|-----------|------------|
| `gt` | greater than (> threshold) |
| `lt` | less than (< threshold) |
| `gte` | greater than or equal (≥ threshold) |
| `lte` | less than or equal (≤ threshold) |
| `eq` | equal (==) — untuk status service |

### Default Alert Rules (seeded)

1. **CPU > 90% for 5 min** → critical
2. **RAM > 90% for 5 min** → critical
3. **Disk > 85% for 1 min** → warning
4. **Disk > 95% for 1 min** → critical
5. **Any service down for 30s** → critical
6. **SSL < 14 days remaining** → warning
7. **SSL < 7 days remaining** → critical
8. **MySQL slow_queries > 50/min** → warning
9. **Redis memory > 80%** → warning
10. **Queue failed_jobs > 10 in 5 min** → critical
11. **Queue size > 100** → warning
12. **PHP-FPM max_children reached in 1 min** → critical
13. **502 errors detected** → critical

---

## Alert Dispatcher

```php
class AlertDispatcher
{
    public function dispatch(Alert $alert): void
    {
        $channels = AlertChannel::where('enabled', true)->get();

        foreach ($channels as $channel) {
            $notification = AlertNotification::create([
                'alert_id' => $alert->id,
                'alert_channel_id' => $channel->id,
                'status' => 'pending',
            ]);

            match ($channel->type) {
                'telegram' => (new TelegramChannel($channel))->send($alert, $notification),
                'discord'  => (new DiscordChannel($channel))->send($alert, $notification),
                'email'    => (new EmailChannel($channel))->send($alert, $notification),
            };
        }
    }
}
```

---

## Channel Config Schema

### Telegram

```json
{
    "bot_token": "123456:ABC-DEF",
    "chat_id": "-100123456",
    "parse_mode": "HTML"
}
```

### Discord

```json
{
    "webhook_url": "https://discord.com/api/webhooks/...",
    "username": "Sentinel Bot",
    "avatar_url": null
}
```

### Email

```json
{
    "to": ["admin@example.com", "ops@example.com"],
    "cc": [],
    "bcc": []
}
```

---

## Message Template Format

```
🔥 {severity_emoji} [{severity}] {title}
Server: {server_name} ({server_host})
Time: {timestamp}

{message}

Metric: {metric_type} = {value}{unit} (threshold: {condition} {threshold})
Status: {status}
View: {dashboard_url}
```

---

## Self Healing

### Flow

```
AlertTriggered
  → Check healing_rules WHERE alert_rule_id = triggered_rule AND enabled = true
    → Check cooldown (healing_logs terakhir dalam cooldown_seconds?)
      → PASS → ExecuteHealingJob
        → Check max_attempts (belum exceed?)
          → Eksekusi action
            → Log hasil ke healing_logs
            → Update alert dengan hasil
            → Event HealingExecuted → Reverb broadcast
```

### Healing Action Types

| Action | Parameter | Contoh Command |
|--------|-----------|---------------|
| `restart_service` | `service_type` | `systemctl restart nginx` / `systemctl restart php8.3-fpm` |
| `clear_cache` | — | Redis: `redis-cli FLUSHDB`, OPCache reload via PHP-FPM reload |
| `run_command` | `command` (whitelisted) | Custom command yang sudah whitelist |
| `scale_worker` | `service_type` | PM2 scale, atau systemctl restart queue-worker |

### Default Healing Rules (seeded, all disabled)

1. **Nginx down** → restart_service nginx (max 3x, cooldown 300s)
2. **PHP-FPM down** → restart_service php-fpm (max 3x, cooldown 300s)
3. **Redis down** → restart_service redis (max 2x, cooldown 600s)
4. **MySQL down** → restart_service mysql (max 2x, cooldown 600s)
5. **Queue stuck (>100)** → scale_worker queue-worker (max 3x, cooldown 300s)

### Safety Guards

- Semua command melalui `CommandWhitelist` validation
- `max_attempts` mencegah infinite restart loop
- `cooldown_seconds` mencegah restart beruntun
- **Atomic cooldown guard:** gunakan Redis lock (`cache::lock('healing:'.$ruleId, $cooldownSeconds)`) untuk mencegah race condition saat dua alert trigger healing rule yang sama secara bersamaan
- Healing rules **default disabled** — admin harus enable manual
- Setiap eksekusi di-log ke `healing_logs` + `audit_logs`
- Jika healing gagal, alert tetap open, tidak auto-resolve
- Post-healing health check: setelah eksekusi, tunggu 5 detik lalu cek ulang status service. Jika masih down → log sebagai `failed`, tidak increment retry count untuk skenario "service start tapi langsung crash lagi"
- `HealingStatus.skipped`: digunakan jika cooldown belum habis atau max_attempts sudah tercapai saat job dieksekusi
