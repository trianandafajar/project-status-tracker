# 06 — Keamanan

## 1. Command Whitelist

Semua shell command yang dijalankan ke server HARUS lolos validasi whitelist.

```php
class CommandWhitelist
{
    private const ALLOWED = [
        // CPU / RAM / Disk
        "top -bn1",
        "free -m",
        "df -",
        "cat /proc/stat",
        "cat /proc/meminfo",
        "cat /proc/loadavg",
        "grep",
        "ps aux",

        // Nginx
        "systemctl is-active nginx",
        "systemctl is-enabled nginx",
        "systemctl restart nginx",
        "systemctl start nginx",
        "systemctl stop nginx",
        "systemctl reload nginx",
        "nginx -v",
        "nginx -t",
        "curl -s http://localhost/nginx_status",
        "tail -",

        // PHP-FPM
        "systemctl is-active php",
        "systemctl restart php",
        "systemctl start php",
        "systemctl stop php",
        "php -v",
        "ps aux | grep php-fpm",
        "curl -s http://localhost/php-fpm-status",

        // PM2
        "pm2 jlist",
        "pm2 restart",
        "pm2 start",
        "pm2 stop",
        "pm2 scale",

        // Redis
        "redis-cli",
        "systemctl is-active redis",
        "systemctl restart redis",
        "systemctl start redis",
        "systemctl stop redis",

        // MySQL
        "mysqladmin",
        "systemctl is-active mysql",
        "systemctl restart mysql",
        "systemctl start mysql",
        "systemctl stop mysql",

        // Docker
        "docker stats",
        "docker ps",
        "docker restart",
        "docker start",
        "docker stop",
        "docker logs",

        // Network
        "ip -s link",
        "ss -tuln",
        "cat /proc/net/dev",
        "netstat -tuln",

        // Port Scanner
        "nc -zv",

        // SSL
        "openssl s_client",
        "echo | openssl",

        // Python
        "ps aux | grep python",

        // Queue (Laravel artisan on remote)
        "php artisan queue:",

        // General
        "systemctl is-active",
        "systemctl restart",
        "systemctl start",
        "systemctl stop",
        "systemctl reload",
        "uptime",
        "whoami",
        "hostname",
        "uname -a",
    ];

    public static function validate(string $command): bool
    {
        $cmd = trim($command);

        foreach (self::ALLOWED as $allowed) {
            if (str_starts_with($cmd, $allowed)) {
                return true;
            }
        }

        throw new UnauthorizedCommandException("Command not whitelisted: $cmd");
    }

    // Sanitization: deteksi karakter berbahaya, tapi ijinkan jika prefix whitelist lolos.
    // Contoh: "top -bn1 | grep Cpu" mengandung "|" tapi prefix "top -bn1" whitelisted → lolos.
    public static function sanitize(string $command): string
    {
        if (preg_match('/[;&|`$()]/', $command)) {
            if (!self::validate($command)) {
                throw new UnauthorizedCommandException("Dangerous characters in command: $command");
            }
        }
        return $command;
    }
}
```

---

## 2. Role-Based Access Control (RBAC)

| Role | Servers | Services | Alerts | Rules | Users | Settings | Healing |
|------|---------|----------|--------|-------|-------|----------|---------|
| **superadmin** | CRUD | All actions | All | CRUD | CRUD | RW | CRUD + Execute |
| **admin** | CRUD | All actions | All | CRUD | Read | R | CRUD + Execute |
| **operator** | Read | Restart/Start/Stop | Ack/Resolve | Read | — | — | Execute only |
| **viewer** | Read | Read | Read | Read | — | — | — |

Implementasi via Laravel Gates + Policies.

```php
// AuthServiceProvider.php
Gate::define('manage-servers', fn (User $user) => in_array($user->role, ['superadmin', 'admin']));
Gate::define('execute-healing', fn (User $user) => in_array($user->role, ['superadmin', 'admin', 'operator']));
Gate::define('manage-users', fn (User $user) => $user->role === 'superadmin');
// ...etc
```

Setiap controller method cek Gate di constructor atau via FormRequest `authorize()`.

---

## 3. Audit Log

Semua action penting dicatat otomatis via middleware + manual call.

### Auto-logged via Middleware

```php
class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Auto-log semua POST/PUT/DELETE ke model
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            AuditLogger::log(
                user: $request->user(),
                action: $request->method() . ' ' . $request->path(),
                model: $request->route()?->parameterNames()[0] ?? null,
                modelId: $request->route()?->parameters[0] ?? null,
                metadata: ['input' => $request->except(['password', 'ssh_key'])]
            );
        }

        return $response;
    }
}
```

### Manual Log Points

- Service restart/start/stop
- Alert acknowledge/resolve
- Healing rule execute
- User create/delete/role change
- Settings change
- SSH connection test

### Retensi

- `audit_logs`: 180 hari (configurable via settings)
- `alerts`: 90 hari
- `logs` (parsed server logs): 14 hari
- `metrics`: 30 hari

Pruning via `PruneOldData` command harian.

---

## 4. Enkripsi Credential

SSH private key dan password dienkripsi sebelum simpan ke database.

```php
class CredentialEncrypter
{
    public function encrypt(string $value): string
    {
        return encrypt($value); // Laravel's AES-256-CBC with APP_KEY
    }

