<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Models\LotteryResults;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(0);

function curlGetContent($url, $returnHeader = false)
{
    if (strpos($url, 'file://') === 0) {
        $filePath = str_replace('file://', '', $url);
        $content = include $filePath;
        $content = json_encode($content);
        return $returnHeader ? ['header' => ['http_code' => 200], 'body' => $content] : $content;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, $returnHeader);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $header = $returnHeader ? curl_getinfo($ch) : [];
    curl_close($ch);
    return $returnHeader ? ['header' => $header, 'body' => $response] : $response;
}

function parseJsonMinhNgoc($content, $prefix)
{
    if (strpos($content, $prefix) !== false) {
        $content = str_replace([$prefix, ';'], '', $content);
        $content = preg_replace("/\{([a-zA-Z0-9]+)\:/", '{"$1":', $content);
        $content = preg_replace("/\,([a-zA-Z0-9]+)\:/", ',"$1":', $content);
        $content = preg_replace("/\:([0-9]+)\,/", ':"$1",', $content);
    }
    return json_decode($content);
}

function selectMinhNgocLiveDomains($startTime)
{
    $elapsed = time() - $startTime;
    if ($elapsed < 5) {
        return 'file://' . BASE_PATH . '/crons/mockdata/minh_ngoc_mock_1.php';
    } elseif ($elapsed < 100) {
        return 'file://' . BASE_PATH . '/crons/mockdata/minh_ngoc_mock_2.php';
    } elseif ($elapsed < 200) {
        return 'file://' . BASE_PATH . '/crons/mockdata/minh_ngoc_mock_3.php';
    } elseif ($elapsed < 300) {
        return 'file://' . BASE_PATH . '/crons/mockdata/minh_ngoc_mock_4.php';
    } else {
        return 'file://' . BASE_PATH . '/crons/mockdata/minh_ngoc_mock_5.php';
    }
}

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
        $lotteryResult->save();
    }

    $lotteryResults[$province->keyid] = $lotteryResult;
}

$start = time();
$flagDone = false;

while (!$flagDone && (time() - $start < 500)) {
    usleep(500000);
    $liveDomain = selectMinhNgocLiveDomains($start);
    $response = curlGetContent($liveDomain, true);

    if ($response['header']['http_code'] != 200 || empty($response['body'])) {
        echo "Failed to fetch data from $liveDomain\n";
        continue;
    }

    $data = parseJsonMinhNgoc($response['body'], 'kqxs.mn=');
    if (!is_object($data) || !isset($data->kq)) {
        echo "Invalid response format.\n";
        continue;
    }

    if ($data->run == 0) {
        continue;
    }

    $flagDone = true;
    $resultsData = ['status' => 0, 'results' => []];

    foreach ($data->kq as $provinceKey => $result) {
        if (!isset($lotteryResults[$provinceKey])) {
            echo "Province with keyid $provinceKey not found.\n";
            continue;
        }

        $lotteryResult = $lotteryResults[$provinceKey];
        $updated = false;
        $updateData = [];

        foreach ($fieldMapping as $jsonKey => $dbColumn) {
            if (isset($result->$jsonKey)) {
                $value = is_array($result->$jsonKey) ? json_encode($result->$jsonKey) : $result->$jsonKey;
                if ($value !== $lotteryResult->$dbColumn) {
                    $updateData[$dbColumn] = $value;
                    $updated = true;
                }
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
            if (empty($lotteryResult->$dbColumn)) {
                $isDone = false;
                $flagDone = false;
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

    try {
        $redis->set('xs_live_mn_' . date('Y-m-d'), json_encode($resultsData));
        echo "Data saved to Redis.\n";
    } catch (\Exception $e) {
        echo "Redis error: " . $e->getMessage() . "\n";
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
        $redis->set('xs_live_mn_' . date('Y-m-d'), json_encode($resultsData));
        echo "Final data saved to Redis.\n";
    } catch (\Exception $e) {
        echo "Redis error: " . $e->getMessage() . "\n";
    }
}

echo "Lottery data processing completed.\n";