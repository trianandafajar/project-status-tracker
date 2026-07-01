<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'type' => fake()->randomElement(['nginx', 'php-fpm', 'mysql', 'redis', 'pm2', 'queue', 'docker']),
            'name' => fake()->randomElement(['nginx', 'php8.3-fpm', 'mysql', 'redis-server', 'pm2', 'queue-worker', 'docker']),
            'status' => fake()->randomElement(['running', 'stopped', 'unknown']),
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
        ]);
    }

    public function stopped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
        ]);
    }
}
