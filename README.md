# PHP Prometheus Observability Demo

A complete PHP application demonstrating Prometheus metrics collection with custom metric generation and structured logging capabilities.

## 📋 Overview

This project showcases a PHP 8.2 web application that generates custom Prometheus metrics and structured JSON logs. The application includes a web interface with authentication, REST API endpoints, and comprehensive observability through Prometheus monitoring and Loki log aggregation.

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

## 🎯 Key Features

### 1. **Custom Prometheus Metrics**
- HTTP request counts by method, endpoint, and status code
- Request duration histograms
- Authentication attempt tracking
- Database query metrics
- Memory usage monitoring
- Active sessions tracking

### 2. **Structured JSON Logging**
- Correlation ID generation for request tracking
- Log levels: INFO, WARNING, ERROR, DEBUG
- Automatic request context inclusion
- API request/response logging
- Authentication attempt logging

### 3. **Web Interface**
- User authentication system with session management
- Dashboard showing system information
- Direct links to API endpoints
- Correlation ID display for request tracking

### 4. **REST API Endpoints**
- `GET /api.php?action=health` - Health check
- `GET /api.php?action=users` - List users
- `GET /api.php?action=stats` - Application statistics (authenticated)
- `GET /api.php?action=slow` - Simulate slow endpoint
- `POST /api.php?action=login` - API login
- `POST /api.php?action=create_user` - Create user (authenticated)
- `POST /api.php?action=error` - Trigger error for testing

### 5. **Observability Integration**
- Prometheus metrics scraping every 15 seconds
- Metrics exposed in standard Prometheus format
- All requests logged with correlation IDs
- Request duration tracking

### 6. **Log Aggregation with Loki**
- Structured JSON logs collected by Grafana Alloy
- Logs shipped to Loki for aggregation
- Logs searchable by correlation ID, level, service
- Automatic log parsing and labeling

## 📊 How Metrics Are Generated

Metrics are generated through the `MetricsCollector` class, which maintains in-memory metric state and exposes it in Prometheus format.

### Metrics Generation Flow

1. **Request Processing**: When a PHP page or API endpoint is accessed:
   ```php
   MetricsCollector::incrementHttpRequests($method, $endpoint, $statusCode);
   MetricsCollector::recordHttpRequestDuration($method, $endpoint, $duration);
   ```

2. **Metric Storage**: Metrics are stored in static class variables:
   - `http_requests_total` - Counter for HTTP requests
   - `http_request_duration_seconds` - Array of durations per endpoint
   - `auth_attempts_total` - Counter for authentication attempts
   - `database_queries_total` - Counter for database operations
   - `active_sessions` - Gauge for active sessions
   - `memory_usage_bytes` - Gauge for memory usage

3. **Prometheus Format**: When `/metrics.php` is accessed:
   - Metrics are formatted according to Prometheus text format
   - Includes HELP and TYPE declarations
   - Labels are included for dimensioning (method, endpoint, status_code, etc.)

### Available Metrics

- `http_requests_total{method, endpoint, status_code}` - Total HTTP requests
- `http_request_duration_seconds_count{method, endpoint}` - Request count
- `http_request_duration_seconds_sum{method, endpoint}` - Total duration
- `auth_attempts_total{type, result}` - Authentication attempts
- `active_sessions` - Number of active sessions
- `php_memory_usage_bytes` - Current PHP memory usage
- `database_queries_total{operation, table}` - Database queries
- `php_page_views_total` - Legacy page view counter
- `php_memory_peak_bytes` - Peak memory usage
- `php_last_access_timestamp` - Last access time

## 📝 How Logs Are Generated

Logs are generated through the `Logger` class, which creates structured JSON log entries with correlation IDs. The logs are then collected by Grafana Alloy and shipped to Loki for aggregation and querying.

### Log Generation Flow

1. **Logger Initialization**: On each request:
   ```php
   $logger = new Logger();
   // Generates unique correlation_id: req_XXXXXXXXX
   ```

2. **Log Entry Creation**: When logging methods are called:
   ```php
   $logger->info('Application started', ['context' => 'data']);
   $logger->logApiRequest($method, $endpoint, $statusCode, $duration);
   $logger->logAuthAttempt($username, $success);
   ```

3. **Log Entry Structure**: Each log entry is JSON formatted:
   ```json
   {
     "timestamp": "2024-01-01T12:00:00+00:00",
     "level": "INFO",
     "message": "API Request",
     "correlation_id": "req_1234567890",
     "service": "php-app",
     "context": {
       "method": "GET",
       "endpoint": "/api.php?action=users",
       "status_code": 200,
       "duration_ms": 45.23
     },
     "request": {
       "method": "GET",
       "uri": "/api.php?action=users",
       "user_agent": "Mozilla/5.0...",
       "ip": "172.17.0.1"
     }
   }
   ```

4. **Log File**: Logs are written to `/var/log/app/application.log` (appends to file)

5. **Log Collection (Alloy)**: Grafana Alloy monitors the log file and:
   - Parses JSON log entries
   - Extracts fields (timestamp, level, message, correlation_id, etc.)
   - Creates labels for filtering
   - Ships logs to Loki via HTTP API

6. **Log Storage (Loki)**: Loki receives and stores logs:
   - Indexes logs by labels (level, correlation_id, service)
   - Provides query interface at http://localhost:3100
   - Enables log searching and filtering

### Log Levels

- **INFO**: General application events, successful operations
- **WARNING**: Authentication failures, non-critical issues
- **ERROR**: Errors, exceptions, intentional error triggers
- **DEBUG**: Detailed debugging information

### Correlation IDs

Every request gets a unique correlation ID (`req_XXXXXXXXX`) that:
- Links logs from the same request
- Can be used to trace requests across systems
- Displayed in the web interface
- Included in API responses

## 🔗 Correlation in Observability

### What is Correlation?

**Correlation** is the practice of linking related observability data (logs, metrics, traces) together to understand the complete picture of a request or event flow through a system.

In simple terms, correlation allows you to:
- **Trace a single request** from start to finish
- **Link logs and metrics** that belong to the same operation
- **Understand relationships** between different system components
- **Debug issues faster** by following a request's journey

**Example**: When a user reports "I got an error", correlation helps you:
1. Find the specific error log
2. See all logs from that user's request
3. Check metrics for that timeframe
4. Understand what caused the error

### Types of Correlation

#### 1. **Request-Level Correlation**
- **What**: Links all observability data for a single HTTP request
- **Scope**: One request from start to finish
- **Use Case**: Debugging a specific user request that failed
- **Implementation**: Correlation ID generated per request

```
Request → correlation_id: req_12345
  ├─ Log: "Request started" (correlation_id: req_12345)
  ├─ Log: "Database query" (correlation_id: req_12345)
  ├─ Metric: http_requests_total (endpoint: /api.php?action=users)
  └─ Log: "Request completed" (correlation_id: req_12345)
```

#### 2. **Session-Level Correlation**
- **What**: Links all requests from a user session
- **Scope**: Multiple requests from the same user session
- **Use Case**: Understanding a user's complete journey
- **Implementation**: Session ID or user ID correlation

```
Session: user_abc
  ├─ Request 1 (correlation_id: req_001)
  ├─ Request 2 (correlation_id: req_002)
  └─ Request 3 (correlation_id: req_003)
```

#### 3. **Trace-Level Correlation** (Not implemented in this demo)
- **What**: Links spans across distributed systems
- **Scope**: Multiple services/components
- **Use Case**: Distributed tracing across microservices
- **Implementation**: Trace ID and Span IDs

```
Trace ID: trace_abc
  ├─ Service A: Span 1
  ├─ Service B: Span 2 (child of Span 1)
  └─ Service C: Span 3 (child of Span 1)
```

### Differences Between Correlation Types

| Type | Scope | Granularity | Use Case | This Demo |
|------|-------|-------------|----------|-----------|
| **Request-Level** | Single request | High (per request) | Debug specific failures | ✅ Implemented |
| **Session-Level** | User session | Medium (multiple requests) | User journey analysis | ⚠️ Partial (session exists but not correlated) |
| **Trace-Level** | Distributed system | High (per operation) | Microservices debugging | ❌ Not implemented |

### How Correlation Works in General

#### Standard Correlation Flow

```
1. Request Arrives
   ↓
2. Generate/Extract Correlation ID
   ↓
3. Propagate Correlation ID
   ├─ → Logs (include in every log entry)
   ├─ → Metrics (optional: as label/tag)
   ├─ → Traces (optional: as trace ID)
   └─ → HTTP Response (return to client)
   ↓
4. Store in Observability Backend
   ├─ Loki (indexed by correlation_id label)
   ├─ Prometheus (indexed by endpoint/method)
   └─ Grafana (can query and correlate)
   ↓
5. Query/Correlate Later
   - Search logs by correlation_id
   - Find metrics for same timeframe
   - Build correlation dashboard
```

