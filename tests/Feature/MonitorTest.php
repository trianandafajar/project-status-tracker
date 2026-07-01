<?php

namespace Tests\Feature;

use App\Jobs\CollectMetricsJob;
use App\Jobs\MonitorServiceJob;
use App\Jobs\ExecuteServiceActionJob;
use App\Models\AuditLog;
use App\Models\Metric;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\Services\Security\CommandWhitelist;
use App\Services\Ssh\SshCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Support\MockSshHelper;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use RefreshDatabase, MockSshHelper;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = Server::factory()->online()->create();
    }

    public function test_collect_metrics_job_creates_cpu_metric(): void
    {
        $this->mockSshWithData($this->getCpuCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'type' => 'cpu',
        ]);

        $metric = Metric::where('server_id', $this->server->id)
            ->where('type', 'cpu')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals('%', $metric->unit);
    }

    public function test_collect_metrics_job_creates_ram_metric(): void
    {
        $this->mockSshWithData($this->getRamCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'type' => 'ram',
        ]);

        $metric = Metric::where('server_id', $this->server->id)
            ->where('type', 'ram')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals('%', $metric->unit);
    }

    public function test_collect_metrics_job_creates_disk_metric(): void
    {
        $this->mockSshWithData($this->getDiskCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'type' => 'disk',
        ]);

        $metric = Metric::where('server_id', $this->server->id)
            ->where('type', 'disk')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals('%', $metric->unit);
    }

    public function test_collect_metrics_job_creates_all_metric_types(): void
    {
        $this->mockSshWithData($this->getAllMetricsCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $types = Metric::where('server_id', $this->server->id)
            ->pluck('type')
            ->toArray();

        $this->assertContains('cpu', $types);
        $this->assertContains('ram', $types);
        $this->assertContains('disk', $types);
        $this->assertCount(3, $types);
    }

    public function test_collect_metrics_job_stores_correct_cpu_value(): void
    {
        $this->mockSshWithData($this->getCpuCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $metric = Metric::where('server_id', $this->server->id)
            ->where('type', 'cpu')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals(14.9, $metric->value);
    }

    public function test_collect_metrics_job_stores_correct_ram_value(): void
    {
        $this->mockSshWithData($this->getRamCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $metric = Metric::where('server_id', $this->server->id)
            ->where('type', 'ram')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals(12.82, round($metric->value, 2));
    }

    public function test_collect_metrics_job_stores_correct_disk_value(): void
    {
        $this->mockSshWithData($this->getDiskCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $metric = Metric::where('server_id', $this->server->id)
            ->where('type', 'disk')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals(45, $metric->value);
    }

    public function test_monitor_service_job_updates_service_to_running(): void
    {
        $service = Service::factory()->stopped()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mockSshWithData($this->getServiceCommandMap('active (running)'));

        MonitorServiceJob::dispatchSync($this->server->id);

        $service->refresh();
        $this->assertEquals('running', $service->status);
    }

    public function test_monitor_service_job_updates_service_to_stopped(): void
    {
        $service = Service::factory()->running()->create([
            'server_id' => $this->server->id,
            'type' => 'nginx',
            'name' => 'nginx',
        ]);

        $this->mockSshWithData($this->getServiceCommandMap('inactive (dead)'));

        MonitorServiceJob::dispatchSync($this->server->id);

        $service->refresh();
        $this->assertEquals('stopped', $service->status);
    }

    public function test_monitor_service_job_does_not_update_if_status_unchanged(): void
    {
        $service = Service::factory()->running()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mockSshWithData($this->getServiceCommandMap('active (running)'));

        MonitorServiceJob::dispatchSync($this->server->id);

        $service->refresh();
        $this->assertEquals('running', $service->status);
    }

    public function test_monitor_service_job_creates_status_history_on_change(): void
    {
        $service = Service::factory()->stopped()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mockSshWithData($this->getServiceCommandMap('active (running)'));

        MonitorServiceJob::dispatchSync($this->server->id);

        $this->assertDatabaseHas('service_status_history', [
            'service_id' => $service->id,
            'status' => 'running',
        ]);
    }

    public function test_monitor_service_job_handles_all_service_types(): void
    {
        $types = ['nginx', 'php-fpm', 'redis', 'mysql', 'docker'];

        foreach ($types as $type) {
            Service::factory()->stopped()->create([
                'server_id' => $this->server->id,
                'type' => $type,
                'name' => $type,
            ]);
        }

        $this->mockSshWithData($this->getServiceCommandMap('active (running)'));

        MonitorServiceJob::dispatchSync($this->server->id);

        $updated = Service::where('server_id', $this->server->id)
            ->where('status', 'running')
            ->count();

        $this->assertEquals(count($types), $updated);
    }

    public function test_execute_service_action_job_creates_audit_log_for_restart(): void
    {
        $service = Service::factory()->create(['server_id' => $this->server->id]);
        $this->mockSshWithData(['systemctl restart' => 'Restarting nginx... OK']);

        ExecuteServiceActionJob::dispatchSync(
            $this->server->id,
            $service->id,
            'restart',
        );

        $this->assertDatabaseHas('audit_logs', [
            'server_id' => $this->server->id,
            'action' => 'service.restart',
            'resource_type' => 'service',
            'resource_id' => $service->id,
        ]);
    }

    public function test_execute_service_action_job_creates_audit_log_for_start(): void
    {
        $service = Service::factory()->create(['server_id' => $this->server->id]);
        $this->mockSshWithData(['systemctl start' => 'Starting nginx... OK']);

        ExecuteServiceActionJob::dispatchSync(
            $this->server->id,
            $service->id,
            'start',
        );

        $this->assertDatabaseHas('audit_logs', [
            'server_id' => $this->server->id,
            'action' => 'service.start',
        ]);
    }

    public function test_execute_service_action_job_creates_audit_log_for_stop(): void
    {
        $service = Service::factory()->create(['server_id' => $this->server->id]);
        $this->mockSshWithData(['systemctl stop' => 'Stopping nginx... OK']);

        ExecuteServiceActionJob::dispatchSync(
            $this->server->id,
            $service->id,
            'stop',
        );

        $this->assertDatabaseHas('audit_logs', [
            'server_id' => $this->server->id,
            'action' => 'service.stop',
        ]);
    }

    public function test_execute_service_action_logs_command_and_output(): void
    {
        $service = Service::factory()->create(['server_id' => $this->server->id]);
        $expectedOutput = 'Restarting nginx... OK';
        $this->mockSshWithData(['systemctl restart' => $expectedOutput]);

        ExecuteServiceActionJob::dispatchSync(
            $this->server->id,
            $service->id,
            'restart',
        );

        $auditLog = AuditLog::where('server_id', $this->server->id)
            ->where('action', 'service.restart')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($expectedOutput, $auditLog->details['output']);
        $this->assertStringContainsString('systemctl restart', $auditLog->details['command']);
    }

    public function test_mock_returns_different_data_per_command(): void
    {
        $commandMap = [
            'loadavg' => '2.00 1.50 1.00',
            'free -m' => '              total        used        free      shared  buff/cache   available
Mem:           16384        8192        4096         256        4096        7782
Swap:          4096           0        4096',
            'df -h /' => 'Filesystem      Size  Used Avail Use% Mounted on
/dev/sda1       200G   100G  100G  50% /',
        ];

        $this->mockSshWithData($commandMap);

        CollectMetricsJob::dispatchSync($this->server->id);

        $cpuMetric = Metric::where('server_id', $this->server->id)
            ->where('type', 'cpu')
            ->first();

        $this->assertNotNull($cpuMetric);

        $ramMetric = Metric::where('server_id', $this->server->id)
            ->where('type', 'ram')
            ->first();

        $this->assertNotNull($ramMetric);

        $diskMetric = Metric::where('server_id', $this->server->id)
            ->where('type', 'disk')
            ->first();

        $this->assertNotNull($diskMetric);
        $this->assertEquals(50, $diskMetric->value);
    }

    public function test_collect_metrics_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('monitor:collect-metrics', ['server' => $this->server->id])
            ->assertExitCode(0);

        Queue::assertPushed(CollectMetricsJob::class, function ($job) {
            return $job->serverId === $this->server->id;
        });
    }

    public function test_collect_metrics_command_dispatches_for_all_servers(): void
    {
        Queue::fake();
        Server::factory()->count(3)->create();

        $this->artisan('monitor:collect-metrics')
            ->assertExitCode(0);

        Queue::assertPushed(CollectMetricsJob::class, 4);
    }

    public function test_check_services_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('monitor:check-services', ['server' => $this->server->id])
            ->assertExitCode(0);

        Queue::assertPushed(MonitorServiceJob::class, function ($job) {
            return $job->serverId === $this->server->id;
        });
    }

    public function test_check_services_command_dispatches_for_all_servers(): void
    {
        Queue::fake();
        Server::factory()->count(3)->create();

        $this->artisan('monitor:check-services')
            ->assertExitCode(0);

        Queue::assertPushed(MonitorServiceJob::class, 4);
    }

    public function test_mock_returns_specific_output_for_known_command(): void
    {
        $this->mockSshWithData(['known-command' => 'specific output']);

        $mockRunner = $this->app->make(SshCommandRunner::class);
        $output = $mockRunner->run($this->server, 'known-command');

        $this->assertEquals('specific output', $output);
    }

    public function test_whitelisted_command_runs_through_mocked_runner(): void
    {
        $whitelist = $this->app->make(CommandWhitelist::class);
        $this->assertTrue($whitelist->validate('free -m'));

        $this->mockSshWithData($this->getRamCommandMap());

        CollectMetricsJob::dispatchSync($this->server->id);

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'type' => 'ram',
        ]);
    }

    public function test_mock_runner_receives_correct_server_and_command(): void
    {
        $mockRunner = Mockery::mock(SshCommandRunner::class);
        $mockRunner->shouldReceive('run')
            ->with(
                Mockery::on(fn ($s) => $s instanceof \App\Models\Server && $s->id === $this->server->id),
                Mockery::on(fn ($c) => str_contains($c, 'uptime')),
            )
            ->andReturn('uptime output');
        $mockRunner->shouldReceive('run')
            ->with(Mockery::any(), Mockery::any())
            ->andReturn('fallback output');

        $this->app->instance(SshCommandRunner::class, $mockRunner);

        $output = $mockRunner->run($this->server, 'uptime');

        $this->assertEquals('uptime output', $output);
    }
}
