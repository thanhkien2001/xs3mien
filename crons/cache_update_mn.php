<?php

/**
 * Cache Manager for XSMN Lottery Data
 * Handles cache clearing and updating for related controllers
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
class CacheManagerXSMN
{
    private $di;
    private $cache;
    private $today;
    private $dayOfWeek;
    private $weekdaySlug;

    public function __construct()
    {
        // Sử dụng DI container đã có sẵn từ xsmn.php
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
    function removeVietnameseAccents($str)
    {
        $accents = [
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A',
            'Á' => 'A',
            'Ạ' => 'A',
            'Ả' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'Ầ' => 'A',
            'Ấ' => 'A',
            'Ậ' => 'A',
            'Ẩ' => 'A',
            'Ẫ' => 'A',
            'Ă' => 'A',
            'Ằ' => 'A',
            'Ắ' => 'A',
            'Ặ' => 'A',
            'Ẳ' => 'A',
            'Ẵ' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ẹ' => 'E',
            'Ẻ' => 'E',
            'Ẽ' => 'E',
            'Ê' => 'E',
            'Ề' => 'E',
            'Ế' => 'E',
            'Ệ' => 'E',
            'Ể' => 'E',
            'Ễ' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Ị' => 'I',
            'Ỉ' => 'I',
            'Ĩ' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ọ' => 'O',
            'Ỏ' => 'O',
            'Õ' => 'O',
            'Ô' => 'O',
            'Ồ' => 'O',
            'Ố' => 'O',
            'Ộ' => 'O',
            'Ổ' => 'O',
            'Ỗ' => 'O',
            'Ơ' => 'O',
            'Ờ' => 'O',
            'Ớ' => 'O',
            'Ợ' => 'O',
            'Ở' => 'O',
            'Ỡ' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Ụ' => 'U',
            'Ủ' => 'U',
            'Ũ' => 'U',
            'Ư' => 'U',
            'Ừ' => 'U',
            'Ứ' => 'U',
            'Ự' => 'U',
            'Ử' => 'U',
            'Ữ' => 'U',
            'Ỳ' => 'Y',
            'Ý' => 'Y',
            'Ỵ' => 'Y',
            'Ỷ' => 'Y',
            'Ỹ' => 'Y',
            'Đ' => 'D'
        ];
        return strtr($str, $accents);
    }

    /**
     * Main method to clear and update all caches by calling URLs
     */
    public function processCache()
    {
        echo "\n🗑️ BẮT ĐẦU XÓA VÀ CẬP NHẬT CACHE XSMN\n";

        $this->clearKetquaxsmnCache();
        $this->clearArchiveCache();
        $this->clearThongkemnCache();
        $this->clearIndexControllerCache();
        $this->clearDudoanCache();
        $this->clearViewCache();

        $this->updateCacheByUrls();

        echo "✅ Hoàn thành xóa và cập nhật cache XSMN!\n";
    }

    /**
     * Clear KetquaxsmnController cache
     */
    private function clearKetquaxsmnCache()
    {
        echo "🗑️ Xóa cache KetquaxsmnController...\n";
        $cacheKeys = [
            "models_XSMN_FULL_PAGE_{$this->today}",
            "models_XSMN_latest_results_6",
            "models_XSMN_latest_results_6_exclude_{$this->today}"
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
     * Clear ArchiveController cache (theo prefix)
     */
    private function clearArchiveCache()
    {
        echo "🗑️ Xóa cache ArchiveController...\n";

        // Lấy danh sách tỉnh XSMN theo thứ
        $provinces = \App\Models\Provinces::find([
            'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
            'bind' => [
                'region' => 'XSMN',
                'draw_days' => '%' . $this->dayOfWeek . '%'
            ]
        ]);

        // Xóa cache theo prefix XSMN_{weekday}
        $prefix1 = "models_XSMN_{$this->weekdaySlug}";
        $this->deleteCacheByPrefix($prefix1);

        // Xóa cache dau_duoi theo ngày (đã xóa ở IndexController rồi)
        // $prefix3 = "models_XSMN_dau_duoi_";
        // $this->deleteCacheByPrefix($prefix3);

        // Xóa cache theo prefix XSMN_{province_keyid} cho từng tỉnh
        foreach ($provinces as $province) {
            $prefix2 = "models_XSMN_{$province->keyid}";
            $this->deleteCacheByPrefix($prefix2);
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
     * Clear ThongkemnController cache
     */
    private function clearThongkemnCache()
    {
        echo "🗑️ Xóa cache ThongkemnController...\n";
        
        // Xóa cache theo prefix mới (dùng md5 URL)
        $prefixes = [
            'models_thongke_xsmn_',
            'models_logan_xsmn_',
            'models_dacbiet_xsmn_',
            'models_dauduoi_xsmn_',
            'models_tansuat_xsmn_',
        ];

        $redis = $this->di->get('redis');
        foreach ($prefixes as $prefix) {
            $this->deleteCacheByPrefix($prefix);
        }
        
        // Xóa cache cho các tỉnh XSMN
        $xsmnProvinceCodes = [
            'xsag', 'xsblieu', 'xsbtr', 'xsbduong', 'xsbp', 'xsbthuan', 'xscm', 'xsct', 
            'xsdl', 'xsdn', 'xsdthap', 'xshg', 'xshcm', 'xskg', 'xsla', 'xsst', 
            'xstn', 'xstg', 'xstv', 'xsvl', 'xsvt'
        ];
        
        foreach ($xsmnProvinceCodes as $code) {
            $this->clearProvinceStatsCache($code, 'xsmn');
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

        // Cache keys từ IndexController (có prefix "models_")
        $cacheKeys = [
            "models_XSMN_TrangChu_{$safeDate}",
            "models_XSMN_KQXS_Ngay_{$safeDate}",
        ];

        // Xóa cache đầu đuôi theo prefix models_XSMN_dau_duoi_
        $this->deleteCacheByPrefix("models_XSMN_dau_duoi_");
        $this->deleteCacheByPrefix("models_XSMN");
        $this->deleteCacheByPrefix("models_XSMN_latest_results_6");

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Clear DudoanController cache for XSMN
     */
    private function clearDudoanCache()
    {
        echo "🗑️ Xóa cache DudoanController XSMN...\n";
        
        // Lấy danh sách tỉnh XSMN theo thứ
        $provinces = \App\Models\Provinces::find([
            'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
            'bind' => [
                'region' => 'XSMN',
                'draw_days' => '%' . $this->dayOfWeek . '%'
            ]
        ]);
        
        // Xóa cache theo prefix XSMN_province_list cho từng tỉnh
        foreach ($provinces as $province) {
            $prefix = "models_XSMN_province_list_province_id_{$province->id}";
            $this->deleteCacheByPrefix($prefix);
        }
        
        // Xóa cache trang chính dự đoán XSMN
        $cacheKeys = [
            "models_XSMN_province_list",
        ];
        
        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }
    
    /**
     * Clear view cache for XSMN
     */
    private function clearViewCache()
    {
        echo "🗑️ Xóa view cache XSMN...\n";
        
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
            
            // Xóa view cache cho dự đoán XSMN
            $dudoanKeys = [
                "page_xsmndudoan",
                "page_dudoansoicau",
                "page_detailxsmn_*"
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
            
            // Xóa view cache cho archive XSMN (theo thứ và theo tỉnh)
            $archiveKeys = $redis->keys("page_archive_XSMN_*");
            foreach ($archiveKeys as $key) {
                $redis->del($key);
                echo "  ✅ Deleted archive view cache: $key\n";
            }
            
            // Xóa view cache cho archive province XSMN theo alias cụ thể
            $xsmnAliases = [
                'xsag', 'xsblieu', 'xsbtr', 'xsbduong', 'xsbp', 'xsbthuan', 'xscm', 'xsct', 
                'xsdl', 'xsdn', 'xsdthap', 'xshg', 'xshcm', 'xskg', 'xsla', 'xsst', 
                'xstn', 'xstg', 'xstv', 'xsvl', 'xsvt'
            ];
            
            foreach ($xsmnAliases as $alias) {
                $keys = $redis->keys("page_archive_province_{$alias}_*");
                foreach ($keys as $key) {
                    $redis->del($key);
                    echo "  ✅ Deleted archive province view cache: $key\n";
                }
            }
            
            echo "✅ Đã xóa view cache XSMN\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi xóa view cache: " . $e->getMessage() . "\n";
        }
    }
    
    public function updateCacheByUrls()
    {
        echo "\n🌐 CẬP NHẬT CACHE BẰNG CÁCH GỌI URL\n";

        $baseUrl = getBaseUrl();

        // Lấy danh sách tỉnh XSMN theo thứ
        $provinces = \App\Models\Provinces::find([
            'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
            'bind' => [
                'region' => 'XSMN',
                'draw_days' => '%' . $this->dayOfWeek . '%'
            ]
        ]);

        $urls = [
            "{$baseUrl}/", // IndexController - trang chủ
            "{$baseUrl}/thong-ke-xo-so-mien-nam-tk-xsmn.html", // ThongkemnController
            "{$baseUrl}/thong-ke-lo-gan-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html", // ThongkemnController
            "{$baseUrl}/thong-ke-dac-biet-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html", // ThongkemnController
            "{$baseUrl}/thong-ke-dau-duoi-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html", // ThongkemnController
            "{$baseUrl}/thong-ke-tan-suat-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html", // ThongkemnController
            "{$baseUrl}/ket-qua-xo-so-mien-nam-{$this->weekdaySlug}.html", // KetquaxsmnController
            "{$baseUrl}/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html", // KetquaxsmnController - URL chính
            "{$baseUrl}/du-doan-xsmn-c61.html", // DudoanController - trang chính dự đoán XSMN
            
            // URLs từ header - XSMN theo thứ
            "{$baseUrl}/xsmn-thu-2-ket-qua-xo-so-mien-nam.html",
            "{$baseUrl}/xsmn-thu-3-ket-qua-xo-so-mien-nam.html",
            "{$baseUrl}/xsmn-thu-4-ket-qua-xo-so-mien-nam.html",
            "{$baseUrl}/xsmn-thu-5-ket-qua-xo-so-mien-nam.html",
            "{$baseUrl}/xsmn-thu-6-ket-qua-xo-so-mien-nam.html",
            "{$baseUrl}/xsmn-thu-7-ket-qua-xo-so-mien-nam.html",
            "{$baseUrl}/xsmn-chu-nhat-ket-qua-xo-so-mien-nam.html",
            
            // URLs từ header - XSMN theo tỉnh
            "{$baseUrl}/ket-qua-xo-so-an-giang-xsag.html",
            "{$baseUrl}/ket-qua-xo-so-bac-lieu-xsblieu.html",
            "{$baseUrl}/ket-qua-xo-so-ben-tre-xsbtr.html",
            "{$baseUrl}/ket-qua-xo-so-binh-duong-xsbduong.html",
            "{$baseUrl}/ket-qua-xo-so-binh-phuoc-xsbp.html",
            "{$baseUrl}/ket-qua-xo-so-binh-thuan-xsbthuan.html",
            "{$baseUrl}/ket-qua-xo-so-ca-mau-xscm.html",
            "{$baseUrl}/ket-qua-xo-so-can-tho-xsct.html",
            "{$baseUrl}/ket-qua-xo-so-da-lat-xsdl.html",
            "{$baseUrl}/ket-qua-xo-so-dong-nai-xsdn.html",
            "{$baseUrl}/ket-qua-xo-so-dong-thap-xsdthap.html",
            "{$baseUrl}/ket-qua-xo-so-hau-giang-xshg.html",
            "{$baseUrl}/ket-qua-xo-so-ho-chi-minh-xshcm.html",
            "{$baseUrl}/ket-qua-xo-so-kien-giang-xskg.html",
            "{$baseUrl}/ket-qua-xo-so-long-an-xsla.html",
            "{$baseUrl}/ket-qua-xo-so-soc-trang-xsst.html",
            "{$baseUrl}/ket-qua-xo-so-tay-ninh-xstn.html",
            "{$baseUrl}/ket-qua-xo-so-tien-giang-xstg.html",
            "{$baseUrl}/ket-qua-xo-so-tra-vinh-xstv.html",
            "{$baseUrl}/ket-qua-xo-so-vinh-long-xsvl.html",
            "{$baseUrl}/ket-qua-xo-so-vung-tau-xsvt.html",
            
            // URLs thống kê theo tỉnh XSMN
            "{$baseUrl}/thong-ke-xsvt.html",
            "{$baseUrl}/thong-ke-xsvl.html",
            "{$baseUrl}/thong-ke-xsbd.html",
            "{$baseUrl}/thong-ke-xstv.html",
            "{$baseUrl}/thong-ke-xshcm.html",
            "{$baseUrl}/thong-ke-xsla.html",
            "{$baseUrl}/thong-ke-xsdl.html",
            "{$baseUrl}/thong-ke-xsbp.html",
            "{$baseUrl}/thong-ke-xshg.html",
            "{$baseUrl}/thong-ke-xskg.html",
            "{$baseUrl}/thong-ke-xstg.html",
            "{$baseUrl}/thong-ke-xstn.html",
            "{$baseUrl}/thong-ke-xsdt.html",
            "{$baseUrl}/thong-ke-xsag.html",
            "{$baseUrl}/thong-ke-xsbt.html",
            "{$baseUrl}/thong-ke-xsbth.html",
            "{$baseUrl}/thong-ke-xsdn.html",
            "{$baseUrl}/thong-ke-xsbl.html",
            "{$baseUrl}/thong-ke-xsct.html",
            "{$baseUrl}/thong-ke-xsst.html",
            "{$baseUrl}/thong-ke-xscm.html",
            
            // URLs thống kê đặc biệt theo tỉnh XSMN
            "{$baseUrl}/thong-ke-dac-biet-xsvt.html",
            "{$baseUrl}/thong-ke-dac-biet-xsvl.html",
            "{$baseUrl}/thong-ke-dac-biet-xsbd.html",
            "{$baseUrl}/thong-ke-dac-biet-xstv.html",
            "{$baseUrl}/thong-ke-dac-biet-xshcm.html",
            "{$baseUrl}/thong-ke-dac-biet-xsla.html",
            "{$baseUrl}/thong-ke-dac-biet-xsdl.html",
            "{$baseUrl}/thong-ke-dac-biet-xsbp.html",
            "{$baseUrl}/thong-ke-dac-biet-xshg.html",
            "{$baseUrl}/thong-ke-dac-biet-xskg.html",
            "{$baseUrl}/thong-ke-dac-biet-xstg.html",
            "{$baseUrl}/thong-ke-dac-biet-xstn.html",
            "{$baseUrl}/thong-ke-dac-biet-xsdt.html",
            "{$baseUrl}/thong-ke-dac-biet-xsag.html",
            "{$baseUrl}/thong-ke-dac-biet-xsbt.html",
            "{$baseUrl}/thong-ke-dac-biet-xsbth.html",
            "{$baseUrl}/thong-ke-dac-biet-xsdn.html",
            "{$baseUrl}/thong-ke-dac-biet-xsbl.html",
            "{$baseUrl}/thong-ke-dac-biet-xsct.html",
            "{$baseUrl}/thong-ke-dac-biet-xsst.html",
            "{$baseUrl}/thong-ke-dac-biet-xscm.html",
            
            // URLs lô gan theo tỉnh XSMN
            "{$baseUrl}/thong-ke-lo-gan-xsvt.html",
            "{$baseUrl}/thong-ke-lo-gan-xsvl.html",
            "{$baseUrl}/thong-ke-lo-gan-xsbd.html",
            "{$baseUrl}/thong-ke-lo-gan-xstv.html",
            "{$baseUrl}/thong-ke-lo-gan-xshcm.html",
            "{$baseUrl}/thong-ke-lo-gan-xsla.html",
            "{$baseUrl}/thong-ke-lo-gan-xsdl.html",
            "{$baseUrl}/thong-ke-lo-gan-xsbp.html",
            "{$baseUrl}/thong-ke-lo-gan-xshg.html",
            "{$baseUrl}/thong-ke-lo-gan-xskg.html",
            "{$baseUrl}/thong-ke-lo-gan-xstg.html",
            "{$baseUrl}/thong-ke-lo-gan-xstn.html",
            "{$baseUrl}/thong-ke-lo-gan-xsdt.html",
            "{$baseUrl}/thong-ke-lo-gan-xsag.html",
            "{$baseUrl}/thong-ke-lo-gan-xsbt.html",
            "{$baseUrl}/thong-ke-lo-gan-xsbth.html",
            "{$baseUrl}/thong-ke-lo-gan-xsdn.html",
            "{$baseUrl}/thong-ke-lo-gan-xsbl.html",
            "{$baseUrl}/thong-ke-lo-gan-xsct.html",
            "{$baseUrl}/thong-ke-lo-gan-xsst.html",
            "{$baseUrl}/thong-ke-lo-gan-xscm.html",
            
            // URLs đầu đuôi theo tỉnh XSMN
            "{$baseUrl}/thong-ke-dau-duoi-xsag.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsvt.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsvl.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsbd.html",
            "{$baseUrl}/thong-ke-dau-duoi-xstv.html",
            "{$baseUrl}/thong-ke-dau-duoi-xshcm.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsla.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsdl.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsbp.html",
            "{$baseUrl}/thong-ke-dau-duoi-xshg.html",
            "{$baseUrl}/thong-ke-dau-duoi-xskg.html",
            "{$baseUrl}/thong-ke-dau-duoi-xstg.html",
            "{$baseUrl}/thong-ke-dau-duoi-xstn.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsdt.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsbt.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsbth.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsdn.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsbl.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsct.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsst.html",
            "{$baseUrl}/thong-ke-dau-duoi-xscm.html",
            
            // URLs tần suất theo tỉnh XSMN
            "{$baseUrl}/thong-ke-tan-suat-xsvt.html",
            "{$baseUrl}/thong-ke-tan-suat-xsvl.html",
            "{$baseUrl}/thong-ke-tan-suat-xsbd.html",
            "{$baseUrl}/thong-ke-tan-suat-xstv.html",
            "{$baseUrl}/thong-ke-tan-suat-xshcm.html",
            "{$baseUrl}/thong-ke-tan-suat-xsla.html",
            "{$baseUrl}/thong-ke-tan-suat-xsdl.html",
            "{$baseUrl}/thong-ke-tan-suat-xsbp.html",
            "{$baseUrl}/thong-ke-tan-suat-xshg.html",
            "{$baseUrl}/thong-ke-tan-suat-xskg.html",
            "{$baseUrl}/thong-ke-tan-suat-xstg.html",
            "{$baseUrl}/thong-ke-tan-suat-xstn.html",
            "{$baseUrl}/thong-ke-tan-suat-xsdt.html",
            "{$baseUrl}/thong-ke-tan-suat-xsag.html",
            "{$baseUrl}/thong-ke-tan-suat-xsbt.html",
            "{$baseUrl}/thong-ke-tan-suat-xsbth.html",
            "{$baseUrl}/thong-ke-tan-suat-xsdn.html",
            "{$baseUrl}/thong-ke-tan-suat-xsbl.html",
            "{$baseUrl}/thong-ke-tan-suat-xsct.html",
            "{$baseUrl}/thong-ke-tan-suat-xsst.html",
            "{$baseUrl}/thong-ke-tan-suat-xscm.html"
        ];

        // Thêm URLs cho từng tỉnh theo thứ
        foreach ($provinces as $province) {
            $urls[] = "{$baseUrl}/xsmn-{$this->weekdaySlug}-ket-qua-xo-so-mien-nam.html";
        }
        
        // Thêm URL theo ngày cụ thể (hôm nay và các ngày xung quanh)
        $today = new \DateTime();
        
        // Thêm URLs cho 7 ngày trước và 1 ngày sau
        for ($i = -14; $i <= 1; $i++) {
            $date = clone $today;
            $date->modify("{$i} days");
            $day = $date->format('j'); // ngày không có số 0 đầu
            $month = $date->format('n'); // tháng không có số 0 đầu
            $year = $date->format('Y');
            $urls[] = "{$baseUrl}/xsmn-{$day}-{$month}-ket-qua-xo-so-mien-nam-ngay-{$day}-{$month}-{$year}.html";
        }
        
        // Mapping tỉnh XSMN với mã ngắn mới
        $xsmnProvinceMap = [
            'Cà Mau' => 'xscm',
            'TP. Hồ Chí Minh' => 'xshcm',
            'Đồng Tháp' => 'xsdthap',
            'Bạc Liêu' => 'xsblieu',
            'Bến Tre' => 'xsbtr',
            'Vũng Tàu' => 'xsvt',
            'Cần Thơ' => 'xsct',
            'Sóc Trăng' => 'xsst',
            'Đồng Nai' => 'xsdn',
            'An Giang' => 'xsag',
            'Bình Thuận' => 'xsbthuan',
            'Tây Ninh' => 'xstn',
            'Bình Dương' => 'xsbduong',
            'Trà Vinh' => 'xstv',
            'Vĩnh Long' => 'xsvl',
            'Bình Phước' => 'xsbp',
            'Hậu Giang' => 'xshg',
            'Long An' => 'xsla',
            'Kiên Giang' => 'xskg',
            'Tiền Giang' => 'xstg',
            'Đà Lạt' => 'xsdl'
        ];
        
        foreach ($provinces as $province) {
            // Tạo slug từ tên tỉnh (ví dụ: An Giang -> an-giang)
            $provinceSlug = strtolower(str_replace(' ', '-', $this->removeVietnameseAccents($province->name)));
            $provinceCode = strtolower($province->code);
            $urls[] = "{$baseUrl}/ket-qua-xo-so-{$provinceSlug}-{$provinceCode}.html";
            
            // Thêm URL trang dự đoán tỉnh cụ thể với mã ngắn mới
            $shortCode = $xsmnProvinceMap[$province->name] ?? 'xsag';
            $urls[] = "{$baseUrl}/du-doan-{$shortCode}.html";
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
    $cacheManager = new CacheManagerXSMN();
    $cacheManager->processCache();
}
