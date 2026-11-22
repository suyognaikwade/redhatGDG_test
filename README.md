# Red Hat OpenShift PHP Test Application

[![OpenShift Compatible](https://img.shields.io/badge/OpenShift-v4.x%20Ready-EE0000?logo=redhat&logoColor=white)](https://www.redhat.com/en/technologies/cloud-computing/openshift)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A lightweight, interactive PHP diagnostic application tailored specifically for deploying, testing, and demonstrating **Red Hat OpenShift Container Platform (OCP)** features.

---

## 🚀 Key Features for OpenShift Testing

| Feature | OpenShift Concept Tested | Endpoint / File |
| :--- | :--- | :--- |
| **Pod Telemetry** | Pod identity, Namespace, Downward API, Service IP | `/index.php`, `/api.php?action=info` |
| **Liveness Probe** | Container health check & automatic self-healing pod restart | `/healthz.php` |
| **Readiness Probe** | Service endpoint routing & zero-downtime rolling updates | `/ready.php` |
| **Probe Simulator** | Interactive toggles to trigger HTTP 503 failures on-demand | Dashboard UI |
| **Autoscaler Stress Test** | Horizontal Pod Autoscaler (HPA) CPU threshold triggering | Dashboard UI (`/api.php?action=cpu_stress`) |
| **Database Connectivity** | Validates connections to MySQL / PostgreSQL services in cluster | Dashboard UI (`/api.php?action=db_test`) |
| **Masked Env Inspector** | ConfigMap & Secret verification without exposing passwords | Dashboard UI |
| **Air-Gapped Ready** | 100% offline self-contained CSS/JS (zero external CDN dependency) | Embedded |

---

## 📦 Deployment Methods on Red Hat OpenShift

### Method 1: OpenShift Web Console (Fastest)

1. Log in to your **Red Hat OpenShift Web Console**.
2. Switch to the **Developer Perspective** and select your Project/Namespace.
3. Click **+Add** ➔ **Import from Git**.
4. In **Git Repo URL**, enter:
   ```text
   https://github.com/suyognaikwade/redhatGDG_test.git
   ```
5. OpenShift will automatically detect the **PHP** Source-to-Image (S2I) builder.
6. Select **Deployment** as the resource type.
7. Check **Create a route to the application**.
8. Click **Create**.

---

### Method 2: OpenShift CLI (`oc new-app`)

Run the following commands using the OpenShift CLI:

```bash
# 1. Create a new project (if you don't have one)
oc new-project php-test-project

# 2. Deploy using the official Red Hat PHP S2I builder
oc new-app php:8.2~https://github.com/suyognaikwade/redhatGDG_test.git --name=redhatgdg-test

# 3. Expose the service through an OpenShift Route (TLS edge termination)
oc create route edge redhatgdg-test --service=redhatgdg-test --port=8080

# 4. View build and deployment logs
oc logs -f bc/redhatgdg-test
oc get pods -w
```

---

### Method 3: Using Pre-Configured OpenShift Manifests

A complete OpenShift-tailored `Deployment`, `Service`, `Route`, and `HPA` are included in the [`openshift/`](openshift/) directory:

```bash
# Apply deployment, service, and route
oc apply -f openshift/deployment.yaml

# Apply Horizontal Pod Autoscaler (optional)
oc apply -f openshift/hpa.yaml
```

---

## 🩺 Configuring Kubernetes Health Probes

Add production-grade health checks using the `oc set probe` CLI command:

### 1. Liveness Probe (Restarts failed pods)
```bash
oc set probe deployment/redhatgdg-test --liveness \
  --get-url=http://:8080/healthz.php \
  --initial-delay-seconds=5 \
  --period-seconds=10 \
  --failure-threshold=3
```

### 2. Readiness Probe (Prevents sending traffic to unready pods)
```bash
oc set probe deployment/redhatgdg-test --readiness \
  --get-url=http://:8080/ready.php \
  --initial-delay-seconds=3 \
  --period-seconds=5 \
  --failure-threshold=2
```

---

## 🧪 Testing OpenShift Features with This App

### 1. Test Self-Healing Pods (Liveness Probe Failure)
1. Open the application dashboard in your browser.
2. In the **Kubernetes Probes** card, click **Simulate Crash (503)**.
3. The `/healthz.php` endpoint will now return HTTP 503.
4. Run `oc get pods -w` in your terminal.
5. After `failureThreshold` (e.g. 30 seconds), OpenShift will automatically kill the unhealthy container and start a fresh pod!

### 2. Test Zero-Downtime Traffic Removal (Readiness Probe Failure)
1. In the dashboard, click **Simulate Not Ready (503)**.
2. The `/ready.php` endpoint will return HTTP 503.
3. OpenShift will immediately remove the pod from the Service endpoints list without terminating it (`oc describe endpoints redhatgdg-test`).

### 3. Test Horizontal Pod Autoscaling (HPA)
1. In the **Autoscaler CPU Stress Test** card, select **10 Seconds** and click **Run CPU Load Test**.
2. Run `oc get hpa -w` in your terminal.
3. Observe how OpenShift detects the CPU surge and dynamically spins up additional pod replicas!

### 4. Test Database Connectivity
1. Deploy a database container (e.g., `oc new-app mysql-ephemeral` or `oc new-app postgresql-ephemeral`).
2. In the dashboard, enter the database service host (e.g. `mysql`), credentials, and click **Test Connection**.

---

## 💻 Local Development & Testing

### Run with Built-in PHP Server
```bash
php -S 0.0.0.0:8080
```
Open [http://localhost:8080](http://localhost:8080) in your browser.

### Run with Docker or Podman
```bash
docker build -t redhatgdg-test .
docker run -p 8080:8080 redhatgdg-test
```

### Run Automated Tests
```bash
php tests/test_endpoints.php
```

---

## 📁 Repository Structure

```text
redhatGDG_test/
├── .s2i/
│   └── environment           # S2I builder configuration (memory limits, docroot)
├── openshift/
│   ├── deployment.yaml       # K8s Deployment, Service, and OpenShift Route
│   └── hpa.yaml              # Horizontal Pod Autoscaler manifest
├── tests/
│   └── test_endpoints.php    # Automated test suite (28 assertions)
├── index.php                 # Interactive OpenShift diagnostics dashboard
├── healthz.php               # Liveness probe endpoint (HTTP 200 / 503)
├── ready.php                 # Readiness probe endpoint (HTTP 200 / 503)
├── api.php                   # JSON API backend (telemetry, stress, DB test)
├── Dockerfile                # UBI/Apache non-root containerfile
├── composer.json             # PHP project descriptor
├── .gitignore                # Ignored cache, logs, and temp files
├── .dockerignore             # Docker build exclusion rules
└── README.md                 # Complete documentation & deployment guide
```

---

## 🛡️ License

This project is licensed under the MIT License.
