<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceStatusFactory extends Factory
{
    protected $model = ServiceStatus::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'status' => fake()->randomElement(['running', 'stopped', 'unknown']),
            'output' => fake()->optional()->sentence(),
            'checked_at' => now(),
        ];
    }
}
