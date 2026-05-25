<?php
/**
 * Mega 645 Scraper - Live Data from minhngoc.group
 * Cào dữ liệu từ live.minhngoc.group với cấu trúc dữ liệu mới
 * Chạy vào thứ 2, 4, 6 lúc 18:30-19:00
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Library\DateHelper;
use App\Library\LotteryDataHelper;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

class Mega645Scraper
{
    private $di;
    private $db;
    private $cache;
    private $redis;
    private $baseUrl = 'https://live.minhngoc.group/O0O/0/xstt/js_m4.js';
    private $drawType = 'Mega645';

    public function __construct()
    {
        $this->di = Phalcon\Di\Di::getDefault();
        $this->db = $this->di->get('db');
        $this->cache = $this->di->get('modelsCache');
        $this->redis = $this->di->get('redis');
    }

    /**
     * Main scraping function with 30-minute loop
     */
    public function scrape()
    {
        echo "\n🚀 BẮT ĐẦU CÀO DỮ LIỆU MEGA 645 - LIVE MINHNGOC\n";
        echo "📅 Thời gian: " . date('Y-m-d H:i:s') . "\n";
        echo "🌐 URL: {$this->baseUrl}\n";
        echo "⏰ Sẽ chạy trong 30 phút (1800 giây)\n";

        $start = time();
        $flagDone = false;
        $lastData = null;

        while (time() - $start < 1800) { // 30 phút = 1800 giây
            usleep(1000000); // 1 giây
            
            echo "\n🔄 Lần thử: " . (time() - $start) . " giây\n";
            
            try {
                // Lấy dữ liệu từ API
                $rawData = $this->fetchData();
                if (!$rawData) {
                    echo "❌ Không lấy được dữ liệu từ API\n";
                    continue;
                }

                // Parse dữ liệu
                $parsedData = $this->parseData($rawData);
                if (!$parsedData) {
                    echo "❌ Không parse được dữ liệu\n";
                    continue;
                }

                // Kiểm tra xem có dữ liệu mới không
                $currentData = json_encode($parsedData);
                if ($currentData === $lastData) {
                    echo "ℹ️ Dữ liệu chưa thay đổi, tiếp tục chờ...\n";
                    continue;
                }

                // Lưu vào database
                $result = $this->saveToDatabase($parsedData);
                if ($result) {
                    echo "✅ Lưu dữ liệu thành công!\n";
                    $this->clearCache();
                    $lastData = $currentData;
                    
                    // Kiểm tra xem có đủ dữ liệu để kết thúc không
                    if ($this->isDataComplete($parsedData)) {
                        echo "🎉 Dữ liệu đã hoàn chỉnh, kết thúc vòng lặp!\n";
                        $flagDone = true;
                        break;
                    }
                } else {
                    echo "❌ Lỗi khi lưu dữ liệu\n";
                }

            } catch (Exception $e) {
                echo "❌ Lỗi: " . $e->getMessage() . "\n";
            }
        }

        if ($flagDone) {
            echo "\n🎉 HOÀN THÀNH CÀO DỮ LIỆU MEGA 645!\n";
            return true;
        } else {
            echo "\n⏰ Hết thời gian 30 phút, dừng lại!\n";
            return false;
        }
    }

    /**
     * Fetch data from API
     */
    private function fetchData()
    {
        echo "📡 Đang lấy dữ liệu từ API...\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '?_=' . time(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_ENCODING => '', // Tự động xử lý gzip/deflate
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: vi-VN,vi;q=0.8,en-US;q=0.5,en;q=0.3',
                'Connection: keep-alive',
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "❌ CURL Error: {$error}\n";
            return false;
        }

        if ($httpCode !== 200) {
            echo "❌ HTTP Error: {$httpCode}\n";
            return false;
        }

        echo "✅ Lấy dữ liệu thành công (HTTP {$httpCode})\n";
        return $response;
    }

    /**
     * Parse raw data to structured format
     */
    private function parseData($rawData)
    {
        echo "🔍 Đang parse dữ liệu...\n";

        // Tìm pattern kqxs.vlt trong response - cần match toàn bộ object
        if (!preg_match('/kqxs\.vlt\s*=\s*({.*?});/s', $rawData, $matches)) {
            echo "❌ Không tìm thấy pattern kqxs.vlt\n";
            echo "🔍 Raw data preview: " . substr($rawData, 0, 200) . "...\n";
            return false;
        }

        $jsonData = $matches[1];
        $data = json_decode($jsonData, true);

        if (!$data) {
            echo "❌ Không parse được JSON: " . json_last_error_msg() . "\n";
            return false;
        }

        // Kiểm tra cấu trúc dữ liệu
        if (!isset($data['mega']) || !isset($data['mega']['kq'])) {
            echo "❌ Thiếu dữ liệu mega trong response\n";
            return false;
        }

        $megaData = $data['mega']['kq'];

        // Kiểm tra có ít nhất 1 số trúng thưởng không
        $hasNumbers = false;
        if (isset($megaData['g'])) {
            for ($i = 1; $i <= 6; $i++) {
                if (isset($megaData['g'][$i]) && $megaData['g'][$i] !== '*' && $megaData['g'][$i] !== '') {
                    $hasNumbers = true;
                    break;
                }
            }
        }

        if (!$hasNumbers) {
            echo "❌ Chưa có số trúng thưởng nào\n";
            return false;
        }

        // Format dữ liệu
        $parsedData = [
            'draw_type' => $this->drawType,
            'draw_number' => $this->extractDrawNumber($megaData),
            'draw_date' => $this->formatDate($megaData['ng'] ?? date('d/m/Y')),
            'numbers' => $this->formatNumbers($megaData),
            'jackpot_amount' => $this->formatJackpot($megaData['jp'] ?? '0'),
            'jackpot_2' => 0, // Mega645 không có jackpot 2
            'giai_nhat_gia_tri' => 10000000,
            'giai_nhi_gia_tri' => 300000,
            'giai_ba_gia_tri' => 30000,
            'jackpot_1_sl' => $this->formatWinnerCount($megaData['sl']['j'] ?? '0'),
            'jackpot_2_sl' => 0,
            'giai_nhat_sl' => $this->formatWinnerCount($megaData['sl']['1'] ?? '0'),
            'giai_nhi_sl' => $this->formatWinnerCount($megaData['sl']['2'] ?? '0'),
            'giai_ba_sl' => $this->formatWinnerCount($megaData['sl']['3'] ?? '0'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        echo "✅ Parse dữ liệu thành công\n";
        echo "📊 Kỳ quay: {$parsedData['draw_number']} - {$parsedData['draw_date']}\n";
        echo "🎲 Số trúng: {$parsedData['numbers']}\n";
        echo "💰 Jackpot: " . number_format($parsedData['jackpot_amount']) . " VNĐ\n";
        echo "🏆 Giải nhất: " . number_format($parsedData['giai_nhat_gia_tri']) . " VNĐ\n";
        echo "🥈 Giải nhì: " . number_format($parsedData['giai_nhi_gia_tri']) . " VNĐ\n";
        echo "🥉 Giải ba: " . number_format($parsedData['giai_ba_gia_tri']) . " VNĐ\n";

        return $parsedData;
    }

    /**
     * Extract draw number from kxs field
     */
    private function extractDrawNumber($megaData)
    {
        if (isset($megaData['kxs']) && preg_match('/#(\d+)/', $megaData['kxs'], $matches)) {
            return $matches[1];
        }
        return '0000';
    }

    /**
     * Format date from DD/MM/YYYY to YYYY-MM-DD
     */
    private function formatDate($date)
    {
        $parts = explode('/', $date);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return date('Y-m-d');
    }

    /**
     * Format numbers array to comma-separated string
     */
    private function formatNumbers($megaData)
    {
        $numbers = [];
        if (isset($megaData['g'])) {
            for ($i = 1; $i <= 6; $i++) {
                if (isset($megaData['g'][$i]) && $megaData['g'][$i] !== '*' && $megaData['g'][$i] !== '') {
                    $numbers[] = str_pad($megaData['g'][$i], 2, '0', STR_PAD_LEFT);
                } else {
                    $numbers[] = '**'; // Placeholder cho số chưa có
                }
            }
        } else {
            // Nếu không có dữ liệu g, tạo placeholder
            for ($i = 1; $i <= 6; $i++) {
                $numbers[] = '**';
            }
        }
        return implode(',', $numbers);
    }

    /**
     * Format jackpot amount
     */
    private function formatJackpot($jackpot)
    {
        if ($jackpot === '*' || $jackpot === '***********' || $jackpot === '') {
            return 0; // Chưa có kết quả
        }
        return (int)str_replace(',', '', $jackpot);
    }

    /**
     * Format winner count
     */
    private function formatWinnerCount($count)
    {
        if ($count === '*' || $count === '') {
            return 0;
        }
        return (int)$count;
    }

    /**
     * Kiểm tra xem dữ liệu đã hoàn chỉnh chưa
     */
    private function isDataComplete($data)
    {
        if (!$data) {
            return false;
        }

        // Kiểm tra tất cả số đã có chưa
        $numbers = explode(',', $data['numbers']);
        $hasPlaceholders = false;
        
        foreach ($numbers as $number) {
            if ($number === '**' || $number === '*' || $number === '') {
                $hasPlaceholders = true;
                break;
            }
        }

        if (!$hasPlaceholders) {
            echo "✅ Dữ liệu hoàn chỉnh - tất cả số đã có đầy đủ\n";
            echo "📊 Số trúng: {$data['numbers']}\n";
            echo "💰 Jackpot: {$data['jackpot_amount']}\n";
            return true;
        }

        echo "⏳ Dữ liệu chưa hoàn chỉnh - còn số chưa có\n";
        return false;
    }

    /**
     * Save data to database
     */
    private function saveToDatabase($data)
    {
        echo "💾 Đang lưu vào database...\n";

        try {
            // Kiểm tra trùng lặp
            $exists = $this->db->fetchOne(
                'SELECT id FROM vietlott_results WHERE draw_type = ? AND draw_number = ? AND draw_date = ?',
                \Phalcon\Db\Enum::FETCH_ASSOC,
                [$data['draw_type'], $data['draw_number'], $data['draw_date']]
            );

            if ($exists) {
                echo "ℹ️ Dữ liệu đã tồn tại, cập nhật...\n";
                
                // Update existing record
                $sql = "UPDATE vietlott_results SET 
                        numbers = ?, jackpot_amount = ?, jackpot_1_sl = ?, 
                        giai_nhat_sl = ?, giai_nhi_sl = ?, giai_ba_sl = ?,
                        created_at = ?
                        WHERE draw_type = ? AND draw_number = ? AND draw_date = ?";
                
                $result = $this->db->execute($sql, [
                    $data['numbers'], $data['jackpot_amount'], $data['jackpot_1_sl'],
                    $data['giai_nhat_sl'], $data['giai_nhi_sl'], $data['giai_ba_sl'],
                    $data['created_at'], $data['draw_type'], $data['draw_number'], $data['draw_date']
                ]);
            } else {
                echo "➕ Thêm dữ liệu mới...\n";
                
                // Insert new record
                $sql = "INSERT INTO vietlott_results 
                        (draw_type, draw_number, draw_date, numbers, jackpot_amount, jackpot_2,
                         giai_nhat_gia_tri, giai_nhi_gia_tri, giai_ba_gia_tri,
                         jackpot_1_sl, jackpot_2_sl, giai_nhat_sl, giai_nhi_sl, giai_ba_sl, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = $this->db->execute($sql, [
                    $data['draw_type'], $data['draw_number'], $data['draw_date'], $data['numbers'],
                    $data['jackpot_amount'], $data['jackpot_2'], $data['giai_nhat_gia_tri'],
                    $data['giai_nhi_gia_tri'], $data['giai_ba_gia_tri'], $data['jackpot_1_sl'],
                    $data['jackpot_2_sl'], $data['giai_nhat_sl'], $data['giai_nhi_sl'],
                    $data['giai_ba_sl'], $data['created_at']
                ]);
            }

            // Lưu data vào file cho live
            if ($result !== false) {
                $this->saveToFile($data);
            }

            return $result !== false;

        } catch (Exception $e) {
            echo "❌ Database Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Save data to file for live updates
     */
    private function saveToFile($data)
    {
        try {
            $liveData = [
                'draw_type' => $data['draw_type'],
                'draw_number' => $data['draw_number'],
                'draw_date' => $data['draw_date'],
                'numbers' => $data['numbers'],
                'prize_values' => [
                    'jackpot_1' => $data['jackpot_amount'],
                    'giai_nhat' => $data['giai_nhat_gia_tri'],
                    'giai_nhi' => $data['giai_nhi_gia_tri'],
                    'giai_ba' => $data['giai_ba_gia_tri']
                ],
                'winners' => [
                    'jackpot_1' => $data['jackpot_1_sl'],
                    'giai_nhat' => $data['giai_nhat_sl'],
                    'giai_nhi' => $data['giai_nhi_sl'],
                    'giai_ba' => $data['giai_ba_sl']
                ],
                'updated_at' => time()
            ];

            LotteryDataHelper::saveData('mega645', $liveData);
            echo "📁 Data saved to file: " . LotteryDataHelper::getFilePath('mega645') . "\n";
            
        } catch (Exception $e) {
            echo "❌ File Save Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Clear related cache
     */
    private function clearCache()
    {
        echo "🗑️ Đang xóa cache...\n";
        
        $cacheKeys = [
            'MEGA_home_mega_latest_v1',
            'MEGA_frequency_limit20_orderASC',
            'MEGA_frequency_limit20_orderDESC',
            'MEGA_latest',
            'MEGA_patterns',
            'Mega645_statistics_limit100',
        ];

        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $this->redis->del($key);
            echo "🗑️ Deleted cache key: $key\n";
        }

        // Xóa cache controller Vietlott645
        $this->clearVietlottControllerCache();
        
        // Update cache bằng URL
        $this->updateCacheByUrls();

        echo "✅ Đã xóa cache và update cache\n";
    }

    /**
     * Clear Vietlott645 controller cache
     */
    private function clearVietlottControllerCache()
    {
        try {
            // Xóa cache controller Vietlott645 theo pattern
            $this->deleteCacheByPattern($this->redis, 'models_Mega645_index');
            $this->deleteCacheByPattern($this->redis, 'models_Mega645_results');
            $this->deleteCacheByPattern($this->redis, 'models_Mega645_byDate');
            $this->deleteCacheByPattern($this->redis, 'models_MEGA_');
            $this->deleteCacheByPattern($this->redis, 'models_Mega645_');
            
            echo "✅ Vietlott645 Controller cache cleared\n";
        } catch (Exception $e) {
            echo "❌ Error clearing controller cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Delete cache by pattern (prefix) using Redis
     */
    private function deleteCacheByPattern($redis, $pattern)
    {
        try {
            // Redis có keys() để lấy tất cả keys theo pattern
            $keys = $redis->keys($pattern . '*');
            
            if ($keys) {
                foreach ($keys as $key) {
                    $redis->del($key);
                    echo "🗑️ Deleted cache key: $key\n";
                }
            }
        } catch (Exception $e) {
            echo "❌ Error deleting cache pattern $pattern: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Update cache by calling URLs
     */
    private function updateCacheByUrls()
    {
        try {
            $config = $this->di->get('config');
            $baseUrl = getBaseUrl();
            $urldudoan = \DateHelper::generateMega645PredictionUrl(date('Y-m-d'));
            $urls = [
                $baseUrl,
                $baseUrl . '/vietlott645/index645',
                $baseUrl . '/Statistics645/statisticspower645',
                $baseUrl . $urldudoan,
            ];
            
            foreach ($urls as $url) {
                $this->updateCacheByUrl($url);
            }
        } catch (Exception $e) {
            echo "❌ Error updating cache URLs: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Update single cache URL
     */
    private function updateCacheByUrl($url)
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: vi-VN,vi;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Connection: keep-alive',
                ]
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($result && $httpCode == 200) {
                echo "✅ Cache updated: $url\n";
                return true;
            } else {
                echo "❌ Cache update failed: $url (HTTP: $httpCode)\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Error updating cache URL: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Chạy scraper nếu được gọi từ command line
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $scraper = new Mega645Scraper();
    $success = $scraper->scrape();
    
    if ($success) {
        echo "\n🎉 HOÀN THÀNH CÀO DỮ LIỆU MEGA 645!\n";
        exit(0);
    } else {
        echo "\n💥 LỖI KHI CÀO DỮ LIỆU MEGA 645!\n";
        exit(1);
    }
}
