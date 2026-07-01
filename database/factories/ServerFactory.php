<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => fake()->domainWord() . '-server',
            'host' => fake()->ipv4(),
            'port' => 22,
            'username' => 'deploy',
            'auth_type' => 'password',
            'auth_key' => encrypt('s3cr3t-k3y'),
            'connection_type' => 'ssh',
            'status' => 'offline',
            'health_score' => 100,
            'os' => fake()->randomElement(['Ubuntu 22.04', 'Ubuntu 24.04', 'Debian 12']),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_checked_at' => now(),
        ]);
    }
}
