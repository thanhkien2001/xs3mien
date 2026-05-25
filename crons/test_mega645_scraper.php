<?php
/**
 * Test Mega 645 Scraper với dữ liệu mẫu
 * Sử dụng test server local thay vì API thật
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

class TestMega645Scraper
{
    private $di;
    private $db;
    private $cache;
    private $redis;
    private $baseUrl = 'https://soicau247.com/test_mega645_data.js'; // Test server với HTTPS
    private $drawType = 'Mega645';

    public function __construct()
    {
        $this->di = Phalcon\Di\Di::getDefault();
        $this->db = $this->di->get('db');
        $this->cache = $this->di->get('modelsCache');
        $this->redis = $this->di->get('redis');
    }

    /**
     * Test scraping function
     */
    public function testScrape()
    {
        echo "\n🧪 TEST MEGA 645 SCRAPER - DỮ LIỆU MẪU\n";
        echo "📅 Thời gian: " . date('Y-m-d H:i:s') . "\n";
        echo "🌐 URL: {$this->baseUrl}\n";
        echo "⏰ Test trong 30 giây\n";

        $start = time();
        $flagDone = false;
        $lastData = null;

        while (time() - $start < 30) { // Test 30 giây
            usleep(2000000); // 2 giây
            
            echo "\n🔄 Lần thử: " . (time() - $start) . " giây\n";
            
            try {
                // Lấy dữ liệu từ test server
                $rawData = $this->fetchData();
                if (!$rawData) {
                    echo "❌ Không lấy được dữ liệu từ test server\n";
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

                // Lưu vào database (test mode - chỉ hiển thị)
                $result = $this->testSaveToDatabase($parsedData);
                if ($result) {
                    echo "✅ Parse và validate dữ liệu thành công!\n";
                    $lastData = $currentData;
                    
                    // Kiểm tra xem có đủ dữ liệu để kết thúc không
                    if ($this->isDataComplete($parsedData)) {
                        echo "🎉 Dữ liệu đã hoàn chỉnh, kết thúc test!\n";
                        $flagDone = true;
                        break;
                    }
                } else {
                    echo "❌ Lỗi khi parse dữ liệu\n";
                }

            } catch (Exception $e) {
                echo "❌ Lỗi: " . $e->getMessage() . "\n";
            }
        }

        if ($flagDone) {
            echo "\n🎉 TEST THÀNH CÔNG!\n";
            return true;
        } else {
            echo "\n⏰ Hết thời gian test!\n";
            return false;
        }
    }

    /**
     * Fetch data from test server
     */
    private function fetchData()
    {
        echo "📡 Đang lấy dữ liệu từ test server...\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '?_=' . time(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
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
     * Test save to database (chỉ hiển thị, không lưu thật)
     */
    private function testSaveToDatabase($data)
    {
        echo "💾 Test lưu vào database...\n";
        echo "📋 Dữ liệu sẽ được lưu:\n";
        echo "   - Draw Type: {$data['draw_type']}\n";
        echo "   - Draw Number: {$data['draw_number']}\n";
        echo "   - Draw Date: {$data['draw_date']}\n";
        echo "   - Numbers: {$data['numbers']}\n";
        echo "   - Jackpot: " . number_format($data['jackpot_amount']) . " VNĐ\n";
        echo "   - Jackpot 1 SL: {$data['jackpot_1_sl']}\n";
        echo "   - Giải nhất SL: {$data['giai_nhat_sl']}\n";
        echo "   - Giải nhì SL: {$data['giai_nhi_sl']}\n";
        echo "   - Giải ba SL: {$data['giai_ba_sl']}\n";
        echo "✅ Test save thành công!\n";
        return true;
    }
}

// Chạy test scraper
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 BẮT ĐẦU TEST MEGA 645 SCRAPER\n";
    echo "📝 Lưu ý: Cần chạy test server trước: php crons/test_server.php\n\n";
    
    $scraper = new TestMega645Scraper();
    $success = $scraper->testScrape();
    
    if ($success) {
        echo "\n🎉 TEST THÀNH CÔNG!\n";
        exit(0);
    } else {
        echo "\n💥 TEST THẤT BẠI!\n";
        exit(1);
    }
}
