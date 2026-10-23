<?php
require_once 'Logger.php';
require_once 'MetricsCollector.php';

// Initialize logging
$logger = new Logger();
$startTime = microtime(true);

// Start session for user tracking
session_start();

$logger->info('Application started', [
    'session_id' => session_id(),
    'php_version' => phpversion(),
    'memory_limit' => ini_get('memory_limit')
]);

// Simple authentication check
$isLoggedIn = isset($_SESSION['user_id']);
$currentUser = $isLoggedIn ? $_SESSION['username'] : null;

// Handle logout
if (isset($_GET['logout'])) {
    $logger->info('User logout', ['user' => $currentUser]);
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple fake authentication
    $validUsers = [
        'admin' => 'password123',
        'demo' => 'demo123',
        'user' => 'user123'
    ];
    
    if (isset($validUsers[$username]) && $validUsers[$username] === $password) {
        $_SESSION['user_id'] = uniqid();
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        
        $logger->logAuthAttempt($username, true, ['user_id' => $_SESSION['user_id']]);
        MetricsCollector::incrementAuthAttempts('login', 'success');
        
        header('Location: index.php');
        exit;
    } else {
        $logger->logAuthAttempt($username, false, ['reason' => 'invalid_credentials']);
        MetricsCollector::incrementAuthAttempts('login', 'failure');
        $loginError = 'Invalid username or password';
    }
}

// Update metrics
MetricsCollector::setActiveSessions(1); // Simplified for demo
$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

MetricsCollector::incrementHttpRequests($_SERVER['REQUEST_METHOD'], '/', 200);
MetricsCollector::recordHttpRequestDuration($_SERVER['REQUEST_METHOD'], '/', $endTime - $startTime);

$logger->logApiRequest($_SERVER['REQUEST_METHOD'], '/', 200, $duration, $currentUser);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Observability Demo</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
        }
        .card h3 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .correlation-id {
            font-family: monospace;
            background: #e9ecef;
            padding: 5px;
            border-radius: 3px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 PHP Observability Demo</h1>
            <p>Logs, Metrics & Traces Correlation Platform</p>
            <div class="correlation-id">
                Correlation ID: <?php echo htmlspecialchars($logger->getCorrelationId()); ?>
            </div>
        </div>
        
        <div class="content">
            <?php if (!$isLoggedIn): ?>
                <div class="login-form">
                    <h2>Login</h2>
                    <p>Use demo credentials: <strong>admin/password123</strong>, <strong>demo/demo123</strong>, or <strong>user/user123</strong></p>
                    
                    <?php if (isset($loginError)): ?>
                        <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn">Login</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="success">
                    Welcome back, <strong><?php echo htmlspecialchars($currentUser); ?></strong>! 
                    <a href="?logout=1" class="btn btn-danger">Logout</a>
                </div>
                
                <div class="dashboard">
                    <div class="card">
                        <h3>📊 System Information</h3>
                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Memory Usage:</strong> <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?> MB</p>
                        <p><strong>Session ID:</strong> <code><?php echo session_id(); ?></code></p>
                        <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                    
                    <div class="card">
                        <h3>🔗 API Endpoints</h3>
                        <p>Test our instrumented APIs:</p>
                        <a href="api.php?action=users" class="btn">GET /api/users</a>
                        <a href="api.php?action=health" class="btn">GET /api/health</a>
                        <a href="test.php" class="btn">Run Tests</a>
                        <a href="info.php" class="btn">PHP Info</a>
                    </div>
                    
                    <div class="card">
                        <h3>📈 Observability Stack</h3>
                        <p>Access monitoring tools:</p>
                        <a href="http://localhost:3000" class="btn" target="_blank">Grafana Dashboard</a>
                        <a href="http://localhost:9090" class="btn" target="_blank">Prometheus</a>
                        <a href="http://localhost:3100" class="btn" target="_blank">Loki</a>
                        <a href="metrics.php" class="btn">View Metrics</a>
                    </div>
                    
                    <div class="card">
                        <h3>🔍 Current Request</h3>
                        <p><strong>Correlation ID:</strong> <code><?php echo $logger->getCorrelationId(); ?></code></p>
                        <p><strong>User Agent:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?></p>
                        <p><strong>IP Address:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?></p>
                        <p><strong>Response Time:</strong> <?php echo round($duration, 2); ?> ms</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple client-side logging for future Faro integration
        console.log('PHP Observability Demo loaded', {
            correlationId: '<?php echo $logger->getCorrelationId(); ?>',
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        });
    </script>
</body>
</html>