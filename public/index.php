<?php

declare(strict_types=1);

use Phalcon\Di\FactoryDefault;

// =========================
// Error reporting theo môi trường
// =========================
if (getenv('APP_ENV') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// =========================
// Output buffering
// =========================
ob_start();

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

try {
    // Load .env nếu có
    if (file_exists(BASE_PATH . '/.env')) {
        $env = parse_ini_file(BASE_PATH . '/.env');
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }

    // Dependency Injector
    $di = new FactoryDefault();

    // Load services, config, autoload, router
    include APP_PATH . '/config/services.php';
    $config = $di->getConfig();

    include BASE_PATH . '/vendor/autoload.php';
    include APP_PATH . '/config/loader.php';
    include APP_PATH . '/config/router.php';

    // =========================
    // Handle CORS preflight
    // =========================
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Length: 0');
        header('Content-Type: text/plain');
        exit;
    }

    // CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');

    // =========================
    // Handle request
    // =========================
    $application = new \Phalcon\Mvc\Application($di);

    $response = $application->handle($_SERVER['REQUEST_URI']);

    // Extra headers
    $response->setHeader('X-Powered-By', 'Phalcon Framework');
    $response->setHeader('X-Runtime', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
    $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
    $response->setHeader('Pragma', 'no-cache');
    $response->setHeader('Expires', '0');
    $response->setHeader('Vary', 'Accept-Encoding');

    echo $response->getContent();
} catch (\Throwable $e) {
    if (getenv('APP_ENV') === 'production') {
        echo 'An error occurred. Please try again later.';
    } else {
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_end_flush();
