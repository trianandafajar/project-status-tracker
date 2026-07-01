<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function view(User $user, Server $server): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin', 'operator']);
    }

    public function update(User $user, Server $server): bool
    {
        return in_array($user->role, ['superadmin', 'admin']);
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->role === 'superadmin';
    }

    public function manage(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin', 'operator']);
    }
}
