<?php
require_once 'MetricsCollector.php';

// Generate multiple 500 errors in the SAME request
for ($i = 0; $i < 5; $i++) {
    MetricsCollector::incrementHttpRequests('GET', '/error.php', 500);
}
MetricsCollector::incrementHttpRequests('GET', '/test.php', 200);

// Output metrics immediately
header('Content-Type: text/plain; charset=utf-8');
echo MetricsCollector::getPrometheusMetrics();
?>

