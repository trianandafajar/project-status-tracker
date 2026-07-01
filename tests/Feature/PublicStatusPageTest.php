<?php

namespace Tests\Feature;

use App\Jobs\PruneStatusChecksJob;
use App\Models\StatusCheck;
use App\Models\StatusMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicStatusPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_homepage_is_public_and_renders_status_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Status');
        $response->assertSee('Add target');
    }

    public function test_public_target_can_be_created_with_valid_payload(): void
    {
        Http::fake([
            'https://portfolio.example.com/health' => Http::response('', 200),
        ]);

        $response = $this->postJson('/api/status/targets', [
            'name' => 'Portfolio App',
            'group_name' => 'Portfolio',
            'type' => 'website',
            'url' => 'https://portfolio.example.com/health',
            'method' => 'HEAD',
            'timeout_seconds' => 5,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('status_monitors', [
            'name' => 'Portfolio App',
            'group_name' => 'Portfolio',
            'type' => 'website',
            'method' => 'HEAD',
            'expected_status_code' => 200,
        ]);

        $this->assertDatabaseHas('status_checks', [
            'status' => 'operational',
            'http_status_code' => 200,
        ]);
    }

    public function test_public_target_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/status/targets', [
            'name' => '',
            'group_name' => 'Portfolio',
            'type' => 'database',
            'url' => 'not-a-url',
            'method' => 'HEAD',
        ]);

        $response->assertStatus(422);

        $errors = $response->json('errors') ?? [];

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('url', $errors);
        $this->assertArrayHasKey('type', $errors);
    }

    public function test_head_check_rejects_keyword_validation(): void
    {
        $response = $this->postJson('/api/status/targets', [
            'name' => 'Portfolio App',
            'group_name' => 'Portfolio',
            'type' => 'website',
            'url' => 'https://portfolio.example.com/health',
            'method' => 'HEAD',
            'expected_keyword' => 'healthy',
        ]);

        $response->assertStatus(422);

        $errors = $response->json('errors') ?? [];

        $this->assertArrayHasKey('expected_keyword', $errors);
    }

    public function test_old_checks_are_pruned_after_six_hours(): void
    {
        $monitor = StatusMonitor::create([
            'name' => 'Portfolio DB',
            'group_name' => 'Portfolio',
            'type' => 'api',
            'url' => 'https://portfolio.example.com/db-health',
            'method' => 'GET',
            'expected_status_code' => 200,
            'timeout_seconds' => 5,
            'enabled' => true,
        ]);

        StatusCheck::create([
            'status_monitor_id' => $monitor->id,
            'status' => 'down',
            'http_status_code' => 502,
            'response_time_ms' => 910,
            'error_message' => 'HTTP 502 Bad Gateway',
            'checked_at' => now()->subHours(7),
        ]);

        StatusCheck::create([
            'status_monitor_id' => $monitor->id,
            'status' => 'operational',
            'http_status_code' => 200,
            'response_time_ms' => 120,
            'error_message' => null,
            'checked_at' => now()->subMinutes(10),
        ]);

        (new PruneStatusChecksJob())->handle();

        $this->assertDatabaseCount('status_checks', 1);
        $this->assertDatabaseHas('status_checks', [
            'status_monitor_id' => $monitor->id,
            'status' => 'operational',
            'http_status_code' => 200,
        ]);
    }

    public function test_snapshot_can_be_polled_without_mutating_state(): void
    {
        $monitor = StatusMonitor::create([
            'name' => 'Portfolio API',
            'group_name' => 'Portfolio',
            'type' => 'api',
            'url' => 'https://portfolio.example.com/api/health',
            'method' => 'GET',
            'expected_keyword' => 'ok',
            'timeout_seconds' => 5,
            'enabled' => true,
            'last_status' => 'operational',
            'last_http_code' => 200,
            'last_response_ms' => 110,
            'last_checked_at' => now(),
            'last_uptime_percent' => 100,
        ]);

        foreach (range(0, 4) as $minuteOffset) {
            StatusCheck::create([
                'status_monitor_id' => $monitor->id,
                'status' => 'operational',
                'http_status_code' => 200,
                'response_time_ms' => 110,
                'error_message' => null,
                'checked_at' => now()->subMinutes($minuteOffset),
            ]);
        }

        $before = StatusCheck::count();

        $response = $this->getJson('/api/status/snapshot')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('poll_interval_seconds', 60)
            ->assertJsonPath('display_window_minutes', 60);

        $sparkBars = collect($response->json('groups.0.monitors.0.spark_bars'));

        $this->assertSame(60, $sparkBars->count());
        $this->assertGreaterThanOrEqual(1, $sparkBars->where('status', 'operational')->count());

        $this->getJson('/api/status/snapshot')->assertOk()->assertJsonPath('summary.operational', 1);

        $this->assertSame($before, StatusCheck::count());
    }
}
