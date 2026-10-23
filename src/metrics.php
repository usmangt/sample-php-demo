<?php
require_once 'Logger.php';
require_once 'MetricsCollector.php';

// Initialize logging
$logger = new Logger();
$startTime = microtime(true);

$logger->info('Metrics endpoint accessed');

// Set Prometheus content type
header('Content-Type: text/plain; charset=utf-8');

// Read page counter for backward compatibility
$counter_file = '/tmp/page_counter.txt';
$page_views = 1;

if (file_exists($counter_file)) {
    $page_views = (int)file_get_contents($counter_file);
}

// Increment and save counter
$page_views++;
file_put_contents($counter_file, $page_views);

// Basic PHP metrics for backward compatibility
echo "# HELP php_page_views_total Total page views\n";
echo "# TYPE php_page_views_total counter\n";
echo "php_page_views_total $page_views\n\n";

echo "# HELP php_memory_peak_bytes Peak memory usage in bytes\n";
echo "# TYPE php_memory_peak_bytes gauge\n";
echo "php_memory_peak_bytes " . memory_get_peak_usage(true) . "\n\n";

echo "# HELP php_last_access_timestamp Last access timestamp\n";
echo "# TYPE php_last_access_timestamp gauge\n";
echo "php_last_access_timestamp " . time() . "\n\n";

// Output comprehensive metrics from MetricsCollector
echo MetricsCollector::getPrometheusMetrics();

// Log metrics access
$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000;

MetricsCollector::incrementHttpRequests($_SERVER['REQUEST_METHOD'], '/metrics.php', 200);
MetricsCollector::recordHttpRequestDuration($_SERVER['REQUEST_METHOD'], '/metrics.php', $endTime - $startTime);

$logger->logApiRequest($_SERVER['REQUEST_METHOD'], '/metrics.php', 200, $duration);
?>