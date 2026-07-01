<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->server = Server::factory()->create();
        $this->service = Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'nginx',
            'name' => 'nginx',
            'status' => 'running',
        ]);
    }

    public function test_authenticated_user_can_list_services(): void
    {
        Service::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/services");

        $response->assertStatus(200);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_list_services_returns_only_given_server_services(): void
    {
        $otherServer = Server::factory()->create();
        Service::factory()->create(['server_id' => $otherServer->id, 'name' => 'mysql']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/services");

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertNotContains('mysql', $names);
    }

    public function test_authenticated_user_can_show_service(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/services/{$this->service->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->service->id,
                'name' => 'nginx',
                'status' => 'running',
            ]);
    }

    public function test_showing_service_from_wrong_server_returns_404(): void
    {
        $otherServer = Server::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$otherServer->id}/services/{$this->service->id}");

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_get_service_history(): void
    {
        ServiceStatus::factory()->count(5)->create([
            'service_id' => $this->service->id,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$this->server->id}/services/{$this->service->id}/history");

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_list_services(): void
    {
        $response = $this->getJson("/api/servers/{$this->server->id}/services");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_show_service(): void
    {
        $response = $this->getJson("/api/servers/{$this->server->id}/services/{$this->service->id}");

        $response->assertStatus(401);
    }

    public function test_viewer_cannot_restart_service(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->postJson("/api/servers/{$this->server->id}/services/{$this->service->id}/restart");

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_start_service(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->postJson("/api/servers/{$this->server->id}/services/{$this->service->id}/start");

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_stop_service(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->postJson("/api/servers/{$this->server->id}/services/{$this->service->id}/stop");

        $response->assertStatus(403);
    }

    public function test_admin_can_access_restart_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertEquals('admin', $admin->fresh()->role);
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/servers/{$this->server->id}/services/{$this->service->id}/restart");

        $this->assertNotEquals(403, $response->status(), 'Response: ' . $response->getContent());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_operator_can_access_start_endpoint(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->assertEquals('operator', $operator->fresh()->role);
        Sanctum::actingAs($operator);

        $response = $this->postJson("/api/servers/{$this->server->id}/services/{$this->service->id}/start");

        $this->assertNotEquals(403, $response->status(), 'Response: ' . $response->getContent());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_listing_services_for_nonexistent_server_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/servers/99999/services');

        $response->assertStatus(404);
    }
}
