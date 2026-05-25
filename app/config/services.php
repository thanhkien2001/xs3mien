<?php
declare(strict_types=1);

use Phalcon\Html\Escaper;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Session\Adapter\Stream as SessionAdapter;
use Phalcon\Session\Manager as SessionManager;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Encryption\Crypt;


/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

$di->setShared('eventsManager', function () {
    $eventsManager = new \Phalcon\Events\Manager();
    $eventsManager->attach('dispatch:beforeException', function ($event, $dispatcher, $exception) {
        if ($exception instanceof \Phalcon\Mvc\Dispatcher\Exception) {
            $dispatcher->forward([
                'namespace' => 'App\Controllers',
                'controller' => 'Errors',
                'action' => 'notFound'
            ]);
            
            return false;
        }
        if ($exception instanceof \Exception) {
            $statusCode = $exception->getCode();
            $action = 'notFound'; // Default
            if ($statusCode >= 400 && $statusCode < 600) {
                switch ($statusCode) {
                    case 401:
                        $action = 'unauthorized';
                        break;
                    case 403:
                        $action = 'forbidden';
                        break;
                    case 500:
                    case 502:
                    case 503:
                    case 504:
                        $action = 'serverError';
                        break;
                    default:
                        $action = 'notFound';
                }
            }
            
            $dispatcher->forward([
                'namespace' => 'App\Controllers',
                'controller' => 'Errors',
                'action' => $action
            ]);
            return false;
        }
        $dispatcher->forward([
            'namespace' => 'App\Controllers',
            'controller' => 'Errors',
            'action' => 'serverError'
        ]);
        return false;
    });
    
    $eventsManager->attach('router:beforeCheckRoutes', function ($event, $router) {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Tách path và query parameters
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);
        
        // Danh sách các API endpoints cần loại trừ
        $excludedPaths = [
            '/lottery',
            '/ws-auth', 
            '/get-token',
            '/set-cookie',
            '/get-token-value',
            '/api',
            '/keno/results',
            '/dudoan-load-more',
            '/dudoan/loadMoreSoiCau',
            '/admin/seo-fields',
            '/admin/seo-fields/edit/archive/province',
            '/admin/login',
            '/xo-so-mien-bac-xsmb/page',
            '/soicau/getData',
            '/page',
        ];
        
        // Kiểm tra vietlott endpoints
        $isVietlottApi = preg_match('/^\/vietlott\d+\/(byDate|results)(\/|$)/', $path);
        
        // Kiểm tra có phải API endpoint không
        $isExcludedApi = false;
        foreach ($excludedPaths as $excludedPath) {
            if (strpos($path, $excludedPath) === 0) {
                $isExcludedApi = true;
                break;
            }
        }
        
        if (substr($path, -5) !== '.html' && 
            !preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|xml|txt|php)$/i', $path) &&
            !$isExcludedApi && !$isVietlottApi && // Loại trừ tất cả API endpoints
            $path !== '/' &&
            substr($path, -1) !== '/') {
            // Tái tạo URL với query parameters
            $newUri = $path . '.html';
            if ($query) {
                $newUri .= '?' . $query;
            }
            header('Location: ' . $newUri, true, 301);
            exit;
        }
    });
    
    return $eventsManager;
});
$di->setShared('router', function () {
    $router = new \Phalcon\Mvc\Router();
    
    // Gắn Events Manager để xử lý auto-redirect
    $router->setEventsManager($this->get('eventsManager'));
    
    return $router;
});
$di->setShared('dispatcher', function () {
    $dispatcher = new \Phalcon\Mvc\Dispatcher();
    
    $dispatcher->setDefaultNamespace('App\Controllers');
    $dispatcher->setDefaultController('index');
    $dispatcher->setDefaultAction('index');
    
    $dispatcher->setEventsManager($this->get('eventsManager'));
    
    $dispatcher->setDefaultAction('notFound');
    
    return $dispatcher;
});

