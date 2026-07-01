<?php

namespace Tests\Unit;

use App\Models\StatusMonitor;
use App\Services\Status\StatusMonitorProbe;
use Tests\TestCase;

class StatusMonitorProbeTest extends TestCase
{
    public function test_classifier_marks_200_as_operational(): void
    {
        $monitor = new StatusMonitor([
            'method' => 'GET',
        ]);

        $result = (new StatusMonitorProbe())->classify($monitor, 200, 'healthy');

        $this->assertSame('operational', $result['status']);
        $this->assertNull($result['error_message']);
    }

    public function test_classifier_marks_502_as_down(): void
    {
        $monitor = new StatusMonitor([
            'method' => 'HEAD',
        ]);

        $result = (new StatusMonitorProbe())->classify($monitor, 502);

        $this->assertSame('down', $result['status']);
        $this->assertSame('HTTP 502 Bad Gateway', $result['error_message']);
    }

    public function test_classifier_marks_timeout_as_down(): void
    {
        $monitor = new StatusMonitor([
            'method' => 'HEAD',
        ]);

        $result = (new StatusMonitorProbe())->classify($monitor, null, '', 'cURL error 28: Operation timed out');

        $this->assertSame('down', $result['status']);
        $this->assertSame('Connection timed out', $result['error_message']);
    }

    public function test_classifier_marks_body_mismatch_as_degraded(): void
    {
        $monitor = new StatusMonitor([
            'expected_keyword' => 'healthy',
            'method' => 'GET',
        ]);

        $result = (new StatusMonitorProbe())->classify($monitor, 200, 'database disconnected');

        $this->assertSame('degraded', $result['status']);
        $this->assertSame("Expected keyword 'healthy' not found", $result['error_message']);
    }

    public function test_classifier_marks_database_false_as_degraded_even_when_http_200(): void
    {
        $monitor = new StatusMonitor([
            'method' => 'GET',
        ]);

        $result = (new StatusMonitorProbe())->classify($monitor, 200, '{"status":"ok","api":true,"database":false}');

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('Database health reports not ok', $result['error_message']);
    }

    public function test_classifier_accepts_optional_database_and_api_fields_when_missing(): void
    {
        $monitor = new StatusMonitor([
            'method' => 'GET',
        ]);

        $result = (new StatusMonitorProbe())->classify($monitor, 200, '{"status":"ok"}');

        $this->assertSame('operational', $result['status']);
        $this->assertNull($result['error_message']);
    }
}
