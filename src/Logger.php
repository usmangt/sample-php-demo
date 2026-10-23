<?php

class Logger 
{
    private $logFile;
    private $correlationId;
    private $traceId = null;
    
    public function __construct($logFile = '/var/log/app/application.log') 
    {
        $this->logFile = $logFile;
        $this->correlationId = $this->generateCorrelationId();
        
        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function getCorrelationId() 
    {
        return $this->correlationId;
    }
    
    public function setCorrelationId($correlationId) 
    {
        $this->correlationId = $correlationId;
    }

    public function getTraceId()
    {
        return $this->traceId;
    }

    /** Attach trace_id to all following log lines for this request. */
    public function setTraceId($traceId)
    {
        $this->traceId = $traceId;
    }
    
    private function generateCorrelationId() 
    {
        return uniqid('req_', true);
    }
    
    public function info($message, $context = []) 
    {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = []) 
    {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = []) 
    {
        $this->log('WARNING', $message, $context);
    }
    
    public function debug($message, $context = []) 
    {
        $this->log('DEBUG', $message, $context);
    }
    
    public function logApiRequest($method, $endpoint, $statusCode, $durationMs, $userId = null, $context = []) 
    {
        $this->info('API Request', array_merge([
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'user_id' => $userId,
            'type' => 'api_request'
        ], $context));
    }
    
    public function logAuthAttempt($username, $success, $context = []) 
    {
        $level = $success ? 'INFO' : 'WARNING';
        $this->log($level, 'Authentication attempt', array_merge([
            'username' => $username,
            'success' => $success,
            'type' => 'auth_attempt',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $context));
    }
    
    private function log($level, $message, $context = []) 
    {
        $logEntry = [
            'timestamp' => date('c'), // ISO 8601 format
            'level' => $level,
            'message' => $message,
            'correlation_id' => $this->correlationId,
            'service' => 'php-app',
            'context' => $context
        ];

        // Present once beginTrace() has run — enables log → Tempo correlation
        if (!empty($this->traceId)) {
            $logEntry['trace_id'] = $this->traceId;
        }
        
        // Add request information if available
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $logEntry['request'] = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
        }
        
        $jsonLog = json_encode($logEntry, JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($this->logFile, $jsonLog, FILE_APPEND | LOCK_EX);
    }
}