#### Correlation ID Propagation

```
┌─────────────────────────────────────────────────────────┐
│              Request Flow with Correlation              │
└─────────────────────────────────────────────────────────┘

Client Request
    │
    ├─ correlation_id generated: req_6789012345.123
    │
    ▼
┌─────────────────┐
│  PHP Application │
└─────────────────┘
    │
    ├─→ Logger.php
    │   └─ All logs include: correlation_id="req_6789012345.123"
    │
    ├─→ index.php / api.php
    │   └─ Correlation ID displayed/returned
    │
    └─→ MetricsCollector.php
        └─ Metrics aggregated (no per-request correlation)
    │
    ▼
┌─────────────────┐
│  Log Storage     │
│  application.log│
└─────────────────┘
    │
    ├─ JSON Log Entry:
    │   {
    │     "correlation_id": "req_6789012345.123",
    │     "level": "INFO",
    │     "message": "API Request",
    │     ...
    │   }
    │
    ▼
┌─────────────────┐
│  Grafana Alloy  │
└─────────────────┘
    │
    ├─ Extracts correlation_id as label
    │
    ▼
┌─────────────────┐
│  Loki            │
└─────────────────┘
    │
    ├─ Indexed by correlation_id label
    ├─ Queryable: {correlation_id="req_6789012345.123"}
    │
    ▼
┌─────────────────┐
│  Grafana         │
└─────────────────┘
    │
    └─ Query logs by correlation_id
       Query metrics by time/correlation
       Build correlation dashboard
```

### How Correlation Works in This Demo Project

#### Current Implementation

**1. Correlation ID Generation**
- **Location**: `Logger.php` line 30-33
- **Method**: `generateCorrelationId()`
- **Format**: `req_` + `uniqid()` = `req_6789012345.12345`
- **Timing**: Generated once per request when Logger is instantiated

**2. Correlation ID Storage**
- **In Logs**: Every log entry includes `correlation_id` field (line 84 in Logger.php)
- **In API Responses**: Correlation ID returned in `meta.correlation_id` (line 42 in api.php)
- **In Web Interface**: Displayed in header and dashboard (lines 189, 250 in index.php)

**3. Correlation ID Propagation**
```
Request Flow:
index.php / api.php
    │
    ├─ new Logger() → generates correlation_id
    │
    ├─ $logger->info() → includes correlation_id in log
    ├─ $logger->logApiRequest() → includes correlation_id in log
    │
    └─ API response → includes correlation_id in meta field
```

**4. Correlation in Observability Stack**

**Logs (Loki)**:
- Alloy extracts `correlation_id` as a label (alloy.yaml line 26, 35)
- Logs queryable by correlation ID: `{correlation_id="req_12345"}`
- Example query in Grafana: `{service="php-app", correlation_id="req_6789012345.123"}`

**Metrics (Prometheus)**:
- Metrics are aggregated (not per-request)
- Can correlate by time window: Find metrics during request timeframe
- Future enhancement: Add correlation_id as optional metric label

**Visualization (Grafana)**:
- Query logs by correlation ID
- Cross-reference with metrics in same time window
- Build dashboards showing correlated data

### System Architecture for Correlation

```
┌─────────────────────────────────────────────────────────────────┐
│                    Correlation Architecture                      │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐
│   Client     │
│  (Browser)   │
└──────┬───────┘
       │ HTTP Request
       │ (No correlation ID yet)
       ▼
┌─────────────────────────────────────────────────────────────┐
│              PHP Application Container                        │
│              (php:8.2-apache)                                 │
│                                                               │
│  ┌────────────────────────────────────────────────────┐     │
│  │ Request Handler (index.php / api.php)               │     │
│  │  1. new Logger() → correlation_id generated        │     │
│  │  2. correlation_id = req_6789012345.123            │     │
│  └────────────────┬───────────────────────────────────┘     │
│                   │                                          │
│                   ├──────────────────────────────┐            │
│                   │                              │            │
│         ┌──────────▼──────────┐      ┌───────────▼──────────┐│
│         │   Logger.php        │      │ MetricsCollector.php ││
│         │                     │      │                      ││
│         │ log() includes:     │      │ incrementHttpRequests││
│         │ - correlation_id    │      │ (aggregated metrics) ││
│         │ - timestamp         │      │                      ││
│         │ - level             │      │                      ││
│         │ - message           │      │                      ││
│         └──────────┬──────────┘      └──────────────────────┘│
│                    │                                         │
│                    ▼                                         │
│         ┌──────────────────────┐                            │
│         │  application.log     │                            │
│         │  (JSON with           │                            │
│         │   correlation_id)     │                            │
│         └──────────┬────────────┘                            │
└────────────────────┼────────────────────────────────────────┘
                     │
                     │ Volume Mount: ./logs:/var/log/app
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Host Filesystem                                 │
│              ./logs/application.log                          │
│                                                              │
│  {"correlation_id":"req_6789012345.123",                    │
│   "level":"INFO",                                            │
│   "message":"API Request",                                   │
│   "service":"php-app", ...}                                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ Read-only mount
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Grafana Alloy Container                         │
│              (grafana/alloy:latest)                          │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │ loki.source.file "php_logs"                        │     │
│  │  - Reads: /var/log/app/application.log             │     │
│  └─────────────────┬──────────────────────────────────┘     │
│                    │                                         │
│                    ▼                                         │
│  ┌────────────────────────────────────────────────────┐     │
│  │ loki.process "php_logs"                             │     │
│  │  - Parse JSON                                        │     │
│  │  - Extract: correlation_id                          │     │
│  │  - Create label: correlation_id="req_6789012345.123"│     │
│  │  - Extract: level, service, timestamp              │     │
│  └─────────────────┬──────────────────────────────────┘     │
│                    │                                         │
│                    ▼                                         │
│  ┌────────────────────────────────────────────────────┐     │
│  │ loki.write "default"                                │     │
│  │  - Ship to: http://loki:3100/loki/api/v1/push      │     │
│  └─────────────────┬──────────────────────────────────┘     │
└────────────────────┼────────────────────────────────────────┘
                     │
                     │ HTTP POST with labels
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Loki Container                                  │
│              (grafana/loki:latest)                          │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Log Storage                                        │     │
│  │  Indexed by labels:                                 │     │
│  │  - service="php-app"                                │     │
│  │  - level="INFO"                                     │     │
│  │  - correlation_id="req_6789012345.123"             │     │
│  └─────────────────┬──────────────────────────────────┘     │
│                    │                                         │
│                    │ Queryable via LogQL                     │
│                    ▼                                         │
│  ┌────────────────────────────────────────────────────┐     │
│  │  LogQL Query Examples:                              │     │
│  │  - {correlation_id="req_6789012345.123"}           │     │
│  │  - {service="php-app", correlation_id=~"req_.*"}   │     │
│  │  - {level="ERROR"}                                  │     │
│  └────────────────────────────────────────────────────┘     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ HTTP API
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Grafana Container                               │
│              (grafana/grafana:latest)                         │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Explore → Loki Datasource                          │     │
│  │  Query: {correlation_id="req_6789012345.123"}      │     │
│  │                                                     │     │
│  │  Results:                                          │     │
│  │  - All logs with that correlation_id               │     │
│  │  - Timestamps, levels, messages                    │     │
│  │  - Complete request flow                           │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Dashboard                                          │     │
│  │  - Logs panel (filtered by correlation_id)         │     │
│  │  - Metrics panel (same time window)                │     │
│  │  - Correlation view                                │     │
│  └────────────────────────────────────────────────────┘     │
└──────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              Prometheus Container                            │
│              (prom/prometheus:latest)                        │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Metrics Storage                                    │     │
│  │  - http_requests_total{endpoint="/api.php",...}    │     │
│  │  - http_request_duration_seconds{...}              │     │
│  │  - Aggregated metrics (not per-request)            │     │
│  │                                                     │     │
│  │  Correlation:                                      │     │
│  │  - Query metrics in time window of request         │     │
│  │  - Link via endpoint/method labels                 │     │
│  └────────────────────────────────────────────────────┘     │
└──────────────────────────────────────────────────────────────┘
```

### Correlation Data Flow in This Project

