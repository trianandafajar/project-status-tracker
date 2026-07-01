<?php

namespace App\Services\Ssh;

use App\Models\Server;
use App\Services\Security\CredentialEncrypter;
use phpseclib3\Net\SSH2;

class SshConnection
{
    private ?SSH2 $connection = null;

    public function __construct(
        private Server $server,
        private CredentialEncrypter $encrypter,
    ) {}

    public function connect(): bool
    {
        $this->connection = new SSH2($this->server->host, $this->server->port);

        $key = $this->encrypter->decrypt($this->server->auth_key);

        if ($this->server->auth_type === 'password') {
            return $this->connection->login($this->server->username, $key);
        }

        return $this->connection->login($this->server->username, $key);
    }

    public function exec(string $command): string
    {
        if (!$this->connection) {
            throw new \RuntimeException('Not connected');
        }

        return $this->connection->exec($command);
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }
}
