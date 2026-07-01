<?php

namespace Database\Factories;

use App\Models\Metric;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetricFactory extends Factory
{
    protected $model = Metric::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'type' => fake()->randomElement(['cpu', 'ram', 'disk']),
            'value' => fake()->randomFloat(2, 0, 100),
            'unit' => fake()->randomElement(['%', 'MB', 'GB']),
            'recorded_at' => now(),
        ];
    }

    public function cpu(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cpu',
            'unit' => '%',
        ]);
    }

    public function ram(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ram',
            'unit' => '%',
        ]);
    }

    public function disk(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'disk',
            'unit' => '%',
        ]);
    }
}
