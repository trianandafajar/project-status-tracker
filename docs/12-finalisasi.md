# 12 — Finalisasi

Checklist pre-release untuk setiap versi dan final project.

---

## General Finalization Checklist

### Code Quality
- [ ] `php artisan test` — semua pass, coverage ≥ 80%
- [ ] `php artisan test --coverage` — report
- [ ] Laravel Pint / PHP CS Fixer — coding style
- [ ] PHPStan level 5 — static analysis
- [ ] Tidak ada `dd()`, `dump()`, `var_dump()` di production code
- [ ] Tidak ada TODO/FIXME unresolved
- [ ] Semua route ada di `03-api-routes.md` — verifikasi dengan `php artisan route:list`

### Database
- [ ] Semua migration fresh-run: `php artisan migrate:fresh --seed`
- [ ] Tidak ada migration error / rollback error
- [ ] Indeks sesuai spec `02-database-schema.md`
- [ ] Foreign key constraint on delete (CASCADE/SET NULL sesuai konteks)

### Queue & Scheduler
- [ ] Semua job terdaftar di scheduler (`routes/console.php`)
- [ ] Semua job ada retry logic + failed job handling
- [ ] `php artisan queue:work` — tidak error
- [ ] `php artisan schedule:run` — tidak error
- [ ] Failed jobs table ready
- [ ] Queue connection: Redis (bukan sync, bukan database)

### Reverb
- [ ] WebSocket connect: `php artisan reverb:start`
- [ ] Channel auth: `POST /broadcasting/auth` return 200
- [ ] Private channel subscribe berhasil di frontend
- [ ] Event broadcast via `php artisan tinker` → event(new ...) diterima frontend

### Security
- [ ] Semua route api pakai `auth:sanctum` middleware (kecuali auth routes)
- [ ] Semua controller action ada Policy/Gate check
- [ ] Command whitelist diuji dengan malicious input
- [ ] SSH key terenkripsi di database (verify langsung via MySQL query)
- [ ] Rate limit berfungsi (test: curl berulang)
- [ ] Audit log mencatat action sensitif
- [ ] `.env.example` tidak mengandung credential asli

### UI/UX
- [ ] Dashboard load < 2 detik
- [ ] Realtime update WebSocket ≤ 2 detik setelah event
- [ ] Mobile responsive (Tailwind responsive classes)
- [ ] Dark mode (Tailwind dark: prefix)
- [ ] Error state: server unreachable, SSH timeout, permission denied — semua ada UI feedback
- [ ] Loading state: skeleton / spinner saat data fetching
- [ ] Empty state: "No servers yet" message + CTA
- [ ] Alert notification muncul di UI (toast) via Reverb

### Documentation
- [ ] README.md: setup instructions, requirements, architecture diagram
- [ ] API docs: bisa generate dari Route list + form request rules
- [ ] All 12 doc files reviewed + up to date

---

## Per-Version Finalization

### v1.0 Sign-off
- [ ] Manual test: tambah server, lihat metrics, restart service
- [ ] Demo ke stakeholder
- [ ] Tag release: `v1.0.0`

### v1.1 Sign-off
- [ ] Manual test: lihat log parsed, trigger 502 analysis
- [ ] Verifikasi parser regex untuk semua format log
- [ ] Tag release: `v1.1.0`

### v1.2 Sign-off
- [ ] Manual test: trigger alert → notifikasi terkirim ke Telegram/Discord/Email
- [ ] Manual test: service down → healing auto restart → service up
- [ ] Verifikasi cooldown & max_attempts
- [ ] Tag release: `v1.2.0`

### v1.3 Sign-off
- [ ] Manual test: 3+ server termonitor bersamaan
- [ ] SSL check, port scan, env check, docker, python, network
- [ ] Health score semua server
- [ ] Tag release: `v1.3.0`

### v2.0 Sign-off
- [ ] Manual test: train model, lihat prediction
- [ ] Verifikasi akurasi model di atas baseline
- [ ] Feedback loop berfungsi
- [ ] Tag release: `v2.0.0`

---

## Edge Case Handling Checklist

### SSH / Server Connectivity
- [ ] SSH timeout: job retry 3x, lalu mark server `status = offline`, buat alert
- [ ] Server unreachable >3 kali berturut-turut: auto-create critical alert
- [ ] Partial metric collection (CPU OK, RAM gagal): simpan yang berhasil, log error untuk yang gagal — jangan abort seluruh job

### Queue / Job
- [ ] Job timeout >60 detik: Laravel job `--timeout=60`, fail → masuk `failed_jobs`
- [ ] Queue connection Redis down: job throw exception → retry → masuk failed_jobs → alert
- [ ] Scheduler overlap: semua job pakai `withoutOverlapping()` (lihat `04-modul-monitoring.md`)

