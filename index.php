<?php
declare(strict_types=1);

/**
 * Red Hat OpenShift PHP Test Application
 * Author: Suyog Naikwade
 */

$podHostname = gethostname() ?: 'unknown-host';
$podIp = getenv('POD_IP') ?: ($_SERVER['SERVER_ADDR'] ?? gethostbyname($podHostname));
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$namespaceFile = '/var/run/secrets/kubernetes.io/serviceaccount/namespace';
$namespace = file_exists($namespaceFile) ? trim((string) file_get_contents($namespaceFile)) : (getenv('POD_NAMESPACE') ?: 'local-dev');

$k8sHost = getenv('KUBERNETES_SERVICE_HOST') ?: 'N/A';
$buildName = getenv('OPENSHIFT_BUILD_NAME') ?: 'Local / Direct';

$tmpDir = sys_get_temp_dir();
$isUnhealthy = file_exists($tmpDir . DIRECTORY_SEPARATOR . 'simulate_unhealthy');
$isNotReady = file_exists($tmpDir . DIRECTORY_SEPARATOR . 'simulate_not_ready');

$memoryUsedMb = round(memory_get_usage(true) / 1024 / 1024, 2);
$memoryPeakMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$memoryLimit = ini_get('memory_limit') ?: 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenShift PHP Test App - <?= htmlspecialchars($podHostname, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --rh-red: #EE0000;
            --rh-red-dark: #A30000;
            --rh-black: #151515;
            --rh-dark: #1F2937;
            --rh-gray: #374151;
            --rh-light-gray: #F3F4F6;
            --rh-border: #E5E7EB;
            --rh-card-bg: #FFFFFF;
            --rh-text-main: #111827;
            --rh-text-muted: #6B7280;
            --rh-success: #10B981;
            --rh-danger: #EF4444;
            --rh-warning: #F59E0B;
            --rh-blue: #2563EB;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --rh-black: #0B0F17;
                --rh-dark: #111827;
                --rh-gray: #1F2937;
                --rh-light-gray: #1E293B;
                --rh-border: #334155;
                --rh-card-bg: #1E293B;
                --rh-text-main: #F9FAFB;
                --rh-text-muted: #94A3B8;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--rh-black);
            color: var(--rh-text-main);
            line-height: 1.5;
            padding: 24px 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Top Brand Header */
        .header {
            background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
            border-top: 4px solid var(--rh-red);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .header-title-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .rh-logo {
            width: 48px;
            height: 48px;
            background-color: var(--rh-red);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -1px;
            box-shadow: 0 4px 12px rgba(238, 0, 0, 0.4);
        }

        .header-title {
            color: #FFFFFF;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header-subtitle {
            color: #94A3B8;
            font-size: 0.875rem;
        }

        .badge-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 600;
            background: #334155;
            color: #E2E8F0;
        }

        .badge.success {
            background: rgba(16, 185, 129, 0.15);
            color: #34D399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #F87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge.red {
            background: rgba(238, 0, 0, 0.15);
            color: #FF6666;
            border: 1px solid rgba(238, 0, 0, 0.3);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            box-shadow: 0 0 8px currentColor;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Metric Cards Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .card {
            background-color: var(--rh-card-bg);
            border: 1px solid var(--rh-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--rh-border);
            padding-bottom: 12px;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .info-label {
            color: var(--rh-text-muted);
        }

        .info-value {
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            word-break: break-all;
            text-align: right;
            max-width: 65%;
        }

        /* Buttons & Controls */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--rh-red);
            color: #FFFFFF;
        }
        .btn-primary:hover {
            background-color: var(--rh-red-dark);
        }

        .btn-danger {
            background-color: var(--rh-danger);
            color: #FFFFFF;
        }
        .btn-danger:hover {
            background-color: #DC2626;
        }

        .btn-success {
            background-color: var(--rh-success);
            color: #FFFFFF;
        }
        .btn-success:hover {
            background-color: #059669;
        }

        .btn-secondary {
            background-color: var(--rh-light-gray);
            color: var(--rh-text-main);
            border-color: var(--rh-border);
        }
        .btn-secondary:hover {
            background-color: var(--rh-border);
        }

        .action-row {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-size: 0.8125rem;
            color: var(--rh-text-muted);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--rh-border);
            border-radius: 6px;
            background-color: var(--rh-light-gray);
            color: var(--rh-text-main);
            font-size: 0.875rem;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--rh-red);
        }

        .alert-box {
            padding: 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-top: 12px;
            display: none;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34D399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #F87171;
        }

        /* Tables & Cheatsheet */
        .table-container {
            max-height: 380px;
            overflow-y: auto;
            border: 1px solid var(--rh-border);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--rh-border);
        }

        th {
            background-color: var(--rh-light-gray);
            color: var(--rh-text-muted);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8125rem;
        }

        .cli-code {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0F172A;
            color: #38BDF8;
            padding: 10px 14px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.8125rem;
            margin-top: 6px;
        }

        .copy-btn {
            background: none;
            border: none;
            color: #94A3B8;
            cursor: pointer;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .copy-btn:hover {
            color: #FFFFFF;
            background: #334155;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Top Header -->
    <header class="header">
        <div class="header-title-box">
            <div class="rh-logo">RH</div>
            <div>
                <h1 class="header-title">Red Hat OpenShift PHP Test Application</h1>
                <p class="header-subtitle">Containerized PHP Runtime &amp; Kubernetes Probe Validation</p>
            </div>
        </div>
        <div class="badge-group">
            <span class="badge red">
                <span class="pulse-dot"></span>
                Pod: <strong id="header-pod-name"><?= htmlspecialchars($podHostname, ENT_QUOTES, 'UTF-8') ?></strong>
            </span>
            <span class="badge">
                Namespace: <strong><?= htmlspecialchars($namespace, ENT_QUOTES, 'UTF-8') ?></strong>
            </span>
            <button class="btn btn-secondary" onclick="fetchMetrics()" title="Refresh live telemetry">
                &#x21bb; Refresh
            </button>
        </div>
    </header>

    <!-- Cards Grid -->
    <div class="grid">
        <!-- 1. Pod Identity & Networking -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">&#128640; Pod Identity &amp; Cluster</h2>
                <span class="badge success">Active</span>
            </div>
            <ul class="info-list">
                <li class="info-item">
                    <span class="info-label">Hostname / Pod</span>
                    <span class="info-value"><?= htmlspecialchars($podHostname, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">Namespace</span>
                    <span class="info-value"><?= htmlspecialchars($namespace, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">Pod IP</span>
                    <span class="info-value"><?= htmlspecialchars($podIp, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">Client IP</span>
                    <span class="info-value"><?= htmlspecialchars($clientIp, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">K8s API Service</span>
                    <span class="info-value"><?= htmlspecialchars($k8sHost, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">OpenShift Build</span>
                    <span class="info-value"><?= htmlspecialchars($buildName, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
            </ul>
        </div>

        <!-- 2. PHP Runtime Metrics -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">&#9881; PHP Runtime &amp; Memory</h2>
                <span class="badge">PHP <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <ul class="info-list">
                <li class="info-item">
                    <span class="info-label">PHP SAPI</span>
                    <span class="info-value"><?= htmlspecialchars(PHP_SAPI, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">Memory Used</span>
                    <span class="info-value" id="val-memory-current"><?= $memoryUsedMb ?> MB</span>
                </li>
                <li class="info-item">
                    <span class="info-label">Peak Memory</span>
                    <span class="info-value"><?= $memoryPeakMb ?> MB</span>
                </li>
                <li class="info-item">
                    <span class="info-label">Memory Limit</span>
                    <span class="info-value"><?= htmlspecialchars($memoryLimit, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="info-item">
                    <span class="info-label">Loaded Extensions</span>
                    <span class="info-value"><?= count(get_loaded_extensions()) ?> extensions</span>
                </li>
                <li class="info-item">
                    <span class="info-label">OPcache Status</span>
                    <span class="info-value"><?= function_exists('opcache_get_status') && !empty(opcache_get_status()) ? 'Enabled' : 'Disabled' ?></span>
                </li>
            </ul>
        </div>

        <!-- 3. Kubernetes Probes Simulation -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">&#128269; Kubernetes Probes</h2>
                <span class="badge" id="probe-summary-badge">Probes Ready</span>
            </div>
            <p style="font-size: 0.8125rem; color: var(--rh-text-muted); margin-bottom: 12px;">
                Simulate unhealthy or maintenance states to test OpenShift self-healing pod restarts and traffic routing.
            </p>
            <ul class="info-list">
                <li class="info-item">
                    <span class="info-label">Liveness (<a href="healthz.php" target="_blank" style="color:var(--rh-blue);">/healthz.php</a>)</span>
                    <span class="badge <?= $isUnhealthy ? 'danger' : 'success' ?>" id="live-liveness-badge">
                        <?= $isUnhealthy ? 'DOWN (503)' : 'UP (200)' ?>
                    </span>
                </li>
                <li class="info-item">
                    <span class="info-label">Readiness (<a href="ready.php" target="_blank" style="color:var(--rh-blue);">/ready.php</a>)</span>
                    <span class="badge <?= $isNotReady ? 'danger' : 'success' ?>" id="live-readiness-badge">
                        <?= $isNotReady ? 'NOT READY (503)' : 'READY (200)' ?>
                    </span>
                </li>
            </ul>
            <div class="action-row">
                <button class="btn <?= $isUnhealthy ? 'btn-success' : 'btn-danger' ?>" id="btn-toggle-liveness" onclick="toggleProbe('liveness')">
                    <?= $isUnhealthy ? 'Restore Liveness (200)' : 'Simulate Crash (503)' ?>
                </button>
                <button class="btn <?= $isNotReady ? 'btn-success' : 'btn-danger' ?>" id="btn-toggle-readiness" onclick="toggleProbe('readiness')">
                    <?= $isNotReady ? 'Restore Readiness (200)' : 'Simulate Not Ready (503)' ?>
                </button>
            </div>
            <div class="alert-box" id="probe-alert"></div>
        </div>
    </div>

    <!-- Interactive Tools Grid -->
    <div class="grid">
        <!-- CPU Stress (HPA testing) -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">&#128293; Autoscaler (HPA) CPU Stress Test</h2>
                <span class="badge">HPA Test</span>
            </div>
            <p style="font-size: 0.8125rem; color: var(--rh-text-muted); margin-bottom: 12px;">
                Spike CPU load with a controlled cryptographic SHA256 loop to trigger OpenShift Horizontal Pod Autoscaling.
            </p>
            <div class="form-group">
                <label for="stress-duration">Stress Duration (Seconds)</label>
                <select id="stress-duration" class="form-control">
                    <option value="2">2 Seconds (Quick verification)</option>
                    <option value="5" selected>5 Seconds (Moderate load)</option>
                    <option value="10">10 Seconds (High CPU spike)</option>
                </select>
            </div>
            <button class="btn btn-primary" id="btn-cpu-stress" onclick="runCpuStress()">
                Run CPU Load Test
            </button>
            <div class="alert-box" id="stress-alert"></div>
        </div>

        <!-- Database Connection Test -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">&#128452; Database Connectivity Test</h2>
                <span class="badge">PDO</span>
            </div>
            <p style="font-size: 0.8125rem; color: var(--rh-text-muted); margin-bottom: 12px;">
                Verify pod connection to OpenShift MySQL, MariaDB, or PostgreSQL service containers.
            </p>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div class="form-group">
                    <label>Driver</label>
                    <select id="db-driver" class="form-control" onchange="updateDefaultPort()">
                        <option value="mysql">MySQL / MariaDB</option>
                        <option value="pgsql">PostgreSQL</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Port</label>
                    <input type="number" id="db-port" class="form-control" value="3306">
                </div>
            </div>
            <div class="form-group">
                <label>Database Host / Service Name</label>
                <input type="text" id="db-host" class="form-control" placeholder="e.g. mysql or postgresql" value="<?= htmlspecialchars((string)(getenv('DATABASE_SERVICE_NAME') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1')), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div class="form-group">
                    <label>Database</label>
                    <input type="text" id="db-name" class="form-control" value="<?= htmlspecialchars((string)(getenv('MYSQL_DATABASE') ?: (getenv('POSTGRESQL_DATABASE') ?: 'testdb')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="db-user" class="form-control" value="<?= htmlspecialchars((string)(getenv('MYSQL_USER') ?: (getenv('POSTGRESQL_USER') ?: 'root')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="db-pass" class="form-control" placeholder="Leave empty if not required" value="">
            </div>
            <button class="btn btn-primary" id="btn-db-test" onclick="testDatabaseConnection()">
                Test Connection
            </button>
            <div class="alert-box" id="db-alert"></div>
        </div>
    </div>

    <!-- Environment Variables & OpenShift CLI Cheatsheet -->
    <div class="grid">
        <!-- Environment Variables -->
        <div class="card" style="grid-column: span 1;">
            <div class="card-header">
                <h2 class="card-title">&#128203; Injected Environment Variables</h2>
                <input type="text" id="env-filter" placeholder="Search variables..." class="form-control" style="width: 180px; padding: 4px 8px; font-size: 0.75rem;" oninput="filterEnvTable()">
            </div>
            <div class="table-container">
                <table id="env-table">
                    <thead>
                        <tr>
                            <th>Variable Key</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody id="env-table-body">
                        <?php
                        $sensitiveKeys = ['KEY', 'SECRET', 'PASSWORD', 'TOKEN', 'AUTH', 'CREDENTIAL'];
                        $env = array_merge($_ENV, getenv());
                        ksort($env);
                        foreach ($env as $key => $val):
                            if (!is_string($key) || !is_scalar($val)) continue;
                            $valStr = (string) $val;
                            $isMasked = false;
                            foreach ($sensitiveKeys as $sk) {
                                if (stripos($key, $sk) !== false) {
                                    $isMasked = true;
                                    break;
                                }
                            }
                            $displayVal = $isMasked
                                ? (strlen($valStr) > 4 ? substr($valStr, 0, 2) . '••••' . substr($valStr, -2) : '••••••••')
                                : $valStr;
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><span style="color:var(--rh-text-muted);"><?= htmlspecialchars($displayVal, ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- OpenShift Quick CLI Cheatsheet -->
        <div class="card" style="grid-column: span 1;">
            <div class="card-header">
                <h2 class="card-title">&#9000; OpenShift CLI (oc) Cheatsheet</h2>
                <span class="badge">oc commands</span>
            </div>
            <p style="font-size: 0.8125rem; color: var(--rh-text-muted); margin-bottom: 12px;">
                Copy and run these commands from your local terminal to manage this deployment:
            </p>
            <div style="display:flex; flex-direction:column; gap: 8px;">
                <div>
                    <span style="font-size:0.75rem; color:var(--rh-text-muted);">1. View Pods &amp; Status</span>
                    <div class="cli-code">
                        <span>oc get pods -w</span>
                        <button class="copy-btn" onclick="copyText('oc get pods -w')">Copy</button>
                    </div>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:var(--rh-text-muted);">2. View Real-time Logs</span>
                    <div class="cli-code">
                        <span>oc logs -f -l app=redhatgdg-test</span>
                        <button class="copy-btn" onclick="copyText('oc logs -f -l app=redhatgdg-test')">Copy</button>
                    </div>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:var(--rh-text-muted);">3. Scale Replicas</span>
                    <div class="cli-code">
                        <span>oc scale deployment/redhatgdg-test --replicas=3</span>
                        <button class="copy-btn" onclick="copyText('oc scale deployment/redhatgdg-test --replicas=3')">Copy</button>
                    </div>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:var(--rh-text-muted);">4. Execute Shell in Pod</span>
                    <div class="cli-code">
                        <span>oc rsh deployment/redhatgdg-test</span>
                        <button class="copy-btn" onclick="copyText('oc rsh deployment/redhatgdg-test')">Copy</button>
                    </div>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:var(--rh-text-muted);">5. Test Liveness Probe via Curl</span>
                    <div class="cli-code">
                        <span>curl -i http://localhost:8080/healthz.php</span>
                        <button class="copy-btn" onclick="copyText('curl -i http://localhost:8080/healthz.php')">Copy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateDefaultPort() {
        const driver = document.getElementById('db-driver').value;
        document.getElementById('db-port').value = driver === 'pgsql' ? '5432' : '3306';
    }

    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard: ' + text);
        }).catch(() => {
            prompt('Copy this command:', text);
        });
    }

    function showAlert(elementId, message, isSuccess) {
        const el = document.getElementById(elementId);
        el.className = 'alert-box ' + (isSuccess ? 'alert-success' : 'alert-error');
        el.textContent = message;
        el.style.display = 'block';
        setTimeout(() => {
            el.style.display = 'none';
        }, 8000);
    }

    function toggleProbe(probeName) {
        const formData = new FormData();
        formData.append('action', 'toggle_probe');
        formData.append('probe', probeName);

        fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('probe-alert', data.message, true);
                    fetchMetrics();
                } else {
                    showAlert('probe-alert', data.error || 'Failed to toggle probe', false);
                }
            })
            .catch(err => {
                showAlert('probe-alert', 'Network error: ' + err.message, false);
            });
    }

    function runCpuStress() {
        const duration = document.getElementById('stress-duration').value;
        const btn = document.getElementById('btn-cpu-stress');
        btn.disabled = true;
        btn.textContent = 'Generating CPU load (' + duration + 's)...';

        const formData = new FormData();
        formData.append('action', 'cpu_stress');
        formData.append('seconds', duration);

        fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Run CPU Load Test';
                if (data.success) {
                    showAlert('stress-alert', data.message, true);
                } else {
                    showAlert('stress-alert', data.error || 'Stress test failed', false);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.textContent = 'Run CPU Load Test';
                showAlert('stress-alert', 'Error: ' + err.message, false);
            });
    }

    function testDatabaseConnection() {
        const btn = document.getElementById('btn-db-test');
        btn.disabled = true;
        btn.textContent = 'Testing connection...';

        const formData = new FormData();
        formData.append('action', 'db_test');
        formData.append('driver', document.getElementById('db-driver').value);
        formData.append('host', document.getElementById('db-host').value);
        formData.append('port', document.getElementById('db-port').value);
        formData.append('database', document.getElementById('db-name').value);
        formData.append('username', document.getElementById('db-user').value);
        formData.append('password', document.getElementById('db-pass').value);

        fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Test Connection';
                if (data.success) {
                    showAlert('db-alert', data.message + ' (Version: ' + data.server_version + ')', true);
                } else {
                    showAlert('db-alert', 'Connection failed: ' + data.error, false);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.textContent = 'Test Connection';
                showAlert('db-alert', 'Request failed: ' + err.message, false);
            });
    }

    function fetchMetrics() {
        fetch('api.php?action=info')
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;
                const d = res.data;

                // Update pod hostname
                document.getElementById('header-pod-name').textContent = d.pod.hostname;

                // Update memory
                document.getElementById('val-memory-current').textContent = d.runtime.memory_current_formatted;

                // Update Probes Badges & Buttons
                const liveBadge = document.getElementById('live-liveness-badge');
                const liveBtn = document.getElementById('btn-toggle-liveness');
                if (d.probes.liveness.is_simulating_down) {
                    liveBadge.className = 'badge danger';
                    liveBadge.textContent = 'DOWN (503)';
                    liveBtn.className = 'btn btn-success';
                    liveBtn.textContent = 'Restore Liveness (200)';
                } else {
                    liveBadge.className = 'badge success';
                    liveBadge.textContent = 'UP (200)';
                    liveBtn.className = 'btn btn-danger';
                    liveBtn.textContent = 'Simulate Crash (503)';
                }

                const readyBadge = document.getElementById('live-readiness-badge');
                const readyBtn = document.getElementById('btn-toggle-readiness');
                if (d.probes.readiness.is_simulating_down) {
                    readyBadge.className = 'badge danger';
                    readyBadge.textContent = 'NOT READY (503)';
                    readyBtn.className = 'btn btn-success';
                    readyBtn.textContent = 'Restore Readiness (200)';
                } else {
                    readyBadge.className = 'badge success';
                    readyBadge.textContent = 'READY (200)';
                    readyBtn.className = 'btn btn-danger';
                    readyBtn.textContent = 'Simulate Not Ready (503)';
                }
            })
            .catch(err => {
                console.warn('Metrics polling error:', err);
            });
    }

    function filterEnvTable() {
        const query = document.getElementById('env-filter').value.toLowerCase();
        const rows = document.querySelectorAll('#env-table-body tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    // Auto-refresh metrics every 10 seconds
    setInterval(fetchMetrics, 10000);
</script>
</body>
</html>
