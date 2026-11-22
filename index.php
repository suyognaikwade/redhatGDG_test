<?php
declare(strict_types=1);

/**
 * Red Hat OpenShift PHP Test Application & GDG Pune Event Hub
 * Venue: Human Cloud, Pune
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
    <title>OpenShift PHP App | GDG @ HumanCloud</title>
    <style>
        :root {
            --rh-red: #EE0000;
            --rh-red-dark: #A30000;
            --rh-black: #0B0F17;
            --rh-dark: #111827;
            --rh-sidebar: #0F172A;
            --rh-card-bg: #1E293B;
            --rh-border: #334155;
            --rh-text-main: #F8FAFC;
            --rh-text-muted: #94A3B8;
            --rh-success: #10B981;
            --rh-danger: #EF4444;
            --rh-warning: #F59E0B;
            --rh-blue: #38BDF8;
            --gdg-blue: #4285F4;
            --gdg-red: #EA4335;
            --gdg-yellow: #FBBC04;
            --gdg-green: #34A853;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--rh-black);
            color: var(--rh-text-main);
            min-height: 100vh;
            display: flex;
        }

        /* Responsive App Layout */
        .app-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background-color: var(--rh-sidebar);
            border-right: 1px solid var(--rh-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid var(--rh-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo-combo {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rh-badge-logo {
            width: 36px;
            height: 36px;
            background-color: var(--rh-red);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFF;
            font-weight: 800;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(238, 0, 0, 0.4);
        }

        .gdg-badge-logo {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--gdg-blue), var(--gdg-green));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFF;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: -0.5px;
        }

        .brand-text h1 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #FFF;
            line-height: 1.2;
        }

        .brand-text p {
            font-size: 0.75rem;
            color: var(--rh-text-muted);
        }

        /* Nav List & Tabs */
        .sidebar-nav {
            padding: 16px 12px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-category {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            padding: 10px 12px 4px;
        }

        .nav-tab-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: none;
            border: 1px solid transparent;
            border-radius: 8px;
            color: #94A3B8;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            width: 100%;
            transition: all 0.2s ease;
        }

        .nav-tab-btn:hover {
            color: #F8FAFC;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .nav-tab-btn.active {
            color: #FFFFFF;
            background: linear-gradient(90deg, rgba(238, 0, 0, 0.15) 0%, rgba(238, 0, 0, 0.05) 100%);
            border-color: rgba(238, 0, 0, 0.4);
            border-left: 3px solid var(--rh-red);
        }

        .nav-tab-btn.event-btn.active {
            background: linear-gradient(90deg, rgba(66, 133, 244, 0.15) 0%, rgba(52, 168, 83, 0.05) 100%);
            border-color: rgba(66, 133, 244, 0.4);
            border-left: 3px solid var(--gdg-blue);
        }

        .nav-icon {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 0.6875rem;
            padding: 2px 7px;
            border-radius: 9999px;
            font-weight: 700;
        }

        .nav-badge.live {
            background: rgba(238, 0, 0, 0.2);
            color: #FF6666;
            border: 1px solid rgba(238, 0, 0, 0.4);
        }

        .nav-badge.today {
            background: rgba(66, 133, 244, 0.2);
            color: #60A5FA;
            border: 1px solid rgba(66, 133, 244, 0.4);
            animation: pulse 2s infinite;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--rh-border);
            font-size: 0.75rem;
            color: var(--rh-text-muted);
            background: rgba(0, 0, 0, 0.2);
        }

        /* Main Content Container */
        .main-content {
            flex-grow: 1;
            padding: 28px 32px;
            overflow-y: auto;
            max-width: 1300px;
        }

        /* Tab Content Switching */
        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
            animation: fadeIn 0.25s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Top Header Bar */
        .top-bar {
            background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
            border-top: 4px solid var(--rh-red);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }

        .top-bar.event-bar {
            border-top: 4px solid var(--gdg-blue);
        }

        .header-title-box h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #FFF;
        }

        .header-title-box p {
            color: var(--rh-text-muted);
            font-size: 0.875rem;
        }

        .badge-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
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

        .badge.gdg {
            background: rgba(66, 133, 244, 0.15);
            color: #93C5FD;
            border: 1px solid rgba(66, 133, 244, 0.3);
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

        /* Grid & Cards */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .card {
            background-color: var(--rh-card-bg);
            border: 1px solid var(--rh-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
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

        /* Buttons */
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
            background-color: #334155;
            color: #F8FAFC;
            border-color: #475569;
        }
        .btn-secondary:hover {
            background-color: #475569;
        }

        .btn-gdg {
            background: linear-gradient(135deg, var(--gdg-blue) 0%, #1D4ED8 100%);
            color: #FFF;
        }
        .btn-gdg:hover {
            opacity: 0.95;
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
            background-color: #0F172A;
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
            background-color: #0F172A;
            color: var(--rh-text-muted);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        code {
            background: rgba(0, 0, 0, 0.4);
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

        /* GDG Event Specific Styles */
        .event-banner {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid rgba(66, 133, 244, 0.3);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .event-banner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gdg-blue), var(--gdg-red), var(--gdg-yellow), var(--gdg-green));
        }

        .timeline {
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
            padding-left: 24px;
            border-left: 2px solid var(--rh-border);
            margin-top: 16px;
        }

        .timeline-item {
            position: relative;
        }

        .timeline-dot {
            position: absolute;
            left: -31px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--gdg-blue);
            border: 2px solid var(--rh-sidebar);
            box-shadow: 0 0 6px var(--gdg-blue);
        }

        .timeline-dot.highlight {
            background-color: var(--rh-red);
            box-shadow: 0 0 8px var(--rh-red);
        }

        .timeline-time {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--gdg-blue);
            margin-bottom: 2px;
        }

        .timeline-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #FFFFFF;
        }

        .timeline-desc {
            font-size: 0.8125rem;
            color: var(--rh-text-muted);
            margin-top: 4px;
        }

        .speaker-card {
            display: flex;
            gap: 14px;
            align-items: center;
            background: #0F172A;
            border: 1px solid var(--rh-border);
            border-radius: 10px;
            padding: 14px;
            margin-top: 10px;
        }

        .speaker-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--rh-red), #F97316);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #FFF;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .speaker-avatar.gdg {
            background: linear-gradient(135deg, var(--gdg-blue), var(--gdg-green));
        }

        .speaker-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #FFFFFF;
        }

        .speaker-info p {
            font-size: 0.75rem;
            color: var(--rh-text-muted);
        }

        .partner-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .partner-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8125rem;
            background: #0F172A;
            border: 1px solid var(--rh-border);
            color: #E2E8F0;
        }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            .app-layout {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid var(--rh-border);
            }
            .sidebar-nav {
                flex-direction: row;
                flex-wrap: wrap;
            }
            .nav-tab-btn {
                width: auto;
            }
            .main-content {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <!-- Left Navigation Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo-combo">
                <div class="rh-badge-logo" title="Red Hat OpenShift">RH</div>
                <div class="gdg-badge-logo" title="GDG Pune">GDG</div>
            </div>
            <div class="brand-text">
                <h1>Red Hat &times; GDG</h1>
                <p>OpenShift &bull; HumanCloud</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-category">Main Navigation</div>
            
            <button class="nav-tab-btn active" id="btn-tab-openshift" onclick="switchTab('openshift')">
                <span class="nav-icon">&#9881;</span>
                <span>OpenShift Dashboard</span>
                <span class="nav-badge live">Live</span>
            </button>

            <button class="nav-tab-btn event-btn" id="btn-tab-gdg" onclick="switchTab('gdg')">
                <span class="nav-icon">&#127915;</span>
                <span>GDG Event Details</span>
                <span class="nav-badge today">Today</span>
            </button>

            <div class="nav-category">Quick Jump</div>
            <button class="nav-tab-btn" onclick="switchTab('openshift'); scrollToElement('section-probes');">
                <span class="nav-icon">&#128269;</span>
                <span>Health Probes</span>
            </button>

            <button class="nav-tab-btn" onclick="switchTab('openshift'); scrollToElement('section-stress');">
                <span class="nav-icon">&#128293;</span>
                <span>HPA Load Test</span>
            </button>

            <button class="nav-tab-btn" onclick="switchTab('openshift'); scrollToElement('section-db');">
                <span class="nav-icon">&#128452;</span>
                <span>Database Test</span>
            </button>

            <button class="nav-tab-btn" onclick="switchTab('openshift'); scrollToElement('section-env');">
                <span class="nav-icon">&#128203;</span>
                <span>Environment Vars</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                <span style="font-weight:600; color:#FFF;">Pod Status</span>
                <span style="color:var(--rh-success); display:flex; align-items:center; gap:4px;">
                    <span class="pulse-dot" style="background-color:var(--rh-success);"></span> Running
                </span>
            </div>
            <div style="font-size:0.7rem; color:var(--rh-text-muted); word-break:break-all;">
                Host: <?= htmlspecialchars($podHostname, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- ============================================== -->
        <!-- TAB 1: OPENSHIFT DASHBOARD                    -->
        <!-- ============================================== -->
        <div id="tab-openshift" class="tab-pane active">
            <!-- Top Header Bar -->
            <header class="top-bar">
                <div class="header-title-box">
                    <h2>Red Hat OpenShift PHP Test Application</h2>
                    <p>Containerized PHP Runtime, S2I Validation &amp; Kubernetes Health Telemetry</p>
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
                <!-- 1. Pod Identity & Cluster -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">&#128640; Pod Identity &amp; Cluster</h3>
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
                        <h3 class="card-title">&#9881; PHP Runtime &amp; Memory</h3>
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
                <div class="card" id="section-probes">
                    <div class="card-header">
                        <h3 class="card-title">&#128269; Kubernetes Probes</h3>
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
                <div class="card" id="section-stress">
                    <div class="card-header">
                        <h3 class="card-title">&#128293; Autoscaler (HPA) CPU Stress Test</h3>
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
                <div class="card" id="section-db">
                    <div class="card-header">
                        <h3 class="card-title">&#128452; Database Connectivity Test</h3>
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
                <div class="card" id="section-env">
                    <div class="card-header">
                        <h3 class="card-title">&#128203; Injected Environment Variables</h3>
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
                                $env = array_merge(is_array($_ENV) ? $_ENV : [], getenv() ?: []);
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">&#9000; OpenShift CLI (oc) Cheatsheet</h3>
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
                                <span>oc logs -f -l app=redhatgdgtest</span>
                                <button class="copy-btn" onclick="copyText('oc logs -f -l app=redhatgdgtest')">Copy</button>
                            </div>
                        </div>
                        <div>
                            <span style="font-size:0.75rem; color:var(--rh-text-muted);">3. Scale Replicas</span>
                            <div class="cli-code">
                                <span>oc scale deployment/redhatgdgtest --replicas=3</span>
                                <button class="copy-btn" onclick="copyText('oc scale deployment/redhatgdgtest --replicas=3')">Copy</button>
                            </div>
                        </div>
                        <div>
                            <span style="font-size:0.75rem; color:var(--rh-text-muted);">4. Execute Shell in Pod</span>
                            <div class="cli-code">
                                <span>oc rsh deployment/redhatgdgtest</span>
                                <button class="copy-btn" onclick="copyText('oc rsh deployment/redhatgdgtest')">Copy</button>
                            </div>
                        </div>
                        <div>
                            <span style="font-size:0.75rem; color:var(--rh-text-muted);">5. Enable SSL / HTTPS Route</span>
                            <div class="cli-code">
                                <span>oc patch route redhatgdgtest -p '{"spec":{"tls":{"termination":"edge"}}}'</span>
                                <button class="copy-btn" onclick="copyText('oc patch route redhatgdgtest -p \'{\&quot;spec\&quot;:{\&quot;tls\&quot;:{\&quot;termination\&quot;:\&quot;edge\&quot;}}}\'')">Copy</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 2: GDG EVENT DETAILS @ HUMANCLOUD          -->
        <!-- ============================================== -->
        <div id="tab-gdg" class="tab-pane">
            <!-- Top Event Header Bar -->
            <header class="top-bar event-bar">
                <div class="header-title-box">
                    <h2>Google Developer Groups (GDG) Pune Event</h2>
                    <p>Build with AI (Gemini) &bull; Pre DevFest Pune '26 Workshop Series 6.0</p>
                </div>
                <div class="badge-group">
                    <span class="badge gdg">
                        <span class="pulse-dot" style="background-color:var(--gdg-blue);"></span>
                        Happening Today &bull; Nov 22, 2026
                    </span>
                    <span class="badge success">
                        Venue: Human Cloud, Pune
                    </span>
                </div>
            </header>

            <!-- Event Highlight Hero Card -->
            <div class="event-banner">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
                    <div>
                        <span style="font-size:0.8125rem; font-weight:700; color:var(--gdg-yellow); text-transform:uppercase; letter-spacing:0.05em;">
                            Community Workshop &bull; Series 6.0
                        </span>
                        <h2 style="font-size:1.6rem; font-weight:800; color:#FFF; margin-top:4px;">
                            Build with AI &amp; OpenShift Cloud-Native Workshop
                        </h2>
                        <p style="color:var(--rh-text-muted); font-size:0.95rem; margin-top:6px; max-width:800px;">
                            A community-driven, deep-dive technical workshop hosted by <strong>GDG Pune</strong> in collaboration with <strong>Human Cloud</strong> and <strong>Unnati Development (Red Hat Authorized Training Centre)</strong>.
                        </p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <span class="badge" style="background:#1E293B; border:1px solid var(--gdg-blue); color:#93C5FD; padding:8px 16px;">
                            &#128338; 09:00 AM &ndash; 02:00 PM IST
                        </span>
                    </div>
                </div>

                <div class="partner-chips">
                    <span class="partner-chip">&#127760; <strong>Host:</strong> GDG Pune</span>
                    <span class="partner-chip">&#127970; <strong>Venue Partner:</strong> Human Cloud</span>
                    <span class="partner-chip">&#129505; <strong>Tech Partner:</strong> Red Hat Authorized Training (Unnati)</span>
                </div>
            </div>

            <!-- Event Info Grid -->
            <div class="grid">
                <!-- Location & Venue Details -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">&#128205; Venue &amp; Location Details</h3>
                        <span class="badge gdg">Human Cloud</span>
                    </div>
                    <ul class="info-list">
                        <li class="info-item">
                            <span class="info-label">Venue</span>
                            <span class="info-value">Human Cloud Headquarters</span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-value" style="font-size:0.8rem;">Baner &ndash; Pashan Link Road, Pune, Maharashtra</span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">City</span>
                            <span class="info-value">Pune, India (411045)</span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Format</span>
                            <span class="info-value">In-Person Hands-on Lab</span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Wi-Fi &amp; Access</span>
                            <span class="info-value">High-speed Guest Wi-Fi</span>
                        </li>
                    </ul>

                    <div style="margin-top:16px; background:#0F172A; border-radius:8px; padding:12px; border:1px solid var(--rh-border);">
                        <div style="font-size:0.8125rem; font-weight:600; color:#FFF; margin-bottom:4px;">
                            &#128161; Live OpenShift S2I Demo in Session
                        </div>
                        <p style="font-size:0.75rem; color:var(--rh-text-muted);">
                            This exact repository (<code style="color:var(--rh-blue);">redhatGDG_test</code>) is built to demonstrate OpenShift's <strong>Source-to-Image (S2I)</strong> mechanism during today's lab!
                        </p>
                    </div>
                </div>

                <!-- Featured Speakers & Facilitators -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">&#128100; Featured Sessions &amp; Speakers</h3>
                        <span class="badge red">Red Hat &bull; GDG</span>
                    </div>

                    <!-- Speaker 1 -->
                    <div class="speaker-card">
                        <div class="speaker-avatar">AB</div>
                        <div class="speaker-info">
                            <h4>Ashutosh Bhakare</h4>
                            <p>Red Hat Authorized Instructor &bull; Cloud Architect</p>
                            <span style="font-size:0.75rem; color:var(--rh-blue); font-weight:500;">
                                Topic: "Source-to-Image (S2I) &amp; Accelerating Delivery on OpenShift"
                            </span>
                        </div>
                    </div>

                    <!-- Speaker 2 -->
                    <div class="speaker-card">
                        <div class="speaker-avatar gdg">GDG</div>
                        <div class="speaker-info">
                            <h4>GDG Pune Core Team</h4>
                            <p>Google Developer Groups Lead Mentors</p>
                            <span style="font-size:0.75rem; color:#34D399; font-weight:500;">
                                Topic: "Build with AI: Prototyping with Gemini &amp; Vertex AI"
                            </span>
                        </div>
                    </div>

                    <!-- Host -->
                    <div class="speaker-card">
                        <div class="speaker-avatar" style="background:linear-gradient(135deg, #6366F1, #8B5CF6);">HC</div>
                        <div class="speaker-info">
                            <h4>Human Cloud Engineering</h4>
                            <p>Host &amp; Infrastructure Enabler</p>
                            <span style="font-size:0.75rem; color:#A78BFA; font-weight:500;">
                                Community Keynote &bull; Cloud Architecture Best Practices
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Agenda / Schedule Timeline -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">&#128337; Today's Event Schedule &amp; Agenda</h3>
                    <span class="badge gdg">Nov 22, 2026</span>
                </div>

                <div class="timeline">
                    <!-- 9:00 AM -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">09:00 AM &ndash; 09:30 AM</div>
                        <div class="timeline-title">&#128187; Check-in, Registration &amp; Morning Networking</div>
                        <div class="timeline-desc">Arrival at Human Cloud, attendee badge pickup, Wi-Fi onboarding, and welcome coffee.</div>
                    </div>

                    <!-- 9:30 AM -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">09:30 AM &ndash; 10:00 AM</div>
                        <div class="timeline-title">&#128227; Welcome Note &amp; Keynote</div>
                        <div class="timeline-desc">GDG Pune organizers and Human Cloud leadership welcome developers; overview of DevFest Pune '26 roadmap.</div>
                    </div>

                    <!-- 10:00 AM -->
                    <div class="timeline-item">
                        <div class="timeline-dot highlight"></div>
                        <div class="timeline-time" style="color:var(--rh-red);">10:00 AM &ndash; 11:30 AM</div>
                        <div class="timeline-title" style="color:#FFF;">&#9729; Technical Spotlight: Source-to-Image (S2I) on Red Hat OpenShift</div>
                        <div class="timeline-desc">
                            Deep dive by Red Hat Authorized Instructor Ashutosh Bhakare. Covers Git-to-Cluster automation, non-root security contexts, build configurations, and Kubernetes health probes (Liveness / Readiness).
                        </div>
                    </div>

                    <!-- 11:30 AM -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">11:30 AM &ndash; 01:00 PM</div>
                        <div class="timeline-title">&#129302; Hands-on Workshop: Build with Gemini &amp; Vertex AI</div>
                        <div class="timeline-desc">
                            Building intelligent applications with Google's Gemini models, multimodal prompts, function calling, and deploying AI workflows into cloud-native environments.
                        </div>
                    </div>

                    <!-- 1:00 PM -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">01:00 PM &ndash; 02:00 PM</div>
                        <div class="timeline-title">&#129346; Q&amp;A, Swag Distribution &amp; Networking Lunch</div>
                        <div class="timeline-desc">Interactive open floor discussions, networking with speakers and peers, lunch hosted at Human Cloud.</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Tab switching logic
    function switchTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.nav-tab-btn').forEach(el => el.classList.remove('active'));

        if (tabName === 'openshift') {
            document.getElementById('tab-openshift').classList.add('active');
            document.getElementById('btn-tab-openshift').classList.add('active');
        } else if (tabName === 'gdg') {
            document.getElementById('tab-gdg').classList.add('active');
            document.getElementById('btn-tab-gdg').classList.add('active');
        }
    }

    function scrollToElement(id) {
        setTimeout(() => {
            const el = document.getElementById(id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }

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

                document.getElementById('header-pod-name').textContent = d.pod.hostname;
                document.getElementById('val-memory-current').textContent = d.runtime.memory_current_formatted;

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

    setInterval(fetchMetrics, 10000);
</script>
</body>
</html>