```
Step-by-Step Correlation Flow:

1. REQUEST ARRIVES
   ┌────────────────────┐
   │ GET /api.php?      │
   │ action=users       │
   └─────────┬──────────┘
             │
             ▼
2. LOGGER INITIALIZED
   ┌────────────────────┐
   │ new Logger()       │
   │ ↓                  │
   │ correlation_id =   │
   │ "req_6789012345"   │
   └─────────┬──────────┘
             │
             ├─────────────────────┐
             │                     │
             ▼                     ▼
3. LOGGING             4. METRICS
   ┌─────────────┐      ┌─────────────┐
   │ info(...)   │      │ increment   │
   │ ↓           │      │ HttpRequests│
   │ correlation_│      │ (aggregated)│
   │ id included │      │             │
   └─────┬───────┘      └─────┬───────┘
         │                    │
         ▼                    ▼
5. LOG FILE          6. METRICS ENDPOINT
   ┌────────────────┐  ┌────────────────┐
   │ application.log│  │ /metrics.php   │
   │ {"correlation_ │  │ (Prometheus    │
   │  id":"req_..."}│  │  format)       │
   └─────┬──────────┘  └─────┬──────────┘
         │                    │
         ▼                    ▼
7. ALLOY COLLECTS    8. PROMETHEUS SCRAPES
   ┌────────────────┐  ┌────────────────┐
   │ Extract        │  │ Scrapes        │
   │ correlation_id │  │ every 15s      │
   │ as label       │  │                │
   └─────┬──────────┘  └─────┬──────────┘
         │                    │
         └────────┬───────────┘
                  │
                  ▼
9. LOKI STORAGE
   ┌──────────────────────────┐
   │ Indexed by:              │
   │ - correlation_id (label) │
   │ - service (label)        │
   │ - level (label)           │
   └─────┬────────────────────┘
         │
         ▼
10. GRAFANA QUERY
    ┌──────────────────────────┐
    │ Explore → Loki           │
    │ Query:                   │
    │ {correlation_id="req_"}   │
    │                          │
    │ Result: All logs for     │
    │ that request             │
    └──────────────────────────┘
```

### Example Scenario: Debugging a Failed Request

Let's trace a complete example of how correlation helps debug an issue:

#### Scenario: User reports "Error when fetching user list"

**Step 1: User Makes Request**
```
User clicks "GET /api/users" button
↓
Browser sends: GET http://localhost:8080/api.php?action=users
↓
PHP Application receives request
```

**Step 2: Correlation ID Generated**
```
Logger.php instantiated
↓
correlation_id = "req_6789012345.123456"
↓
This ID will be used throughout the request
```

**Step 3: Request Processing**
```
api.php processes request:
├─ Logger::info('Users list requested')
│  └─ Log: {"correlation_id":"req_6789012345.123","message":"Users list requested",...}
│
├─ MetricsCollector::incrementDatabaseQueries('SELECT', 'users')
│  └─ Metric: database_queries_total{operation="SELECT",table="users"} += 1
│
├─ Simulate database query (50ms delay)
│
└─ Return response with correlation_id
   └─ Response: {"status":200,"data":[...],"meta":{"correlation_id":"req_6789012345.123",...}}
```

**Step 4: Logs Written**
```
application.log now contains:
Line 1: {"correlation_id":"req_6789012345.123","level":"INFO","message":"Users list requested",...}
Line 2: {"correlation_id":"req_6789012345.123","level":"INFO","message":"API Request","endpoint":"/api.php?action=users",...}
```

**Step 5: Alloy Collects and Ships**
```
Alloy reads application.log
├─ Parses JSON: correlation_id="req_6789012345.123"
├─ Creates label: correlation_id="req_6789012345.123"
└─ Ships to Loki with labels:
   {
     "labels": {
       "service": "php-app",
       "correlation_id": "req_6789012345.123",
       "level": "INFO"
     },
     "log": "{...full log entry...}"
   }
```

**Step 6: Querying in Grafana (Debugging)**

**Scenario A: User reports error**
1. User provides correlation ID from error message: `req_6789012345.123`
2. In Grafana Explore → Loki:
   ```
   Query: {correlation_id="req_6789012345.123"}
   ```
3. Results show ALL logs for that request:
   ```
   [2024-11-02 12:00:00] INFO: API request started
   [2024-11-02 12:00:00] INFO: Users list requested
   [2024-11-02 12:00:01] INFO: API Request completed (duration: 50ms)
   ```

**Scenario B: Find all error requests**
1. Query in Grafana:
   ```
   Query: {level="ERROR"}
   ```
2. See all error logs with their correlation IDs
3. Click on a correlation ID to see full request context

**Scenario C: Correlate with Metrics**
1. Find error log with correlation_id at timestamp: `2024-11-02 12:00:00`
2. Query Prometheus metrics for same time window:
   ```
   http_requests_total{endpoint="/api.php?action=users"}
   ```
3. See if error rate increased during that time

#### Complete Correlation Query Example

**In Grafana Explore (Loki Datasource)**:
```logql
{correlation_id="req_6789012345.123"}
```

**Results**:
```
┌────────────────────────────────────────────────────────────────┐
│ Timestamp           │ Level │ Message              │ Correlation │
├─────────────────────┼───────┼──────────────────────┼────────────┤
│ 2024-11-02 12:00:00 │ INFO  │ API request started  │ req_678... │
│ 2024-11-02 12:00:00 │ INFO  │ Users list requested│ req_678... │
│ 2024-11-02 12:00:01 │ INFO  │ API Request completed     │ req_678... │
└────────────────────────────────────────────────────────────────┘
```

**Advanced Queries**:
```logql
# All requests with correlation IDs
{correlation_id=~"req_.*"}

# Errors with correlation IDs
{level="ERROR", correlation_id=~"req_.*"}

# Specific endpoint with correlation
{service="php-app", endpoint="/api.php?action=users"}

# All logs from requests in last 5 minutes
{service="php-app"} | correlation_id != ""
```

### Correlation Implementation Details

#### In Logger.php
```php
// Generation (line 30-33)
private function generateCorrelationId() {
    return uniqid('req_', true);
    // Returns: "req_6789012345.123456"
}

// Inclusion in logs (line 84)
'correlation_id' => $this->correlationId,
```

#### In Alloy Configuration (config/alloy.yaml)
```yaml
// Extraction (line 26)
correlation_id = "correlation_id"

// Label creation (line 35)
correlation_id = "",

// Result: Label created in Loki for filtering
```

#### In API Responses (api.php line 42)
```php
'meta' => [
    'correlation_id' => $logger->getCorrelationId(),
    // Client can use this to query logs
]
```

### Benefits of Correlation in This Project

1. **Faster Debugging**: Find all logs for a specific request instantly
2. **Request Tracing**: Follow a request from start to finish
3. **Error Context**: See complete context when errors occur
4. **User Support**: User provides correlation ID → find their exact request
5. **Pattern Detection**: Find patterns in correlated request flows
6. **Time-Based Correlation**: Link logs and metrics by time window

### Limitations in Current Implementation

1. **Metrics Don't Include Correlation IDs**: Metrics are aggregated, not per-request
   - **Workaround**: Correlate by time window and endpoint
   - **Future**: Could add correlation_id as optional metric label for sample requests

2. **No Distributed Correlation**: Only works within this single PHP application
   - **Future**: Could add HTTP header propagation for distributed systems

3. **Session Correlation Not Implemented**: Each request gets new correlation ID
   - **Future**: Could link correlation IDs by session ID

### Correlation Best Practices

1. **Always Include in Logs**: Every log entry should have correlation_id
2. **Return to Client**: Include in API responses for client debugging
3. **Make Searchable**: Index correlation_id as label in Loki
4. **Display in UI**: Show correlation ID in web interface for easy copying
5. **Use in Dashboards**: Build correlation-based dashboards

## 📊 Observability Methodologies: RED, USE, and Four Golden Signals

This project supports industry-standard observability methodologies that combine metrics and logs for comprehensive monitoring. Understanding these patterns will help you build effective dashboards and monitoring strategies.

---

### RED Metrics: Rate, Errors, Duration

**RED** (Rate, Errors, Duration) is a methodology focused on **service/request monitoring**. It answers: "Is my service healthy from a user's perspective?"

#### Rate (Requests per Second)

**What it measures**: How many requests your service is handling per unit of time.

**Metrics in this project:**
```promql
# Total request rate
sum(rate(http_requests_total[5m]))

# Rate by endpoint
sum(rate(http_requests_total[5m])) by (endpoint)

# Rate by HTTP method
sum(rate(http_requests_total[5m])) by (method)
```

**Why logs are essential:**
- **Identify traffic patterns**: See which users, endpoints, and user agents are generating traffic
- **Detect anomalies**: Spot unusual request patterns (bots, attacks, API abuse)
- **Understand composition**: See what types of requests make up your traffic

**Log correlation:**
```logql
# Request patterns in logs
{service="php-app"} | json | message="API Request"

# Traffic sources
{service="php-app"} | json | line_format "{{.request.ip}} - {{.request.user_agent}}"

# Popular endpoints
{service="php-app"} | json | message="API Request" | line_format "{{.context.endpoint}}"
```

