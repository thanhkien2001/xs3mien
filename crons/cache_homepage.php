<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

class CacheHomepageCli
{
    private $baseUrl;
    private $redis;
    private $viewCache;
    private $di;

    public function __construct()
    {
        $this->di = \Phalcon\Di\Di::getDefault();
        $this->baseUrl = getBaseUrl();
        $this->redis = $this->di->get('redis');
        $this->viewCache = $this->di->get('viewCache');
    }

    /**
     * Xóa view cache cho trang chủ
     */
    public function clearHomepageViewCache()
    {
        echo "\n🗑️ XÓA VIEW CACHE CHO TRANG CHỦ\n";
        
        try {
            // Chuyển sang database 1 (viewCache)
            $this->redis->select(1);
            
            // Xóa cache cho trang chủ
            $homepageKeys = [
                "page_homepage_index",
                "homepage_index"
            ];
            
            $deletedCount = 0;
            foreach ($homepageKeys as $key) {
                $deleted = $this->redis->del($key);
                if ($deleted) {
                    echo "🗑️ Deleted view cache: $key\n";
                    $deletedCount++;
                }
            }
            
            echo "✅ Đã xóa {$deletedCount} view cache keys\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi xóa view cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Cập nhật view cache cho trang chủ
     */
    public function updateHomepageViewCache()
    {
        echo "\n🔄 CẬP NHẬT VIEW CACHE CHO TRANG CHỦ\n";
        
        try {
            $urls = [
                $this->baseUrl . "/",
                $this->baseUrl . "/index.html"
            ];
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($urls as $url) {
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
     * Cập nhật cache cho một URL
     */
    private function updateCacheByUrl($url)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Cache Update Bot');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Clear-Cache: 1'
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
    public function checkCache()
    {
        echo "\n🔍 KIỂM TRA CACHE HIỆN TẠI\n";
        
        try {
            $this->redis->select(1);
            
            $homepageKeys = [
                "page_homepage_index",
                "homepage_index"
            ];
            
            $cachedCount = 0;
            foreach ($homepageKeys as $key) {
                $exists = $this->redis->exists($key);
                if ($exists) {
                    $cachedCount++;
                    echo "✅ Cached: $key\n";
                } else {
                    echo "❌ Not cached: $key\n";
                }
            }
            
            echo "\n📊 Tổng kết: {$cachedCount}/" . count($homepageKeys) . " keys đã được cache\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi kiểm tra cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Chạy tất cả các tác vụ
     */
    public function run()
    {
        echo "🚀 BẮT ĐẦU QUẢN LÝ CACHE TRANG CHỦ\n";
        echo "Thời gian: " . date('Y-m-d H:i:s') . "\n";
        
        // Xóa cache cũ
        $this->clearHomepageViewCache();
        
        // Cập nhật cache mới
        $this->updateHomepageViewCache();
        
        // Kiểm tra kết quả
        $this->checkCache();
        
        echo "\n✅ HOÀN THÀNH QUẢN LÝ CACHE TRANG CHỦ\n";
    }
}

// Chạy script
if (php_sapi_name() === 'cli') {
    $task = new CacheHomepageCli();
    $task->run();
}
?>