    public function decrypt(string $encrypted): string
    {
        return decrypt($encrypted);
    }
}

// Server model
class Server extends Model
{
    protected function sshKey(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => app(CredentialEncrypter::class)->decrypt($value),
            set: fn (string $value) => app(CredentialEncrypter::class)->encrypt($value),
        );
    }
}
```

---

## 5. Rate Limiting

```php
// AppServiceProvider boot()
RateLimiter::for('api', fn (Request $request) =>
    Limit::perMinute(120)->by($request->user()?->id ?: $request->ip())
);

RateLimiter::for('auth', fn (Request $request) =>
    Limit::perMinute(10)->by($request->ip())
);

RateLimiter::for('export', fn (Request $request) =>
    Limit::perMinute(5)->by($request->user()?->id ?: $request->ip())
);

RateLimiter::for('scan', fn (Request $request) =>
    Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
);
```

Route groups:
```php
Route::middleware(['auth:sanctum', 'throttle:api', 'audit.log'])->group(function () {
    Route::middleware('throttle:export')->group(function () { /* export routes */ });
    Route::middleware('throttle:scan')->group(function () { /* scan/healing execute routes */ });
});
```

---

## 6. Queue Isolation

Semua command ke server remote via Queue, tidak pernah langsung dari request HTTP.

```php
// Controller
public function restart(Server $server, Service $service): JsonResponse
{
    // Tidak langsung: $ssh->exec('systemctl restart nginx')
    // Tapi:
    dispatch(new ExecuteServiceActionJob($server, $service, 'restart'));

    return response()->json(['message' => 'Restart queued']);
}
```

Benefit:
- Request tidak timeout meski command lambat
- Retry otomatis jika SSH gagal
- Rate limit alami via queue worker count
- Audit trail built-in via job lifecycle

---

## 7. SSH Connection

```php
class SshConnection
{
    public function __construct(
        private string $host,
        private int $port,
        private string $user,
        private string $privateKey, // decrypted on use, never cached
    ) {}

    public function execute(string $command): string
    {
        CommandWhitelist::validate($command);
        CommandWhitelist::sanitize($command);

        // Use phpseclib/phpseclib (pure PHP SSH, no ext dependency)
        $ssh = new SSH2($this->host, $this->port);
        $key = PublicKeyLoader::load($this->privateKey);

        if (!$ssh->login($this->user, $key)) {
            throw new SshConnectionException("SSH login failed: {$this->host}");
        }

        $output = $ssh->exec($command);
        $exitCode = $ssh->getExitStatus();

        if ($exitCode !== 0 && $exitCode !== null) {
            throw new SshCommandException("Command failed with exit code $exitCode: $command\n$output");
        }

        return $output;
    }
}
```

---

## 8. Sanctum Token

- Token expire dalam 24 jam (configurable)
- Token scope: `server:read`, `server:write`, `service:action`, `admin`
- Rotate token via `/auth/refresh`

---

## 9. Additional Hardening

- **CORS:** Allow only dashboard domain
- **HSTS:** Enable via nginx config
- **CSP:** Content-Security-Policy header di Blade view
- **SQL injection:** Eloquent + prepared statements (built-in Laravel)
- **XSS:** Blade auto-escape `{{ }}` + CSP header
- **CSRF:** Laravel built-in + API pakai token, bukan cookie session
- **File upload:** Tidak ada file upload di sistem ini (credential via text input terenkripsi)