**Example correlation scenario:**
```
Metric shows: Rate spike from 100 req/sec → 500 req/sec at 2:00 PM
Log query: {service="php-app"} | timestamp >= "2024-11-02T14:00:00Z"
Result: Shows 400 requests from IP 192.168.1.100 (bot/crawler)
Insight: Not a real user spike, just automated traffic
Action: Filter bot traffic or implement rate limiting
```

#### Errors (Error Rate)

**What it measures**: The rate of failed requests (HTTP 4xx/5xx errors).

**Metrics in this project:**
```promql
# Error rate (5xx server errors)
sum(rate(http_requests_total{status_code=~"5.."}[5m]))

# Error percentage
(
  sum(rate(http_requests_total{status_code=~"5.."}[5m])) /
  sum(rate(http_requests_total[5m]))
) * 100

# Error rate by endpoint
sum(rate(http_requests_total{status_code=~"5.."}[5m])) by (endpoint)

# Client errors (4xx)
sum(rate(http_requests_total{status_code=~"4.."}[5m]))
```

**Why logs are essential:**
- **Error messages**: See actual error messages and stack traces
- **Error context**: Understand what parameters, users, or operations caused errors
- **Error patterns**: Identify recurring errors that need fixing
- **Correlation IDs**: Trace complete request flow for failed requests

**Log correlation:**
```logql
# All error logs
{level="ERROR", service="php-app"}

# Errors with specific messages
{level="ERROR"} |= "database"
{level="ERROR"} |= "timeout"
{level="ERROR"} |= "connection"

# Errors with full context
{level="ERROR"} | json | line_format "{{.correlation_id}}: {{.message}} ({{.context.endpoint}})"

# Most common errors
topk(5, count_over_time({level="ERROR"}[1h]))
```

**Example correlation scenario:**
```
Metric shows: Error rate at 5% at 2:15 PM
Log query: {level="ERROR", service="php-app"} | timestamp >= "2024-11-02T14:15:00Z"
Result: Shows 50 errors, all with message "Database connection timeout"
Correlation IDs: req_123, req_124, req_125...
Investigation: Query logs by correlation_id to see full request flow
Insight: All errors are database timeouts, not application bugs
Root cause: Database connection pool exhausted
Action: Increase database connection pool size
```

#### Duration (Response Time)

**What it measures**: How long requests take to complete (latency).

**Metrics in this project:**
```promql
# 95th percentile latency (most common metric)
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))

# 50th percentile (median)
histogram_quantile(0.50, rate(http_request_duration_seconds_bucket[5m]))

# Average latency
rate(http_request_duration_seconds_sum[5m]) / rate(http_request_duration_seconds_count[5m])

# Duration by endpoint
histogram_quantile(0.95, 
  sum(rate(http_request_duration_seconds_bucket[5m])) by (endpoint, le)
)

# Min/Max latency
min(rate(http_request_duration_seconds_sum[5m]))
max(rate(http_request_duration_seconds_sum[5m]))
```

**Why logs are essential:**
- **Slow request identification**: Find which specific requests are slow
- **Bottleneck detection**: See which operations within a request are slow
- **User impact**: Understand which users or endpoints are affected
- **Request flow analysis**: Trace slow requests using correlation IDs

**Log correlation:**
```logql
# Slow requests (> 1 second)
{service="php-app"} | json | duration_ms > 1000

# Very slow requests (> 2 seconds)
{service="php-app"} | json | duration_ms > 2000

# Slow requests with endpoint info
{service="php-app"} | json | duration_ms > 1000 | 
  line_format "{{.context.endpoint}}: {{.context.duration_ms}}ms"

# Get correlation IDs for slow requests
{service="php-app"} | json | duration_ms > 2000 | 
  line_format "{{.correlation_id}}"
```

**Example correlation scenario:**
```
Metric shows: P95 latency spike from 200ms → 2.5s
Log query: {service="php-app"} | json | duration_ms > 2000
Result: Shows slow requests to /api.php?action=slow
Investigation: Query by correlation_id to see complete request flow
Insight: /api.php?action=slow is a test endpoint designed to be slow
Action: Filter test endpoints from production monitoring or exclude from alerts
```

#### RED Dashboard Recommendations

**Row 1: RED Overview**
- **Panel 1**: Request Rate (time series) - `sum(rate(http_requests_total[5m]))`
- **Panel 2**: Error Rate (time series) - `sum(rate(http_requests_total{status_code=~"5.."}[5m]))`
- **Panel 3**: P95 Duration (time series) - `histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))`
- **Panel 4**: Alert Status (indicator if any metric breached threshold)

**Row 2: Rate Deep Dive + Logs**
- **Panel 1**: Request Rate by Endpoint (time series)
- **Panel 2**: Top Endpoints by Volume (stat panel)
- **Panel 3**: Recent Requests (log panel) - `{service="php-app"} | json | message="API Request" | limit 20`

**Row 3: Errors Deep Dive + Logs**
- **Panel 1**: Error Rate by Endpoint (bar chart)
- **Panel 2**: Error Rate Percentage (gauge: green <1%, yellow 1-5%, red >5%)
- **Panel 3**: Recent Errors (log panel) - `{level="ERROR", service="php-app"} | limit 20`
- **Panel 4**: Error Breakdown (pie chart) - `topk(5, count_over_time({level="ERROR"}[1h]))`

**Row 4: Duration Deep Dive + Logs**
- **Panel 1**: Latency Percentiles (time series: P50, P95, P99)
- **Panel 2**: Latency by Endpoint (heatmap)
- **Panel 3**: Slow Requests (log panel) - `{service="php-app"} | json | duration_ms > 1000 | limit 10`
- **Panel 4**: Duration Distribution (histogram)

**Row 5: Correlation Investigation**
- **Panel 1**: Correlation ID Search (input variable)
- **Panel 2**: Request Flow (log panel) - `{correlation_id="$correlation_id"}`

---

### USE Metrics: Utilization, Saturation, Errors

**USE** (Utilization, Saturation, Errors) is a methodology focused on **infrastructure/resource monitoring**. It answers: "Are my resources (CPU, memory, disk, network) healthy?"

#### Utilization (Resource Usage)

**What it measures**: How much of a resource is currently being used.

**Metrics in this project:**
```promql
# Memory utilization (in bytes)
php_memory_usage_bytes

# Memory utilization as percentage (if memory_limit is known)
(php_memory_usage_bytes / 134217728) * 100  # Assuming 128MB limit

# Peak memory usage
php_memory_peak_bytes
```

**Missing metrics** (would require additional exporters):
- CPU utilization (would need node_exporter or PHP-FPM exporter)
- Disk I/O utilization
- Network bandwidth utilization
- Database connection pool utilization

**Why logs are essential:**
- **Identify memory-intensive operations**: See which endpoints or operations cause memory spikes
- **Memory leak detection**: Find patterns in logs that correlate with memory growth
- **Application context**: Understand what application logic causes high resource usage

**Log correlation:**
```logql
# Memory warnings
{level="WARNING"} |= "memory"

# High memory usage events
{service="php-app"} | json | message=~"memory"

# Operations that might cause memory issues
{service="php-app"} | json | context.endpoint="/api.php?action=stats"
```

**Example correlation scenario:**
```
Metric shows: Memory usage at 95% (120MB / 128MB)
Log query: {level="WARNING"} |= "memory" | timestamp >= "2024-11-02T14:00:00Z"
Result: Logs show warnings from /api.php?action=stats endpoint
Context: Stats endpoint loads large datasets into memory
Insight: /api.php?action=stats endpoint is memory-intensive
Action: Optimize stats endpoint or add memory limits per request
```

#### Saturation (Resource Constraints)

**What it measures**: How "full" a resource is, or how long it has to wait for service.

**Metrics in this project (partial):**
```promql
# Memory saturation (closest proxy)
php_memory_usage_bytes / php_memory_peak_bytes

# Active sessions (connection saturation proxy)
active_sessions
```

**Missing metrics** (would require additional exporters):
- CPU saturation (load average, queue length)
- Disk I/O saturation (queue depth, wait time)
- Network saturation (packet loss, retransmissions)
- Connection pool saturation (waiting connections)
- Thread pool saturation

**Why logs are essential:**
- **Saturation symptoms**: Error logs show symptoms before metrics detect saturation
- **Resource exhaustion**: Logs reveal when resources are exhausted (connection errors, timeouts)
- **User impact**: Logs show which users/requests are affected by saturation

