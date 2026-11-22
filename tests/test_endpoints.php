<?php
declare(strict_types=1);

/**
 * Automated Verification & Endpoint Test Suite
 * For Red Hat OpenShift PHP Test Project
 */

$testCount = 0;
$failCount = 0;

function assertTrue(bool $condition, string $testName): void {
    global $testCount, $failCount;
    $testCount++;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

echo "=== Red Hat OpenShift PHP Test Suite ===\n\n";

$phpBin = defined('PHP_BINARY') && file_exists(PHP_BINARY) ? PHP_BINARY : 'php';

// 1. Syntax Check
echo "[1/4] Checking PHP Syntax for all project files...\n";
$phpFiles = ['index.php', 'api.php', 'healthz.php', 'ready.php'];
foreach ($phpFiles as $file) {
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file;
    assertTrue(file_exists($filePath), "File exists: {$file}");
    $output = [];
    $returnVar = 0;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $returnVar);
    assertTrue($returnVar === 0, "Syntax valid (lint passed): {$file}");
}

// 2. Healthz Endpoint Tests
echo "\n[2/4] Testing /healthz.php (Liveness Probe)...\n";
$tmpDir = sys_get_temp_dir();
$unhealthyFile = $tmpDir . DIRECTORY_SEPARATOR . 'simulate_unhealthy';
if (file_exists($unhealthyFile)) {
    unlink($unhealthyFile);
}

$healthzPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'healthz.php';
$out = [];
exec('"' . $phpBin . '" ' . escapeshellarg($healthzPath), $out);
$dataHealthy = json_decode(implode("\n", $out), true);

assertTrue(is_array($dataHealthy), 'healthz returns valid JSON');
assertTrue(($dataHealthy['status'] ?? '') === 'UP', 'healthz status is UP when healthy');
assertTrue(($dataHealthy['probe'] ?? '') === 'liveness', 'healthz identifies as liveness probe');

// Test unhealthy simulation
file_put_contents($unhealthyFile, 'test');
$outDown = [];
exec('"' . $phpBin . '" ' . escapeshellarg($healthzPath), $outDown);
$dataDown = json_decode(implode("\n", $outDown), true);

assertTrue(is_array($dataDown), 'healthz returns valid JSON when simulated down');
assertTrue(($dataDown['status'] ?? '') === 'DOWN', 'healthz status is DOWN when simulation file exists');
if (file_exists($unhealthyFile)) {
    unlink($unhealthyFile);
}

// 3. Ready Endpoint Tests
echo "\n[3/4] Testing /ready.php (Readiness Probe)...\n";
$notReadyFile = $tmpDir . DIRECTORY_SEPARATOR . 'simulate_not_ready';
if (file_exists($notReadyFile)) {
    unlink($notReadyFile);
}

$readyPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ready.php';
$outReady = [];
exec('"' . $phpBin . '" ' . escapeshellarg($readyPath), $outReady);
$dataReady = json_decode(implode("\n", $outReady), true);

assertTrue(is_array($dataReady), 'ready.php returns valid JSON');
assertTrue(($dataReady['status'] ?? '') === 'READY', 'ready.php status is READY when warm');
assertTrue(($dataReady['probe'] ?? '') === 'readiness', 'ready.php identifies as readiness probe');

// Test not-ready simulation
file_put_contents($notReadyFile, 'test');
$outNotReady = [];
exec('"' . $phpBin . '" ' . escapeshellarg($readyPath), $outNotReady);
$dataNotReady = json_decode(implode("\n", $outNotReady), true);

assertTrue(is_array($dataNotReady), 'ready.php returns valid JSON when simulated not ready');
assertTrue(($dataNotReady['status'] ?? '') === 'NOT_READY', 'ready.php status is NOT_READY when simulation file exists');
if (file_exists($notReadyFile)) {
    unlink($notReadyFile);
}

// 4. API & Masking Tests
echo "\n[4/4] Testing /api.php telemetry & credential masking...\n";
$apiPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api.php';
$runnerPath = $tmpDir . DIRECTORY_SEPARATOR . 'openshift_test_runner.php';

$runnerCode = <<<PHP
<?php
putenv("DB_PASSWORD=SuperSecret12345");
putenv("API_KEY=AKIA1234567890EXAMPLE");
putenv("APP_ENV=production");
\$_GET['action'] = 'info';
require {$healthzPath}; // dummy load check
require {$apiPath};
PHP;
// Let's create runner file cleanly
$runnerCode = "<?php\n"
    . "putenv('DB_PASSWORD=SuperSecret12345');\n"
    . "putenv('API_KEY=AKIA1234567890EXAMPLE');\n"
    . "putenv('APP_ENV=production');\n"
    . "\$_GET['action'] = 'info';\n"
    . "require " . var_export($apiPath, true) . ";\n";

file_put_contents($runnerPath, $runnerCode);

$outApi = [];
exec('"' . $phpBin . '" ' . escapeshellarg($runnerPath), $outApi);
$apiData = json_decode(implode("\n", $outApi), true);

assertTrue(is_array($apiData) && ($apiData['success'] ?? false) === true, 'api.php info action succeeds');
assertTrue(isset($apiData['data']['pod']['hostname']), 'api.php returns pod hostname');
assertTrue(isset($apiData['data']['runtime']['php_version']), 'api.php returns runtime PHP version');

$env = $apiData['data']['env_variables'] ?? [];
assertTrue(isset($env['DB_PASSWORD']), 'DB_PASSWORD present in env list');
assertTrue(strpos($env['DB_PASSWORD'] ?? '', '••••') !== false, 'DB_PASSWORD is properly masked with bullets');
assertTrue(($env['DB_PASSWORD'] ?? '') !== 'SuperSecret12345', 'DB_PASSWORD is never revealed in plain text');
assertTrue(isset($env['API_KEY']) && strpos($env['API_KEY'] ?? '', '••••') !== false, 'API_KEY is properly masked');
assertTrue(($env['APP_ENV'] ?? '') === 'production', 'Non-sensitive env variables remain legible');

// Test CPU Stress endpoint
$stressRunnerPath = $tmpDir . DIRECTORY_SEPARATOR . 'openshift_stress_runner.php';
$stressRunnerCode = "<?php\n"
    . "\$_POST['action'] = 'cpu_stress';\n"
    . "\$_POST['seconds'] = '1';\n"
    . "require " . var_export($apiPath, true) . ";\n";
file_put_contents($stressRunnerPath, $stressRunnerCode);

$outStress = [];
exec('"' . $phpBin . '" ' . escapeshellarg($stressRunnerPath), $outStress);
$stressData = json_decode(implode("\n", $outStress), true);

assertTrue(is_array($stressData) && ($stressData['success'] ?? false) === true, 'api.php cpu_stress action executes successfully');
assertTrue(($stressData['iterations'] ?? 0) > 0, 'cpu_stress completed SHA256 iterations');

// Clean up runners
@unlink($runnerPath);
@unlink($stressRunnerPath);

echo "\n========================================\n";
echo "Test Results: {$testCount} executed, {$failCount} failed.\n";
if ($failCount === 0) {
    echo "SUCCESS: All OpenShift test suite checks PASSED!\n";
    exit(0);
} else {
    echo "FAILURE: {$failCount} test(s) failed.\n";
    exit(1);
}
