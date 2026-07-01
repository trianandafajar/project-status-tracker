<?php

namespace App\Services\Status;

use App\Models\StatusMonitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class StatusMonitorProbe
{
    public function check(StatusMonitor $monitor): array
    {
        $timeout = max(1, (int) $monitor->timeout_seconds);
        $method = strtoupper($monitor->method ?: 'HEAD');
        $startedAt = microtime(true);

        try {
            $request = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->withOptions([
                    'allow_redirects' => ['max' => 5],
                    'verify' => false,
                ]);

            $response = $method === 'GET'
                ? $request->get($monitor->url)
                : $request->head($monitor->url);

            $responseTimeMs = max(1, (int) round((microtime(true) - $startedAt) * 1000));

            return $this->buildResult(
                $monitor,
                $response->status(),
                $responseTimeMs,
                $method === 'GET' ? $response->body() : '',
                null
            );
        } catch (ConnectionException $exception) {
            $responseTimeMs = max(1, (int) round((microtime(true) - $startedAt) * 1000));

            return $this->buildResult($monitor, null, $responseTimeMs, '', $exception->getMessage());
        } catch (Throwable $exception) {
            $responseTimeMs = max(1, (int) round((microtime(true) - $startedAt) * 1000));

            return $this->buildResult($monitor, null, $responseTimeMs, '', $exception->getMessage());
        }
    }

    public function classify(
        StatusMonitor $monitor,
        ?int $httpStatusCode,
        string $body = '',
        ?string $transportError = null,
    ): array {
        $expectedStatus = (int) ($monitor->expected_status_code ?: 200);
        $expectedKeyword = trim((string) ($monitor->expected_keyword ?: ''));

        if ($transportError) {
            return [
                'status' => 'down',
                'error_message' => $this->normalizeTransportError($transportError),
                'is_up' => false,
            ];
        }

        if (! $httpStatusCode) {
            return [
                'status' => 'down',
                'error_message' => 'No HTTP response received',
                'is_up' => false,
            ];
        }

        if ($httpStatusCode >= 500) {
            return [
                'status' => 'down',
                'error_message' => 'HTTP '.$httpStatusCode.' '.$this->httpReason($httpStatusCode),
                'is_up' => false,
            ];
        }

        if ($httpStatusCode !== $expectedStatus) {
            return [
                'status' => 'degraded',
                'error_message' => "Expected HTTP {$expectedStatus}, got {$httpStatusCode}",
                'is_up' => false,
            ];
        }

        if ($expectedKeyword !== '' && ! Str::contains(Str::lower($body), Str::lower($expectedKeyword))) {
            return [
                'status' => 'degraded',
                'error_message' => "Expected keyword '{$expectedKeyword}' not found",
                'is_up' => false,
            ];
        }

        return [
            'status' => 'operational',
            'error_message' => null,
            'is_up' => true,
        ];
    }

    private function buildResult(
        StatusMonitor $monitor,
        ?int $httpStatusCode,
        ?int $responseTimeMs,
        string $body = '',
        ?string $transportError = null,
    ): array {
        $classification = $this->classify($monitor, $httpStatusCode, $body, $transportError);

        return [
            'status' => $classification['status'],
            'is_up' => $classification['is_up'],
            'http_status_code' => $httpStatusCode,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $classification['error_message'],
        ];
    }

    private function normalizeTransportError(string $message): string
    {
        $normalized = Str::lower($message);

        return match (true) {
            Str::contains($normalized, ['timed out', 'timeout']) => 'Connection timed out',
            Str::contains($normalized, ['could not resolve host', 'getaddrinfo', 'name or service not known']) => 'DNS lookup failed',
            Str::contains($normalized, ['ssl', 'tls', 'certificate']) => 'TLS handshake failed',
            Str::contains($normalized, ['connection refused']) => 'Connection refused',
            Str::contains($normalized, ['failed to connect', 'could not connect']) => 'Connection failed',
            default => 'Request failed: '.trim($message),
        };
    }

    private function httpReason(int $statusCode): string
    {
        return match ($statusCode) {
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'Server Error',
        };
    }
}