**Log correlation:**
```logql
# Saturation symptoms (timeouts, queues)
{level="WARNING"} |= "timeout"
{level="ERROR"} |= "connection"
{service="php-app"} | json | duration_ms > 5000

# Resource warnings
{level="WARNING"} |= "slow"
{level="WARNING"} |= "queue"
```

**Example correlation scenario:**
```
Metric shows: Memory near limit, active_sessions increasing
Log query: {level="ERROR"} |= "connection" | timestamp >= "2024-11-02T15:00:00Z"
Result: Multiple "Database connection timeout" errors
Investigation: Query by correlation_id to see affected requests
Insight: Connection pool exhausted due to slow queries
Root cause: Unoptimized database query in stats endpoint
Action: Add database query optimization and connection pooling
```

#### Errors (Resource Errors)

**What it measures**: Count of errors related to resource exhaustion or constraints.

**Metrics in this project:**
```promql
# System-level errors (5xx)
sum(rate(http_requests_total{status_code=~"5.."}[5m]))

# Resource-related errors (if labeled appropriately)
sum(rate(http_requests_total{status_code=~"5.."}[5m])) by (endpoint)
```

**Why logs are essential:**
- **Specific error types**: Identify resource-specific errors (out of memory, connection pool exhausted)
- **Error context**: See which operations trigger resource errors
- **Recovery patterns**: Understand how system recovers from resource errors

**Log correlation:**
```logql
# Resource errors
{level="ERROR"} |= "connection"
{level="ERROR"} |= "memory"
{level="ERROR"} |= "timeout"

# System errors with context
{level="ERROR"} | json | context.error != ""
```

#### USE Dashboard Recommendations

**Row 1: Utilization Metrics**
- **Panel 1**: Memory Usage (gauge) - `php_memory_usage_bytes`
- **Panel 2**: Memory Usage Over Time (time series)
- **Panel 3**: Active Sessions (stat panel) - `active_sessions`
- **Panel 4**: Memory Warnings (log panel) - `{level="WARNING"} |= "memory"`

**Row 2: Saturation Indicators**
- **Panel 1**: Memory Saturation Ratio (gauge)
- **Panel 2**: Database Query Rate - `sum(rate(database_queries_total[5m]))`
- **Panel 3**: Resource Errors (log panel) - `{level="ERROR"} |= "connection" OR |= "timeout"`

**Row 3: Resource Health**
- **Panel 1**: System Health Status (stat panel)
- **Panel 2**: Resource Error Rate (time series)
- **Panel 3**: Recent Resource Warnings (log panel)

---

### Four Golden Signals (Google SRE)

