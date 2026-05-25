<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Models\LotteryResults;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(0);

$fieldMapping = [
    '8' => 'eighth_prize',
    '7' => 'seventh_prize',
    '6' => 'sixth_prize',
    '5' => 'fifth_prize',
    '4' => 'fourth_prize',
    '3' => 'third_prize',
    '2' => 'second_prize',
    '1' => 'first_prize',
    '0' => 'special_prize'
];

// Sử dụng Redis từ DI
$redis = $di->get('redis');

$today = date('N');
$provinces = \App\Models\Provinces::find([
    'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
    'bind' => [
        'region' => 'XSMN',
        'draw_days' => '%' . $today . '%'
    ]
]);

if (count($provinces) === 0) {
    echo "No provinces scheduled for today (day $today).\n";
    exit;
}

$lotteryResults = [];
$drawDate = date('Y-m-d');
$drawType = 'XSMN';

foreach ($provinces as $province) {
    $lotteryResult = LotteryResults::findFirst([
        'conditions' => 'province_id = :province_id: AND draw_date = :draw_date: AND draw_type = :draw_type:',
        'bind' => [
            'province_id' => $province->id,
            'draw_date' => $drawDate,
            'draw_type' => $drawType
        ]
    ]);

    if (!$lotteryResult) {
        $lotteryResult = new LotteryResults();
        $dataToAssign = [
            'province_id' => $province->id,
            'draw_date' => $drawDate,
            'draw_type' => $drawType,
            'status' => 0
        ];
        foreach ($fieldMapping as $dbColumn) {
            $dataToAssign[$dbColumn] = '';
        }
        $lotteryResult->assign($dataToAssign);
        if (!$lotteryResult->save()) {
            echo "Failed to initialize result for province {$province->keyid}: " . implode(', ', $lotteryResult->getMessages()) . "\n";
        }
    }

    $lotteryResults[$province->keyid] = $lotteryResult;
}

$start = time();
$flagDone = false;
$lastData = null;


while (time() - $start < 2100) { // Chạy tối đa 35 phút
    usleep(500000); // Đợi 0.5 giây
    $liveDomain = selectMinhNgocLiveDomains();
    $fetchUrl = $liveDomain . '/xstt/js_m1.js?_=' . time();
    echo "Fetching from $fetchUrl at " . (time() - $start) . " seconds\n";
    $response = curlGetContent($fetchUrl);

    if (strpos($response['header']['http_code'], '200') === false || $response['body'] == '') {
        echo "Failed to fetch data from $fetchUrl\n";
        continue;
    }

    $data = parseJsonMinhNgoc($response['body'], 'kqxs.mn=');
    if (!is_object($data) || !isset($data->kq)) {
        echo "Invalid response format from $fetchUrl. Raw Body: " . $response['body'] . "\n";
        continue;
    }

    if ($data->run == 0) {
        echo "Data not ready yet.\n";
        continue;
    }

    $resultsData = ['status' => 0, 'results' => []];
    $allProvincesDone = true;

    foreach ($data->kq as $provinceKey => $result) {
        if (!isset($lotteryResults[$provinceKey])) {
            echo "Province with keyid $provinceKey not found in scheduled provinces.\n";
            continue;
        }

        $lotteryResult = $lotteryResults[$provinceKey];
        $updated = false;
        $updateData = [];

        foreach ($fieldMapping as $jsonKey => $dbColumn) {
            if (isset($result->$jsonKey) && $result->$jsonKey !== $lotteryResult->$dbColumn) {
                $value = is_array($result->$jsonKey) ? json_encode($result->$jsonKey) : $result->$jsonKey;
                $updateData[$dbColumn] = $value;
                $updated = true;
            }
        }

        if ($updated) {
            $lotteryResult->assign($updateData);
            if (!$lotteryResult->save()) {
                echo "Failed to save result for province $provinceKey: " . implode(', ', $lotteryResult->getMessages()) . "\n";
            } else {
                echo "Updated result for province $provinceKey.\n";
            }
        }

        $isDone = true;
        foreach ($fieldMapping as $dbColumn) {
            $value = $lotteryResult->$dbColumn;
            if (!isValidResult(json_decode($value, true) ?: $value)) {
                $isDone = false;
                $allProvincesDone = false;
                break;
            }
        }

        if ($isDone) {
            $lotteryResult->status = 1;
        }

        $resultsData['results'][$provinceKey] = [];
        foreach ($fieldMapping as $jsonKey => $dbColumn) {
            if (!empty($lotteryResult->$dbColumn)) {
                $resultsData['results'][$provinceKey][$jsonKey] = json_decode($lotteryResult->$dbColumn, true) ?: $lotteryResult->$dbColumn;
            }
        }
    }

    // Chỉ lưu vào Redis nếu dữ liệu thay đổi
    $currentData = json_encode($resultsData);
    if ($currentData !== $lastData) {
        try {
            $redis->setex('xs_live_mn_' . date('Y-m-d'), 3600, $currentData); // TTL 24 giờ
            echo "Data saved to Redis.\n";
            $lastData = $currentData;
        } catch (\Exception $e) {
            echo "Redis error: " . $e->getMessage() . "\n";
        }
    }

    if ($allProvincesDone) {
        echo "All provinces have complete and valid data. Breaking loop.\n";
        $flagDone = true;
        break;
    }
}

if ($flagDone) {
    foreach ($lotteryResults as $lotteryResult) {
        if ($lotteryResult->status != 1) {
            $lotteryResult->status = 1;
            $lotteryResult->save();
        }
    }
    try {
        $resultsData['status'] = 1;
        $redis->setex('xs_live_mn_' . date('Y-m-d'), 3600, json_encode($resultsData));
        echo "Final data saved to Redis.\n";
    } catch (\Exception $e) {
        echo "Redis error: " . $e->getMessage() . "\n";
    }
    
    // ===== XÓA VÀ CẬP NHẬT CACHE =====
    // Gọi cache manager để xử lý cache
    require_once __DIR__ . '/cache_update_mn.php';
    $cacheManager = new CacheManagerXSMN();
    $cacheManager->processCache();
}

echo "Lottery data processing completed.\n";
