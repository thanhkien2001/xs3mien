<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;
use App\Models\PredictionArticles;
use App\Models\Provinces;
use App\Library\DudoanHelper;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(0);

/**
 * Class CreatePredictionArticles
 * Tạo bài viết dự đoán cho XS 3 miền
 */
class CreatePredictionArticles
{
    private $di;
    private $db;
    private $cache;
    private $redis;
    private $baseUrl;

    public function __construct()
    {
        $this->di = Phalcon\Di\Di::getDefault();
        $this->db = $this->di->get('db');
        $this->cache = $this->di->get('modelsCache');
        $this->redis = $this->di->get('redis');
        $this->baseUrl = getBaseUrl();
    }

    /**
     * Tạo slug từ title
     */
    public function createSlug($title)
    {
        // Mapping các ký tự có dấu sang không dấu
        $map = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
            'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
            'Đ' => 'D'
        ];
        
        // Thay thế các ký tự có dấu
        $slug = strtr($title, $map);
        
        // Loại bỏ các ký tự đặc biệt, chỉ giữ lại chữ cái, số, khoảng trắng và dấu gạch ngang
        $slug = preg_replace('/[^a-zA-Z0-9\s-]/', '', $slug);
        
        // Chuyển về chữ thường
        $slug = strtolower(trim($slug));
        
        // Thay thế khoảng trắng và dấu gạch ngang liên tiếp bằng một dấu gạch ngang
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Tạo slug trực tiếp từ thông tin bài viết
     */
    public function createSlugFromInfo($region, $date, $provinceName = null)
    {
        $regionSlug = strtolower($region);
        $dateSlug = date('d-m-Y', strtotime($date));
        
        if ($provinceName) {
            // Slug cho tỉnh cụ thể - bỏ mã miền
            $provinceSlug = $this->createSlug($provinceName);
            return "du-doan-{$provinceSlug}-{$dateSlug}-soi-cau-xo-so-{$provinceSlug}-{$dateSlug}";
        } else {
            // Slug cho tổng hợp - giữ mã miền
            $regionNameSlug = ($region === 'XSMB') ? 'mien-bac' : (($region === 'XSMN') ? 'mien-nam' : 'mien-trung');
            return "du-doan-{$regionSlug}-{$dateSlug}-soi-cau-xo-so-{$regionNameSlug}-{$dateSlug}";
        }
    }

