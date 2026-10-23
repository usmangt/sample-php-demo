<?php
require_once 'Logger.php';
require_once 'MetricsCollector.php';

// Initialize logging and start timing
$logger = new Logger();
$startTime = microtime(true);

// Get error type from query parameter
$errorType = $_GET['type'] ?? '500';
$statusCode = (int)$errorType;

// Validate status code (only allow 4xx and 5xx)
if ($statusCode < 400 || $statusCode >= 600) {
    $statusCode = 500;
}

// Log the error
$logger->error("Intentional error generated for testing", [
    'status_code' => $statusCode,
    'error_type' => $errorType,
    'endpoint' => '/error.php',
    'method' => $_SERVER['REQUEST_METHOD']
]);

// Update metrics BEFORE sending response
MetricsCollector::incrementHttpRequests($_SERVER['REQUEST_METHOD'], '/error.php', $statusCode);

$endTime = microtime(true);
MetricsCollector::recordHttpRequestDuration($_SERVER['REQUEST_METHOD'], '/error.php', $endTime - $startTime);

// Log API request
$duration = ($endTime - $startTime) * 1000;
$logger->logApiRequest($_SERVER['REQUEST_METHOD'], '/error.php', $statusCode, $duration);

// Set response code
http_response_code($statusCode);

// Send JSON response
header('Content-Type: application/json');

$response = [
    'status' => $statusCode,
    'message' => 'This is an intentional error for testing metrics',
    'error_type' => $errorType,
    'meta' => [
        'correlation_id' => $logger->getCorrelationId(),
        'timestamp' => date('c'),
        'duration_ms' => round($duration, 2)
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>

