# PHP Prometheus Observability Demo

A sample PHP application demonstrating Prometheus metrics collection with log and tracing capabilities

## 📋 Overview

This project showcases a PHP 8.2 web application. This project is experimental so it may not always work and is not recommended to be used in production.

## Demo Video

Check out the video, where I have also explained this in a video that covers everything how to use this project and how it works

<div align="center">
  <a href="https://www.youtube.com/watch?v=SEN5wfb2gDY"><img src="https://img.youtube.com/vi/SEN5wfb2gDY/0.jpg" alt=" Getting complete Observability for PHP applications with Grafana Stack | Quick Demo "></a>
</div>

## Link to Blogpost

Check out these blog posts that contains a very detailed explanation of this project as how PHP can generate telemetry data.

**English:** https://devm.io/php/bringing-modern-observability-solutions-to-your-php-applications

**Deutsch:** https://entwickler.de/php/php-observability-grafana-tracing


## 🏗️ Project Structure

```
PHP-Prometheus/
├── src/
│   ├── index.php              # Main web interface with login dashboard
│   ├── api.php                # REST API endpoints (users, health, stats, etc.)
│   ├── metrics.php            # Prometheus metrics endpoint
│   ├── Logger.php              # Structured JSON logging class
│   ├── MetricsCollector.php   # Prometheus metrics generation class
│   ├── info.php               # PHP information page
│   ├── simple.php             # Simple test page
│   └── test.php               # PHP info debug page
├── docker-compose.yaml        # Docker setup with PHP app, Prometheus, Loki, and Alloy
├── prometheus.yml             # Prometheus scraping configuration
├── config/
│   ├── alloy.yaml             # Grafana Alloy configuration (log collection)
│   └── loki.yaml              # Loki configuration
├── logs/                      # Application log files directory
└── README.md                  # This file
```

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Ports 8080, 9090, 3100, 3000, and 12345 available

### Start Services

```bash
docker-compose up -d
```

### Access Services

- **PHP Application**: http://localhost:8080
- **Grafana Dashboard**: http://localhost:3000 (admin/admin)
- **Prometheus**: http://localhost:9090
- **Loki**: http://localhost:3100
- **Alloy UI**: http://localhost:12345
- **Metrics Endpoint**: http://localhost:8080/metrics.php

### Demo Credentials

Use these credentials to login:
- `admin` / `password123`
- `demo` / `demo123`
- `user` / `user123`

## 🔗 Useful Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f php-app

# Restart services
docker-compose restart

# Execute commands in container
docker-compose exec php-app bash

# Check Prometheus targets
curl http://localhost:9090/targets
```

---

**Perfect for learning Prometheus metrics collection with PHP applications!**
