<?php

namespace App\Services\Ssh;

use App\Models\Server;

class SshCommandRunner
{
    public function __construct(
        private SshConnection $connection,
    ) {}

    public function run(Server $server, string $command): string
    {
        $this->connection->connect();

        try {
            return $this->connection->exec($command);
        } finally {
            $this->connection->disconnect();
        }
    }
}
