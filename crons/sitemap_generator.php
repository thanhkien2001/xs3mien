<?php
/**
 * Cron job để tự động tạo sitemap URLs hàng ngày
 * Chạy mỗi ngày lúc 1:00 AM
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

use Phalcon\Di\FactoryDefault;

$di = new FactoryDefault();
Phalcon\Di\Di::setDefault($di);
include BASE_PATH . "/crons/common.php";
$config = include APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/loader.php';
require_once APP_PATH . '/config/services.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(0);

try {
    
    // Get current date
    $currentYear = date('Y');
    $currentMonth = date('m');
    $currentDay = date('d');
    
    echo "🚀 Bắt đầu tạo sitemap URLs cho ngày {$currentDay}/{$currentMonth}/{$currentYear}\n";
    
    // Tạo URLs cho ngày hiện tại và update vào file tháng
    generateDailyUrls($currentYear, $currentMonth, $currentDay);
    
    // Nếu là ngày đầu tháng, tạo URLs cho tháng trước
    if ($currentDay == '01') {
        $prevMonth = $currentMonth - 1;
        $prevYear = $currentYear;
        
        if ($prevMonth == 0) {
            $prevMonth = 12;
            $prevYear = $currentYear - 1;
        }
        
        echo "📅 Tạo URLs cho tháng trước: {$prevMonth}/{$prevYear}\n";
        generateMonthlyUrls($prevYear, $prevMonth);
    }
    
    echo "✅ Hoàn thành tạo sitemap URLs\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    error_log("Sitemap Generator Error: " . $e->getMessage());
}

/**
 * Tạo URLs cho ngày hiện tại và update vào file tháng
 */
function generateDailyUrls($year, $month, $day) {
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
    $baseUrl = 'https://soicau247.com';
    
    // Tạo URLs cho ngày hiện tại
    $dailyKetQuaUrls = generateKetQuaUrlsForDay($year, $month, $day, $baseUrl);
    $dailyVietlottUrls = generateVietlottUrlsForDay($year, $month, $day, $baseUrl);
    $dailyDuDoanUrls = generateDuDoanUrlsForDay($year, $month, $day, $baseUrl);
    
    // Update file tháng với URLs ngày hiện tại
    updateMonthlyFile($dailyKetQuaUrls, "ket-qua-{$year}-{$monthStr}");
    updateMonthlyFile($dailyVietlottUrls, "vietlott-{$year}-{$monthStr}");
    updateMonthlyFile($dailyDuDoanUrls, "du-doan-{$year}-{$monthStr}");
    
    echo "📊 Đã thêm " . count($dailyKetQuaUrls) . " URLs kết quả, " . count($dailyVietlottUrls) . " URLs Vietlott, " . count($dailyDuDoanUrls) . " URLs dự đoán (bao gồm tỉnh) cho ngày {$dayStr}/{$monthStr}/{$year}\n";
}

/**
 * Update file tháng với URLs mới
 */
function updateMonthlyFile($newUrls, $filename) {
    try {
        $cacheDir = __DIR__ . '/../cache/sitemaps/';
        
        // Tạo thư mục nếu chưa có
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $filePath = $cacheDir . $filename . '.json';
        
        $existingUrls = [];
        
        // Đọc file hiện tại nếu có
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
            // Kiểm tra xem URL đã tồn tại chưa
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
            echo "✅ Đã update file {$filename}.json với " . count($allUrls) . " URLs\n";
        } else {
            echo "❌ Lỗi khi update file: {$filePath}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Exception khi update file sitemap: " . $e->getMessage() . "\n";
    }
}

/**
 * Tạo URLs cho một tháng cụ thể
 */
function generateMonthlyUrls($year, $month) {
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $baseUrl = 'https://soicau247.com'; // Thay đổi theo domain của bạn
    
    // Tạo URLs cho Kết quả xổ số
    $ketQuaUrls = generateKetQuaUrls($year, $month, $baseUrl);
    saveUrlsToFile($ketQuaUrls, "ket-qua-{$year}-{$monthStr}");
    
    // Tạo URLs cho Vietlott
    $vietlottUrls = generateVietlottUrls($year, $month, $baseUrl);
    saveUrlsToFile($vietlottUrls, "vietlott-{$year}-{$monthStr}");
    
    // Tạo URLs cho Dự đoán (bao gồm cả tỉnh)
    // $duDoanUrls = generateDuDoanUrls($year, $month, $baseUrl);
    // saveUrlsToFile($duDoanUrls, "du-doan-{$year}-{$monthStr}");
    
    echo "📊 Đã tạo " . count($ketQuaUrls) . " URLs kết quả, " . count($vietlottUrls) . " URLs Vietlott, \n."  /*count($duDoanUrls) . " URLs dự đoán (bao gồm tỉnh)"*/;
}