    /**
     * Tạo title cho bài viết dự đoán
     */
    public function generateTitle($region, $date, $provinceName = null)
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam', 
            'XSMT' => 'Miền Trung'
        ];
        
        $regionName = $regionNames[$region];
        $dateFormatted = date('d/m/Y', strtotime($date));
        $dateSlug = date('d-m-Y', strtotime($date));
        
        // Lấy thứ trong tuần
        $dayNames = [
            1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 
            5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'
        ];
        $dayOfWeek = $dayNames[date('N', strtotime($date))];
        
        if ($provinceName) {
            // Bỏ mã miền (XSMB, XSMN, XSMT) khi có tỉnh cụ thể
            return "Dự đoán {$provinceName} {$dateFormatted} ({$dayOfWeek}), soi cầu Xổ Số {$provinceName}";
        } else {
            // Giữ mã miền cho bài tổng hợp
            return "Dự đoán {$region} {$dateFormatted} ({$dayOfWeek}), soi cầu Xổ Số {$regionName}";
        }
    }

    /**
     * Tạo content cho bài viết dự đoán
     */
 public function generateContent($region, $date, $provinceName = null)
{
    $regionNames = [
        'XSMB' => 'Miền Bắc',
        'XSMN' => 'Miền Nam',
        'XSMT' => 'Miền Trung'
    ];
    
    $regionName = $regionNames[$region];
    $dateFormatted = date('d/m/Y', strtotime($date));
    $dateSlug = date('d-m-Y', strtotime($date));
    
    if ($provinceName) {
        // Content cho tỉnh cụ thể
        return "Nhận định và phân tích kết quả xổ số {$provinceName} ngày {$dateSlug}. Thống kê các cặp số hot, lô gan, dự báo giải đặc biệt và các giải phụ dựa trên dữ liệu thống kê.";
    } else {
        // Content cho tổng hợp
        return "Nhận định và phân tích kết quả xổ số {$regionName} ngày {$dateSlug}. Thống kê các cặp số hot, lô gan, dự báo giải đặc biệt và các giải phụ dựa trên dữ liệu thống kê.";
    }
}
    public function getImageUrl($region, $provinceName = null)
    {
        // Nếu là bài chung cho region
        if ($provinceName === null) {
            $imageMap = [
                'XSMB' => '/img/soicaududoan/xsmb/dudoanxsmb.png',
                'XSMN' => '/img/soicaududoan/xsmn/dudoanxsmn.png',
                'XSMT' => '/img/soicaududoan/xsmt/dudoanxsmt.png'
            ];
            return $imageMap[$region] ?? '/img/soicaududoan/xsmb/dudoanxsmb.png';
        }
        
        // Mapping tên tỉnh với file ảnh
        $provinceImageMap = [
            'XSMB' => [
                'Hà Nội' => 'hanoi.png',
                'Quảng Ninh' => 'quangninh.png',
                'Bắc Ninh' => 'bacninh.png',
                'Hải Phòng' => 'haiphong.png',
                'Nam Định' => 'namdinh.png',
                'Thái Bình' => 'thaibinh.png',
            ],
            'XSMN' => [
                'An Giang' => 'angiang.png',
                'Bạc Liêu' => 'baclieu.png',
                'Bến Tre' => 'bentre.png',
                'Bình Dương' => 'binhduong.png',
                'Bình Phước' => 'binhphuoc.png',
                'Bình Thuận' => 'binhthuan.png',
                'Cà Mau' => 'camau.png',
                'Cần Thơ' => 'cantho.png',
                'Đà Lạt' => 'dalat.png',
                'Đồng Nai' => 'dongnai.png',
                'Đồng Tháp' => 'dongthap.png',
                'Hậu Giang' => 'haugiang.png',
                'Hồ Chí Minh' => 'hochiminh.png',
                'Kiên Giang' => 'kiengiang.png',
                'Long An' => 'longan.png',
                'Sóc Trăng' => 'soctrang.png',
                'Tây Ninh' => 'tayninh.png',
                'Tiền Giang' => 'tiengiang.png',
                'Trà Vinh' => 'travinh.png',
                'Vĩnh Long' => 'vinhlong.png',
                'Vũng Tàu' => 'vungtau.png'
            ],
            'XSMT' => [
                'Bình Định' => 'binhdinh.png',
                'Đắk Lắk' => 'daklak.png',
                'Đắk Nông' => 'daknong.png',
                'Đà Nẵng' => 'danang.png',
                'Gia Lai' => 'gialai.png',
                'Huế' => 'hue.png',
                'Khánh Hòa' => 'khanhoa.png',
                'Kon Tum' => 'kontum.png',
                'Ninh Thuận' => 'ninhthuan.png',
                'Phú Yên' => 'phuyen.png',
                'Quảng Bình' => 'quangbinh.png',
                'Quảng Nam' => 'quangnam.png',
                'Quảng Ngãi' => 'quangngai.png',
                'Quảng Trị' => 'quangtri.png'
            ]
        ];
        
        if (isset($provinceImageMap[$region][$provinceName])) {
            return "/img/soicaududoan/" . strtolower($region) . "/" . $provinceImageMap[$region][$provinceName];
        }
        
        // Fallback nếu không tìm thấy mapping
        $provinceSlug = strtolower($provinceName);
        $provinceSlug = str_replace([' ', 'đ', 'Đ'], ['', 'd', 'd'], $provinceSlug);
        $provinceSlug = preg_replace('/[^a-z0-9]/', '', $provinceSlug);
        
        return "/img/soicaududoan/" . strtolower($region) . "/{$provinceSlug}.png";
    }

    /**
     * Tạo bài viết dự đoán
     */
    public function createPredictionArticle($region, $date, $provinceId = null, $provinceName = null)
    {
        try {
            // Kiểm tra xem bài viết đã tồn tại chưa
            $conditions = 'region = :region: AND prediction_date = :date:';
            $bind = ['region' => $region, 'date' => $date];
            
            if ($provinceId === null) {
                $conditions .= ' AND province_id IS NULL';
            } else {
                $conditions .= ' AND province_id = :province_id:';
                $bind['province_id'] = $provinceId;
            }
            
            $existing = PredictionArticles::findFirst([
                'conditions' => $conditions,
                'bind' => $bind
            ]);
            
            if ($existing) {
                echo "Bài viết đã tồn tại cho {$region} " . ($provinceName ?? 'chung') . " ngày {$date}\n";
                return false;
            }
            
            $title = $this->generateTitle($region, $date, $provinceName);
            $slug = $this->createSlugFromInfo($region, $date, $provinceName);
            $content = $this->generateContent($region, $date, $provinceName);
            $imageUrl = $this->getImageUrl($region, $provinceName);
            
            // Tạo bài viết mới
            $prediction = new PredictionArticles();
            $prediction->title = $title;
            $prediction->slug = $slug;
            $prediction->content = $content;
            $prediction->prediction_date = $date;
            $prediction->region = $region;
            $prediction->image_url = $imageUrl;
            $prediction->province_id = $provinceId;
            
            if ($prediction->save()) {
                echo "✅ Đã tạo bài viết: {$title}\n";
                return $prediction; // Trả về object thay vì true
            } else {
                echo "❌ Lỗi khi tạo bài viết: " . implode(', ', $prediction->getMessages()) . "\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi tạo bài viết cho {$region} " . ($provinceName ?? 'chung') . ": " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Xóa cache cho XS 3 miền và detail 3 miền
     */
    public function clearCacheForAllRegions($regions = ['XSMB', 'XSMN', 'XSMT'])
    {
        echo "\n🗑️ XÓA CACHE CHO XS 3 MIỀN VÀ DETAIL 3 MIỀN\n";
        
        
        foreach ($regions as $region) {
            // Xóa cache list dự đoán
            $listCacheKeys = [
                DudoanHelper::buildCacheKey($region, 'list10'),
                DudoanHelper::buildCacheKey($region, 'list5'),
                DudoanHelper::buildCacheKey($region, 'latest'),
            ];
            
            foreach ($listCacheKeys as $key) {
                $this->cache->delete($key);
                $this->redis->del($key);
                echo "🗑️ Deleted cache key: $key\n";
            }
            
            // Xóa cache detail theo pattern - tìm trong cả database 0 và 1
            $detailPatterns = [
                "models_{$region}_Dudoan_detail_*",
                "models_{$region}_dudoan_detail_*",
                "models_{$region}_list10",
                "models_{$region}_list*",
                "models_dudoan_soicau_list*",
            ];
            
            // Tìm trong database 0 (models cache)
            $this->redis->select(0);
            foreach ($detailPatterns as $pattern) {
                $keys = $this->redis->keys($pattern);
                if ($keys) {
                    foreach ($keys as $key) {
                        $this->cache->delete($key);
                        $this->redis->del($key);
                        echo "🗑️ Deleted models cache: $key\n";
                    }
                }
            }
            
            // Tìm trong database 1 (view cache) 
            $this->redis->select(1);
            foreach ($detailPatterns as $pattern) {
                $keys = $this->redis->keys($pattern);
                if ($keys) {
                    foreach ($keys as $key) {
                        $this->redis->del($key);
                        echo "🗑️ Deleted view cache: $key\n";
                    }
                }
            }
        }
        
        // Xóa cache chung - tìm trong cả database 0 và 1
        $globalCacheKeys = [
            'all_predictions_latest',
            'homepage_predictions',
        ];
        
        $globalPatternKeys = [
            'models_Dudoan_*',
        ];
        
        // Xóa cache chung (không có pattern)
        foreach ($globalCacheKeys as $key) {
            $this->cache->delete($key);
            $this->redis->del($key);
            echo "🗑️ Deleted global cache: $key\n";
        }
        
        // Xóa cache theo pattern - tìm trong cả 2 database
        foreach ($globalPatternKeys as $pattern) {
            // Database 0 (models cache)
            $this->redis->select(0);
            $keys = $this->redis->keys($pattern);
            if ($keys) {
                foreach ($keys as $k) {
                    $this->cache->delete($k);
                    $this->redis->del($k);
                    echo "🗑️ Deleted global models cache: $k\n";
                }
            }
            
            // Database 1 (view cache)
            $this->redis->select(1);
            $keys = $this->redis->keys($pattern);
            if ($keys) {
                foreach ($keys as $k) {
                    $this->redis->del($k);
                    echo "🗑️ Deleted global view cache: $k\n";
                }
            }
        }
        
        echo "✅ Đã xóa cache cho XS 3 miền và detail 3 miền\n";
    }

    /**
     * Cập nhật cache cho một region
     */
    public function updateCacheForRegion($region)
    {
        try {
            // Lấy danh sách bài viết mới nhất
            $predictions = PredictionArticles::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => $region],
                'order' => 'prediction_date DESC, province_id ASC, id DESC',
                'limit' => 10
            ]);
            
            $finalResult = [
                'predictions' => $predictions,
                'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
            ];
            
            // Cập nhật cache
            $cacheKey = DudoanHelper::buildCacheKey($region, 'list10');
            $this->cache->set($cacheKey, $finalResult, 86000);
            
            echo "🔄 Đã cập nhật cache cho {$region}: {$cacheKey} (" . $predictions->count() . " bài viết)\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi cập nhật cache cho {$region}: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Cập nhật cache cho XS 3 miền và detail 3 miền
     */
    public function updateCacheForAllRegions($createdArticles = [], $regions = ['XSMB', 'XSMN', 'XSMT'])
    {
        echo "\n🔄 CẬP NHẬT CACHE CHO XS 3 MIỀN VÀ DETAIL 3 MIỀN\n";
        
        
        foreach ($regions as $region) {
            try {
                // Cập nhật cache list dự đoán
                $this->updateCacheForRegion($region);
                
                // Cập nhật cache detail bằng cách gọi URL
                $detailUrls = [];
                if ($region === 'XSMB') {
                    $detailUrls[] = $this->baseUrl . "/du-doan-xsmb-c59.html";
                } elseif ($region === 'XSMN') {
                    $detailUrls[] = $this->baseUrl . "/du-doan-xsmn-c61.html";
                } elseif ($region === 'XSMT') {
                    $detailUrls[] = $this->baseUrl . "/du-doan-xsmt-c60.html";
                }
                $detailUrls[] = $this->baseUrl . "/du-doan-xo-so-soi-cau.html";
                
                foreach ($detailUrls as $url) {
                    $this->updateCacheByUrl($url);
                }
                
            } catch (Exception $e) {
                echo "❌ Lỗi khi cập nhật cache cho {$region}: " . $e->getMessage() . "\n";
            }
        }
        
        // Cập nhật cache chung
        $globalUrls = [
            $this->baseUrl . "/du-doan-xo-so-soi-cau.html",
        ];
        if (in_array('XSMB', $regions, true)) {
            $globalUrls[] = $this->baseUrl . "/du-doan-xsmb-c59.html";
        }
        if (in_array('XSMN', $regions, true)) {
            $globalUrls[] = $this->baseUrl . "/du-doan-xsmn-c61.html";
        }
        if (in_array('XSMT', $regions, true)) {
            $globalUrls[] = $this->baseUrl . "/du-doan-xsmt-c60.html";
        }
        
        foreach ($globalUrls as $url) {
            $this->updateCacheByUrl($url);
        }
        
        // Cập nhật cache detail cho bài viết mới tạo
        if (!empty($createdArticles)) {
            echo "\n🔄 CẬP NHẬT CACHE DETAIL CHO BÀI VIẾT MỚI\n";
            $this->updateCacheForNewArticles($createdArticles);
        }
        
        echo "✅ Đã cập nhật cache cho XS 3 miền và detail 3 miền\n";
    }

    /**
     * Cập nhật cache detail cho bài viết mới tạo
     */
    public function updateCacheForNewArticles($createdArticles)
    {
        foreach ($createdArticles as $region => $articles) {
            if (empty($articles['slugs'])) continue;
            
            echo "📝 Cập nhật cache detail cho {$region} (" . count($articles['slugs']) . " bài viết)\n";
            
            foreach ($articles['slugs'] as $slug) {
                // Xác định URL detail dựa trên region - sử dụng format mới
                $detailUrl = '';
                if ($region === 'XSMB') {
                    $detailUrl = $this->baseUrl . "/{$slug}.html";
                } elseif ($region === 'XSMN') {
                    $detailUrl = $this->baseUrl . "/{$slug}.html";
                } elseif ($region === 'XSMT') {
                    $detailUrl = $this->baseUrl . "/{$slug}.html";
                }
                
                if ($detailUrl) {
                    $this->updateCacheByUrl($detailUrl);
                }
            }
        }
    }

    /**
     * Xóa view cache theo prefix cho region
     */
    public function clearViewCacheForRegion($region)
    {
        echo "\n🗑️ XÓA VIEW CACHE CHO {$region}\n";
        
        try {
            // Chuyển sang database 1 (viewCache) trước
            $this->redis->select(1);
            
            // Xóa cache cho các trang list
            $regionLower = strtolower($region);
            $listKeys = [
                "page_du-doan-{$regionLower}",
                "page_dudoansoicau_list",
                "page_{$regionLower}dudoan_list",
            ];
            
            foreach ($listKeys as $key) {
                $deleted = $this->redis->del($key);
                echo "🗑️ Deleted view cache: $key (result: $deleted)\n";
            }
            
            // Xóa cache cho các trang detail theo prefix
            $detailPrefix = "page_detail" . strtolower($region) . "_";
            $detailKeys = $this->redis->keys($detailPrefix . "*");
            
            echo "🔍 Tìm thấy " . count($detailKeys) . " keys với pattern: {$detailPrefix}*\n";
            foreach ($detailKeys as $key) {
                $deleted = $this->redis->del($key);
                echo "🗑️ Deleted detail cache: $key (result: $deleted)\n";
            }
            
            echo "✅ Đã xóa view cache cho {$region} (" . count($detailKeys) . " detail keys)\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi xóa view cache cho {$region}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Lấy 15 slug mới nhất cho region
     */
    public function getLatestSlugsForRegion($region, $limit = 20)
    {
        try {
            $predictions = PredictionArticles::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => $region],
                'order' => 'prediction_date DESC, id DESC',
                'limit' => $limit,
                'columns' => 'slug'
            ]);
            
            $slugs = [];
            foreach ($predictions as $prediction) {
                $slugs[] = $prediction->slug;
            }
            
            echo "📝 Lấy được " . count($slugs) . " slug cho {$region}\n";
            return $slugs;
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi lấy slug cho {$region}: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Cập nhật view cache cho region
     */
    public function updateViewCacheForRegion($region)
    {
        echo "\n🔄 CẬP NHẬT VIEW CACHE CHO {$region}\n";
        
        try {
            // Cập nhật cache cho các trang list
            $regionLower = strtolower($region);
            $listUrls = [
                $this->baseUrl . "/du-doan-{$regionLower}.html",
                $this->baseUrl . "/du-doan-xo-so-soi-cau.html",
            ];
            
            foreach ($listUrls as $url) {
                $this->updateCacheByUrl($url);
            }
            
            // Cập nhật cache cho các trang detail (lấy 15 slug mới nhất)
            $slugs = $this->getLatestSlugsForRegion($region, 20);
            foreach ($slugs as $slug) {
                $detailUrl = $this->baseUrl . "/{$slug}.html";
                $this->updateCacheByUrl($detailUrl);
            }
            
            echo "✅ Đã cập nhật view cache cho {$region}\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi cập nhật view cache cho {$region}: " . $e->getMessage() . "\n";
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
     * Tạo bài viết cho một region
     */
    public function createArticlesForRegion($region)
    {
        $createdCount = 0;
        $createdSlugs = []; // Thu thập slugs của bài viết mới tạo
        
        // Lấy ngày mai
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayOfWeek = date('N', strtotime($tomorrow)); // 1-7: Thứ 2 - Chủ nhật
        
        echo "\n--- TẠO BÀI VIẾT CHO {$region} ---\n";
        echo "Ngày: {$tomorrow} (Thứ {$dayOfWeek})\n";
        
        // Lấy danh sách tỉnh quay vào ngày mai
        $provinces = Provinces::find([
            'conditions' => 'region = :region: AND FIND_IN_SET(:day:, draw_days)',
            'bind' => ['region' => $region, 'day' => $dayOfWeek],
            'order' => 'id ASC'
        ]);
        
        if ($provinces->count() === 0) {
            echo "Không có tỉnh nào quay {$region} vào ngày mai\n";
            return ['count' => 0, 'slugs' => [], 'date' => $tomorrow];
        }
        
        $provinceNames = [];
        foreach ($provinces as $province) {
            $provinceNames[] = $province->name;
        }
        echo "Tìm thấy " . $provinces->count() . " tỉnh: " . implode(', ', $provinceNames) . "\n";
        
        // Tạo bài chung cho region
        $result = $this->createPredictionArticle($region, $tomorrow, null, null);
        if ($result) {
            $createdCount++;
            $createdSlugs[] = $result->slug;
        }
        
        // Tạo bài riêng biệt cho từng tỉnh
        foreach ($provinces as $province) {
            $result = $this->createPredictionArticle($region, $tomorrow, $province->id, $province->name);
            if ($result) {
                $createdCount++;
                $createdSlugs[] = $result->slug;
            }
        }
        
        return ['count' => $createdCount, 'slugs' => $createdSlugs, 'date' => $tomorrow];
    }

    /**
     * Tạo sitemap URLs cho một region sau khi tạo bài viết
     */
    public function generateSitemapUrlsForRegion($region, $date)
    {
        try {
            // Parse ngày
            $dateParts = explode('-', $date);
            $year = (int)$dateParts[0];
            $month = (int)$dateParts[1];
            $day = (int)$dateParts[2];
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
            
            // Map region sang timezone cho lastmod
            $regionTimes = [
                'XSMB' => ['hour' => 18, 'minute' => 30],
                'XSMN' => ['hour' => 16, 'minute' => 30],
                'XSMT' => ['hour' => 17, 'minute' => 30]
            ];
            
            $time = $regionTimes[$region] ?? ['hour' => 0, 'minute' => 0];
            $lastmod = date('Y-m-d\TH:i:s+07:00', mktime($time['hour'], $time['minute'], 0, $month, $day, $year));
            
            // Lấy thứ trong tuần
            $dayOfWeek = date('N', strtotime($date)); // 1-7: Thứ 2 - Chủ nhật
            
            // Lịch quay theo thứ trong tuần
            $schedule = [
                1 => ['xsmb' => ['ha-noi'], 'xsmn' => ['ca-mau', 'tp-ho-chi-minh', 'dong-thap'], 'xsmt' => ['thua-thien-hue', 'phu-yen']],
                2 => ['xsmb' => ['quang-ninh'], 'xsmn' => ['bac-lieu', 'ben-tre', 'vung-tau'], 'xsmt' => ['dak-lak', 'quang-nam']],
                3 => ['xsmb' => ['bac-ninh'], 'xsmn' => ['can-tho', 'soc-trang', 'dong-nai'], 'xsmt' => ['da-nang', 'khanh-hoa']],
                4 => ['xsmb' => ['ha-noi'], 'xsmn' => ['an-giang', 'binh-thuan', 'tay-ninh'], 'xsmt' => ['binh-dinh', 'quang-binh', 'quang-tri']],
                5 => ['xsmb' => ['hai-phong'], 'xsmn' => ['binh-duong', 'tra-vinh', 'vinh-long'], 'xsmt' => ['gia-lai', 'ninh-thuan']],
                6 => ['xsmb' => ['nam-dinh'], 'xsmn' => ['binh-phuoc', 'hau-giang', 'tp-ho-chi-minh', 'long-an'], 'xsmt' => ['da-nang', 'dak-nong', 'quang-ngai']],
                7 => ['xsmb' => ['thai-binh'], 'xsmn' => ['kien-giang', 'tien-giang', 'da-lat'], 'xsmt' => ['khanh-hoa', 'kon-tum', 'thua-thien-hue']]
            ];
            
            // Mapping tên tỉnh sang slug
            $provinceMap = [
                // Miền Bắc
                'ha-noi' => 'ha-noi',
                'quang-ninh' => 'quang-ninh',
                'bac-ninh' => 'bac-ninh',
                'hai-phong' => 'hai-phong',
                'nam-dinh' => 'nam-dinh',
                'thai-binh' => 'thai-binh',
                // Miền Nam
                'ca-mau' => 'ca-mau', 'tp-ho-chi-minh' => 'tp-ho-chi-minh', 'dong-thap' => 'dong-thap',
                'bac-lieu' => 'bac-lieu', 'ben-tre' => 'ben-tre', 'vung-tau' => 'vung-tau',
                'can-tho' => 'can-tho', 'soc-trang' => 'soc-trang', 'dong-nai' => 'dong-nai',
                'an-giang' => 'an-giang', 'binh-thuan' => 'binh-thuan', 'tay-ninh' => 'tay-ninh',
                'binh-duong' => 'binh-duong', 'tra-vinh' => 'tra-vinh', 'vinh-long' => 'vinh-long',
                'binh-phuoc' => 'binh-phuoc', 'hau-giang' => 'hau-giang', 'long-an' => 'long-an',
                'kien-giang' => 'kien-giang', 'tien-giang' => 'tien-giang', 'da-lat' => 'da-lat',
                // Miền Trung
                'thua-thien-hue' => 'thua-thien-hue', 'phu-yen' => 'phu-yen', 'dak-lak' => 'dak-lak',
                'quang-nam' => 'quang-nam', 'da-nang' => 'da-nang', 'khanh-hoa' => 'khanh-hoa',
                'binh-dinh' => 'binh-dinh', 'quang-binh' => 'quang-binh', 'quang-tri' => 'quang-tri',
                'gia-lai' => 'gia-lai', 'ninh-thuan' => 'ninh-thuan', 'dak-nong' => 'dak-nong',
                'quang-ngai' => 'quang-ngai', 'kon-tum' => 'kon-tum'
            ];
            
            $urls = [];
            $regionKey = strtolower($region); // xsmb, xsmt hoặc xsmn
            
            // Tạo URL cho tổng hợp miền
            if ($region === 'XSMB') {
                $urls[] = [
                    'url' => "https://soicau247.com/du-doan-xsmb-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-bac-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];
            } elseif ($region === 'XSMN') {
                $urls[] = [
                    'url' => "https://soicau247.com/du-doan-xsmn-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-nam-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];
            } elseif ($region === 'XSMT') {
                $urls[] = [
                    'url' => "https://soicau247.com/du-doan-xsmt-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-trung-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];
            }
            
            // Tạo URLs cho từng tỉnh theo lịch quay
            if (isset($schedule[$dayOfWeek][$regionKey])) {
                foreach ($schedule[$dayOfWeek][$regionKey] as $province) {
                    $provinceSlug = $provinceMap[$province] ?? strtolower(str_replace(' ', '-', $province));
                    $urls[] = [
                        'url' => "https://soicau247.com/du-doan-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.7',
                        'changefreq' => 'daily'
                    ];
                }
            }
            
            // Update file sitemap
            $filename = "du-doan-{$year}-{$monthStr}";
            $this->updateSitemapFile($urls, $filename);
            
            echo "📍 Đã tạo " . count($urls) . " sitemap URLs cho {$region}\n";
            
        } catch (Exception $e) {
            echo "❌ Lỗi khi tạo sitemap URLs cho {$region}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Update file sitemap với URLs mới
     */
    private function updateSitemapFile($newUrls, $filename)
    {
        try {
            $cacheDir = BASE_PATH . '/cache/sitemaps/';
            
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            $filePath = $cacheDir . $filename . '.json';
            $existingUrls = [];
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $data = json_decode($content, true);
                if ($data && isset($data['urls'])) {
                    $existingUrls = $data['urls'];
                }
            }
            
            // Merge URLs mới với URLs cũ (tránh duplicate)
            $allUrls = $existingUrls;
            foreach ($newUrls as $newUrl) {
                $exists = false;
                foreach ($allUrls as $existingUrl) {
                    if ($existingUrl['url'] === $newUrl['url']) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $allUrls[] = $newUrl;
                }
            }
            
            // Lưu file đã update
            $data = [
                'generated_at' => date('Y-m-d H:i:s'),
                'total_urls' => count($allUrls),
                'urls' => $allUrls
            ];
            
            $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            if ($result !== false) {
                echo "✅ Đã update file sitemap {$filename}.json với " . count($allUrls) . " URLs\n";
            } else {
                echo "❌ Lỗi khi update file sitemap: {$filePath}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Exception khi update file sitemap: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Chạy script chính
     */
    public function run($regions = null)
    {
        echo "=== BẮT ĐẦU TẠO BÀI VIẾT DỰ ĐOÁN ===\n";
        echo "Thời gian: " . date('d/m/Y H:i:s') . "\n\n";

        $totalCreated = 0;
        if ($regions === null) {
            $regions = ['XSMB', 'XSMN', 'XSMT'];
        }
        $createdArticles = []; // Thu thập bài viết mới tạo

        // Xóa cache cũ cho tất cả regions
        echo "\n🗑️ XÓA CACHE CŨ CHO TẤT CẢ REGIONS\n";
        $this->clearCacheForAllRegions($regions);

        foreach ($regions as $region) {
            // Xóa view cache cho region cụ thể
            $this->clearViewCacheForRegion($region);
            
            // Tạo bài viết mới
            echo "\n📝 TẠO BÀI VIẾT CHO {$region}\n";
            $result = $this->createArticlesForRegion($region);
            $totalCreated += $result['count'];
            $createdArticles[$region] = $result; // Lưu slugs để update cache detail
            
            // Tạo sitemap URLs cho region vừa tạo
            if ($result['count'] > 0) {
                echo "\n🗺️ TẠO SITEMAP URLs CHO {$region}\n";
                $this->generateSitemapUrlsForRegion($region, $result['date']);
            }
        }

        // Cập nhật cache mới cho tất cả regions
        echo "\n🔄 CẬP NHẬT CACHE MỚI CHO TẤT CẢ REGIONS\n";
        $this->updateCacheForAllRegions($createdArticles, $regions);
        
        // Cập nhật view cache cho từng region
        foreach ($regions as $region) {
            $this->updateViewCacheForRegion($region);
        }

        echo "\n=== HOÀN THÀNH ===\n";
        echo "Tổng số bài viết đã tạo: {$totalCreated}\n";
        echo "Thời gian: " . date('d/m/Y H:i:s') . "\n";

        echo "Tạo bài viết dự đoán hoàn thành.\n";
    }
}

// Chạy script nếu được gọi trực tiếp
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $regions = null;
    if (!empty($argv)) {
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            if (strpos($arg, '--region=') === 0) {
                $value = strtoupper(substr($arg, 9));
                if (in_array($value, ['XSMB', 'XSMN', 'XSMT'], true)) {
                    $regions = [$value];
                }
            } elseif ($arg === '--region' && isset($argv[$i + 1])) {
                $value = strtoupper($argv[$i + 1]);
                if (in_array($value, ['XSMB', 'XSMN', 'XSMT'], true)) {
                    $regions = [$value];
                }
                $i++;
            }
        }
    }
    $creator = new CreatePredictionArticles();
    $creator->run($regions);
}
