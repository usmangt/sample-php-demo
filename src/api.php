<?php
require_once 'Logger.php';
require_once 'MetricsCollector.php';
require_once 'SimpleTracer.php';

// Initialize logging and start timing
$logger = new Logger();
$startTime = microtime(true);

// Active trace for this request (null = endpoint not instrumented)
$activeTraceId = null;
$activeTracer = null;
$activeSpan = null;

// Set JSON response header
header('Content-Type: application/json');

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Start session for user tracking
session_start();

$logger->info('API request started', [
    'endpoint' => $_SERVER['REQUEST_URI'],
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/** Start a root span for this request (shared by health / error / login). */
function beginTrace($spanName) {
    global $logger, $activeTracer, $activeSpan, $activeTraceId;
    $activeTracer = new SimpleTracer($logger->getCorrelationId());
    $activeSpan = $activeTracer->startSpan($spanName);
    $activeTracer->addAttribute($activeSpan, 'http.method', $_SERVER['REQUEST_METHOD']);
    $activeTracer->addAttribute($activeSpan, 'http.route', $_SERVER['REQUEST_URI'] ?? '');
    $activeTraceId = $activeTracer->getTraceId();
    // So ERROR/INFO logs for this request include trace_id (not only API Request)
    $logger->setTraceId($activeTraceId);
}

/** Finish span before sending the HTTP response. */
function endTrace($statusCode, $errorMessage = null) {
    global $activeTracer, $activeSpan;
    if (!$activeTracer || !$activeSpan) {
        return;
    }
    $attrs = ['http.status_code' => (string) $statusCode];
    $otelStatus = 1; // OK
    if ($statusCode >= 400) {
        $otelStatus = 2; // ERROR
        if ($errorMessage) {
            $attrs['error.message'] = $errorMessage;
        }
    }
    $activeTracer->finishSpan($activeSpan, $attrs, $otelStatus);
}

function sendResponse($data, $statusCode = 200, $message = 'Success') {
    global $logger, $startTime, $activeTraceId;
    
    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000;
    
    $meta = [
        'correlation_id' => $logger->getCorrelationId(),
        'timestamp' => date('c'),
        'duration_ms' => round($duration, 2)
    ];
    if (!empty($activeTraceId)) {
        $meta['trace_id'] = $activeTraceId;
    }

    $response = [
        'status' => $statusCode,
        'message' => $message,
        'data' => $data,
        'meta' => $meta
    ];
    
    http_response_code($statusCode);

    $logContext = ['response_size' => strlen(json_encode($response))];
    if (!empty($activeTraceId)) {
        $logContext['trace_id'] = $activeTraceId;
    }
    
    $logger->logApiRequest(
        $_SERVER['REQUEST_METHOD'], 
        $_SERVER['REQUEST_URI'], 
        $statusCode, 
        $duration,
        $_SESSION['username'] ?? null,
        $logContext
    );
    
    MetricsCollector::incrementHttpRequests($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $statusCode);
    MetricsCollector::recordHttpRequestDuration($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $endTime - $startTime);
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

function sendError($message, $statusCode = 400) {
    endTrace($statusCode, $message);
    sendResponse(null, $statusCode, $message);
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest($action);
        break;
    case 'POST':
        handlePostRequest($action);
        break;
    default:
        beginTrace('HTTP ' . $_SERVER['REQUEST_METHOD']);
        sendError('Method not allowed', 405);
}

function handleGetRequest($action) {
    global $logger;
    
    switch ($action) {
        case 'health':
            beginTrace('GET /api.php?action=health');
            $logger->info('Health check requested');
            endTrace(200);
            sendResponse([
                'status' => 'healthy',
                'version' => '1.0.0',
                'php_version' => phpversion(),
                'memory_usage' => memory_get_usage(true),
                'uptime' => time() - $_SERVER['REQUEST_TIME']
            ]);
            break;
            
        case 'users':
            $logger->info('Users list requested');
            MetricsCollector::incrementDatabaseQueries('SELECT', 'users');
            usleep(50000);
            $users = [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'admin'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'user'],
                ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'role' => 'user']
            ];
            sendResponse($users);
            break;
            
        case 'stats':
            if (!isAuthenticated()) {
                beginTrace('GET /api.php?action=stats');
                sendError('Authentication required', 401);
            }
            $logger->info('Statistics requested', ['user' => $_SESSION['username']]);
            sendResponse([
                'total_requests' => rand(1000, 5000),
                'active_users' => rand(10, 100),
                'error_rate' => round(rand(0, 50) / 10, 2),
                'avg_response_time' => rand(50, 200),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ]);
            break;
            
        case 'slow':
            $logger->info('Slow endpoint requested - simulating delay');
            $delay = rand(1, 3);
            sleep($delay);
            sendResponse([
                'message' => 'This was a slow operation',
                'delay_seconds' => $delay,
                'timestamp' => date('c')
            ]);
            break;
            
        default:
            beginTrace('GET /api.php?action=' . ($action ?: 'unknown'));
            sendError('Invalid action', 404);
    }
}

function handlePostRequest($action) {
    global $logger;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'login':
            beginTrace('POST /api.php?action=login');
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            $logger->info('API login attempt', ['username' => $username]);
            
            $validUsers = [
                'admin' => 'password123',
                'demo' => 'demo123',
                'user' => 'user123'
            ];
            
            if (isset($validUsers[$username]) && $validUsers[$username] === $password) {
                $_SESSION['user_id'] = uniqid();
                $_SESSION['username'] = $username;
                $_SESSION['login_time'] = time();
                
                $logger->logAuthAttempt($username, true, ['login_method' => 'api']);
                MetricsCollector::incrementAuthAttempts('api_login', 'success');
                endTrace(200);
                sendResponse([
                    'user_id' => $_SESSION['user_id'],
                    'username' => $username,
                    'token' => 'fake-jwt-token-' . uniqid()
                ]);
            } else {
                $logger->logAuthAttempt($username, false, [
                    'login_method' => 'api',
                    'reason' => 'invalid_credentials'
                ]);
                MetricsCollector::incrementAuthAttempts('api_login', 'failure');
                sendError('Invalid credentials', 401);
            }
            break;
            
        case 'create_user':
            if (!isAuthenticated()) {
                beginTrace('POST /api.php?action=create_user');
                sendError('Authentication required', 401);
            }
            $logger->info('User creation requested', [
                'admin_user' => $_SESSION['username'],
                'new_user_data' => $input
            ]);
            MetricsCollector::incrementDatabaseQueries('INSERT', 'users');
            usleep(100000);
            if (empty($input['name']) || empty($input['email'])) {
                beginTrace('POST /api.php?action=create_user');
                sendError('Name and email are required', 400);
            }
            sendResponse([
                'id' => rand(1000, 9999),
                'name' => $input['name'],
                'email' => $input['email'],
                'role' => $input['role'] ?? 'user',
                'created_at' => date('c')
            ], 201, 'User created successfully');
            break;
            
        case 'error':
            // Demo 500 — used for metrics → logs → traces walkthrough
            beginTrace('POST /api.php?action=error');
            $logger->error('Intentional error endpoint triggered', is_array($input) ? $input : []);
            sendError('This is an intentional error for testing', 500);
            break;
            
        default:
            beginTrace('POST /api.php?action=' . ($action ?: 'unknown'));
            sendError('Invalid action', 404);
    }
}
?>
