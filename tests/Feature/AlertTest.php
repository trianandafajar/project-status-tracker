<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->server = Server::factory()->create();
    }

    public function test_authenticated_user_can_list_alerts(): void
    {
        Alert::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/alerts');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_show_alert(): void
    {
        $alert = Alert::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/alerts/{$alert->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $alert->id,
                'title' => $alert->title,
            ]);
    }

    public function test_unauthenticated_user_cannot_list_alerts(): void
    {
        $response = $this->getJson('/api/alerts');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_acknowledge_alert(): void
    {
        $alert = Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/alerts/{$alert->id}/acknowledge");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $alert->id,
                'status' => 'acknowledged',
                'acknowledged_by' => $this->user->id,
            ]);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'acknowledged',
            'acknowledged_by' => $this->user->id,
        ]);
    }

    public function test_authenticated_user_can_resolve_alert(): void
    {
        $alert = Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/alerts/{$alert->id}/resolve");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $alert->id,
                'status' => 'resolved',
                'resolved_by' => $this->user->id,
            ]);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'resolved',
            'resolved_by' => $this->user->id,
        ]);
    }

    public function test_acknowledging_already_resolved_alert(): void
    {
        $alert = Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/alerts/{$alert->id}/acknowledge");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'acknowledged',
            ]);
    }

    public function test_alert_list_can_filter_by_status(): void
    {
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'open',
        ]);
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'acknowledged',
        ]);
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/alerts?status=open');

        $response->assertStatus(200);
        foreach ($response->json('data') as $alert) {
            $this->assertEquals('open', $alert['status']);
        }
    }

    public function test_alert_list_can_filter_by_severity(): void
    {
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'severity' => 'critical',
        ]);
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'severity' => 'warning',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/alerts?severity=critical');

        $response->assertStatus(200);
        foreach ($response->json('data') as $alert) {
            $this->assertEquals('critical', $alert['severity']);
        }
    }

    public function test_alert_list_can_filter_by_server_id(): void
    {
        $otherServer = Server::factory()->create();

        Alert::factory()->create([
            'server_id' => $this->server->id,
            'title' => 'Server 1 Alert',
        ]);
        Alert::factory()->create([
            'server_id' => $otherServer->id,
            'title' => 'Other Server Alert',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/alerts?server_id={$this->server->id}");

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Server 1 Alert', $titles);
        $this->assertNotContains('Other Server Alert', $titles);
    }

    public function test_alert_list_can_combine_filters(): void
    {
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'open',
            'severity' => 'critical',
        ]);
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'open',
            'severity' => 'warning',
        ]);
        Alert::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'resolved',
            'severity' => 'critical',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/alerts?status=open&severity=critical&server_id={$this->server->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_showing_nonexistent_alert_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alerts/99999');

        $response->assertStatus(404);
    }

    public function test_acknowledging_nonexistent_alert_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/alerts/99999/acknowledge');

        $response->assertStatus(404);
    }

    public function test_resolving_nonexistent_alert_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/alerts/99999/resolve');

        $response->assertStatus(404);
    }
}
