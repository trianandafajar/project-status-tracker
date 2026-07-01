<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'alert_rule_id' => null,
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'severity' => fake()->randomElement(['critical', 'warning', 'info']),
            'status' => 'open',
            'triggered_at' => Carbon::now()->subMinutes(fake()->numberBetween(1, 120)),
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'warning',
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'acknowledged',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
        ]);
    }
}
