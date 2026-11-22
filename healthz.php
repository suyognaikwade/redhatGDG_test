<?php
declare(strict_types=1);

/**
 * Kubernetes / OpenShift Liveness Probe Endpoint
 * Default Path: /healthz.php
 *
 * Returns:
 * - HTTP 200 OK: Container is healthy and running.
 * - HTTP 503 Service Unavailable: Simulated crash or fatal error (triggers pod restart).
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

$simulateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simulate_unhealthy';

if (file_exists($simulateFile)) {
    if (!headers_sent()) {
        http_response_code(503);
    }
    echo json_encode([
        'status' => 'DOWN',
        'probe' => 'liveness',
        'timestamp' => gmdate('c'),
        'hostname' => gethostname(),
        'message' => 'Simulated liveness failure active (/tmp/simulate_unhealthy exists)',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!headers_sent()) {
    http_response_code(200);
}
echo json_encode([
    'status' => 'UP',
    'probe' => 'liveness',
    'timestamp' => gmdate('c'),
    'hostname' => gethostname(),
    'uptime_seconds' => function_exists('sys_getloadavg') ? (int) @file_get_contents('/proc/uptime') : null,
    'php_version' => PHP_VERSION,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