The **Four Golden Signals** (from Google's Site Reliability Engineering book) are: **Latency, Traffic, Errors, and Saturation**. They provide a comprehensive view of service health.

#### 1. Latency (Time to Respond)

**What it measures**: How long it takes to serve a request.

**Metrics in this project:**
```promql
# 95th percentile latency (most important)
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))

# 50th percentile (median)
histogram_quantile(0.50, rate(http_request_duration_seconds_bucket[5m]))

# Average latency
rate(http_request_duration_seconds_sum[5m]) / rate(http_request_duration_seconds_count[5m])

# Latency by endpoint
histogram_quantile(0.95, 
  sum(rate(http_request_duration_seconds_bucket[5m])) by (endpoint, le)
)
```

**Log correlation:**
```logql
# Slow requests in logs
{service="php-app"} | json | duration_ms > 1000

# Latency patterns by endpoint
{service="php-app"} | json | 
  line_format "{{.context.endpoint}}: {{.context.duration_ms}}ms"

# Find correlation IDs for slow requests
{service="php-app"} | json | duration_ms > 2000 | 
  line_format "{{.correlation_id}}"
```

**Why logs are essential:**
- **Per-request details**: See exact duration for each request
- **Slow request tracing**: Use correlation IDs to trace complete slow request flow
- **Endpoint breakdown**: Identify which endpoints cause latency issues
- **User context**: See which users experience slow requests

**Example dashboard panel:**
- **Panel 1**: P95 Latency (time series) - from Prometheus
- **Panel 2**: Slow Requests (log panel) - from Loki showing correlation IDs
- **Panel 3**: Latency by Endpoint (table) - combining Prometheus metrics and log context

#### 2. Traffic (Demand)

**What it measures**: How much demand is being placed on your system.

**Metrics in this project:**
```promql
# Request rate (requests per second)
sum(rate(http_requests_total[5m]))

# Traffic by endpoint
sum(rate(http_requests_total[5m])) by (endpoint)

# Traffic by HTTP method
sum(rate(http_requests_total[5m])) by (method)

# Traffic by status code
sum(rate(http_requests_total[5m])) by (status_code)
```

**Log correlation:**
```logql
# Request patterns
{service="php-app"} | json | message="API Request"

# Traffic sources (IPs and user agents)
{service="php-app"} | json | 
  line_format "{{.request.ip}} - {{.request.user_agent}}"

# Popular endpoints from logs
{service="php-app"} | json | message="API Request" | 
  line_format "{{.context.endpoint}}"

# Top request sources
topk(10, count_over_time({service="php-app"}[1h]))
```

**Why logs are essential:**
- **Traffic composition**: Understand who is making requests (users, bots, API clients)
- **Anomaly detection**: Identify unusual traffic patterns
- **User behavior**: Analyze how users interact with your service
- **Traffic sources**: See geographic distribution, user agents, referrers

**Example dashboard panel:**
- **Panel 1**: Request Rate (time series) - from Prometheus
- **Panel 2**: Top Endpoints (stat panel) - from Prometheus
- **Panel 3**: Traffic Sources (log panel) - from Loki showing IPs and user agents
- **Panel 4**: Request Patterns (heatmap) - from Prometheus

#### 3. Errors (Failure Rate)

**What it measures**: The rate of requests that fail.

**Metrics in this project:**
```promql
# Error rate (5xx server errors)
sum(rate(http_requests_total{status_code=~"5.."}[5m]))

# Error percentage
(
  sum(rate(http_requests_total{status_code=~"5.."}[5m])) /
  sum(rate(http_requests_total[5m]))
) * 100

# Errors by endpoint
sum(rate(http_requests_total{status_code=~"5.."}[5m])) by (endpoint)

# Client errors (4xx)
sum(rate(http_requests_total{status_code=~"4.."}[5m]))
```

**Log correlation:**
```logql
# Error logs
{level="ERROR", service="php-app"}

# Error messages
{level="ERROR"} | line_format "{{.message}}"

# Errors with context and correlation IDs
{level="ERROR"} | json | 
  line_format "{{.correlation_id}}: {{.message}} ({{.context.endpoint}})"

# Error patterns
{level="ERROR"} |= "database"
{level="ERROR"} |= "timeout"
{level="ERROR"} |= "connection"

# Most common errors
topk(5, count_over_time({level="ERROR"}[1h]))
```

**Why logs are essential:**
- **Error messages**: See actual error messages and stack traces (not just error counts)
- **Error context**: Understand what parameters, users, or operations caused errors
- **Correlation IDs**: Trace complete request flow for failed requests
- **Error trends**: Identify recurring errors and patterns

**Example dashboard panel:**
- **Panel 1**: Error Rate % (gauge: green <1%, yellow 1-5%, red >5%) - from Prometheus
- **Panel 2**: Error Rate Over Time (time series) - from Prometheus
- **Panel 3**: Recent Errors (log panel) - from Loki with correlation IDs
- **Panel 4**: Error Breakdown (pie chart) - from Loki showing most common error messages

#### 4. Saturation (Resource Fullness)

**What it measures**: How "full" your service is (resource constraints).

**Metrics in this project:**
```promql
# Memory saturation
php_memory_usage_bytes
php_memory_peak_bytes

# Active sessions (connection saturation proxy)
active_sessions

# Database query saturation (if you track query queue)
sum(rate(database_queries_total[5m]))
```

**Missing metrics** (would require additional exporters):
- CPU saturation
- Disk I/O saturation
- Network saturation
- Connection pool saturation

**Log correlation:**
```logql
# Saturation symptoms
{level="WARNING"} |= "timeout"
{level="ERROR"} |= "connection"
{service="php-app"} | json | duration_ms > 5000

# Resource warnings
{level="WARNING"} |= "memory"
{level="WARNING"} |= "slow"
{level="WARNING"} |= "queue"
```

**Why logs are essential:**
- **Saturation symptoms**: Error and warning logs show saturation symptoms before metrics detect it
- **Resource exhaustion context**: Understand what operations trigger resource exhaustion
- **User impact**: See which users/requests are affected by saturation

**Example dashboard panel:**
- **Panel 1**: Memory Usage (gauge) - from Prometheus
- **Panel 2**: Active Sessions (stat panel) - from Prometheus
- **Panel 3**: Saturation Warnings (log panel) - from Loki showing timeout/connection errors

#### Four Golden Signals Dashboard Recommendations

**Row 1: All Four Signals Overview**
- **Panel 1**: Latency P95 (time series)
- **Panel 2**: Traffic Rate (time series)
- **Panel 3**: Error Rate % (gauge with thresholds)
- **Panel 4**: Memory Saturation (gauge)

**Row 2: Latency Deep Dive**
- **Panel 1**: Latency Percentiles (P50, P95, P99) - Prometheus
- **Panel 2**: Latency by Endpoint (heatmap) - Prometheus
- **Panel 3**: Slow Requests (log panel) - Loki: `{service="php-app"} | json | duration_ms > 1000`

**Row 3: Traffic Deep Dive**
- **Panel 1**: Request Rate by Endpoint - Prometheus
- **Panel 2**: Request Rate by Method - Prometheus
- **Panel 3**: Traffic Sources (log panel) - Loki: Top IPs and user agents

**Row 4: Errors Deep Dive**
- **Panel 1**: Error Rate by Endpoint - Prometheus
- **Panel 2**: Error Rate Over Time - Prometheus
- **Panel 3**: Recent Errors (log panel) - Loki: `{level="ERROR", service="php-app"} | limit 20`
- **Panel 4**: Error Breakdown - Loki: Most common error messages

**Row 5: Saturation Deep Dive**
- **Panel 1**: Memory Usage Over Time - Prometheus
- **Panel 2**: Active Sessions - Prometheus
- **Panel 3**: Saturation Warnings (log panel) - Loki: `{level="WARNING"} |= "timeout" OR |= "connection"`

**Row 6: Correlation Investigation**
- **Panel 1**: Correlation ID Input (variable)
- **Panel 2**: Complete Request Flow (log panel) - Loki: `{correlation_id="$correlation_id"}`

---

### Why Combine Metrics and Logs?

#### Metrics Answer:
- **What happened?** (numbers, trends, rates)
- **How much?** (counts, percentages)
- **When?** (time series)

#### Logs Answer:
- **Why did it happen?** (error messages, stack traces, context)
- **Who?** (users, IPs, user agents)
- **What context?** (request details, parameters, correlation IDs)
- **Full story?** (complete request flow via correlation IDs)

#### Together They Provide:
- **Complete observability**: Numbers + context = full picture
- **Faster debugging**: Metrics show the problem, logs explain why
- **Better understanding**: Quantitative data + qualitative details
- **Proactive monitoring**: Detect issues before users report them

---

### Correlation Patterns for Monitoring

#### Pattern 1: Metric Spike → Log Investigation

**Workflow:**
1. **See metric anomaly** in Grafana dashboard (e.g., error rate spike)
2. **Identify timeframe** when anomaly occurred
3. **Query logs for that time**: `{level="ERROR"} | timestamp >= "2024-11-02T14:00:00Z"`
4. **Extract correlation IDs** from error logs
5. **Trace specific requests**: `{correlation_id="req_123"}`
6. **Understand root cause** from complete request flow

**Example:**
```
Step 1: Error rate metric shows spike to 10% at 2:00 PM
Step 2: Query logs: {level="ERROR", service="php-app"} | timestamp >= "2024-11-02T14:00:00Z"
Step 3: Result shows 50 errors with correlation_ids: req_123, req_124, req_125...
Step 4: Query one error: {correlation_id="req_123"}
Step 5: See complete request flow showing "Database connection timeout"
Step 6: Root cause: Database connection pool exhausted
Step 7: Action: Increase database connection pool size
```

#### Pattern 2: Slow Request → Log Analysis

**Workflow:**
1. **Identify slow timeframe** from latency metrics
2. **Find slow requests in logs**: `{service="php-app"} | json | duration_ms > 2000`
3. **Identify patterns** (endpoints, users, operations)
4. **Get correlation IDs** for slow requests
5. **Trace request flow** to find bottleneck

**Example:**
```
Step 1: P95 latency increases from 200ms to 2s at 3:00 PM
Step 2: Query logs: {service="php-app"} | json | duration_ms > 2000
Step 3: Result shows all slow requests are to /api.php?action=slow
Step 4: Check if intentional: Logs show "Slow endpoint requested - simulating delay"
Step 5: Insight: Test endpoint, not production issue
Alternative: If real slow requests, use correlation_id to trace full flow
```

#### Pattern 3: Error Budget Burn → Log Deep Dive

**Workflow:**
1. **Monitor error rate** approaching SLO threshold
2. **Investigate error patterns** in logs
3. **Identify problematic endpoints** or operations
4. **Get correlation IDs** for representative errors
5. **Trace errors** to understand failure mode
6. **Implement fix** based on root cause

**Example:**
```
Step 1: Error rate at 0.08% (SLO: < 0.1%)
Step 2: Query logs: {level="ERROR"} | json | line_format "{{.context.endpoint}}: {{.message}}"
Step 3: Result: 80% of errors from /api.php?action=create_user
Step 4: Get correlation IDs: {level="ERROR"} | json | context.endpoint="/api.php?action=create_user"
Step 5: Trace error: {correlation_id="req_error_123"}
Step 6: Complete flow shows validation failure
Step 7: Root cause: Missing input validation
Step 8: Action: Fix input validation in create_user endpoint
```

---

### Best Practices: Combining Metrics and Logs

#### 1. Always Pair Metrics with Logs

**❌ BAD**: Dashboard shows only metrics
- You see "error rate is 5%" but don't know why

**✅ GOOD**: Dashboard shows metrics + related logs
- Panel 1: Error rate metric (Prometheus)
- Panel 2: Recent error logs (Loki) - same time range
- Click on metric spike → Automatically filter logs to that time

#### 2. Use Correlation IDs as Bridge

**Pattern:**
```
Metrics show: Error at endpoint /api.php?action=users
↓
Get correlation IDs from error logs: {level="ERROR"} | json
↓
Query logs: {correlation_id="req_123"}
↓
See complete request flow and understand why it failed
```

#### 3. Time-Based Correlation

**Workflow:**
1. Metrics show anomaly → Note the timeframe
2. Query logs for same timeframe
3. Look for patterns in logs
4. Use correlation IDs for deep dive into specific requests

#### 4. Dashboard Drill-Down

**Three-level drill-down:**
- **Level 1**: Metrics overview (RED/USE/Golden Signals)
- **Level 2**: Logs for that timeframe (when anomaly detected)
- **Level 3**: Correlation ID search (full request trace for specific issues)

#### 5. Alerting Integration

**Combined alerting strategy:**
- **Alert on metrics**: Fast detection of anomalies
- **Include log context**: Alert message includes sample correlation IDs
- **Link to investigation**: Alert links to dashboard with logs pre-filtered

**Example alert:**
```
Alert: High Error Rate
Condition: Error rate > 1% for 5 minutes
Message: "Error rate at 1.2%. Sample correlation IDs: req_123, req_124, req_125"
Link: Dashboard with error logs filtered to this time range
```

---

### Recommended Dashboard Structure

#### Dashboard: "PHP App - RED + Logs Correlation"

**Row 1: RED Overview**
- Rate: `sum(rate(http_requests_total[5m]))`
- Errors: `sum(rate(http_requests_total{status_code=~"5.."}[5m]))`
- Duration: `histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))`
- Alert Status: Indicator if any RED metric breached threshold

**Row 2: Rate Deep Dive + Logs**
- Request Rate (time series) - Prometheus
- Top Endpoints (stat panel) - Prometheus
- Recent Requests (log panel) - Loki: `{service="php-app"} | json | message="API Request" | limit 20`

**Row 3: Errors Deep Dive + Logs**
- Error Rate (time series) - Prometheus
- Error Rate by Endpoint (bar chart) - Prometheus
- Recent Errors (log panel) - Loki: `{level="ERROR", service="php-app"} | limit 20`

**Row 4: Duration Deep Dive + Logs**
- P95 Latency (time series) - Prometheus
- Latency by Endpoint (heatmap) - Prometheus
- Slow Requests (log panel) - Loki: `{service="php-app"} | json | duration_ms > 1000`

**Row 5: Correlation Investigation**
- Correlation ID Input (variable)
- Request Flow (log panel) - Loki: `{correlation_id="$correlation_id"}`

---

### Summary

**RED Metrics** focus on **service health** from a user perspective:
- ✅ **Rate**: Request volume - Metrics show numbers, logs show patterns
- ✅ **Errors**: Failure rate - Metrics show counts, logs show why
- ✅ **Duration**: Response time - Metrics show percentiles, logs show specific slow requests

**USE Metrics** focus on **resource health**:
- ⚠️ **Utilization**: Resource usage - Partial (memory available, CPU would need node_exporter)
- ⚠️ **Saturation**: Resource constraints - Partial (memory available, others would need exporters)
- ✅ **Errors**: Resource errors - Metrics show counts, logs show specific errors

**Four Golden Signals** provide **comprehensive service view**:
- ✅ **Latency**: Response time - Fully supported with logs
- ✅ **Traffic**: Request demand - Fully supported with logs
- ✅ **Errors**: Failure rate - Fully supported with logs
- ⚠️ **Saturation**: Resource fullness - Partial (memory supported, others need exporters)

**Combining Metrics and Logs** provides:
- **Complete picture**: What happened (metrics) + Why it happened (logs)
- **Faster debugging**: Metrics detect issues, logs explain root cause
- **Better correlation**: Use correlation IDs to trace requests across metrics and logs
- **Proactive monitoring**: Detect and investigate issues before users report them

All three methodologies are supported in this demo project, with logs providing essential context and correlation through correlation IDs.

## 🔑 Key PHP Functions

### Logger Class

#### `__construct($logFile = '/var/log/app/application.log')`
Initializes the logger, generates correlation ID, and ensures log directory exists.

#### `getCorrelationId()`
Returns the unique correlation ID for the current request.

#### `setCorrelationId($correlationId)`
Manually set a correlation ID (useful for distributed tracing).

#### `info($message, $context = [])`
Log an informational message with optional context data.

#### `error($message, $context = [])`
Log an error message with optional context data.

#### `warning($message, $context = [])`
Log a warning message with optional context data.

#### `debug($message, $context = [])`
Log a debug message with optional context data.

#### `logApiRequest($method, $endpoint, $statusCode, $durationMs, $userId = null, $context = [])`
Specialized logging for API requests with automatic context extraction.

#### `logAuthAttempt($username, $success, $context = [])`
Log authentication attempts with success/failure status.

### MetricsCollector Class

#### `incrementHttpRequests($method, $endpoint, $statusCode)`
Increment the HTTP request counter for a specific method, endpoint, and status code.

#### `recordHttpRequestDuration($method, $endpoint, $durationSeconds)`
Record the duration of an HTTP request (stored for histogram calculation).

#### `incrementAuthAttempts($type, $result)`
Increment authentication attempt counter (type: 'login' or 'api_login', result: 'success' or 'failure').

#### `setActiveSessions($count)`
Set the number of active sessions (gauge metric).

#### `updateMemoryUsage()`
Update the current PHP memory usage gauge.

#### `incrementDatabaseQueries($operation, $table = 'unknown')`
Increment database query counter for a specific operation and table.

#### `getPrometheusMetrics()`
Generate and return all metrics in Prometheus text format.

### API Functions (api.php)

#### `isAuthenticated()`
Check if the current session has an authenticated user.

#### `sendResponse($data, $statusCode = 200, $message = 'Success')`
Send a standardized JSON response with logging and metrics updates.

#### `sendError($message, $statusCode = 400)`
Send an error response (wrapper around sendResponse).

#### `handleGetRequest($action)`
Route GET requests to appropriate handlers based on action parameter.

#### `handlePostRequest($action)`
Route POST requests to appropriate handlers based on action parameter.

## 🔄 How the Application Works

### Request Flow

1. **User/Browser Request**: Access http://localhost:8080 or API endpoint

2. **Application Initialization**:
   - `Logger` instance created (generates correlation ID)
   - Request start time recorded
   - Session started (if needed)

3. **Request Processing**:
   - **Web Interface (index.php)**:
     - Handles login/logout
     - Displays dashboard if authenticated
     - Shows correlation ID and request info
   
   - **API Endpoints (api.php)**:
     - Routes requests by method and action
     - Validates authentication for protected endpoints
     - Simulates database operations
     - Returns JSON responses

4. **Metrics Collection**:
   - HTTP request counted
   - Request duration recorded
   - Authentication attempts tracked
   - Database queries counted (if applicable)

5. **Logging**:
   - Application events logged with correlation ID
   - API requests logged with full context
   - Authentication attempts logged
   - Errors logged with appropriate level

6. **Response**:
   - HTML rendered (web interface) or JSON returned (API)
   - Correlation ID included in response
   - Metrics and logs written

### Metrics Scraping (Prometheus)

1. **Prometheus Configuration**: Scrapes `/metrics.php` every 15 seconds
2. **Metrics Endpoint**: `metrics.php` collects all metrics from `MetricsCollector`
3. **Format Conversion**: Metrics formatted in Prometheus text format
4. **Storage**: Prometheus stores metrics in its time-series database
5. **Querying**: Metrics can be queried in Prometheus UI at http://localhost:9090

### Log Collection Flow (Alloy → Loki)

1. **PHP Application**: Writes structured JSON logs to `/var/log/app/application.log`
2. **Grafana Alloy**: 
   - Monitors the log file continuously
   - Parses JSON entries and extracts fields
   - Creates labels (level, correlation_id, service)
   - Formats logs for Loki ingestion
3. **Loki**: 
   - Receives logs via HTTP API at `http://loki:3100/loki/api/v1/push`
   - Indexes logs by labels for fast searching
   - Stores logs efficiently
4. **Querying**: Logs can be queried using LogQL in Grafana Explore or Loki UI

### Grafana Integration

Grafana is pre-configured with:
- **Prometheus datasource**: Ready for querying metrics
- **Loki datasource**: Ready for querying logs
- **Auto-provisioned**: Datasources are automatically configured on startup

To use Grafana:
1. Access Grafana at http://localhost:3000
2. Login with `admin` / `admin`
3. Go to **Explore** to query metrics or logs
4. Create dashboards to visualize your data

## 🏛️ Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT/BROWSER                          │
│                    (http://localhost:8080)                      │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         │ HTTP Requests
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    PHP Application Container                     │
│                    (php:8.2-apache)                              │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  index.php          api.php         metrics.php          │  │
│  │  (Web UI)           (REST API)      (Metrics Endpoint)     │  │
│  └───────┬──────────────────┬──────────────────┬────────────┘  │
│          │                  │                  │                │
│          ▼                  ▼                  ▼                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐       │
│  │   Logger    │    │   Logger    │    │MetricsCollector│     │
│  │   Class     │    │   Class     │    │    Class      │       │
│  └──────┬──────┘    └──────┬──────┘    └──────┬───────┘       │
│         │                  │                   │                │
│         └──────────────────┼───────────────────┘                │
│                            │                                     │
│                            ▼                                     │
│                  ┌──────────────────┐                          │
│                  │  /var/log/app/    │                          │
│                  │ application.log   │                          │
│                  │ (JSON Logs)       │                          │
│                  └──────────────────┘                          │
└──────────────────────────────────────────────────────────────────┘
                         │
                         │ Metrics Scraping (every 15s)
                         │ GET /metrics.php
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Prometheus Container                          │
│                    (prom/prometheus:latest)                      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Prometheus Server                                         │  │
│  │  - Scrapes: php-app:80/metrics.php                        │  │
│  │  - Scrape Interval: 15 seconds                             │  │
│  │  - Storage: Time-series database                          │  │
│  │  - Retention: 200 hours                                    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Prometheus UI                                             │  │
│  │  http://localhost:9090                                     │  │
│  │  - Query metrics                                           │  │
│  │  - View targets                                            │  │
│  │  - Graph visualization                                     │  │
│  └──────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘

Data Flow:
1. Client → PHP App: HTTP requests (web or API)
2. PHP App → Logger: Structured JSON logs written
3. PHP App → MetricsCollector: Metrics incremented in memory
4. Prometheus → PHP App: Scrapes /metrics.php endpoint
5. MetricsCollector → Prometheus: Returns Prometheus-formatted metrics
6. Prometheus: Stores metrics in time-series database
```

### Component Interactions

**Logger Class**:
- Generates correlation IDs: `req_XXXXXXXXX`
- Writes structured JSON to `/var/log/app/application.log`
- Includes request context, user info, timing data

**MetricsCollector Class**:
- Maintains in-memory metric state
- Tracks HTTP requests, durations, auth attempts, DB queries
- Formats metrics in Prometheus text format
- Exposes via `/metrics.php` endpoint

**Prometheus**:
- Scrapes `/metrics.php` every 15 seconds
- Stores metrics with labels (method, endpoint, status_code, etc.)
- Provides query interface at http://localhost:9090

## 📈 Configuration

### Prometheus Configuration (`prometheus.yml`)

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']
  
  - job_name: 'php-app'
    static_configs:
      - targets: ['php-app:80']
    scrape_interval: 15s
    metrics_path: '/metrics.php'
```

**Key Settings**:
- Scrapes PHP app metrics every 15 seconds
- Metrics endpoint: `/metrics.php`
- Target: `php-app:80` (internal Docker network)

### Docker Services (`docker-compose.yaml`)

**php-app**:
- Image: `php:8.2-apache`
- Port: `8080:80` (host:container)
- Volumes: `./src:/var/www/html`, `./logs:/var/log/app`
- Network: `prometheus-network`
- Logs written to `/var/log/app/application.log`

**prometheus**:
- Image: `prom/prometheus:latest`
- Port: `9090:9090`
- Config: `./prometheus.yml`
- Storage: `prometheus_data` volume (200h retention)
- Network: `prometheus-network`

**loki**:
- Image: `grafana/loki:latest`
- Port: `3100:3100`
- Config: `./config/loki.yaml`
- Storage: `loki_data` volume
- Network: `prometheus-network`
- Receives logs from Alloy

**alloy**:
- Image: `grafana/alloy:latest`
- Port: `12345:12345` (Alloy UI)
- Config: `./config/alloy.yaml`
- Volumes: `./logs:/var/log/app:ro` (read-only access to logs)
- Network: `prometheus-network`
- Monitors log files and ships to Loki

**grafana**:
- Image: `grafana/grafana:latest`
- Port: `3000:3000`
- Credentials: `admin` / `admin`
- Volumes: `grafana_data` (persistent storage), `./config/grafana/provisioning` (auto-configured datasources)
- Network: `prometheus-network`
- Pre-configured with Prometheus and Loki datasources
- Depends on: Prometheus and Loki

## 🧪 Testing

### Generate Metrics

```bash
# Access web interface
curl http://localhost:8080

# Call API endpoints
curl http://localhost:8080/api.php?action=health
curl http://localhost:8080/api.php?action=users

# Test slow endpoint
curl http://localhost:8080/api.php?action=slow

# Test authentication
curl -X POST http://localhost:8080/api.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

### View Metrics

```bash
# Direct metrics endpoint
curl http://localhost:8080/metrics.php

# Prometheus targets
curl http://localhost:9090/targets

# Query in Prometheus UI
# Visit http://localhost:9090 and try:
# - http_requests_total
# - rate(http_requests_total[5m])
# - http_request_duration_seconds_sum
```

### Check Logs

```bash
# View application logs (inside container)
docker-compose exec php-app tail -f /var/log/app/application.log

# View logs from host filesystem
tail -f ./logs/application.log

# Query logs in Loki (using LogQL)
curl -G -s "http://localhost:3100/loki/api/v1/query_range" \
  --data-urlencode 'query={service="php-app"}' \
  --data-urlencode 'limit=10'

# Check Alloy status
curl http://localhost:12345/ready
```

## 🔍 Troubleshooting

### Services Not Starting

```bash
# Check service status
docker-compose ps

# View logs
docker-compose logs php-app
docker-compose logs prometheus
docker-compose logs loki
docker-compose logs alloy
docker-compose logs grafana
```

### Metrics Not Appearing

1. **Check Prometheus Targets**:
   - Visit http://localhost:9090/targets
   - Verify `php-app` target is UP

2. **Verify Metrics Endpoint**:
   ```bash
   curl http://localhost:8080/metrics.php
   ```
   Should return Prometheus-formatted metrics

3. **Check Network**:
   - Ensure both containers are on `prometheus-network`
   - Verify Prometheus can reach `php-app:80`

### Logs Not Appearing

1. **Check Log Directory**:
   ```bash
   docker-compose exec php-app ls -la /var/log/app/
   ls -la ./logs/
   ```

2. **Check Permissions**:
   - Logger creates directory if it doesn't exist
   - Ensure PHP process has write permissions

3. **View Logs Locally**:
   ```bash
   docker-compose exec php-app tail -f /var/log/app/application.log
   tail -f ./logs/application.log
   ```

4. **Check Alloy is Collecting Logs**:
   ```bash
   # Check Alloy UI
   curl http://localhost:12345/ready
   
   # Check Alloy logs
   docker-compose logs alloy
   ```

5. **Check Loki is Receiving Logs**:
   ```bash
   # Query Loki API
   curl -G -s "http://localhost:3100/loki/api/v1/labels"
   
   # Check Loki logs
   docker-compose logs loki
   ```

## 🔧 Setup Issues & Fixes

This section documents the issues encountered during setup and their solutions.

### Loki Configuration Issues

#### Issue 1: Schema Version Error
**Error**: `schema v13 is required to store Structured Metadata... your schema version is v11`

**Cause**: Newer Loki versions require structured metadata support, but the default schema (v11 with boltdb-shipper) doesn't support it.

**Solution**: Added `limits_config` section in `config/loki.yaml`:
```yaml
limits_config:
  allow_structured_metadata: false
  retention_period: 168h
```

This allows Loki to run with the existing schema without requiring a migration.

#### Issue 2: Permission Denied for /tmp/loki/rules
**Error**: `mkdir /tmp/loki/rules: permission denied`

**Cause**: Loki container didn't have permissions to create the rules directory in the volume.

**Solutions Applied**:
1. Added `user: "0:0"` to the loki service in `docker-compose.yaml` to run as root
2. Added minimal ruler configuration in `config/loki.yaml`:
   ```yaml
   ruler:
     storage:
       type: local
       local:
         directory: /tmp/loki/rules
   ```
3. Restored `rules_directory` in the storage configuration

#### Issue 3: Ruler Storage Directory Required
**Error**: `directory required for local rules config`

**Cause**: Loki requires a ruler storage directory even if not using alerting rules.

**Solution**: Added minimal ruler configuration pointing to `/tmp/loki/rules` directory (see Issue 2 solution).

### PHP Application Permission Issues

#### Issue 4: Log File Permission Denied
**Error**: `file_put_contents(/var/log/app/application.log): Failed to open stream: Permission denied`

**Cause**: The PHP/Apache process (running as www-data user) couldn't write to the mounted logs directory.

**Solution**: Added startup command in `docker-compose.yaml` for php-app service:
```yaml
user: "0:0"  # Run container as root
command: >
  bash -c "
    mkdir -p /var/log/app &&
    chmod 777 /var/log/app &&
    apache2-foreground
  "
```

This ensures:
- Container runs as root (`user: "0:0"`)
- Log directory is created if it doesn't exist
- Directory permissions are set to 777 (world-writable)
- Apache starts normally

**Note**: Using `user: "0:0"` (root) is acceptable for a demo environment. For production, use proper user/group permissions.

### Summary of Configuration Changes

**docker-compose.yaml**:
- Added `user: "0:0"` to `php-app` and `loki` services
- Added startup command to `php-app` to create and set permissions for `/var/log/app`
- Added Loki and Alloy services with proper volume mounts
- Added Grafana service with pre-configured datasources (Prometheus and Loki)

**config/loki.yaml**:
- Added `limits_config` with `allow_structured_metadata: false`
- Added minimal `ruler` configuration with local storage
- Kept `rules_directory` in storage configuration
- Disabled analytics reporting

**config/alloy.yaml**:
- Simplified configuration to only collect `application.log`
- Basic JSON parsing and label extraction
- Direct shipping to Loki

These changes ensure:
✅ Loki starts without schema version errors
✅ Loki can create required directories
✅ PHP application can write logs
✅ Alloy can read log files and ship to Loki
✅ All services work together correctly

## 📚 Next Steps

1. **Explore Prometheus Queries**:
   - Query metrics in Prometheus UI
   - Create rate calculations
   - Build custom dashboards

2. **Analyze Logs in Grafana**:
   - Open Grafana: http://localhost:3000
   - Go to **Explore** and select **Loki** datasource
   - Query logs using LogQL: `{service="php-app"}`
   - Search by correlation ID: `{correlation_id="req_*"}`
   - Filter by log level: `{level="ERROR"}`
   - Track request flows across logs

3. **Create Grafana Dashboards**:
   - Build custom dashboards for metrics and logs
   - Correlate logs and metrics using correlation IDs
   - Visualize request patterns and errors

4. **Extend Metrics**:
   - Add custom business metrics
   - Create alerting rules
   - Build Grafana dashboards

5. **Enhance Logging**:
   - Add more context to logs
   - Implement log aggregation
   - Set up log analysis tools

## 📝 Notes

- **In-Memory Metrics**: Metrics are stored in PHP static variables and reset when the PHP-FPM process restarts
- **Log Persistence**: Logs are written to files and persist across requests
- **Log Collection**: Grafana Alloy monitors log files and ships them to Loki automatically
- **Correlation IDs**: Each request gets a unique correlation ID for tracing across logs
- **Session Management**: Sessions are used for authentication tracking
- **No Database**: This demo uses in-memory data structures; no actual database connection
- **Simple Setup**: Basic Loki/Alloy configuration focused on application.log collection only

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
