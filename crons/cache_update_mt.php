<?php

/**
 * Cache Manager for XSMT Lottery Data
 * Handles cache clearing and updating for related controllers
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

use Phalcon\Di\FactoryDefault;
use App\Models\LotteryResults;

class CacheManagerXSMT
{
    private $di;
    private $cache;
    private $today;
    private $dayOfWeek;
    private $weekdaySlug;

    public function __construct()
    {
        // Sử dụng DI container đã có sẵn từ xsmt.php
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
        echo "\n🗑️ BẮT ĐẦU XÓA VÀ CẬP NHẬT CACHE XSMT\n";

        $this->clearKetquaxsmtCache();
        $this->clearArchiveCache();
        $this->clearThongkemtCache();
        $this->clearIndexControllerCache();
        $this->clearDudoanCache();
        $this->clearViewCache();

        $this->updateCacheByUrls();

        echo "✅ Hoàn thành xóa và cập nhật cache XSMT!\n";
    }

    /**
     * Clear KetquaxsmtController cache
     */
    private function clearKetquaxsmtCache()
    {
        echo "🗑️ Xóa cache KetquaxsmtController...\n";
        $cacheKeys = [
            "models_XSMT_FULL_PAGE_{$this->today}",
            "models_XSMT_latest_results_6",
            "models_XSMT_latest_results_6_exclude_{$this->today}"
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

        // Lấy danh sách tỉnh XSMT theo thứ
        $provinces = \App\Models\Provinces::find([
            'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
            'bind' => [
                'region' => 'XSMT',
                'draw_days' => '%' . $this->dayOfWeek . '%'
            ]
        ]);

        // Xóa cache theo prefix XSMT_{weekday}
        $prefix1 = "models_XSMT_{$this->weekdaySlug}";
        $this->deleteCacheByPrefix($prefix1);

        // Xóa cache theo prefix XSMT_{province_keyid} cho từng tỉnh
        foreach ($provinces as $province) {
            $prefix2 = "models_XSMT_{$province->keyid}";
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
     * Clear ThongkemtController cache
     */
    private function clearThongkemtCache()
    {
        echo "🗑️ Xóa cache ThongkemtController...\n";

        // Xóa cache theo prefix mới (dùng md5 URL)
        $prefixes = [
            'models_thongke_xsmt_',
            'models_logan_xsmt_',
            'models_dacbiet_xsmt_',
            'models_dauduoi_xsmt_',
            'models_tansuat_xsmt_',
        ];

        $redis = $this->di->get('redis');
        foreach ($prefixes as $prefix) {
            $this->deleteCacheByPrefix($prefix);
        }

        // Xóa cache cho các tỉnh XSMT
        $xsmtProvinceCodes = [
            'xsbdinh',
            'xsdna',
            'xsdlk',
            'xsdnong',
            'xsgl',
            'xshue',
            'xskhoa',
            'xskontum',
            'xsnt',
            'xspy',
            'xsqb',
            'xsqn',
            'xsqng',
            'xsqtri'
        ];

        foreach ($xsmtProvinceCodes as $code) {
            $this->clearProvinceStatsCache($code, 'xsmt');
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
            "models_thongke_{$region}_v2_{$provinceCode}_*",
            "models_logan_{$region}_v2_{$provinceCode}_*",
            "models_dacbiet_{$region}_v2_{$provinceCode}_*",
            "models_dauduoi_{$region}_v2_{$provinceCode}_*",
            "models_tansuat_{$region}_v2_{$provinceCode}_*"
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

        // Cache keys từ IndexController
        $cacheKeys = [
            "models_XSMT_TrangChu_{$safeDate}",
            "models_XSMT_KQXS_Ngay_{$safeDate}",
        ];

        // Xóa cache đầu đuôi theo prefix XSMT_dau_duoi_
        $this->deleteCacheByPrefix("models_XSMT_dau_duoi_");
        $this->deleteCacheByPrefix("models_XSMT");
        $this->deleteCacheByPrefix("models_XSMT_latest_results_6");

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            // Xóa bằng cả 2 cách: cache service và Redis trực tiếp
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Clear DudoanController cache for XSMT
     */
    private function clearDudoanCache()
    {
        echo "🗑️ Xóa cache DudoanController XSMT...\n";

        // Lấy danh sách tỉnh XSMT theo thứ
        $provinces = \App\Models\Provinces::find([
            'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
            'bind' => [
                'region' => 'XSMT',
                'draw_days' => '%' . $this->dayOfWeek . '%'
            ]
        ]);

        // Xóa cache theo prefix XSMT_province_list cho từng tỉnh
        foreach ($provinces as $province) {
            $prefix = "models_XSMT_province_list_province_id_{$province->id}";
            $this->deleteCacheByPrefix($prefix);
        }

        // Xóa cache trang chính dự đoán XSMT
        $cacheKeys = [
            "models_XSMT_province_list",
        ];

        $redis = $this->di->get('redis');
        foreach ($cacheKeys as $key) {
            $this->cache->delete($key);
            $redis->del($key);
            echo "  ✅ Đã xóa: {$key}\n";
        }
    }

    /**
     * Clear view cache for XSMT
     */
    private function clearViewCache()
    {
        echo "🗑️ Xóa view cache XSMT...\n";

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

            // Xóa view cache cho dự đoán XSMT
            $dudoanKeys = [
                "page_xsmtdudoan",
                "page_dudoansoicau",
                "page_detailxsmt_*"
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

            // Xóa view cache cho archive XSMT (theo thứ và theo tỉnh)
            $archiveKeys = $redis->keys("page_archive_XSMT_*");
            foreach ($archiveKeys as $key) {
                $redis->del($key);
                echo "  ✅ Deleted archive view cache: $key\n";
            }

            // Xóa view cache cho archive province XSMT theo alias cụ thể
            $xsmtAliases = [
                'xsbdinh',
                'xsdna',
                'xsdlk',
                'xsdnong',
                'xsgl',
                'xshue',
                'xskhoa',
                'xskontum',
                'xsnt',
                'xspy',
                'xsqb',
                'xsqn',
                'xsqng',
                'xsqtri'
            ];

            foreach ($xsmtAliases as $alias) {
                $keys = $redis->keys("page_archive_province_{$alias}_*");
                foreach ($keys as $key) {
                    $redis->del($key);
                    echo "  ✅ Deleted archive province view cache: $key\n";
                }
            }

            echo "✅ Đã xóa view cache XSMT\n";
        } catch (Exception $e) {
            echo "❌ Lỗi khi xóa view cache: " . $e->getMessage() . "\n";
        }
    }

    public function updateCacheByUrls()
    {
        echo "\n🌐 CẬP NHẬT CACHE BẰNG CÁCH GỌI URL\n";

        $baseUrl = getBaseUrl();

        // Lấy danh sách tỉnh XSMT theo thứ
        $provinces = \App\Models\Provinces::find([
            'conditions' => 'region = :region: AND draw_days LIKE :draw_days:',
            'bind' => [
                'region' => 'XSMT',
                'draw_days' => '%' . $this->dayOfWeek . '%'
            ]
        ]);

        $urls = [
            "{$baseUrl}/", // IndexController - trang chủ
            "{$baseUrl}/thong-ke-xo-so-mien-trung-tk-xsmt.html", // ThongkemtController
            "{$baseUrl}/thong-ke-lo-gan-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html", // ThongkemtController
            "{$baseUrl}/thong-ke-dac-biet-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html", // ThongkemtController
            "{$baseUrl}/thong-ke-dau-duoi-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html", // ThongkemtController
            "{$baseUrl}/thong-ke-tan-suat-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html", // ThongkemtController
            "{$baseUrl}/ket-qua-xo-so-mien-trung-{$this->weekdaySlug}.html", // KetquaxsmtController
            "{$baseUrl}/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html", // KetquaxsmtController - URL chính
            "{$baseUrl}/du-doan-xsmt-c60.html", // DudoanController - trang chính dự đoán XSMT

            // URLs từ header - XSMT theo thứ
            "{$baseUrl}/xsmt-thu-2-ket-qua-xo-so-mien-trung.html",
            "{$baseUrl}/xsmt-thu-3-ket-qua-xo-so-mien-trung.html",
            "{$baseUrl}/xsmt-thu-4-ket-qua-xo-so-mien-trung.html",
            "{$baseUrl}/xsmt-thu-5-ket-qua-xo-so-mien-trung.html",
            "{$baseUrl}/xsmt-thu-6-ket-qua-xo-so-mien-trung.html",
            "{$baseUrl}/xsmt-thu-7-ket-qua-xo-so-mien-trung.html",
            "{$baseUrl}/xsmt-chu-nhat-ket-qua-xo-so-mien-trung.html",

            // URLs từ header - XSMT theo tỉnh
            "{$baseUrl}/ket-qua-xo-so-binh-dinh-xsbdinh.html",
            "{$baseUrl}/ket-qua-xo-so-da-nang-xsdna.html",
            "{$baseUrl}/ket-qua-xo-so-dak-lak-xsdlk.html",
            "{$baseUrl}/ket-qua-xo-so-dak-nong-xsdnong.html",
            "{$baseUrl}/ket-qua-xo-so-gia-lai-xsgl.html",
            "{$baseUrl}/ket-qua-xo-so-hue-xstth.html",
            "{$baseUrl}/ket-qua-xo-so-khanh-hoa-xskhoa.html",
            "{$baseUrl}/ket-qua-xo-so-kon-tum-xskontum.html",
            "{$baseUrl}/ket-qua-xo-so-ninh-thuan-xsnt.html",
            "{$baseUrl}/ket-qua-xo-so-phu-yen-xspy.html",
            "{$baseUrl}/ket-qua-xo-so-quang-binh-xsqb.html",
            "{$baseUrl}/ket-qua-xo-so-quang-nam-xsqn.html",
            "{$baseUrl}/ket-qua-xo-so-quang-ngai-xsqng.html",
            "{$baseUrl}/ket-qua-xo-so-quang-tri-xsqtri.html",

            // URLs thống kê theo tỉnh XSMT
            "{$baseUrl}/thong-ke-dac-biet-xsmn.html",
            "{$baseUrl}/thong-ke-dac-biet-xsmt.html",
            "{$baseUrl}/lo-gan-xsmb.html",
            "{$baseUrl}/thong-ke-lo-gan-xsmn.html",
            "{$baseUrl}/thong-ke-lo-gan-xsmt.html",
            "{$baseUrl}/",
            "{$baseUrl}/thong-ke-dau-duoi-mien-nam.html",
            "{$baseUrl}/thong-ke-dau-duoi-mien-trung.html",
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
            "{$baseUrl}/thong-ke-dau-duoi-xsdng.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsbdinh.html",
            "{$baseUrl}/thong-ke-dau-duoi-xstth.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsdlk.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsqng.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsqt.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsqb.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsqn.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsdno.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsgl.html",
            "{$baseUrl}/thong-ke-dau-duoi-xsnt.html",
            "{$baseUrl}/thong-ke-dau-duoi-xspy.html",
            "{$baseUrl}/thong-ke-dau-duoi-xskh.html",
            "{$baseUrl}/thong-ke-dau-duoi-xs-kontum.html",
            "{$baseUrl}/tan-suat-lo-to-xsmb.html",
            "{$baseUrl}/thong-ke-tan-suat-mien-trung.html",
            "{$baseUrl}/thong-ke-tan-suat-mien-nam.html",
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
            "{$baseUrl}/thong-ke-tan-suat-xscm.html",
            "{$baseUrl}/thong-ke-tan-suat-xsdng.html",
            "{$baseUrl}/thong-ke-tan-suat-xsbdinh.html",
            "{$baseUrl}/thong-ke-tan-suat-xstth.html",
            "{$baseUrl}/thong-ke-tan-suat-xsdlk.html",
            "{$baseUrl}/thong-ke-tan-suat-xsqng.html",
            "{$baseUrl}/thong-ke-tan-suat-xsqt.html",
            "{$baseUrl}/thong-ke-tan-suat-xsqb.html",
            "{$baseUrl}/thong-ke-tan-suat-xsqn.html",
            "{$baseUrl}/thong-ke-tan-suat-xsdno.html",
            "{$baseUrl}/thong-ke-tan-suat-xsgl.html",
            "{$baseUrl}/thong-ke-tan-suat-xsnt.html",
            "{$baseUrl}/thong-ke-tan-suat-xspy.html",
            "{$baseUrl}/thong-ke-tan-suat-xskh.html",
            "{$baseUrl}/thong-ke-tan-suat-xs-kontum.html"
        ];

        // Thêm URLs cho từng tỉnh theo thứ
        foreach ($provinces as $province) {
            $urls[] = "{$baseUrl}/xsmt-{$this->weekdaySlug}-ket-qua-xo-so-mien-trung.html";
        }

        // Thêm URL theo ngày cụ thể (hôm nay và các ngày xung quanh)
        $today = new \DateTime();

        // Thêm URLs cho 7 ngày trước và 1 ngày sau
        for ($i = -14; $i <= 1; $i++) {
            $date = clone $today;
            $date->modify("{$i} days");
            $day = $date->format('j');
            $month = $date->format('n');
            $year = $date->format('Y');
            $urls[] = "{$baseUrl}/xsmt-{$day}-{$month}-ket-qua-xo-so-mien-trung-ngay-{$day}-{$month}-{$year}.html";
        }

        // Mapping tỉnh XSMT với mã ngắn mới
        $xsmtProvinceMap = [
            'Huế' => 'xsh',
            'Phú Yên' => 'xspy',
            'Đắk Lắk' => 'xsdlk',
            'Quảng Nam' => 'xsqn',
            'Đà Nẵng' => 'xsdn',
            'Khánh Hòa' => 'xskh',
            'Bình Định' => 'xsbd',
            'Quảng Bình' => 'xsqb',
            'Quảng Trị' => 'xsqt',
            'Gia Lai' => 'xsgl',
            'Ninh Thuận' => 'xsnt',
            'Đắk Nông' => 'xsdnong',
            'Quảng Ngãi' => 'xsqng',
            'Kon Tum' => 'xskt'
        ];

        foreach ($provinces as $province) {
            // Tạo slug từ tên tỉnh (ví dụ: Quảng Nam -> quang-nam)
            $provinceSlug = strtolower(str_replace(' ', '-', $this->removeVietnameseAccents($province->name)));
            $provinceCode = strtolower($province->code);
            $urls[] = "{$baseUrl}/ket-qua-xo-so-{$provinceSlug}-{$provinceCode}.html";

            // Thêm URL trang dự đoán tỉnh cụ thể với mã ngắn mới
            $shortCode = $xsmtProvinceMap[$province->name] ?? 'xsh';
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
    $cacheManager = new CacheManagerXSMT();
    $cacheManager->processCache();
}
