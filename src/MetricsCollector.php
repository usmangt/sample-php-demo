<?php

class MetricsCollector 
{
    private static $metrics = null;
    private static $metricsFile = '/var/log/app/php_metrics.json';
    
    private static function loadMetrics() 
    {
        if (self::$metrics !== null) {
            return;
        }
        
        // Try to load from file
        if (file_exists(self::$metricsFile)) {
            $data = file_get_contents(self::$metricsFile);
            $decoded = json_decode($data, true);
            if ($decoded !== null) {
                self::$metrics = $decoded;
                return;
            }
        }
        
        // Initialize empty metrics
        self::$metrics = [
            'http_requests_total' => [],
            'http_request_duration_seconds' => [],
            'auth_attempts_total' => [],
            'active_sessions' => 0,
            'memory_usage_bytes' => 0,
            'database_queries_total' => []
        ];
    }
    
    private static function saveMetrics() 
    {
        if (self::$metrics === null) {
            return;
        }
        
        // Save to file atomically
        $tempFile = self::$metricsFile . '.tmp';
        file_put_contents($tempFile, json_encode(self::$metrics, JSON_PRETTY_PRINT), LOCK_EX);
        rename($tempFile, self::$metricsFile);
    }
    
    public static function incrementHttpRequests($method, $endpoint, $statusCode) 
    {
        self::loadMetrics();
        $key = "{$method}|{$endpoint}|{$statusCode}";
        if (!isset(self::$metrics['http_requests_total'][$key])) {
            self::$metrics['http_requests_total'][$key] = 0;
        }
        self::$metrics['http_requests_total'][$key]++;
        self::saveMetrics();
    }
    
    public static function recordHttpRequestDuration($method, $endpoint, $durationSeconds) 
    {
        self::loadMetrics();
        $key = "{$method}|{$endpoint}";
        if (!isset(self::$metrics['http_request_duration_seconds'][$key])) {
            self::$metrics['http_request_duration_seconds'][$key] = [];
        }
        // Keep only last 1000 durations to avoid memory issues
        self::$metrics['http_request_duration_seconds'][$key][] = $durationSeconds;
        if (count(self::$metrics['http_request_duration_seconds'][$key]) > 1000) {
            array_shift(self::$metrics['http_request_duration_seconds'][$key]);
        }
        self::saveMetrics();
    }
    
    public static function incrementAuthAttempts($type, $result) 
    {
        self::loadMetrics();
        $key = "{$type}|{$result}";
        if (!isset(self::$metrics['auth_attempts_total'][$key])) {
            self::$metrics['auth_attempts_total'][$key] = 0;
        }
        self::$metrics['auth_attempts_total'][$key]++;
        self::saveMetrics();
    }
    
    public static function setActiveSessions($count) 
    {
        self::loadMetrics();
        self::$metrics['active_sessions'] = $count;
        self::saveMetrics();
    }
    
    public static function updateMemoryUsage() 
    {
        self::loadMetrics();
        self::$metrics['memory_usage_bytes'] = memory_get_usage(true);
    }
    
    public static function incrementDatabaseQueries($operation, $table = 'unknown') 
    {
        self::loadMetrics();
        $key = "{$operation}|{$table}";
        if (!isset(self::$metrics['database_queries_total'][$key])) {
            self::$metrics['database_queries_total'][$key] = 0;
        }
        self::$metrics['database_queries_total'][$key]++;
        self::saveMetrics();
    }
    
    public static function getPrometheusMetrics() 
    {
        self::loadMetrics();
        self::updateMemoryUsage();
        
        $output = [];
        
        // HTTP requests counter
        $output[] = "# HELP http_requests_total Total HTTP requests";
        $output[] = "# TYPE http_requests_total counter";
        foreach (self::$metrics['http_requests_total'] as $key => $value) {
            list($method, $endpoint, $statusCode) = explode('|', $key);
            $output[] = "http_requests_total{method=\"{$method}\",endpoint=\"{$endpoint}\",status_code=\"{$statusCode}\"} {$value}";
        }
        
        // HTTP request duration histogram (simplified)
        $output[] = "# HELP http_request_duration_seconds HTTP request duration";
        $output[] = "# TYPE http_request_duration_seconds histogram";
        foreach (self::$metrics['http_request_duration_seconds'] as $key => $durations) {
            list($method, $endpoint) = explode('|', $key);
            $count = count($durations);
            $sum = array_sum($durations);
            $output[] = "http_request_duration_seconds_count{method=\"{$method}\",endpoint=\"{$endpoint}\"} {$count}";
            $output[] = "http_request_duration_seconds_sum{method=\"{$method}\",endpoint=\"{$endpoint}\"} {$sum}";
        }
        
        // Authentication attempts
        $output[] = "# HELP auth_attempts_total Authentication attempts";
        $output[] = "# TYPE auth_attempts_total counter";
        foreach (self::$metrics['auth_attempts_total'] as $key => $value) {
            list($type, $result) = explode('|', $key);
            $output[] = "auth_attempts_total{type=\"{$type}\",result=\"{$result}\"} {$value}";
        }
        
        // Active sessions gauge
        $output[] = "# HELP active_sessions Number of active sessions";
        $output[] = "# TYPE active_sessions gauge";
        $output[] = "active_sessions " . self::$metrics['active_sessions'];
        
        // Memory usage
        $output[] = "# HELP php_memory_usage_bytes PHP memory usage in bytes";
        $output[] = "# TYPE php_memory_usage_bytes gauge";
        $output[] = "php_memory_usage_bytes " . self::$metrics['memory_usage_bytes'];
        
        // Database queries
        $output[] = "# HELP database_queries_total Database queries executed";
        $output[] = "# TYPE database_queries_total counter";
        foreach (self::$metrics['database_queries_total'] as $key => $value) {
            list($operation, $table) = explode('|', $key);
            $output[] = "database_queries_total{operation=\"{$operation}\",table=\"{$table}\"} {$value}";
        }
        
        return implode("\n", $output) . "\n";
    }
}
