<?php
declare(strict_types=1);

/**
 * Backend API for OpenShift PHP Diagnostics & Interactive Tests
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

function getOpenShiftInfo(): array {
    $namespaceFile = '/var/run/secrets/kubernetes.io/serviceaccount/namespace';
    $namespace = file_exists($namespaceFile) ? trim((string) file_get_contents($namespaceFile)) : (getenv('POD_NAMESPACE') ?: 'local / dev');

    $tmpDir = sys_get_temp_dir();
    $unhealthyFile = $tmpDir . DIRECTORY_SEPARATOR . 'simulate_unhealthy';
    $notReadyFile = $tmpDir . DIRECTORY_SEPARATOR . 'simulate_not_ready';

    $serverLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.0, 0.0, 0.0];

    // Safely collect environment variables
    $sensitivePatterns = ['/KEY/i', '/SECRET/i', '/PASSWORD/i', '/TOKEN/i', '/AUTH/i', '/CREDENTIAL/i', '/PRIVATE/i'];
    $envArr = is_array($_ENV) ? $_ENV : [];
    $getEnvArr = getenv() ?: [];
    $rawEnv = array_merge($envArr, $getEnvArr);
    ksort($rawEnv);

    $envVariables = [];
    foreach ($rawEnv as $key => $val) {
        if (!is_string($key) || !is_scalar($val)) {
            continue;
        }
        $isSensitive = false;
        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, (string) $key)) {
                $isSensitive = true;
                break;
            }
        }
        $valStr = (string) $val;
        $envVariables[$key] = $isSensitive
            ? (strlen($valStr) > 4 ? substr($valStr, 0, 2) . '••••' . substr($valStr, -2) : '••••••••')
            : $valStr;
    }

    return [
        'pod' => [
            'hostname' => gethostname(),
            'namespace' => $namespace,
            'node_name' => getenv('NODE_NAME') ?: (getenv('KUBERNETES_NODE_NAME') ?: 'N/A'),
            'pod_ip' => getenv('POD_IP') ?: ($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname() ?: 'localhost')),
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'kubernetes_service_host' => getenv('KUBERNETES_SERVICE_HOST') ?: 'N/A',
            'kubernetes_service_port' => getenv('KUBERNETES_SERVICE_PORT') ?: 'N/A',
            'openshift_build_name' => getenv('OPENSHIFT_BUILD_NAME') ?: 'Local / Direct Run',
            'openshift_build_reference' => getenv('OPENSHIFT_BUILD_REFERENCE') ?: 'main',
        ],
        'runtime' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI / Built-in',
            'sapi' => PHP_SAPI,
            'memory_current' => memory_get_usage(true),
            'memory_current_formatted' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_peak_formatted' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'system_load' => $serverLoad,
            'loaded_extensions_count' => count(get_loaded_extensions()),
            'opcache_enabled' => function_exists('opcache_get_status') && !empty(opcache_get_status()),
        ],
        'probes' => [
            'liveness' => [
                'endpoint' => '/healthz.php',
                'status' => file_exists($unhealthyFile) ? 'DOWN' : 'UP',
                'is_simulating_down' => file_exists($unhealthyFile),
            ],
            'readiness' => [
                'endpoint' => '/ready.php',
                'status' => file_exists($notReadyFile) ? 'NOT_READY' : 'READY',
                'is_simulating_down' => file_exists($notReadyFile),
            ],
        ],
        'env_variables' => $envVariables,
        'timestamp' => gmdate('c'),
    ];
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'info';

switch ($action) {
    case 'info':
        echo json_encode([
            'success' => true,
            'data' => getOpenShiftInfo(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        break;

    case 'toggle_probe':
        $probe = $_POST['probe'] ?? $_GET['probe'] ?? '';
        $tmpDir = sys_get_temp_dir();

        if ($probe === 'liveness') {
            $file = $tmpDir . DIRECTORY_SEPARATOR . 'simulate_unhealthy';
            if (file_exists($file)) {
                @unlink($file);
                $state = 'UP';
            } else {
                @file_put_contents($file, (string) time());
                $state = 'DOWN';
            }
            echo json_encode([
                'success' => true,
                'probe' => 'liveness',
                'new_status' => $state,
                'message' => "Liveness simulation set to {$state}. OpenShift will restart this pod if DOWN persists past failureThreshold.",
            ]);
        } elseif ($probe === 'readiness') {
            $file = $tmpDir . DIRECTORY_SEPARATOR . 'simulate_not_ready';
            if (file_exists($file)) {
                @unlink($file);
                $state = 'READY';
            } else {
                @file_put_contents($file, (string) time());
                $state = 'NOT_READY';
            }
            echo json_encode([
                'success' => true,
                'probe' => 'readiness',
                'new_status' => $state,
                'message' => "Readiness simulation set to {$state}. OpenShift will stop sending traffic to this pod without restarting it.",
            ]);
        } else {
            if (!headers_sent()) {
                http_response_code(400);
            }
            echo json_encode([
                'success' => false,
                'error' => 'Invalid probe name. Use "liveness" or "readiness".',
            ]);
        }
        break;

    case 'db_test':
        $driver = $_POST['driver'] ?? 'mysql';
        $host = $_POST['host'] ?? (getenv('DATABASE_SERVICE_NAME') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1'));
        $port = (int) ($_POST['port'] ?? ($driver === 'pgsql' ? 5432 : 3306));
        $database = $_POST['database'] ?? (getenv('MYSQL_DATABASE') ?: (getenv('POSTGRESQL_DATABASE') ?: 'testdb'));
        $username = $_POST['username'] ?? (getenv('MYSQL_USER') ?: (getenv('POSTGRESQL_USER') ?: 'root'));
        $password = $_POST['password'] ?? (getenv('MYSQL_PASSWORD') ?: (getenv('POSTGRESQL_PASSWORD') ?: ''));

        $startTime = microtime(true);
        try {
            if ($driver === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            }

            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            $versionStmt = $pdo->query('SELECT VERSION() as ver');
            $versionRow = $versionStmt ? $versionStmt->fetch(PDO::FETCH_ASSOC) : null;
            $serverVer = $versionRow['ver'] ?? 'Unknown';
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            echo json_encode([
                'success' => true,
                'message' => "Successfully connected to {$driver} at {$host}:{$port} ({$durationMs}ms)",
                'server_version' => $serverVer,
                'database' => $database,
            ]);
        } catch (\Throwable $e) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'target' => "{$driver}://{$host}:{$port}/{$database}",
            ]);
        }
        break;

    case 'cpu_stress':
        $seconds = min(max((int) ($_POST['seconds'] ?? $_GET['seconds'] ?? 3), 1), 10);
        $start = microtime(true);
        $iterations = 0;

        while ((microtime(true) - $start) < $seconds) {
            for ($i = 0; $i < 1000; $i++) {
                hash('sha256', (string) ($start + $i + $iterations));
            }
            $iterations += 1000;
        }
        $elapsed = round(microtime(true) - $start, 3);

        echo json_encode([
            'success' => true,
            'message' => "CPU stress executed for {$elapsed} seconds ({$iterations} SHA256 iterations)",
            'elapsed_seconds' => $elapsed,
            'iterations' => $iterations,
            'hostname' => gethostname(),
        ]);
        break;

    default:
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action: ' . htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8'),
        ]);
        break;
}
