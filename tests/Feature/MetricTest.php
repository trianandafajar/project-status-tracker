<?php

namespace Tests\Feature;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create();
    }

    public function test_authenticated_user_can_list_metrics(): void
    {
        Metric::factory()->count(5)->create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertStatus(200);
    }

    public function test_list_metrics_can_filter_by_type(): void
    {
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'recorded_at' => now(),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'ram',
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics?type=cpu");

        $response->assertStatus(200);
        foreach ($response->json('data') as $metric) {
            $this->assertEquals('cpu', $metric['type']);
        }
    }

    public function test_authenticated_user_can_get_latest_metrics(): void
    {
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 45.5,
            'recorded_at' => now()->subMinutes(5),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 52.1,
            'recorded_at' => now(),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'ram',
            'value' => 68.3,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics/latest");

        $response->assertStatus(200);

        $types = collect($response->json('data'))->pluck('type');
        $this->assertContains('cpu', $types);
        $this->assertContains('ram', $types);

        $cpuMetrics = collect($response->json('data'))->where('type', 'cpu')->first();
        $this->assertEquals(52.1, $cpuMetrics['value']);
    }

    public function test_authenticated_user_can_get_metric_history(): void
    {
        $now = Carbon::now();

        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 30,
            'recorded_at' => (clone $now)->subMinutes(30),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 50,
            'recorded_at' => (clone $now)->subMinutes(15),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 80,
            'recorded_at' => $now,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics/history?type=cpu&interval=1h");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_metric_history_defaults_to_one_hour(): void
    {
        Carbon::setTestNow(Carbon::now());
        $now = Carbon::now();

        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 10,
            'recorded_at' => (clone $now)->subHours(2),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 20,
            'recorded_at' => (clone $now)->subMinutes(30),
        ]);
        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'value' => 30,
            'recorded_at' => $now,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics/history?type=cpu");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        Carbon::setTestNow();
    }

    public function test_metric_history_validates_type_required(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics/history");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_metric_history_accepts_different_intervals(): void
    {
        $now = Carbon::now();

        Metric::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'cpu',
            'recorded_at' => (clone $now)->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/metrics/history?type=cpu&interval=1d");

        $response->assertStatus(200);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_latest_metrics_are_rejected(): void
    {
        $response = $this->getJson("/api/servers/{$this->server->id}/metrics/latest");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_history_metrics_are_rejected(): void
    {
        $response = $this->getJson("/api/servers/{$this->server->id}/metrics/history");

        $response->assertStatus(401);
    }

    public function test_metrics_for_nonexistent_server_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/servers/99999/metrics');

        $response->assertStatus(404);
    }
}
