<?php
declare(strict_types=1);

/**
 * Kubernetes / OpenShift Readiness Probe Endpoint
 * Default Path: /ready.php
 *
 * Returns:
 * - HTTP 200 OK: Container is ready to receive traffic from Service/Route.
 * - HTTP 503 Service Unavailable: Pod is warm/busy or simulating maintenance (traffic removed from endpoints).
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

$simulateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simulate_not_ready';

if (file_exists($simulateFile)) {
    if (!headers_sent()) {
        http_response_code(503);
    }
    echo json_encode([
        'status' => 'NOT_READY',
        'probe' => 'readiness',
        'timestamp' => gmdate('c'),
        'hostname' => gethostname(),
        'message' => 'Simulated readiness failure active (/tmp/simulate_not_ready exists)',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!headers_sent()) {
    http_response_code(200);
}
echo json_encode([
    'status' => 'READY',
    'probe' => 'readiness',
    'timestamp' => gmdate('c'),
    'hostname' => gethostname(),
    'memory_usage_bytes' => memory_get_usage(true),
    'php_version' => PHP_VERSION,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