/**
 * Tạo URLs cho kết quả xổ số của một ngày cụ thể
 */
function generateKetQuaUrlsForDay($year, $month, $day, $baseUrl) {
    $urls = [];
    // Không thêm số 0 đứng trước - dùng số nguyên trực tiếp
    $monthStr = (int)$month;
    $dayStr = (int)$day;
    $lastmod = date('Y-m-d\TH:i:s+07:00', mktime(0, 0, 0, $month, $day, $year));
    
    // XSMB
    $urls[] = [
        'url' => $baseUrl . "/xsmb-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-bac-ngay-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];
    
    // XSMN
    $urls[] = [
        'url' => $baseUrl . "/xsmn-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-nam-ngay-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];
    
    // XSMT
    $urls[] = [
        'url' => $baseUrl . "/xsmt-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-trung-ngay-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];
    
    return $urls;
}

/**
 * Tạo URLs cho Vietlott của một ngày cụ thể
 */
function generateVietlottUrlsForDay($year, $month, $day, $baseUrl) {
    $urls = [];
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
    $lastmod = date('Y-m-d\TH:i:s+07:00', mktime(0, 0, 0, $month, $day, $year));
    
    // Lấy thứ trong tuần
    $dayOfWeek = date('w', mktime(0, 0, 0, $month, $day, $year));
    
    // Thứ 3, 5, 7: Power 655 + Max3D Pro
    if (in_array($dayOfWeek, [2, 4, 6])) {
        $urls[] = [
            'url' => $baseUrl . "/ket-qua-xoso-power-6-55-vietlott-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        
        $urls[] = [
            'url' => $baseUrl . "/ket-qua-xoso-max-3d-pro-vietlott-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
    }
    
    // Thứ 2, 4, 6: Max 3D
    if (in_array($dayOfWeek, [1, 3, 5])) {
        $urls[] = [
            'url' => $baseUrl . "/ket-qua-xoso-max-3d-vietlott-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
    }
    
    // Thứ 4, 6, CN: Mega 645
    if (in_array($dayOfWeek, [3, 5, 0])) {
        $urls[] = [
            'url' => $baseUrl . "/ket-qua-xoso-mega-6-45-vietlott-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
    }
    
    // Keno quay hàng ngày
    $urls[] = [
        'url' => $baseUrl . "/ket-qua-xoso-keno-vietlott-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];
    
    return $urls;
}

/**
 * Tạo URLs cho dự đoán của một ngày cụ thể (bao gồm cả tỉnh)
 */
function generateDuDoanUrlsForDay($year, $month, $day, $baseUrl) {
    $urls = [];
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
    $lastmod = date('Y-m-d\TH:i:s+07:00', mktime(0, 0, 0, $month, $day, $year));
    
    // Lấy thứ trong tuần
    $dayOfWeek = date('N', mktime(0, 0, 0, $month, $day, $year)); // 1-7: Thứ 2 - Chủ nhật
    
    // Dự đoán XSMB (hàng ngày)
    $urls[] = [
        'url' => $baseUrl . "/du-doan-xsmb-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-bac-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];
    
    // Dự đoán XSMN (hàng ngày)
    $urls[] = [
        'url' => $baseUrl . "/du-doan-xsmn-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-nam-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];
    
    // Dự đoán XSMT (hàng ngày)
    $urls[] = [
        'url' => $baseUrl . "/du-doan-xsmt-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-trung-{$dayStr}-{$monthStr}-{$year}.html",
        'lastmod' => $lastmod,
        'priority' => '0.8',
        'changefreq' => 'daily'
    ];

    // Lịch quay theo thứ trong tuần
    $schedule = [
        // Thứ 2 (Monday)
        1 => [
            'xsmn' => ['ca-mau', 'tp-ho-chi-minh', 'dong-thap'],
            'xsmt' => ['thua-thien-hue', 'phu-yen']
        ],
        // Thứ 3 (Tuesday)
        2 => [
            'xsmn' => ['bac-lieu', 'ben-tre', 'vung-tau'],
            'xsmt' => ['dak-lak', 'quang-nam']
        ],
        // Thứ 4 (Wednesday)
        3 => [
            'xsmn' => ['can-tho', 'soc-trang', 'dong-nai'],
            'xsmt' => ['da-nang', 'khanh-hoa']
        ],
        // Thứ 5 (Thursday)
        4 => [
            'xsmn' => ['an-giang', 'binh-thuan', 'tay-ninh'],
            'xsmt' => ['binh-dinh', 'quang-binh', 'quang-tri']
        ],
        // Thứ 6 (Friday)
        5 => [
            'xsmn' => ['binh-duong', 'tra-vinh', 'vinh-long'],
            'xsmt' => ['gia-lai', 'ninh-thuan']
        ],
        // Thứ 7 (Saturday)
        6 => [
            'xsmn' => ['binh-phuoc', 'hau-giang', 'tp-ho-chi-minh', 'long-an'],
            'xsmt' => ['da-nang', 'dak-nong', 'quang-ngai']
        ],
        // Chủ nhật (Sunday)
        7 => [
            'xsmn' => ['kien-giang', 'tien-giang', 'da-lat'],
            'xsmt' => ['khanh-hoa', 'kon-tum', 'thua-thien-hue']
        ]
    ];
    
    // Tạo URLs cho từng tỉnh theo lịch quay
    if (isset($schedule[$dayOfWeek])) {
        // URLs cho tỉnh Miền Nam - ✅ BỎ prefix xsmn-
        if (isset($schedule[$dayOfWeek]['xsmn'])) {
            foreach ($schedule[$dayOfWeek]['xsmn'] as $province) {
                $provinceSlug = createProvinceSlug($province);
                $urls[] = [
                    'url' => $baseUrl . "/du-doan-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.7',
                    'changefreq' => 'daily'
                ];
            }
        }
        
        // URLs cho tỉnh Miền Trung - ✅ BỎ prefix xsmt-
        if (isset($schedule[$dayOfWeek]['xsmt'])) {
            foreach ($schedule[$dayOfWeek]['xsmt'] as $province) {
                $provinceSlug = createProvinceSlug($province);
                $urls[] = [
                    'url' => $baseUrl . "/du-doan-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.7',
                    'changefreq' => 'daily'
                ];
            }
        }
    }

    return $urls;
}

/**
 * Tạo URLs cho kết quả xổ số
 */
function generateKetQuaUrls($year, $month, $baseUrl) {
    $urls = [];
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
        $lastmod = date('Y-m-d\TH:i:s+07:00', mktime(0, 0, 0, $month, $day, $year));
        
        // XSMB
        $urls[] = [
            'url' => $baseUrl . "/xsmb-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-bac-ngay-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        
        // XSMN
        $urls[] = [
            'url' => $baseUrl . "/xsmn-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-nam-ngay-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        
        // XSMT
        $urls[] = [
            'url' => $baseUrl . "/xsmt-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-trung-ngay-{$dayStr}-{$monthStr}-{$year}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
    }
    
    return $urls;
}

/**
 * Tạo URLs cho Vietlott
 */
function generateVietlottUrls($year, $month, $baseUrl) {
    $urls = [];
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
        $lastmod = date('Y-m-d\TH:i:s+07:00', mktime(0, 0, 0, $month, $day, $year));
        
        // Lấy thứ trong tuần
        $dayOfWeek = date('w', mktime(0, 0, 0, $month, $day, $year));
        
        // Thứ 3, 5, 7: Power 655 + Max3D Pro
        if (in_array($dayOfWeek, [2, 4, 6])) {
            $urls[] = [
                'url' => $baseUrl . "/ket-qua-xoso-power-6-55-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                'lastmod' => $lastmod,
                'priority' => '0.8',
                'changefreq' => 'daily'
            ];
            
            $urls[] = [
                'url' => $baseUrl . "/ket-qua-xoso-max-3d-pro-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                'lastmod' => $lastmod,
                'priority' => '0.8',
                'changefreq' => 'daily'
            ];
        }
        
        // Thứ 2, 4, 6: Max 3D
        if (in_array($dayOfWeek, [1, 3, 5])) {
            $urls[] = [
                'url' => $baseUrl . "/ket-qua-xoso-max-3d-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                'lastmod' => $lastmod,
                'priority' => '0.8',
                'changefreq' => 'daily'
            ];
        }
        
        // Thứ 4, 6, CN: Mega 645
        if (in_array($dayOfWeek, [3, 5, 0])) {
            $urls[] = [
                'url' => $baseUrl . "/ket-qua-xoso-mega-6-45-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                'lastmod' => $lastmod,
                'priority' => '0.8',
                'changefreq' => 'daily'
            ];
        }
        
        // Keno quay hàng ngày
        $urls[] = [
            'url' => $baseUrl . "/ket-qua-xoso-keno-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
    }
    
    return $urls;
}


/**
 * Tạo slug cho tên tỉnh
 */
function createProvinceSlug($provinceName) {
    // Mapping tên tỉnh sang slug
    $provinceMap = [
        'ca-mau' => 'ca-mau',
        'tp-ho-chi-minh' => 'tp-ho-chi-minh',
        'dong-thap' => 'dong-thap',
        'bac-lieu' => 'bac-lieu',
        'ben-tre' => 'ben-tre',
        'vung-tau' => 'vung-tau',
        'can-tho' => 'can-tho',
        'soc-trang' => 'soc-trang',
        'dong-nai' => 'dong-nai',
        'an-giang' => 'an-giang',
        'binh-thuan' => 'binh-thuan',
        'tay-ninh' => 'tay-ninh',
        'binh-duong' => 'binh-duong',
        'tra-vinh' => 'tra-vinh',
        'vinh-long' => 'vinh-long',
        'binh-phuoc' => 'binh-phuoc',
        'hau-giang' => 'hau-giang',
        'long-an' => 'long-an',
        'kien-giang' => 'kien-giang',
        'tien-giang' => 'tien-giang',
        'da-lat' => 'da-lat',
        'thua-thien-hue' => 'thua-thien-hue',
        'phu-yen' => 'phu-yen',
        'dak-lak' => 'dak-lak',
        'quang-nam' => 'quang-nam',
        'da-nang' => 'da-nang',
        'khanh-hoa' => 'khanh-hoa',
        'binh-dinh' => 'binh-dinh',
        'quang-binh' => 'quang-binh',
        'quang-tri' => 'quang-tri',
        'gia-lai' => 'gia-lai',
        'ninh-thuan' => 'ninh-thuan',
        'dak-nong' => 'dak-nong',
        'quang-ngai' => 'quang-ngai',
        'kon-tum' => 'kon-tum'
    ];
    
    return $provinceMap[$provinceName] ?? strtolower(str_replace(' ', '-', $provinceName));
}

/**
 * Tạo URLs cho dự đoán của cả tháng (bao gồm cả tỉnh)
 */
// function generateDuDoanUrls($year, $month, $baseUrl) {
//     $urls = [];
//     $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
//     $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
//     for ($day = 1; $day <= $daysInMonth; $day++) {
//         $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
//         $lastmod = date('Y-m-d\TH:i:s+07:00', mktime(0, 0, 0, $month, $day, $year));
        
//         // Lấy thứ trong tuần
//         $dayOfWeek = date('N', mktime(0, 0, 0, $month, $day, $year)); // 1-7: Thứ 2 - Chủ nhật
        
//         // Dự đoán XSMB (hàng ngày)
//         $urls[] = [
//             'url' => $baseUrl . "/du-doan-xsmb-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-bac-{$dayStr}-{$monthStr}-{$year}.html",
//             'lastmod' => $lastmod,
//             'priority' => '0.8',
//             'changefreq' => 'daily'
//         ];
        
//         // Dự đoán XSMN (hàng ngày)
//         $urls[] = [
//             'url' => $baseUrl . "/du-doan-xsmn-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-nam-{$dayStr}-{$monthStr}-{$year}.html",
//             'lastmod' => $lastmod,
//             'priority' => '0.8',
//             'changefreq' => 'daily'
//         ];
        
//         // Dự đoán XSMT (hàng ngày)
//         $urls[] = [
//             'url' => $baseUrl . "/du-doan-xsmt-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-trung-{$dayStr}-{$monthStr}-{$year}.html",
//             'lastmod' => $lastmod,
//             'priority' => '0.8',
//             'changefreq' => 'daily'
//         ];
        
//         // Dự đoán Mega 6/45 (Thứ 4, 6, CN)
//         if (in_array($dayOfWeek, [3, 5, 7])) {
//             $urls[] = [
//                 'url' => $baseUrl . "/du-doan-mega-6-45-ngay-{$dayStr}-{$monthStr}-{$year}.html",
//                 'lastmod' => $lastmod,
//                 'priority' => '0.8',
//                 'changefreq' => 'daily'
//             ];
//         }
        
//         // Dự đoán Power 6/55 (Thứ 3, 5, 7)
//         if (in_array($dayOfWeek, [2, 4, 6])) {
//             $urls[] = [
//                 'url' => $baseUrl . "/du-doan-power-6-55-ngay-{$dayStr}-{$monthStr}-{$year}.html",
//                 'lastmod' => $lastmod,
//                 'priority' => '0.8',
//                 'changefreq' => 'daily'
//             ];
//         }

//         // Lịch quay theo thứ trong tuần
//         $schedule = [
//             // Thứ 2 (Monday)
//             1 => [
//                 'xsmn' => ['ca-mau', 'tp-ho-chi-minh', 'dong-thap'],
//                 'xsmt' => ['thua-thien-hue', 'phu-yen']
//             ],
//             // Thứ 3 (Tuesday)
//             2 => [
//                 'xsmn' => ['bac-lieu', 'ben-tre', 'vung-tau'],
//                 'xsmt' => ['dak-lak', 'quang-nam']
//             ],
//             // Thứ 4 (Wednesday)
//             3 => [
//                 'xsmn' => ['can-tho', 'soc-trang', 'dong-nai'],
//                 'xsmt' => ['da-nang', 'khanh-hoa']
//             ],
//             // Thứ 5 (Thursday)
//             4 => [
//                 'xsmn' => ['an-giang', 'binh-thuan', 'tay-ninh'],
//                 'xsmt' => ['binh-dinh', 'quang-binh', 'quang-tri']
//             ],
//             // Thứ 6 (Friday)
//             5 => [
//                 'xsmn' => ['binh-duong', 'tra-vinh', 'vinh-long'],
//                 'xsmt' => ['gia-lai', 'ninh-thuan']
//             ],
//             // Thứ 7 (Saturday)
//             6 => [
//                 'xsmn' => ['binh-phuoc', 'hau-giang', 'tp-ho-chi-minh', 'long-an'],
//                 'xsmt' => ['da-nang', 'dak-nong', 'quang-ngai']
//             ],
//             // Chủ nhật (Sunday)
//             7 => [
//                 'xsmn' => ['kien-giang', 'tien-giang', 'da-lat'],
//                 'xsmt' => ['khanh-hoa', 'kon-tum', 'thua-thien-hue']
//             ]
//         ];
        
//         // Tạo URLs cho từng tỉnh theo lịch quay
//         if (isset($schedule[$dayOfWeek])) {
//             // URLs cho tỉnh Miền Nam - ✅ BỎ prefix xsmn-
//             if (isset($schedule[$dayOfWeek]['xsmn'])) {
//                 foreach ($schedule[$dayOfWeek]['xsmn'] as $province) {
//                     $provinceSlug = createProvinceSlug($province);
//                     $urls[] = [
//                         'url' => $baseUrl . "/du-doan-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}.html",
//                         'lastmod' => $lastmod,
//                         'priority' => '0.7',
//                         'changefreq' => 'daily'
//                     ];
//                 }
//             }
            
//             // URLs cho tỉnh Miền Trung - ✅ BỎ prefix xsmt-
//             if (isset($schedule[$dayOfWeek]['xsmt'])) {
//                 foreach ($schedule[$dayOfWeek]['xsmt'] as $province) {
//                     $provinceSlug = createProvinceSlug($province);
//                     $urls[] = [
//                         'url' => $baseUrl . "/du-doan-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceSlug}-{$dayStr}-{$monthStr}-{$year}.html",
//                         'lastmod' => $lastmod,
//                         'priority' => '0.7',
//                         'changefreq' => 'daily'
//                     ];
//                 }
//             }
//         }
//     }
    
//     return $urls;
// }


/**
 * Lưu URLs vào file
 */
function saveUrlsToFile($urls, $filename) {
    try {
        $cacheDir = __DIR__ . '/../cache/sitemaps/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $filePath = $cacheDir . $filename . '.json';
        
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_urls' => count($urls),
            'urls' => $urls
        ];
        
        $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result !== false) {
            echo "✅ Đã lưu {$data['total_urls']} URLs vào file: {$filePath}\n";
        } else {
            echo "❌ Lỗi khi lưu file: {$filePath}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Exception khi lưu file sitemap: " . $e->getMessage() . "\n";
    }
}