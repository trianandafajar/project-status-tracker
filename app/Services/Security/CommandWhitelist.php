<?php

namespace App\Services\Security;

class CommandWhitelist
{
    private const ALLOWED = [
        'cat /proc/loadavg',
        'cat /proc/stat',
        'cat /proc/meminfo',
        'cat /proc/net/dev',
        'top -bn1',
        'free -m',
        'df -',
        'grep ',
        'ps aux',
        'systemctl is-active ',
        'systemctl is-enabled ',
        'systemctl restart ',
        'systemctl start ',
        'systemctl stop ',
        'systemctl reload ',
        'nginx -v',
        'nginx -t',
        'curl -s ',
        'tail -',
        'php -v',
        'php artisan queue:',
        'pm2 jlist',
        'pm2 list',
        'pm2 status',
        'pm2 restart',
        'pm2 start',
        'pm2 stop',
        'pm2 scale',
        'redis-cli',
        'redis-cli PING',
        'redis-cli INFO',
        'mysqladmin',
        'mysqladmin version',
        'mysqladmin ping',
        'docker stats',
        'docker ps',
        'docker restart',
        'docker start',
        'docker stop',
        'docker logs',
        'ss -tuln',
        'netstat -tuln',
        'ip -s link',
        'nc -zv',
        'openssl s_client',
        'echo | openssl',
        'uptime',
        'whoami',
        'hostname',
        'uname -a',
    ];

    public function validate(string $command): bool
    {
        $cmd = trim($command);

        if (preg_match('/[;&`$()]/', $cmd)) {
            foreach (self::ALLOWED as $allowed) {
                if (str_starts_with($cmd, $allowed)) {
                    return true;
                }
            }
            return false;
        }

        foreach (self::ALLOWED as $allowed) {
            if (str_starts_with($cmd, $allowed)) {
                return true;
            }
        }

        return false;
    }

    public function whitelist(): array
    {
        return self::ALLOWED;
    }
}
