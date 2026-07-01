<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $serverPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->serverPayload = [
            'name' => 'Production Server',
            'host' => '192.168.1.100',
            'port' => 22,
            'username' => 'deploy',
            'auth_type' => 'password',
            'auth_key' => 's3cr3t-k3y',
            'connection_type' => 'ssh',
            'notes' => 'Main production server',
            'os' => 'Ubuntu 22.04',
        ];
    }

    public function test_authenticated_user_can_create_server(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/servers', $this->serverPayload);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Production Server',
                'host' => '192.168.1.100',
            ]);
    }

    public function test_unauthenticated_user_cannot_create_server(): void
    {
        $response = $this->postJson('/api/servers', $this->serverPayload);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_servers(): void
    {
        Server::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/servers');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_authenticated_user_can_show_server(): void
    {
        $server = Server::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/servers/{$server->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $server->id,
                'name' => $server->name,
            ]);
    }

    public function test_showing_nonexistent_server_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/servers/99999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_update_server(): void
    {
        $server = Server::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/servers/{$server->id}", [
                'name' => 'Updated Server',
                'host' => '10.0.0.1',
                'port' => 2222,
                'username' => 'root',
                'auth_type' => 'key',
                'auth_key' => 'new-key',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Server',
                'host' => '10.0.0.1',
            ]);
    }

    public function test_authenticated_user_can_delete_server(): void
    {
        $server = Server::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/servers/{$server->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Server deleted successfully']);

        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    public function test_deleting_nonexistent_server_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/servers/99999');

        $response->assertStatus(404);
    }

    public function test_create_server_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/servers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'host', 'port', 'username', 'auth_type', 'auth_key']);
    }

    public function test_create_server_validates_port_range(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/servers', array_merge($this->serverPayload, ['port' => 99999]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['port']);
    }

    public function test_list_servers_can_filter_by_status(): void
    {
        Server::factory()->create(['status' => 'online']);
        Server::factory()->create(['status' => 'offline']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/servers?status=online');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('online', $response->json('data')[0]['status']);
    }

    public function test_list_servers_can_search(): void
    {
        Server::factory()->create(['name' => 'Production']);
        Server::factory()->create(['name' => 'Staging']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/servers?search=Production');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_any_authenticated_role_can_create_server(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->postJson('/api/servers', $this->serverPayload);

        $response->assertStatus(201);
    }
}
