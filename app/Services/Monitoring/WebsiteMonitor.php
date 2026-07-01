<?php

namespace App\Services\Monitoring;

use App\Models\Website;
use Carbon\Carbon;

class WebsiteMonitor
{
    public function check(Website $website): array
    {
        $url = $website->url;
        $timeout = $website->timeout_seconds;
        $startedAt = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $responseMs = (int) round(($totalTime ?: microtime(true) - $startedAt) * 1000);
        $body = $response ? substr($response, $headerSize) : '';

        // SSL check
        $sslDays = $this->checkSsl($url);
        $sslStatus = $this->sslStatus($sslDays);

        // Determine if up
        $statusOk = $httpCode > 0 && $httpCode === $website->expected_status_code;
        $keywordOk = true;
        if ($website->expected_keyword && $body) {
            $keywordOk = stripos($body, $website->expected_keyword) !== false;
        }
        $isUp = $statusOk && $keywordOk;

        // Degraded: keyword missing or wrong status but still responding
        $status = $isUp ? 'up' : ($httpCode > 0 && $httpCode !== $website->expected_status_code ? 'degraded' : 'down');

        $errorMessage = $error ?: null;
        if (!$errorMessage && !$statusOk) {
            $errorMessage = "Expected HTTP {$website->expected_status_code}, got {$httpCode}";
        }
        if (!$errorMessage && !$keywordOk) {
            $errorMessage = "Expected keyword '{$website->expected_keyword}' not found";
        }

        return [
            'http_status_code' => $httpCode ?: 0,
            'response_time_ms' => $responseMs,
            'ssl_days_remaining' => $sslDays,
            'ssl_status' => $sslStatus,
            'is_up' => $isUp,
            'status' => $status,
            'error_message' => $errorMessage,
        ];
    }

    private function checkSsl(string $url): ?int
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return null;

        $stream = @stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$host}:443",
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT, $stream
        );

        if (!$client) return null;

        $cert = stream_context_get_params($client);
        if (empty($cert['options']['ssl']['peer_certificate'])) {
            fclose($client);
            return null;
        }

        $certData = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
        fclose($client);

        $validTo = $certData['validTo_time_t'] ?? null;
        if (!$validTo) return null;

        return (int) round(($validTo - time()) / 86400);
    }

    private function sslStatus(?int $daysRemaining): ?string
    {
        if ($daysRemaining === null) return null;
        if ($daysRemaining <= 0) return 'expired';
        if ($daysRemaining < 30) return 'expiring_soon';
        return 'valid';
    }
}