### Healing
- [ ] Concurrent healing race: Redis lock per healing rule (lihat `05-alert-dan-self-healing.md`)
- [ ] Healing cooldown: cek `healing_logs` terakhir sebelum eksekusi, skip jika dalam cooldown
- [ ] Post-healing check: tunggu 5 detik, cek ulang status service, log hasil

### Database
- [ ] High-frequency metric insert: 1 server × 3 type × 1 menit = 4.320 row/hari. Untuk 10 server = 43.200 row/hari. Partition metrics table by month jika data >1 juta row.
- [ ] Deadlock pada concurrent insert: gunakan `insertOrIgnore` atau upsert pattern

### Data Integrity
- [ ] Corrupted metric value (NaN, negative, null): `parseNumber()` return `0.0` + log warning, jangan throw
- [ ] Log file tidak ditemukan: `ParseLogsJob` catch exception, skip file, lanjut ke source berikutnya
- [ ] Model file corrupted (v2.0): `PredictFailureJob` catch exception, skip prediction, alert "Model file invalid"
- [ ] Feature vector NaN (v2.0): `FeatureEngineering` replace null/missing dengan 0, log warning

### Sentinel Self-Monitoring
- [ ] Sentinel server sendiri tidak termonitor: tambahkan server Sentinel di dashboard-nya sendiri (dogfooding)
- [ ] Disk Sentinel penuh: `PruneOldData` hapus metrics >30 hari, logs >14 hari, alerts >90 hari, audit_logs >180 hari
- [ ] Reverb disconnect: frontend fallback polling 30 detik

---

## Production Deployment Checklist

### Server Requirements
- [ ] PHP 8.3 + extensions: bcmath, ctype, curl, fileinfo, json, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml
- [ ] MySQL 8.0+
- [ ] Redis 7.0+
- [ ] Supervisor untuk queue worker + Reverb
- [ ] Nginx dengan config yang benar (lihat template below)

### Supervisor Config

```ini
[program:sentinel-queue]
command=php /var/www/sentinel/artisan queue:work redis --sleep=1 --tries=3 --max-time=300 --timeout=60
numprocs=1
autostart=true
autorestart=true
user=www-data

[program:sentinel-reverb]
command=php /var/www/sentinel/artisan reverb:start
numprocs=1
autostart=true
autorestart=true
user=www-data

[program:sentinel-scheduler]
command=php /var/www/sentinel/artisan schedule:work
numprocs=1
autostart=true
autorestart=true
user=www-data
```

### Nginx Config Template

```nginx
server {
    listen 80;
    server_name sentinel.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sentinel.example.com;

    root /var/www/sentinel/public;
    index index.php;

    # SSL cert managed by aaPanel / certbot

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /app/ {
        # Reverb WebSocket
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Environment Variables

```env
APP_NAME="Sentinel Server Monitor"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sentinel.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sentinel
DB_USERNAME=sentinel
DB_PASSWORD=strong-password-here

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=strong-redis-password
REDIS_PORT=6379

QUEUE_CONNECTION=redis

REVERB_APP_ID=sentinel
REVERB_APP_KEY=sentinel-key
REVERB_APP_SECRET=sentinel-secret
REVERB_HOST=sentinel.example.com
REVERB_PORT=8080
REVERB_SCHEME=https

SANCTUM_STATEFUL_DOMAINS=sentinel.example.com
SESSION_DOMAIN=.sentinel.example.com
```

### Final Checks
- [ ] `php artisan optimize` (config + route + view cache)
- [ ] `php artisan storage:link` (jika ada file storage)
- [ ] CHMOD: `storage/`, `bootstrap/cache/` writable
- [ ] `.env` APP_DEBUG=false
- [ ] Database backup cron job active
- [ ] Monitoring Sentinel itu sendiri (dogfooding): tambahkan server Sentinel di dashboard-nya sendiri?
- [ ] SSL certificate untuk domain Sentinel
- [ ] Firewall: restrict SSH access
- [ ] Log rotation untuk `storage/logs/laravel.log`

---

## Post-Launch

- [ ] Monitor metric Sentinel sendiri (CPU, RAM, Disk)
- [ ] Review alert pertama yang muncul
- [ ] Tuning threshold alert rule berdasarkan data real
- [ ] Feedback loop untuk perbaikan v2 model
- [ ] User training untuk operator + admin
- [ ] Backup database harian

---

## Total Deliverables Summary

| Versi | Model | Controller | Service | Job | Migration | Test |
|-------|-------|-----------|---------|-----|-----------|------|
| v1.0 | 7 | 10 | 17 | 2 | 9 | 8 |
| v1.1 | +2 | +2 | +8 | +2 | +2 | +3 |
| v1.2 | +4 | +3 | +10 | +2 | +4 | +4 |
| v1.3 | +6 | +6 | +6 | +3 | +6 | +4 |
| v2.0 | +1 | +2 | +5 | +4 | +2 | +4 |
| **Total** | **20** | **23** | **46** | **13** | **23** | **23** |

**Route endpoints:** ~92
**Database tables:** 22