$di->setShared('crypt', function () {
    $crypt = new \Phalcon\Encryption\Crypt();
    $crypt->setKey('keyhttponly12hanshd2642jsnhcshc1');
    $crypt->setCipher('aes-256-cbc');
    return $crypt;
});

$di->setShared('cookies', function () {
    $cookies = new \Phalcon\Http\Response\Cookies();
    $cookies->useEncryption(false); 
    return $cookies;
});

$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);
    

    $view->registerEngines([
        '.volt' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions([
                'path' => $config->application->cacheDir,
                'separator' => '_',
                'compileAlways' => false,
                'stat' => false,
                'autoescape' => true,
                'optimize' => true,
            ]);

            return $volt;
        },
        '.phtml' => PhpEngine::class,
        ".html" => PhpEngine::class
    ]);

    return $view;
});

$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'port'     => $config->database->port,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    return new $class($params);
});

$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

$di->setShared('modelsManager', function () {
    $manager = new \Phalcon\Mvc\Model\Manager();
    
    $manager->setModelPrefix('');
    
    if ($this->has('eventsManager')) {
        $manager->setEventsManager($this->getEventsManager());
    }
    
    return $manager;
});

$di->set('flash', function () {
    $escaper = new Escaper();
    $flash = new Flash($escaper);
    $flash->setImplicitFlush(false);
    $flash->setCssClasses([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);

    return $flash;
});

$di->setShared('flashSession', function () {
    $escaper = new Escaper();
    $flashSession = new \Phalcon\Flash\Session($escaper);
    $flashSession->setCssClasses([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);
    return $flashSession;
});

$di->setShared('redis', function () {
    $redis = new Redis();
    try {
        $redis->connect('127.0.0.1', 6379);
        return $redis;
    } catch (\Exception $e) {
        throw new \Exception('Kết nối Redis thất bại: ' . $e->getMessage());
    }
});

$di->setShared('modelsCache', function () {
    $serializerFactory = new \Phalcon\Storage\SerializerFactory();

    $adapter = new \Phalcon\Cache\Adapter\Redis($serializerFactory, [
        'host' => '127.0.0.1',
        'port' => 6379,
        'index' => 0,
        'lifetime' => 86000,
        'prefix' => 'models_',
        'auth' => '',
        'serializer' => 'json',
        'persistent' => true,
        'timeout' => 2.0,
    ]);

    return new \Phalcon\Cache\Cache($adapter);
});
// HTML page cache (per-URI)
$di->setShared('viewCache', function () {
    $serializerFactory = new \Phalcon\Storage\SerializerFactory();
    $adapter = new \Phalcon\Cache\Adapter\Redis($serializerFactory, [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'index'      => 1,  // Sử dụng database 1 để tránh conflict với modelsCache
        'lifetime'   => 86000,
        'prefix'     => 'page_',
        'auth'       => '',
        'serializer' => 'none',
        'persistent' => true,
        'timeout'    => 2.0,
    ]);
    return new \Phalcon\Cache\Cache($adapter);
});

$di->setShared('session', function () {
    $session = new SessionManager();
    $sessionSavePath = BASE_PATH . '/app/cache/session';
    
    if (!is_dir($sessionSavePath)) {
        mkdir($sessionSavePath, 0777, true);
    }

    $files = new SessionAdapter([
        'savePath' => $sessionSavePath,
    ]);
    $session->setAdapter($files);
    
    $session->setOptions([
        'lifetime' => 1440,
        'cookie_lifetime' => 0,
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'gc_probability' => 1,
        'gc_divisor' => 1000,
    ]);
    
    $session->start();

    return $session;
});

$di->setShared('logger', function () {
    $logger = new \Phalcon\Logger\Logger('main');
    
    $logDir = BASE_PATH . '/app/cache/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $adapter = new \Phalcon\Logger\Adapter\Stream($logDir . '/app.log');
    $logger->addAdapter('file', $adapter);
    
    return $logger;
});

