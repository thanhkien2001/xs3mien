<?php
/**
 * Script test để populate Redis với dữ liệu live mẫu
 * Chạy: php crons/test_live_data.php
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$redis = $di->get('redis');
$today = date('Y-m-d');

echo "=== TEST LIVE DATA POPULATOR ===\n";
echo "Date: $today\n\n";

// ===== XSMN (Miền Nam) =====
echo "1. Populating XSMN data...\n";
$xsmnData = [
    'status' => 1,
    'results' => [
        '1' => [ 
            '8' => 18,
            '7' => 167,
            '6' => ['6157', '4480', '4282'],
            '5' => 5006,
            '4' => ['*', '*****', '52836', '*', '****', '*', '*'],
            '3' => ['64684', '21283'],
            '2' => 40482,
            '1' => 94006,
            '0' => 862490
        ],
        '19' => [  
            '8' => 25,
            '7' => '029',
            '6' => ['4119', '2071', '6718'],
            '5' => 2797,
            '4' => ['89017', '13579', '+++++', '53781', '++++++', '12345', '67890'],
            '3' => ['12345', '++++'],
            '2' => 12345,
            '1' => '+++',
            '0' => '++'
        ],
        '20' => [
            '8' => 33,
            '7' => 839,
            '6' => ['1850', '9777', '7791'],
            '5' => 8946,
            '4' => ['15365', '++++', '++++', '93244', '****', '12345', '67890'],
            '3' => ['12345', '67890'],
            '2' => 12345,
            '1' => 67890,
            '0' => 123456
        ],
        '21' => [ 
            '8' => 33,
            '7' => 839,
            '6' => ['1850', '9777', '7791'],
            '5' => 8946,
            '4' => ['15365', '++++', '++++', '93244', '****', '12345', '67890'],
            '3' => ['12345', '67890'],
            '2' => 12345,
            '1' => 67890,
            '0' => 123456
        ]
    ]
];

try {
    $redis->setex('xs_live_mn_' . $today, 3600, json_encode($xsmnData, JSON_UNESCAPED_UNICODE));
    echo "   ✅ XSMN data saved to Redis key: xs_live_mn_$today\n";
} catch (\Exception $e) {
    echo "   ❌ XSMN Redis error: " . $e->getMessage() . "\n";
}

// ===== XSMT (Miền Trung) =====
echo "\n2. Populating XSMT data...\n";
$xsmtData = [
    'status' => 1,
    'results' => [
        '30' => [ 
            '8' => 33,
            '7' => 839,
            '6' => ['1850', '9777', '7791'],
            '5' => 8946,
            '4' => ['15365', '++++', '++++', '93244', '****', '12345', '67890'],
            '3' => ['12345', '67890'],
            '2' => 12345,
            '1' => 67890,
            '0' => 123456
        ],
        '37' => [ 
            '8' => 97,
            '7' => 881,
            '6' => ['2903', '4687', '8132'],
            '5' => 8519,
            '4' => ['32193', '82718', '****', '50116', '59568', '13258', '85590'],
            '3' => ['****', '71854'],
            '2' => 44309,
            '1' => 78478,
            '0' => 308821
        ],
        '38' => [ 
            '8' => 97,
            '7' => 881,
            '6' => ['2903', '4687', '8132'],
            '5' => 8519,
            '4' => ['32193', '82718', '****', '50116', '59568', '13258', '85590'],
            '3' => ['****', '71854'],
            '2' => 44309,
            '1' => 78478,
            '0' => 308821
        ],
    ]
];

try {
    $redis->setex('xs_live_mt_' . $today, 3600, json_encode($xsmtData, JSON_UNESCAPED_UNICODE));
    echo "   ✅ XSMT data saved to Redis key: xs_live_mt_$today\n";
} catch (\Exception $e) {
    echo "   ❌ XSMT Redis error: " . $e->getMessage() . "\n";
}

// ===== XSMB (Miền Bắc) =====
echo "\n3. Populating XSMB data...\n";
$xsmbData = [
    'status' => 1,
    'results' => [
        '50' => [  // Database ID của Nam Định (thứ 7)
            '7' => ['12', '43', '++', '12'],
            '6' => ['32193', '+++++++', '77279'],
            '5' => 8519,
            '4' => ['32193', '82718', '77279', '50116'],
            '3' => ['08329', '71854','*****','*****','*****', '+++'],
            '2' => ['08329', '71854'],
            '1' => 78478,
            '0' => 308821
        ]
    ]
];

try {
    $redis->setex('xs_live_mb_' . $today, 3600, json_encode($xsmbData, JSON_UNESCAPED_UNICODE));
    echo "   ✅ XSMB data saved to Redis key: xs_live_mb_$today\n";
} catch (\Exception $e) {
    echo "   ❌ XSMB Redis error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
echo "Bây giờ bạn có thể:\n";
echo "1. Mở trang live: https://soicau247.com/truc-tiep-ket-qua-xo-so-mien-nam-ttxsmn-xsmn.html\n";
echo "2. Hoặc trang kết quả: https://soicau247.com/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html\n";
echo "3. Mở Developer Console (F12) để xem logs\n";
echo "4. Kết quả sẽ hiển thị ngay lập tức!\n\n";

// Verify data
echo "=== VERIFY DATA ===\n";
$verifyKeys = [
    'xs_live_mn_' . $today,
    'xs_live_mt_' . $today,
    'xs_live_mb_' . $today
];

foreach ($verifyKeys as $key) {
    $data = $redis->get($key);
    if ($data) {
        $decoded = json_decode($data, true);
        $provinceCount = count($decoded['results'] ?? []);
        echo "✅ $key: $provinceCount provinces\n";
    } else {
        echo "❌ $key: NOT FOUND\n";
    }
}

