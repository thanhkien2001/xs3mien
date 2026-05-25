<?php

/**
 * Cache Manager for XSMB Lottery Data
 * Handles cache clearing and updating for related controllers
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

class CacheManager
{
    private $di;
    private $cache;
    private $today;
    private $dayOfWeek;
    private $weekdaySlug;

    public function __construct()
    {
        // Sử dụng DI container đã có sẵn từ xsmb.php
        $this->di = \Phalcon\Di\Di::getDefault();
        $this->cache = $this->di->get('modelsCache');
        $this->today = date('Y-m-d');
        $this->dayOfWeek = date('N');

        $weekdayMap = [
            1 => 'thu-2',
            2 => 'thu-3',
            3 => 'thu-4',
            4 => 'thu-5',
            5 => 'thu-6',
            6 => 'thu-7',
            7 => 'chu-nhat'
        ];
        $this->weekdaySlug = $weekdayMap[$this->dayOfWeek];
    }

    /**
     * Main method to clear and update all caches by calling URLs
     */
    public function processCache()
    {
        echo "\n🗑️ BẮT ĐẦU XÓA VÀ CẬP NHẬT CACHE\n";

        $this->clearKetquaxsmbCache();
        $this->clearArchiveCache();
        $this->clearThongkembCache();
        $this->clearIndexControllerCache();
        $this->clearDudoanCache();
        $this->clearViewCache();

        $this->updateCacheByUrls();

        echo "✅ Hoàn thành xóa và cập nhật cache!\n";
    }

    /**
     * Clear KetquaxsmbController cache
     */
    private function clearKetquaxsmbCache()
    {
        echo "🗑️ Xóa cache KetquaxsmbController...\n";
        $cacheKeys = [
            "models_XSMB_FULL_PAGE_{$this->today}",
            "models_KQXS_{$this->today}",
            "models_XSMB_latest_results_6",
            "models_XSMB_latest_results_6_exclude_{$this->today}"
        ];

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Clear ArchiveController cache
     */
    private function clearArchiveCache()
    {
        echo "🗑️ Xóa cache ArchiveController...\n";
        $cacheKeys = [
            "models_XSMB_{$this->weekdaySlug}_p1",
        ];

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Clear ThongkembController cache
     */
    private function clearThongkembCache()
    {
        echo "🗑️ Xóa cache ThongkembController...\n";

        // Xóa cache theo prefix mới (dùng md5 URL) - giống như MN và MT
        $prefixes = [
            'models_thongke_xsmb_',
            'models_logan_xsmb_',
            'models_dacbiet_xsmb_',
            'models_dauduoi_xsmb_',
            'models_tansuat_xsmb_',
        ];

        $redis = $this->di->get('redis');
        foreach ($prefixes as $prefix) {
            $this->deleteCacheByPrefix($prefix);
        }

        // Xóa cache cho các tỉnh XSMB (nếu có)
        $xsmbProvinceCodes = [
            'xsmb',
            'xshn',
            'xshp',
            'xsqn',
            'xsbn',
            'xstb',
            'xshb',
            'xshn',
            'xshp'
        ];

        foreach ($xsmbProvinceCodes as $code) {
            $this->clearProvinceStatsCache($code, 'xsmb');
        }
    }

    /**
     * Xóa cache thống kê theo tỉnh
     */
    private function clearProvinceStatsCache($provinceCode, $region)
    {
        $redis = $this->di->get('redis');

        // Cache keys theo pattern từ controller
        $patterns = [
            "models_thongke_{$region}_{$provinceCode}_*",
            "models_logan_{$region}_{$provinceCode}_*",
            "models_dacbiet_{$region}_{$provinceCode}_*",
            "models_dauduoi_{$region}_{$provinceCode}_*",
            "models_tansuat_{$region}_{$provinceCode}_*"
        ];

        // Tìm trong database 0 (models cache)
        $redis->select(0);
        foreach ($patterns as $pattern) {
            $keys = $redis->keys($pattern);
            foreach ($keys as $key) {
                $this->cache->delete($key);
                $redis->del($key);
                echo "  ✅ Deleted models cache: $key\n";
            }
        }

        // Tìm trong database 1 (view cache)
        $redis->select(1);
        foreach ($patterns as $pattern) {
            $keys = $redis->keys($pattern);
            foreach ($keys as $key) {
                $redis->del($key);
                echo "  ✅ Deleted view cache: $key\n";
            }
        }
    }

    /**
     * Clear IndexController cache
     */
    private function clearIndexControllerCache()
    {
        echo "🗑️ Xóa cache IndexController...\n";

        $today = date('Y-m-d');
        $safeDate = str_replace('-', '', $today);
        $cacheKeys = [
            "models_XSMB_TrangChu_{$safeDate}",
            "models_XSMB_KQXS_Ngay_{$safeDate}",
        ];

        // Xóa cache đầu đuôi theo prefix XSMB_dau_duoi_
        $this->deleteCacheByPrefix("models_XSMB_dau_duoi_");
        $this->deleteCacheByPrefix("models_XSMB");
        $this->deleteCacheByPrefix("models_XSMB_latest_results_6");

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Clear DudoanController cache for XSMB
     */
    private function clearDudoanCache()
    {
        echo "🗑️ Xóa cache DudoanController XSMB...\n";

        // Xóa cache trang chính dự đoán XSMB
        $cacheKeys = [
            "models_XSMB_list10",
        ];

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Xóa cache theo prefix
     */
    private function deleteCacheByPrefix($prefix)
    {
        try {
            // Sử dụng Redis service trực tiếp để tìm và xóa keys theo pattern
            $redis = $this->di->get('redis');
            $keys = $redis->keys($prefix . '*');

            if (!empty($keys)) {
                foreach ($keys as $key) {
                    // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
                    $this->cache->delete($key);
                    $redis->del($key);
                    echo "  ✅ Đã xóa: {$key}\n";
                }
            } else {
                echo "  ℹ️ Không tìm thấy cache với prefix: {$prefix}\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ Lỗi xóa cache prefix {$prefix}: " . $e->getMessage() . "\n";
        }
    }



    /**
     * Clear view cache for XSMB
     */
    private function clearViewCache()
    {
        echo "🗑️ Xóa view cache XSMB...\n";

        try {
            $redis = $this->di->get('redis');
            $viewCache = $this->di->get('viewCache');

            // Chuyển sang database 1 (viewCache)
            $redis->select(1);

            // Xóa view cache cho trang chủ
            $homepageKeys = [
                "page_homepage_index",
                "homepage_index"
            ];

            foreach ($homepageKeys as $key) {
                $deleted = $redis->del($key);
                if ($deleted) {
                    echo "  ✅ Deleted view cache: $key\n";
                }
            }

            // Xóa view cache cho dự đoán XSMB
            $dudoanKeys = [
                "page_xsmbdudoan",
                "page_dudoansoicau",
                "page_detailxsmb_*"
            ];

            foreach ($dudoanKeys as $key) {
                if (strpos($key, '*') !== false) {
                    // Xóa theo pattern
                    $pattern = str_replace('*', '', $key);
                    $keys = $redis->keys($pattern . '*');
                    foreach ($keys as $k) {
                        $redis->del($k);
                        echo "  ✅ Deleted view cache: $k\n";
                    }
                } else {
                    $deleted = $redis->del($key);
                    if ($deleted) {
                        echo "  ✅ Deleted view cache: $key\n";
                    }
                }
            }

            // Xóa view cache cho archive XSMB (theo thứ, không có theo tỉnh)
            $archiveKeys = $redis->keys("page_archive_XSMB_*");
            foreach ($archiveKeys as $key) {
                $redis->del($key);
                echo "  ✅ Deleted archive view cache: $key\n";
            }

            echo "✅ Đã xóa view cache XSMB\n";
        } catch (Exception $e) {
            echo "❌ Lỗi khi xóa view cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Update cache by calling controller URLs
     */
    public function updateCacheByUrls()
    {
        echo "\n🌐 CẬP NHẬT CACHE BẰNG CÁCH GỌI URL\n";

        $baseUrl = getBaseUrl();
        $urls = [
            "{$baseUrl}/", // IndexController - trang chủ
            "{$baseUrl}/thong-ke-xo-so-mien-bac-tk-xsmb.html", // ThongkembController
            "{$baseUrl}/lo-gan-xsmb.html", // ThongkembController
            "{$baseUrl}/dac-biet-xsmb.html", // ThongkembController
            "{$baseUrl}/", // ThongkembController
            "{$baseUrl}/tan-suat-lo-to-xsmb.html", // ThongkembController
            "{$baseUrl}/xsmb-{$this->weekdaySlug}-ket-qua-xo-so-mien-bac.html", // ArchiveController
            "{$baseUrl}/ket-qua-xo-so-mien-bac-{$this->weekdaySlug}.html", // KetquaxsmbController
            "{$baseUrl}/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html", // KetquaxsmbController - URL chính
            "{$baseUrl}/du-doan-xsmb-c59.html", // DudoanController - trang chính dự đoán XSMB

            // URLs từ header - XSMB theo thứ
            "{$baseUrl}/xsmb-thu-2-ket-qua-xo-so-mien-bac.html",
            "{$baseUrl}/xsmb-thu-3-ket-qua-xo-so-mien-bac.html",
            "{$baseUrl}/xsmb-thu-4-ket-qua-xo-so-mien-bac.html",
            "{$baseUrl}/xsmb-thu-5-ket-qua-xo-so-mien-bac.html",
            "{$baseUrl}/xsmb-thu-6-ket-qua-xo-so-mien-bac.html",
            "{$baseUrl}/xsmb-thu-7-ket-qua-xo-so-mien-bac.html",
            "{$baseUrl}/xsmb-chu-nhat-ket-qua-xo-so-mien-bac.html",

            // URLs thống kê theo tỉnh XSMB (nếu có)
            "{$baseUrl}/thong-ke-xsmb.html",
            "{$baseUrl}/dac-biet-xsmb.html",
            "{$baseUrl}/lo-gan-xsmb.html",
            "{$baseUrl}/",
            "{$baseUrl}/tan-suat-lo-to-xsmb.html"
        ];

        // Thêm URL theo ngày cụ thể (hôm nay và các ngày xung quanh)
        $today = new \DateTime();

        // Thêm URLs cho 7 ngày trước và 1 ngày sau
        for ($i = -14; $i <= 1; $i++) {
            $date = clone $today;
            $date->modify("{$i} days");
            $day = $date->format('j'); // ngày không có số 0 đầu
            $month = $date->format('n'); // tháng không có số 0 đầu
            $year = $date->format('Y');
            $urls[] = "{$baseUrl}/xsmb-{$day}-{$month}-ket-qua-xo-so-mien-bac-ngay-{$day}-{$month}-{$year}.html";
        }

        foreach ($urls as $url) {
            try {
                $response = curlGetContent($url);
                $httpCode = $response['header']['http_code'];

                if ($httpCode == 200) {
                    echo "  ✅ Đã cập nhật cache: {$url}\n";
                } else {
                    $errorMsg = isset($response['error']) ? " - " . $response['error'] : "";
                    echo "  ❌ Lỗi cập nhật cache: {$url} - HTTP {$httpCode}{$errorMsg}\n";
                }
            } catch (\Exception $e) {
                echo "  ❌ Lỗi cập nhật cache: {$url} - " . $e->getMessage() . "\n";
            }
        }

        echo "✅ Hoàn thành cập nhật cache bằng URL!\n";
    }
}

// Nếu file được gọi trực tiếp từ command line
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $cacheManager = new CacheManager();
    $cacheManager->processCache();
}
