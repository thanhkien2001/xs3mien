<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Models\Provinces;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(0);

/**
 * Class CacheQuaythuProvinces
 * Quản lý view cache cho các trang quay thử tỉnh
 */
class CacheQuaythuProvinces
{
    private $di;
    private $redis;
    private $baseUrl;
    private $viewCache;

    public function __construct()
    {
        $this->di = Phalcon\Di\Di::getDefault();
        $this->redis = $this->di->get('redis');
        $this->viewCache = $this->di->get('viewCache');
        $this->baseUrl = getBaseUrl();
    }

    /**
     * Lấy danh sách tất cả tỉnh có quay thử
     */
    public function getAllQuaythuProvinces()
    {
        $provinces = Provinces::find([
            'conditions' => 'region IN (:region1:, :region2:)',
            'bind' => [
                'region1' => 'XSMN',
                'region2' => 'XSMT'
            ],
            'order' => 'region ASC, name ASC'
        ]);

        $provinceUrls = [];
        foreach ($provinces as $province) {
            $provinceCode = strtolower($province->code);
            
            // Xử lý các trường hợp đặc biệt
            $specialMappings = [
                'xskt' => 'xsktum',  // Kon Tum
                'xsqnm' => 'xsqn',   // Quảng Nam  
                'xsđl' => 'xsdl'     // Đà Lạt (loại bỏ ký tự đặc biệt)
            ];
            
            if (isset($specialMappings[$provinceCode])) {
                $provinceCode = $specialMappings[$provinceCode];
            }
            
            $provinceUrls[] = [
                'code' => $provinceCode,
                'name' => $province->name,
                'region' => $province->region,
                'url' => $this->baseUrl . "/quay-thu-{$provinceCode}.html"
            ];
        }

        return $provinceUrls;
    }

    /**
     * Xóa view cache cho tất cả tỉnh quay thử
     */
    public function clearQuaythuViewCache()
    {
        echo "\n🗑️ XÓA VIEW CACHE CHO TẤT CẢ TỈNH QUAY THỬ\n";
        
        try {
            // Chuyển sang database 1 (viewCache)
            $this->redis->select(1);
            
            // Lấy danh sách tỉnh
            $provinces = $this->getAllQuaythuProvinces();
            $deletedCount = 0;
            
            foreach ($provinces as $province) {
                $cacheKey = "page_quaythu_{$province['code']}";
                $deleted = $this->redis->del($cacheKey);
                if ($deleted) {
                    echo "🗑️ Deleted view cache: $cacheKey\n";
                    $deletedCount++;
                }
            }
            
            // Xóa cache cho các trang miền chính
            $regionKeys = [
                'page_quaythu_xsmn',
                'page_quaythu_xsmt',
                'page_quaythu_xsmb'
            ];
            
            foreach ($regionKeys as $key) {
                $deleted = $this->redis->del($key);
                if ($deleted) {
                    echo "🗑️ Deleted region cache: $key\n";
                    $deletedCount++;
                }
            }
            
            echo "✅ Đã xóa {$deletedCount} view cache keys\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi xóa view cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Cập nhật view cache cho tất cả tỉnh quay thử
     */
    public function updateQuaythuViewCache()
    {
        echo "\n🔄 CẬP NHẬT VIEW CACHE CHO TẤT CẢ TỈNH QUAY THỬ\n";
        
        try {
            $provinces = $this->getAllQuaythuProvinces();
            $successCount = 0;
            $failCount = 0;
            
            foreach ($provinces as $province) {
                $result = $this->updateCacheByUrl($province['url']);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            // Cập nhật cache cho các trang miền chính
            $regionUrls = [
                $this->baseUrl . "/quay-thu-xsmn.html",
                $this->baseUrl . "/quay-thu-xsmt.html",
                $this->baseUrl . "/quay-thu-xsmb.html"
            ];
            
            foreach ($regionUrls as $url) {
                $result = $this->updateCacheByUrl($url);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            echo "✅ Thành công: {$successCount} URLs\n";
            echo "❌ Thất bại: {$failCount} URLs\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi cập nhật view cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Cập nhật cache bằng cách gọi URL
     */
    public function updateCacheByUrl($url)
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

    /**
     * Kiểm tra cache hiện tại
     */
    public function checkCurrentCache()
    {
        echo "\n🔍 KIỂM TRA CACHE HIỆN TẠI\n";
        
        try {
            $this->redis->select(1);
            
            // Kiểm tra cache cho các tỉnh
            $provinces = $this->getAllQuaythuProvinces();
            $cachedCount = 0;
            
            foreach ($provinces as $province) {
                $cacheKey = "page_quaythu_{$province['code']}";
                $exists = $this->redis->exists($cacheKey);
                if ($exists) {
                    $cachedCount++;
                    echo "✅ Cached: {$province['name']} ({$province['code']})\n";
                } else {
                    echo "❌ Not cached: {$province['name']} ({$province['code']})\n";
                }
            }
            
            echo "\n📊 Tổng kết: {$cachedCount}/" . count($provinces) . " tỉnh đã được cache\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi kiểm tra cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Chạy script chính
     */
    public function run($action = 'all')
    {
        echo "=== QUẢN LÝ VIEW CACHE QUAY THỬ TỈNH ===\n";
        echo "Thời gian: " . date('d/m/Y H:i:s') . "\n";
        echo "Action: $action\n\n";

        switch ($action) {
            case 'clear':
                $this->clearQuaythuViewCache();
                break;
                
            case 'update':
                $this->updateQuaythuViewCache();
                break;
                
            case 'check':
                $this->checkCurrentCache();
                break;
                
            case 'all':
            default:
                $this->clearQuaythuViewCache();
                $this->updateQuaythuViewCache();
                break;
        }

        echo "\n=== HOÀN THÀNH ===\n";
        echo "Thời gian: " . date('d/m/Y H:i:s') . "\n";
    }
}

// Chạy script nếu được gọi trực tiếp
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = 'all';
    if (!empty($argv)) {
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            if (strpos($arg, '--action=') === 0) {
                $action = substr($arg, 9);
            } elseif ($arg === '--action' && isset($argv[$i + 1])) {
                $action = $argv[$i + 1];
                $i++;
            }
        }
    }
    
    $cacheManager = new CacheQuaythuProvinces();
    $cacheManager->run($action);
}

