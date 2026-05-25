<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Models\KenoResults;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

class KenoSimpleScraper
{
    private $di;
    private $baseUrl = 'https://vesominhngoc.com.vn/xstt/dai.php?id=119';
    private $dataFile = '';
    private $cache;

    public function __construct()
    {
        $this->di = Phalcon\Di\Di::getDefault();
        $this->dataFile = __DIR__ . '/../public/cache/keno_data.json';
        $this->cache = $this->di->get('modelsCache');
    }

    public function scrape()
    {
        echo "\n🚀 BẮT ĐẦU CÀO DỮ LIỆU KENO\n";
        echo "📅 Thời gian: " . date('Y-m-d H:i:s') . "\n";
        echo "🌐 URL: {$this->baseUrl}\n";
        echo "⏰ Sẽ chạy trong 5 phút (300 giây) hoặc dừng khi có dữ liệu mới\n";

        $startTime = time();
        $maxRunTime = 300;
        $checkInterval = 7; 
        $lastData = null;

        while (time() - $startTime < $maxRunTime) {
            $elapsed = time() - $startTime;
            echo "\n🔄 Lần thử: {$elapsed} giây / {$maxRunTime} giây\n";
            
            try {
                $rawData = $this->fetchData();
                if (!$rawData) {
                    echo "❌ Không lấy được dữ liệu từ API\n";
                    sleep($checkInterval);
                    continue;
                }

                $parsedData = $this->parseData($rawData);
                if (!$parsedData) {
                    echo "❌ Không parse được dữ liệu\n";
                    sleep($checkInterval);
                    continue;
                }

                $currentDataHash = md5(json_encode($parsedData));
                if ($currentDataHash === $lastData) {
                    echo "ℹ️ Dữ liệu chưa thay đổi, tiếp tục chờ...\n";
                    sleep($checkInterval);
                    continue;
                }
                $lastData = $currentDataHash;

                if (!$this->isNewDraw($parsedData)) {
                    echo "ℹ️ Kỳ quay đã tồn tại trong database, tiếp tục chờ...\n";
                    sleep($checkInterval);
                    continue;
                }

                if (!$this->isValidData($parsedData)) {
                    echo "⚠️ Dữ liệu không hợp lệ (có dấu * hoặc +), tiếp tục chờ...\n";
                    sleep($checkInterval);
                    continue;
                }
                $this->updateDataFile($parsedData);

                echo "✅ Cập nhật dữ liệu Keno thành công!\n";
                echo "⏰ Tổng thời gian chạy: " . (time() - $startTime) . " giây\n";
                return true;

            } catch (Exception $e) {
                echo "❌ Lỗi: " . $e->getMessage() . "\n";
                sleep($checkInterval);
                continue;
            }
        }

        // Hết thời gian 5 phút
        echo "\n⏰ Đã hết thời gian 5 phút, dừng scraper\n";
        echo "ℹ️ Không tìm thấy dữ liệu mới trong thời gian chờ\n";
        return false;
    }
    private function fetchData()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: vi-VN,vi;q=0.8,en-US;q=0.5,en;q=0.3',
                'Connection: keep-alive',
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode == 200) {
            return $response;
        }

        echo "❌ HTTP Error: $httpCode\n";
        return false;
    }

    private function parseData($rawData)
    {
        try {
            preg_match('/kqxs\[119\]=(.+?);/', $rawData, $matches);
            if (!isset($matches[1])) {
                echo "❌ Không tìm thấy dữ liệu trong response\n";
                return false;
            }

            $jsonData = $matches[1];
            $data = json_decode($jsonData, true);

            if (!$data) {
                echo "❌ Không parse được JSON data\n";
                return false;
            }

            $drawDateTime = $this->calculateDrawDateTime($data['date'], $data['time']);

            $parsedData = [
                'draw_number' => $data['lv'],
                'draw_date' => $drawDateTime,   
                'draw_time' => $data['time'],
                'numbers' => $data['1'],
                'even_count' => (int)$data['info']['chan'],
                'odd_count' => (int)$data['info']['le'],
                'large_count' => (int)$data['info']['lon'],
                'small_count' => (int)$data['info']['nho'],
                'total' => (int)$data['info']['tong'],
                'live' => $data['live'] === '1',
                'timestamp' => time(),
                'last_updated' => date('Y-m-d H:i:s')
            ];

            return $parsedData;

        } catch (Exception $e) {
            echo "❌ Lỗi parse data: " . $e->getMessage() . "\n";
            return false;
        }
    }
    private function calculateDrawDateTime($dateString, $timeString)
    {
        try {
            $kenoTimes = [
                '06:00','06:08','06:16','06:24','06:32','06:40','06:48','06:56',
                '07:04','07:12','07:20','07:28','07:36','07:44','07:52',
                '08:00','08:08','08:16','08:24','08:32','08:40','08:48','08:56',
                '09:04','09:12','09:20','09:28','09:36','09:44','09:52',
                '10:00','10:08','10:16','10:24','10:32','10:40','10:48','10:56',
                '11:04','11:12','11:20','11:28','11:36','11:44','11:52',
                '12:00','12:08','12:16','12:24','12:32','12:40','12:48','12:56',
                '13:04','13:12','13:20','13:28','13:36','13:44','13:52',
                '14:00','14:08','14:16','14:24','14:32','14:40','14:48','14:56',
                '15:04','15:12','15:20','15:28','15:36','15:44','15:52',
                '16:00','16:08','16:16','16:24','16:32','16:40','16:48','16:56',
                '17:04','17:12','17:20','17:28','17:36','17:44','17:52',
                '18:00','18:08','18:16','18:24','18:32','18:40','18:48','18:56',
                '19:04','19:12','19:20','19:28','19:36','19:44','19:52',
                '20:00','20:08','20:16','20:24','20:32','20:40','20:48','20:56',
                '21:04','21:12','21:20','21:28','21:36','21:44','21:52'
            ];
            $convertedDate = $this->convertDateFormat($dateString);
            
            $currentTime = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
            $currentTimeStr = $currentTime->format('H:i');
            
            if (!empty($timeString)) {
                $drawTime = $timeString;
            } else {
                $drawTime = $this->findNearestDrawTime($kenoTimes, $currentTimeStr);
            }
            
            $drawDateTime = $convertedDate . ' ' . $drawTime . ':00';
            
            echo "🕐 Tính toán giờ quay: {$dateString} {$timeString} → {$drawDateTime}\n";
            
            return $drawDateTime;
            
        } catch (Exception $e) {
            echo "❌ Lỗi tính toán datetime: " . $e->getMessage() . "\n";
            $convertedDate = $this->convertDateFormat($dateString);
            return $convertedDate . ' 12:00:00';
        }
    }

    private function findNearestDrawTime($kenoTimes, $currentTime)
    {
        $currentMinutes = $this->timeToMinutes($currentTime);
        $nearestTime = '12:00'; // Default
        $minDiff = PHP_INT_MAX;
        
        foreach ($kenoTimes as $time) {
            $timeMinutes = $this->timeToMinutes($time);
            $diff = abs($timeMinutes - $currentMinutes);
            
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearestTime = $time;
            }
        }
        
        return $nearestTime;
    }

    private function timeToMinutes($timeStr)
    {
        list($hours, $minutes) = explode(':', $timeStr);
        return (int)$hours * 60 + (int)$minutes;
    }
    private function isNewDraw($data)
    {
        try {
            $drawNumber = $data['draw_number'];
            $drawDateTime = $data['draw_date'];
            
            echo "🔍 Kiểm tra kỳ quay: {$drawNumber} - {$drawDateTime}\n";
            
            $existing = KenoResults::findFirst([
                'conditions' => 'draw_number = :draw_number: AND draw_date = :draw_date:',
                'bind' => [
                    'draw_number' => $drawNumber,
                    'draw_date' => $drawDateTime
                ]
            ]);
            
            if ($existing) {
                echo "ℹ️ Kỳ quay {$drawNumber} đã tồn tại trong database\n";
                return false;
            }
            
            echo "✅ Kỳ quay {$drawNumber} là mới, tiếp tục xử lý\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Lỗi kiểm tra kỳ quay: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function isValidData($data)
    {
        echo "🔍 Kiểm tra dữ liệu có hợp lệ...\n";
        
        // Kiểm tra numbers có chứa dấu * hoặc + không
        if (isset($data['numbers']) && is_array($data['numbers'])) {
            foreach ($data['numbers'] as $number) {
                if (strpos($number, '*') !== false || strpos($number, '+') !== false) {
                    echo "❌ Tìm thấy dấu đặc biệt trong numbers: {$number}\n";
                    return false; // Có dấu đặc biệt = không hợp lệ
                }
            }
        }
        $fieldsToCheck = ['draw_number', 'draw_date', 'draw_time'];
        foreach ($fieldsToCheck as $field) {
            if (isset($data[$field]) && (strpos($data[$field], '*') !== false || strpos($data[$field], '+') !== false)) {
                echo "❌ Tìm thấy dấu đặc biệt trong {$field}: {$data[$field]}\n";
                return false;
            }
        }

        if (isset($data['numbers']) && count($data['numbers']) !== 20) {
            echo "❌ Số lượng numbers không đúng: " . count($data['numbers']) . " (cần 20)\n";
            return false;
        }

        if (isset($data['even_count']) && isset($data['odd_count'])) {
            if ($data['even_count'] + $data['odd_count'] !== 20) {
                echo "❌ Tổng chẵn + lẻ không đúng: " . ($data['even_count'] + $data['odd_count']) . " (cần 20)\n";
                return false;
            }
        }

        echo "✅ Dữ liệu hợp lệ\n";
        return true;
    }
    private function updateDataFile($data)
    {
        try {
            $cacheDir = dirname($this->dataFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            $nextDraw = $this->calculateNextDraw();
            $countdown = $this->calculateCountdown();

            $fullData = [
                'timestamp' => $data['timestamp'],
                'live' => $data['live'],
                'current_draw' => $data,
                'next_draw' => $nextDraw,
                'countdown' => $countdown,
                'last_updated' => $data['last_updated']
            ];

            $jsonData = json_encode($fullData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            if (file_put_contents($this->dataFile, $jsonData)) {
                echo "✅ Đã cập nhật file: {$this->dataFile}\n";
                
                $this->saveToDatabase($data);
                
                return true;
            } else {
                echo "❌ Không thể ghi file\n";
                return false;
            }

        } catch (Exception $e) {
            echo "❌ Lỗi update file: " . $e->getMessage() . "\n";
            return false;
        }
    }
    private function convertDateFormat($dateString)
    {
        try {
            $date = DateTime::createFromFormat('d-m-Y', $dateString);
            if ($date) {
                return $date->format('Y-m-d');
            }
            
            $timestamp = strtotime($dateString);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
            
            echo "⚠️ Không thể convert date: {$dateString}\n";
            return $dateString;     
            
        } catch (Exception $e) {
            echo "❌ Lỗi convert date: " . $e->getMessage() . "\n";
            return $dateString;
        }
    }

    /**
     * Lưu vào database
     */
    private function saveToDatabase($data)
    {
        try {
            echo "💾 Lưu vào database...\n";
            $drawDateTime = $data['draw_date'];
            echo "📅 Lưu với datetime: {$drawDateTime}\n";
            
            $kenoResult = new KenoResults();
            $kenoResult->setDrawNumber($data['draw_number']);
            $kenoResult->setDrawDate($drawDateTime); // Lưu datetime đầy đủ
            $kenoResult->setNumbers(implode(',', $data['numbers']));
            $kenoResult->setEvenCount($data['even_count']);
            $kenoResult->setOddCount($data['odd_count']);
            $kenoResult->setLargeCount($data['large_count']);
            $kenoResult->setSmallCount($data['small_count']);
            $kenoResult->setCreatedAt($data['last_updated']);

            if ($kenoResult->save()) {
                echo "✅ Đã lưu kỳ quay {$data['draw_number']} vào database với datetime: {$drawDateTime}\n";
                
                // 🔥 CLEAR CACHE sau khi save thành công
                $this->clearKenoCache();
                
                return true;
            } else {
                echo "❌ Lỗi khi lưu database: " . implode(', ', $kenoResult->getMessages()) . "\n";
                return false;
            }

        } catch (Exception $e) {
            echo "❌ Lỗi database: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Clear cache sau khi có dữ liệu mới (theo pattern các cron khác)
     */
    private function clearKenoCache()
    {
        try {
            echo "🧹 Đang clear cache Keno...\n";
            
            // Cache keys từ KenoController
            $cacheKeys = [
                'models_KENO_recent_' . date('Y-m-d-H-i'),
                'models_KENO_stats_' . date('Y-m-d-H'),
            ];
            
            $redis = $this->di->get('redis');
            foreach ($cacheKeys as $key) {
                // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
                $this->cache->delete($key);
                $redis->del($key);
                echo "🗑️ Đã xóa cache: {$key}\n";
            }
            
            // Xóa cache theo prefix pattern
            $prefixes = [
                'models_KENO_recent_',
                'models_KENO_stats_',
            ];
            
            foreach ($prefixes as $prefix) {
                $this->deleteCacheByPrefix($prefix);
            }
            
            echo "✅ Cache Keno cleared successfully\n";
            
            // Update cache bằng URL
            $this->updateCacheByUrls();
            
        } catch (Exception $e) {
            echo "⚠️ Cache clear error: " . $e->getMessage() . "\n";
        }
    }
    private function updateCacheByUrls()
    {
        try {
            echo "🔄 Đang update cache bằng URL...\n";
            
            $baseUrl = 'http://localhost'; // Thay đổi theo domain của bạn
            $today = date('d-m-Y');
            $urls = [
                '/ket-qua-xoso-keno-vietlott-' . $today, // Keno index page
            ];
            
            foreach ($urls as $url) {
                $fullUrl = $baseUrl . $url;
                echo "🌐 Calling: {$fullUrl}\n";
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $fullUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language: vi-VN,vi;q=0.8,en-US;q=0.5,en;q=0.3',
                    ]
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($response && $httpCode == 200) {
                    echo "✅ Cache updated for: {$url}\n";
                } else {
                    echo "❌ Failed to update cache for: {$url} (HTTP {$httpCode})\n";
                }
            }
            
            echo "✅ Cache update completed\n";
            
        } catch (Exception $e) {
            echo "❌ Error updating cache by URLs: " . $e->getMessage() . "\n";
        }
    }

    private function deleteCacheByPrefix($prefix)
    {
        try {
            $redis = $this->di->get('redis');
            $keys = $redis->keys($prefix . '*');

            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $this->cache->delete($key);
                    $redis->del($key);
                    echo "🗑️ Đã xóa cache prefix: {$key}\n";
                }
            } else {
                echo "ℹ️ Không tìm thấy cache với prefix: {$prefix}\n";
            }
        } catch (Exception $e) {
            echo "❌ Lỗi xóa cache prefix {$prefix}: " . $e->getMessage() . "\n";
        }
    }

    private function calculateNextDraw()
    {
        $currentTime = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $currentMinute = (int)$currentTime->format('i');
        
        $nextDrawMinute = ceil($currentMinute / 8) * 8;
        
        if ($nextDrawMinute >= 60) {
            $currentTime->modify('+1 hour');
            $nextDrawMinute -= 60;
        }
        
        $currentTime->setTime((int)$currentTime->format('H'), $nextDrawMinute);
        
        return [
            'time' => $currentTime->format('H:i'),
            'datetime' => $currentTime->format('Y-m-d H:i:s'),
            'timestamp' => $currentTime->getTimestamp()
        ];
    }
    private function calculateCountdown()
    {
        $nextDraw = $this->calculateNextDraw();
        $nextDrawTime = new DateTime($nextDraw['datetime']);
        $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        $diff = $nextDrawTime->getTimestamp() - $now->getTimestamp();
        
        return max(0, $diff);
    }
    public function getCurrentData()
    {
        if (file_exists($this->dataFile)) {
            $jsonData = file_get_contents($this->dataFile);
            return json_decode($jsonData, true);
        }
        
        return null;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $scraper = new KenoSimpleScraper();
    $success = $scraper->scrape();
    
    if ($success) {
        echo "\n🎉 HOÀN THÀNH CÀO DỮ LIỆU KENO!\n";
        exit(0);
    } else {
        echo "\n💥 LỖI KHI CÀO DỮ LIỆU KENO!\n";
        exit(1);
    }
}
