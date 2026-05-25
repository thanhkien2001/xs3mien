<?php
/**
 * Vietlott Max3D Scraper
 * Cào dữ liệu từ vesominhngoc.com.vn với ID: 118
 * Chạy vào thứ 2, 3, 5, 7 lúc 18:30-19:00
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Library\LotteryDataHelper;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

class VietlottMax3DScraper
{
    private $di;
    private $db;
    private $cache;
    private $baseUrl = 'https://vesominhngoc.com.vn/xstt/dai.php?id=118';
    private $drawType = 'MAX3D';

    public function __construct()
    {
        $this->di = Phalcon\Di\Di::getDefault();
        $this->db = $this->di->get('db');
        $this->cache = $this->di->get('modelsCache');
    }

    /**
     * Main scraping function with 30-minute loop
     */
    public function scrape()
    {
        echo "\n🚀 BẮT ĐẦU CÀO DỮ LIỆU VIETLOTT MAX3D\n";
        echo "📅 Thời gian: " . date('Y-m-d H:i:s') . "\n";
        echo "🌐 URL: {$this->baseUrl}\n";
        echo "⏰ Sẽ chạy trong 30 phút (1800 giây)\n";

        $start = time();
        $flagDone = false;
        $lastData = null;

        while (time() - $start < 1800) { // 30 phút = 1800 giây
            usleep(1000000); // 0.5 giây
            
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
            echo "\n🎉 HOÀN THÀNH CÀO DỮ LIỆU MAX3D!\n";
            return true;
        } else {
            echo "\n⏰ Hết thời gian 30 phút, dừng lại!\n";
            return false;
        }
    }

    /**
     * Kiểm tra xem dữ liệu đã hoàn chỉnh chưa
     */
    private function isDataComplete($data)
    {
        if (!$data) {
            return false;
        }

        // Kiểm tra có dấu * hoặc + trong dữ liệu không
        $hasPlaceholders = false;
        $dataString = json_encode($data);
        
        if (strpos($dataString, '*') !== false || strpos($dataString, '+') !== false) {
            $hasPlaceholders = true;
        }

        if (!$hasPlaceholders) {
            echo "✅ Dữ liệu hoàn chỉnh - không còn dấu * hoặc +\n";
            return true;
        }

        echo "⏳ Dữ liệu chưa hoàn chỉnh - vẫn còn dấu * hoặc +\n";
        return false;
    }

    /**
     * Fetch data from API
     */
    private function fetchData()
    {
        echo "📡 Đang lấy dữ liệu từ API...\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
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

        // Tìm pattern kqxs[118] trong response (có thể có dấu \ escape)
        if (!preg_match('/kqxs\\\[118\\\]\s*=\s*({[^}]+});/', $rawData, $matches)) {
            // Thử pattern không có escape
            if (!preg_match('/kqxs\[118\]\s*=\s*({[^}]+});/', $rawData, $matches)) {
                echo "❌ Không tìm thấy pattern kqxs[118]\n";
                echo "🔍 Raw data preview: " . substr($rawData, 0, 200) . "...\n";
                return false;
            }
        }

        $jsonData = $matches[1];
        $data = json_decode($jsonData, true);

        if (!$data) {
            echo "❌ Không parse được JSON: " . json_last_error_msg() . "\n";
            return false;
        }

        // Validate required fields
        if (!isset($data['date']) || !isset($data['lv']) || !isset($data['1'])) {
            echo "❌ Thiếu dữ liệu bắt buộc\n";
            return false;
        }

        // Format dữ liệu cho Max3D
        $parsedData = [
            'draw_type' => $this->drawType,
            'draw_number' => $data['lv'],
            'draw_date' => $this->formatDate($data['date']),
            'jackpot_numbers' => $this->formatNumbers($data['1'] ?? []),
            'first_numbers' => $this->formatNumbers($data['2'] ?? []),
            'second_numbers' => $this->formatNumbers($data['3'] ?? []),
            'third_numbers' => $this->formatNumbers($data['4'] ?? []),
            'jackpot_winners' => $this->formatMaxWinnersCombined($data['slg'] ?? [], $data['slgplus'] ?? [], 0),
            'first_winners' => $this->formatMaxWinnersCombined($data['slg'] ?? [], $data['slgplus'] ?? [], 1),
            'second_winners' => $this->formatMaxWinnersCombined($data['slg'] ?? [], $data['slgplus'] ?? [], 2),
            'third_winners' => $this->formatMaxWinnersCombined($data['slg'] ?? [], $data['slgplus'] ?? [], 3),
            'secondary_prize_winners' => null,
            'fourth_winners' => $this->formatMaxWinnersSingle($data['slgplus'] ?? [], 4),
            'fifth_winners' => $this->formatMaxWinnersSingle($data['slgplus'] ?? [], 5),
            'sixth_winners' => $this->formatMaxWinnersSingle($data['slgplus'] ?? [], 6),
            'created_at' => date('Y-m-d H:i:s')
        ];

        echo "✅ Parse dữ liệu thành công\n";
        echo "📊 Kỳ quay: {$parsedData['draw_number']} - {$parsedData['draw_date']}\n";
        echo "🎲 Jackpot: " . $parsedData['jackpot_numbers'] . "\n";
        echo "🎲 Giải nhất: " . $parsedData['first_numbers'] . "\n";
        echo "🎲 Giải nhì: " . $parsedData['second_numbers'] . "\n";
        echo "🎲 Giải ba: " . $parsedData['third_numbers'] . "\n";
        echo "🏆 Jackpot winners: " . $parsedData['jackpot_winners'] . "\n";
        echo "🏆 First winners: " . $parsedData['first_winners'] . "\n";
        echo "🏆 Second winners: " . $parsedData['second_winners'] . "\n";
        echo "🏆 Third winners: " . $parsedData['third_winners'] . "\n";
        echo "🏆 Fourth winners: " . $parsedData['fourth_winners'] . "\n";
        echo "🏆 Fifth winners: " . $parsedData['fifth_winners'] . "\n";
        echo "🏆 Sixth winners: " . $parsedData['sixth_winners'] . "\n";
        echo "🔍 Raw slg: " . json_encode($data['slg'] ?? []) . "\n";
        echo "🔍 Raw slgplus: " . json_encode($data['slgplus'] ?? []) . "\n";

        return $parsedData;
    }

    /**
     * Format date from DD-MM-YYYY to YYYY-MM-DD
     */
    private function formatDate($date)
    {
        $parts = explode('-', $date);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return date('Y-m-d');
    }

    /**
     * Format numbers array to comma-separated string
     */
    private function formatNumbers($numbers)
    {
        if (!is_array($numbers)) {
            return '';
        }
        
        $formatted = [];
        foreach ($numbers as $number) {
            $formatted[] = str_pad($number, 3, '0', STR_PAD_LEFT);
        }
        return implode(',', $formatted);
    }

    /**
     * Format winners array for Max3D
     */
    private function formatMaxWinners($winners, $index)
    {
        if (!is_array($winners)) {
            return '0,0';
        }
        
        // Max3D: slg[0]=18, slg[1]=44, slg[2]=87, slg[3]=107
        // Max3D: slgplus[0]=0, slgplus[1]=0, slgplus[2]=6, slgplus[3]=2, slgplus[4]=65, slgplus[5]=366, slgplus[6]=3899
        $count = $winners[$index] ?? '0';
        $amount = '0'; // Max3D không có thông tin tiền thưởng
        
        return $count . ',' . $amount;
    }

    /**
     * Format winners array for Max3D (combined slg + slgplus)
     */
    private function formatMaxWinnersCombined($slg, $slgplus, $index)
    {
        if (!is_array($slg) || !is_array($slgplus)) {
            return '0,0';
        }
        
        // Max3D: second_winners = slg[2] + slgplus[2] = 87 + 6 = 87,6
        // Max3D: third_winners = slg[3] + slgplus[3] = 107 + 2 = 107,2
        $count = $slg[$index] ?? '0';
        $amount = $slgplus[$index] ?? '0';
        
        return $count . ',' . $amount;
    }

    /**
     * Format winners array for Max3D (single number only)
     */
    private function formatMaxWinnersSingle($winners, $index)
    {
        if (!is_array($winners)) {
            return '0';
        }
        
        // Max3D: fourth_winners = slgplus[4] = 65
        // Max3D: fifth_winners = slgplus[5] = 366
        // Max3D: sixth_winners = slgplus[6] = 3899
        return $winners[$index] ?? '0';
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
                'SELECT id FROM max_results WHERE draw_type = ? AND draw_number = ? AND draw_date = ?',
                \Phalcon\Db\Enum::FETCH_ASSOC,
                [$data['draw_type'], $data['draw_number'], $data['draw_date']]
            );

            if ($exists) {
                echo "ℹ️ Dữ liệu đã tồn tại, cập nhật...\n";
                
                // Update existing record
                $sql = "UPDATE max_results SET 
                        jackpot_numbers = ?, first_numbers = ?, second_numbers = ?, third_numbers = ?,
                        jackpot_winners = ?, first_winners = ?, second_winners = ?, third_winners = ?,
                        secondary_prize_winners = ?, fourth_winners = ?, fifth_winners = ?, sixth_winners = ?,
                        created_at = ?
                        WHERE draw_type = ? AND draw_number = ? AND draw_date = ?";
                
                $result = $this->db->execute($sql, [
                    $data['jackpot_numbers'], $data['first_numbers'], $data['second_numbers'], $data['third_numbers'],
                    $data['jackpot_winners'], $data['first_winners'], $data['second_winners'], $data['third_winners'],
                    $data['secondary_prize_winners'], $data['fourth_winners'], $data['fifth_winners'], $data['sixth_winners'],
                    $data['created_at'], $data['draw_type'], $data['draw_number'], $data['draw_date']
                ]);
            } else {
                echo "➕ Thêm dữ liệu mới...\n";
                
                // Insert new record
                $sql = "INSERT INTO max_results 
                        (draw_type, draw_number, draw_date, jackpot_numbers, first_numbers, 
                         second_numbers, third_numbers, jackpot_winners, first_winners, 
                         second_winners, third_winners, secondary_prize_winners, fourth_winners, 
                         fifth_winners, sixth_winners, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = $this->db->execute($sql, [
                    $data['draw_type'], $data['draw_number'], $data['draw_date'], 
                    $data['jackpot_numbers'], $data['first_numbers'], $data['second_numbers'], 
                    $data['third_numbers'], $data['jackpot_winners'], $data['first_winners'],
                    $data['second_winners'], $data['third_winners'], $data['secondary_prize_winners'],
                    $data['fourth_winners'], $data['fifth_winners'], $data['sixth_winners'], $data['created_at']
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
                'jackpot_numbers' => $data['jackpot_numbers'],
                'first_numbers' => $data['first_numbers'],
                'second_numbers' => $data['second_numbers'],
                'third_numbers' => $data['third_numbers'],
                'winners' => [
                    'jackpot' => $data['jackpot_winners'],
                    'first' => $data['first_winners'],
                    'second' => $data['second_winners'],
                    'third' => $data['third_winners'],
                    'fourth' => $data['fourth_winners'],
                    'fifth' => $data['fifth_winners'],
                    'sixth' => $data['sixth_winners']
                ],
                'updated_at' => time()
            ];

            LotteryDataHelper::saveData('max3d', $liveData);
            echo "📁 Data saved to file: " . LotteryDataHelper::getFilePath('max3d') . "\n";
            
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
            'MAX3D_index_date_null_limit_1',
            'MAX3D_index_limit_1',
            'MAX3D_otherDates_limit_12',
            'MAX3D_frequentNumbers_limit_60',
            'MAX3D_rareNumbers_limit_60'
        ];

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $redis->del($key);
            echo "🗑️ Deleted cache key: $key\n";
        }
        
        // Xóa cache controller VietlottMax
        $this->clearVietlottControllerCache();
        
        // Update cache bằng URL
        $this->updateCacheByUrls();

        echo "✅ Đã xóa cache và update cache\n";
    }

    /**
     * Clear VietlottMax controller cache
     */
    private function clearVietlottControllerCache()
    {
        try {
            $redis = $this->di->get('redis');
            
            // Xóa cache controller VietlottMax theo pattern
            $this->deleteCacheByPattern($redis, 'models_MAX3D_index');
            
            echo "✅ VietlottMax Controller cache cleared\n";
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
            
            $urls = [
                $baseUrl . '/ket-qua-xoso-max-3d-vietlott-' . date('d-m-Y') . '.html',
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
    $scraper = new VietlottMax3DScraper();
    $success = $scraper->scrape();
    
    if ($success) {
        echo "\n🎉 HOÀN THÀNH CÀO DỮ LIỆU MAX3D!\n";
        exit(0);
    } else {
        echo "\n💥 LỖI KHI CÀO DỮ LIỆU MAX3D!\n";
        exit(1);
    }
}
