<?php

namespace App\Library;

use App\Models\SeoMetadata;

class SeoHelper
{
    /**
     * Get the correct base URL for the current environment
     */
    private static function getBaseUrl(): string
    {
        // 1. Try BASE_URL from environment (explicitly set for the frontend domain)
        $baseUrl = getenv('BASE_URL');
        if ($baseUrl) {
            return rtrim($baseUrl, '/');
        }

        // 2. Try HTTP_HOST for dynamic environments (like ngrok or local dev)
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }

        // 3. Fallback to API_URL (legacy behavior)
        $apiUrl = getenv('API_URL');
        if ($apiUrl) {
            return rtrim($apiUrl, '/');
        }
        
        // 4. Ultimate fallback to production domain
        return 'https://soicau247.com';
    }

    /**
     * Generate SEO meta for custom pages (managed via SeoFieldManager under 'custom_pages')
     */
    public static function generateCustomPageMeta(string $pageSubtype, array $variables = []): array
    {
        $seoFieldManager = new SeoFieldManager();
        $customSeoData = $seoFieldManager->generateSeoData('custom_pages', $pageSubtype, $variables);
        
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        return [
            'seo_title' => $customSeoData['title_template'] ?? '',
            'seo_description' => $customSeoData['description_template'] ?? '',
            'seo_keywords' => $customSeoData['keywords_template'] ?? '',
            'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
            'og_title' => $customSeoData['title_template'] ?? '',
            'og_description' => $customSeoData['description_template'] ?? '',
            'canonical_url' => $canonicalUrl
        ];
    }

    // Page kết quả xổ số theo thứ và theo tỉnh
    public static function generateArchiveMeta(string $region, ?string $weekdaySlug = null, $province = null, int $page = 1): array
    {
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';

        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $weekdayNames = [
            'thu-2' => 'Thứ 2',
            'thu-3' => 'Thứ 3',
            'thu-4' => 'Thứ 4',
            'thu-5' => 'Thứ 5',
            'thu-6' => 'Thứ 6',
            'thu-7' => 'Thứ 7',
            'chu-nhat' => 'Chủ nhật'
        ];

        $regionName = $regionNames[$region] ?? $region;
        $seoFieldManager = new \App\Library\SeoFieldManager();

        // Chuẩn bị biến để thay thế trong template
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region, // Thêm regionCode
            'provinceName' => $province ? $province->name : '',
            'provinceCode' => $province ? strtoupper($province->code) : '', // Thêm provinceCode
            'weekdayText' => $weekdayNames[$weekdaySlug] ?? ''
        ];

        if ($province) {
            // Theo tỉnh - sử dụng custom fields
            $seoData = $seoFieldManager->generateSeoData('archive', 'province', $variables);
            
            // Fallback nếu không có custom fields
            if (empty($seoData)) {
            $title = "Lịch sử kết quả xổ số {$province->name} - {$regionName}";
            $description = "Xem lịch sử kết quả xổ số {$province->name} {$regionName} qua các kỳ quay. Dữ liệu chính xác, cập nhật liên tục.";
                $keywords = "lịch sử kết quả xổ số, {$province->name}, {$regionName}, xsmb, xsmn, xsmt, kết quả xổ số cũ";
        } else {
                $title = $seoData['title_template'] ?? "Lịch sử kết quả xổ số {$province->name} - {$regionName}";
                $description = $seoData['description_template'] ?? "Xem lịch sử kết quả xổ số {$province->name} {$regionName} qua các kỳ quay. Dữ liệu chính xác, cập nhật liên tục.";
                $keywords = $seoData['keywords_template'] ?? "lịch sử kết quả xổ số, {$province->name}, {$regionName}, xsmb, xsmn, xsmt, kết quả xổ số cũ";
            }
        } else {
            // Theo thứ - sử dụng custom fields
            $seoData = $seoFieldManager->generateSeoData('archive', 'weekday', $variables);
            
            // Fallback nếu không có custom fields
            if (empty($seoData)) {
                $title = "Kết quả xổ số {$regionName} {$variables['weekdayText']} - Lịch sử kết quả";
                $description = "Xem lịch sử kết quả xổ số {$regionName} các ngày {$variables['weekdayText']}. Dữ liệu chính xác, cập nhật liên tục.";
                $keywords = "lịch sử kết quả xổ số, {$regionName}, xsmb, xsmn, xsmt, kết quả xổ số cũ";
            } else {
                $title = $seoData['title_template'] ?? "Kết quả xổ số {$regionName} {$variables['weekdayText']} - Lịch sử kết quả";
                $description = $seoData['description_template'] ?? "Xem lịch sử kết quả xổ số {$regionName} các ngày {$variables['weekdayText']}. Dữ liệu chính xác, cập nhật liên tục.";
                $keywords = $seoData['keywords_template'] ?? "lịch sử kết quả xổ số, {$regionName}, xsmb, xsmn, xsmt, kết quả xổ số cũ";
            }
        }

        // Canonical URL luôn trỏ về trang gốc (không có page parameter)
        $canonicalUrl = $baseUrl . $currentUri;

        if ($page > 1) {
            $title .= " - Trang {$page}";
        }

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'heading_title' => $seoData['h1_template'] ?? $title,
            'og_title' => $title,
            'og_description' => $description,
            'canonical_url' => $canonicalUrl,
            'noindex' => $page > 1  // Thêm noindex cho các trang phân trang
        ];
    }

    /**
     * Tạo meta cho trang kết quả xổ số 3 miền
     */
    public static function generateLotteryResultMeta($region, $date, $provinceName = null)
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;
        $dateFormatted = date('d/m/Y', strtotime($date));

        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region,
            'provinceName' => $provinceName ?? '',
            'date' => $dateFormatted
        ];
        
        $customSeoData = $seoFieldManager->generateSeoData('lottery_results', 'detail', $variables);
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        
        // Tạo canonical URL từ date với format đúng (không có số 0 đứng trước)
        // Format: /xsmb-{day}-{month}-ket-qua-xo-so-mien-bac-ngay-{day}-{month}-{year}.html
        $dateObj = new \DateTime($date);
        $day = (int)$dateObj->format('j');  // j = day không có số 0
        $month = (int)$dateObj->format('n'); // n = month không có số 0
        $year = $dateObj->format('Y');
        
        $regionLower = strtolower($region);
        $regionUrls = [
            'xsmb' => "/xsmb-{$day}-{$month}-ket-qua-xo-so-mien-bac-ngay-{$day}-{$month}-{$year}.html",
            'xsmn' => "/xsmn-{$day}-{$month}-ket-qua-xo-so-mien-nam-ngay-{$day}-{$month}-{$year}.html",
            'xsmt' => "/xsmt-{$day}-{$month}-ket-qua-xo-so-mien-trung-ngay-{$day}-{$month}-{$year}.html",
        ];
        
        $canonicalUrl = $baseUrl . ($regionUrls[$regionLower] ?? $_SERVER['REQUEST_URI'] ?? '');
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        if ($provinceName) {
            $title = "Kết quả xổ số {$provinceName} ngày {$dateFormatted}";
            $description = "Kết quả xổ số {$provinceName} ngày {$dateFormatted}. Xem ngay các giải thưởng đặc biệt, giải nhất và các giải khác.";
        } else {
            $title = "Kết quả xổ số {$regionName} ngày {$dateFormatted}";
            $description = "Kết quả xổ số {$regionName} ngày {$dateFormatted}. Xem ngay các giải thưởng đặc biệt, giải nhất và các giải khác.";
        }

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'heading_title' => $title,
            'og_title' => $title,
            'og_description' => $description,
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Lấy SEO metadata cho trang danh sách kết quả xổ số and live xổ số 3 miền
     * Sử dụng SeoFieldManager để lấy custom fields từ admin
     */
    public static function getSeoData($pageType, $pageId = null, $defaultData = [])
    {
        $seoFieldManager = new \App\Library\SeoFieldManager();
        // Check if this is a base page by examining the current URL
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $isBasePage = preg_match('/\/ket-qua-(xsmb|xsmn|xsmt)-xo-so-kien-thiet-(mien-bac|mien-nam|mien-trung)-(sxmb|sxmn|sxmt)-(kqxsmb|kqxsmn|kqxsmt)\.html$/', $currentUri);

        // Determine subtype for base pages
        if ($isBasePage) {
            if (strpos($currentUri, 'xsmb') !== false) {
                $pageType = 'lottery_results_base_xsmb';
            } elseif (strpos($currentUri, 'xsmn') !== false) {
                $pageType = 'lottery_results_base_xsmn';
            } elseif (strpos($currentUri, 'xsmt') !== false) {
                $pageType = 'lottery_results_base_xsmt';
            }
        }
        // Map pageType to SeoFieldManager pageType and pageSubtype
        $pageTypeMapping = [
            'xsmb_list' => ['lottery_results', 'list', ['regionName' => 'Miền Bắc', 'regionCode' => 'XSMB']],
            'xsmn_list' => ['lottery_results', 'list', ['regionName' => 'Miền Nam', 'regionCode' => 'XSMN']],
            'xsmt_list' => ['lottery_results', 'list', ['regionName' => 'Miền Trung', 'regionCode' => 'XSMT']],
            'xsmb_live' => ['lottery_results', 'live', ['regionName' => 'Miền Bắc', 'regionCode' => 'XSMB']],
            'xsmn_live' => ['lottery_results', 'live', ['regionName' => 'Miền Nam', 'regionCode' => 'XSMN']],
            'xsmt_live' => ['lottery_results', 'live', ['regionName' => 'Miền Trung', 'regionCode' => 'XSMT']],
            'quaythu_xs' => ['quaythu', 'xs', []],
            'quaythu_xsmb' => ['quaythu', 'xsmb', ['regionName' => 'Miền Bắc', 'regionCode' => 'XSMB']],
            'quaythu_xsmn' => ['quaythu', 'xsmn', ['regionName' => 'Miền Nam', 'regionCode' => 'XSMN']],
            'quaythu_xsmt' => ['quaythu', 'xsmt', ['regionName' => 'Miền Trung', 'regionCode' => 'XSMT']],
            'quaythu_power655' => ['quaythu', 'power655', []],
            'quaythu_mega645' => ['quaythu', 'mega645', []],
            'quaythu_province' => ['quaythu', 'province', []],
            'lottery_results_base_xsmb' => ['lottery_results', 'base_xsmb', ['regionName' => 'Miền Bắc', 'regionCode' => 'XSMB']],
            'lottery_results_base_xsmn' => ['lottery_results', 'base_xsmn', ['regionName' => 'Miền Nam', 'regionCode' => 'XSMN']],
            'lottery_results_base_xsmt' => ['lottery_results', 'base_xsmt', ['regionName' => 'Miền Trung', 'regionCode' => 'XSMT']]
        ];
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        
        // Try to get custom fields first
        if (isset($pageTypeMapping[$pageType])) {
            [$seoPageType, $seoPageSubtype, $variables] = $pageTypeMapping[$pageType];
            
            // Add all custom data from defaultData
            if (isset($defaultData['provinceName'])) {
                $variables['provinceName'] = $defaultData['provinceName'];
            }
            if (isset($defaultData['provinceCode'])) {
                $variables['provinceCode'] = $defaultData['provinceCode'];
            }
            if (isset($defaultData['region'])) {
                $variables['region'] = $defaultData['region'];
            }
            
            // Generate SEO data from custom fields
            $customSeoData = $seoFieldManager->generateSeoData($seoPageType, $seoPageSubtype, $variables);
            
            if (!empty($customSeoData)) {
                // Convert SeoFieldManager format to expected format
                return [
                    'seo_title' => $customSeoData['title_template'] ?? '',
                    'seo_description' => $customSeoData['description_template'] ?? '',
                    'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                    'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
                    'canonical_url' => $canonicalUrl
                ];
            }
        }
        
        // Fallback to default data if custom fields not available
        $defaultSeoData = [
            'xsmb_list' => [
                'seo_title' => 'XSMB - Kết Quả Xổ Số Miền Bắc Hôm Nay - SXMB - KQXSMB',
                'seo_description' => 'Kết quả xổ số Miền Bắc hôm nay - XSMB - SXMB - KQXSMB. Cập nhật kết quả xổ số kiến thiết Miền Bắc mới nhất, chính xác và nhanh nhất.',
                'seo_keywords' => 'kết quả xổ số, xsmb, miền bắc, sxmb, kqxsmb',
                'canonical_url' => $canonicalUrl
            ],
            'xsmn_list' => [
                'seo_title' => 'XSMN - Kết Quả Xổ Số Miền Nam Hôm Nay - SXMN - KQXSMN',
                'seo_description' => 'Kết quả xổ số Miền Nam hôm nay - XSMN - SXMN - KQXSMN. Cập nhật kết quả xổ số kiến thiết Miền Nam mới nhất, chính xác và nhanh nhất.',
                'seo_keywords' => 'kết quả xổ số, xsmn, miền nam, sxmn, kqxsmn',
                'canonical_url' => $canonicalUrl
            ],
            'xsmt_list' => [
                'seo_title' => 'XSMT - Kết Quả Xổ Số Miền Trung Hôm Nay - SXMT - KQXSMT',
                'seo_description' => 'Kết quả xổ số Miền Trung hôm nay - XSMT - SXMT - KQXSMT. Cập nhật kết quả xổ số kiến thiết Miền Trung mới nhất, chính xác và nhanh nhất.',
                'seo_keywords' => 'kết quả xổ số, xsmt, miền trung, sxmt, kqxsmt',
                'canonical_url' => $canonicalUrl
            ],
            'xsmb_live' => [
                'seo_title' => 'Trực Tiếp XSMB - Xổ Số Miền Bắc Live Hôm Nay',
                'seo_description' => 'Xem trực tiếp kết quả xổ số Miền Bắc hôm nay. Live XSMB realtime, cập nhật kết quả nhanh chóng và chính xác nhất.',
                'seo_keywords' => 'trực tiếp xsmb, live xsmb, xổ số miền bắc live, kết quả xsmb hôm nay',
                'canonical_url' => $canonicalUrl
            ],
            'xsmn_live' => [
                'seo_title' => 'Trực Tiếp XSMN - Xổ Số Miền Nam Live Hôm Nay',
                'seo_description' => 'Xem trực tiếp kết quả xổ số Miền Nam hôm nay. Live XSMN realtime, cập nhật kết quả nhanh chóng và chính xác nhất.',
                'seo_keywords' => 'trực tiếp xsmn, live xsmn, xổ số miền nam live, kết quả xsmn hôm nay',
                'canonical_url' => $canonicalUrl
            ],
            'xsmt_live' => [
                'seo_title' => 'Trực Tiếp XSMT - Xổ Số Miền Trung Live Hôm Nay',
                'seo_description' => 'Xem trực tiếp kết quả xổ số Miền Trung hôm nay. Live XSMT realtime, cập nhật kết quả nhanh chóng và chính xác nhất.',
                'seo_keywords' => 'trực tiếp xsmt, live xsmt, xổ số miền trung live, kết quả xsmt hôm nay',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_xs' => [
                'seo_title' => 'Quay Thử XS - Quay Thử Xổ Số Online',
                'seo_description' => 'Quay thử xổ số online miễn phí. Công cụ quay thử XS giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử xs, quay thử xổ số online, thử vận may xs, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_xsmb' => [
                'seo_title' => 'Quay Thử XSMB - Quay Thử Xổ Số Miền Bắc Online',
                'seo_description' => 'Quay thử xổ số Miền Bắc online miễn phí. Công cụ quay thử XSMB giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử xsmb, quay thử xổ số miền bắc, thử vận may xsmb, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_xsmn' => [
                'seo_title' => 'Quay Thử XSMN - Quay Thử Xổ Số Miền Nam Online',
                'seo_description' => 'Quay thử xổ số Miền Nam online miễn phí. Công cụ quay thử XSMN giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử xsmn, quay thử xổ số miền nam, thử vận may xsmn, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_xsmt' => [
                'seo_title' => 'Quay Thử XSMT - Quay Thử Xổ Số Miền Trung Online',
                'seo_description' => 'Quay thử xổ số Miền Trung online miễn phí. Công cụ quay thử XSMT giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử xsmt, quay thử xổ số miền trung, thử vận may xsmt, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_power655' => [
                'seo_title' => 'Quay Thử Power 655 - Quay Thử Vietlott Power 655 Online',
                'seo_description' => 'Quay thử Vietlott Power 655 online miễn phí. Công cụ quay thử Power 655 giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử power 655, quay thử vietlott power 655, thử vận may power 655, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_mega645' => [
                'seo_title' => 'Quay Thử Mega 645 - Quay Thử Vietlott Mega 645 Online',
                'seo_description' => 'Quay thử Vietlott Mega 645 online miễn phí. Công cụ quay thử Mega 645 giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử mega 645, quay thử vietlott mega 645, thử vận may mega 645, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'quaythu_province' => [
                'seo_title' => 'Quay Thử Xổ Số {provinceCode} - Quay Thử Online',
                'seo_description' => 'Quay thử xổ số {provinceCode} online miễn phí. Công cụ quay thử xổ số {region} giúp bạn thử vận may với các con số may mắn.',
                'seo_keywords' => 'quay thử {provinceCode}, quay thử xổ số {region}, thử vận may {provinceCode}, công cụ quay thử',
                'canonical_url' => $canonicalUrl
            ],
            'lottery_results_base_xsmb' => [
                'seo_title' => 'XSMB - Kết Quả Xổ Số Miền Bắc Hôm Nay - SXMB - KQXSMB',
                'seo_description' => 'Kết quả xổ số Miền Bắc hôm nay - XSMB - SXMB - KQXSMB. Cập nhật kết quả xổ số kiến thiết Miền Bắc mới nhất, chính xác và nhanh nhất.',
                'seo_keywords' => 'kết quả xổ số, xsmb, miền bắc, sxmb, kqxsmb',
                'canonical_url' => $canonicalUrl
            ],
            'lottery_results_base_xsmn' => [
                'seo_title' => 'XSMN - Kết Quả Xổ Số Miền Nam Hôm Nay - SXMN - KQXSMN',
                'seo_description' => 'Kết quả xổ số Miền Nam hôm nay - XSMN - SXMN - KQXSMN. Cập nhật kết quả xổ số kiến thiết Miền Nam mới nhất, chính xác và nhanh nhất.',
                'seo_keywords' => 'kết quả xổ số, xsmn, miền nam, sxmn, kqxsmn',
                'canonical_url' => $canonicalUrl
            ],
            'lottery_results_base_xsmt' => [
                'seo_title' => 'XSMT - Kết Quả Xổ Số Miền Trung Hôm Nay - SXMT - KQXSMT',
                'seo_description' => 'Kết quả xổ số Miền Trung hôm nay - XSMT - SXMT - KQXSMT. Cập nhật kết quả xổ số kiến thiết Miền Trung mới nhất, chính xác và nhanh nhất.',
                'seo_keywords' => 'kết quả xổ số, xsmt, miền trung, sxmt, kqxsmt',
                'canonical_url' => $canonicalUrl
            ]
        ];
        if (isset($defaultSeoData[$pageType])) {
            $seoData = $defaultSeoData[$pageType];
            
            // Thay thế placeholder nếu có biến động
            if (!empty($defaultData)) {
                foreach ($seoData as $key => $value) {
                    foreach ($defaultData as $placeholder => $replacement) {
                        $seoData[$key] = str_replace('{' . $placeholder . '}', $replacement, $value);
                    }
                }
            }
            
            return $seoData;
        }
        return [
            'seo_title' => 'Kết Quả Xổ Số Hôm Nay',
            'seo_description' => 'Kết quả xổ số hôm nay. Cập nhật kết quả xổ số kiến thiết mới nhất, chính xác và nhanh nhất.',
            'seo_keywords' => 'kết quả xổ số, xsmb, xsmn, xsmt',
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Tạo meta tags cho trang dự đoán detail
     */
    public static function generatePredictionMeta($region, $date, $title, $provinceName = null)
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;
        $dateFormatted = date('d/m/Y', strtotime($date));
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;

        // Initialize SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region,
            'provinceName' => $provinceName ?? '',
            'date' => $dateFormatted,
            'title' => $title
        ];

        // Check if this is a base page by examining the current URL
        $isBasePage = preg_match('/\/du-doan-xo-so-soi-cau\.html$/', $currentUri);
        
        if ($isBasePage) {
            // Use base page SEO
            $customSeoData = $seoFieldManager->generateSeoData('predictions', 'base', []);
            
            if (!empty($customSeoData)) {
                return [
                    'seo_title' => $customSeoData['title_template'] ?? '',
                    'seo_description' => $customSeoData['description_template'] ?? '',
                    'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                    'heading_title' => $customSeoData['h1_template'] ?? '',
                    'canonical_url' => $canonicalUrl,
                    'og_title' => $customSeoData['title_template'] ?? '',
                    'og_description' => $customSeoData['description_template'] ?? ''
                ];
            }
            
            // Fallback for base page
            return [
                'seo_title' => 'Dự đoán xổ số soi cầu - Phân tích chuyên sâu số may mắn',
                'seo_description' => 'Dự đoán xổ số soi cầu chính xác nhất. Phân tích thống kê, soi cầu chuyên sâu giúp bạn tìm ra con số may mắn cho các miền.',
                'seo_keywords' => 'dự đoán xổ số, soi cầu, phân tích xổ số, số may mắn, dự đoán miền bắc, dự đoán miền nam, dự đoán miền trung',
                'og_title' => 'Dự đoán xổ số soi cầu - Phân tích chuyên sâu số may mắn',
                'og_description' => 'Dự đoán xổ số soi cầu chính xác nhất. Phân tích thống kê, soi cầu chuyên sâu giúp bạn tìm ra con số may mắn cho các miền.',
                'canonical_url' => $canonicalUrl
            ];
        }
        
        // Try to get custom fields from SeoFieldManager for date-specific pages
        $customSeoData = $seoFieldManager->generateSeoData('predictions', 'detail', $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'heading_title' => $customSeoData['h1_template'] ?? '',
                'canonical_url' => $canonicalUrl,
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? ''
            ];
        }

        // Fallback to default data
        $seoTitle = "Dự đoán xổ số {$regionName} ngày {$dateFormatted} - {$title}";
        $description = "Dự đoán xổ số {$regionName} ngày {$dateFormatted}. Phân tích chuyên sâu, soi cầu chính xác giúp bạn chọn số may mắn.";

        if ($provinceName) {
            $seoTitle = "Dự đoán xổ số {$provinceName} ngày {$dateFormatted} - {$title}";
            $description = "Dự đoán xổ số {$provinceName} ngày {$dateFormatted}. Soi cầu, phân tích thống kê để tìm ra con số may mắn.";
        }

        $keywords = "dự đoán xổ số, soi cầu, {$regionName}, {$dateFormatted}";
        if ($provinceName) {
            $keywords .= ", {$provinceName}";
        }

        return [
            'seo_title' => $seoTitle,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'og_title' => $seoTitle,
            'og_description' => $description,
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Tạo meta tags cho trang danh sách dự đoán 3 miền 
     */
    public static function generatePredictionListingMeta($region, $predictions = [])
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;
        
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region,
            'region' => $region,
            'date' => date('d/m/Y'),
            'dateStr' => date('d/m/Y')
        ];
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        $customSeoData = $seoFieldManager->generateSeoData('predictions', 'listing', $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;

        $title = "Dự đoán xổ số {$regionName} - Soi cầu {$region} chính xác";
        $description = "Dự đoán xổ số {$regionName} hôm nay. Soi cầu {$region} chính xác nhất với phân tích thống kê, lô gan, đầu đuôi. Cập nhật dự đoán mới nhất.";
        $keywords = "dự đoán xổ số, soi cầu {$region}, dự đoán {$regionName}, thống kê xổ số, phân tích {$region}";

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'heading_title' => $title,
            'og_title' => $title,
            'og_description' => $description,
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Generate SEO meta for homepage (CACHED)
     */
    public static function generateHomepageMeta(): array
    {
        $baseUrl = self::getBaseUrl();
        
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $customSeoData = $seoFieldManager->generateSeoData('homepage', 'default', []);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $baseUrl . '/'
            ];
        }
        
        // Fallback to default data if no custom fields
        return [
            'seo_title' => 'Soi Cầu 247 - Dự Đoán Xổ Số Chính Xác 3 Miền XSMB XSMN XSMT',
            'seo_description' => 'Soi cầu 247 - Dự đoán xổ số chính xác 3 miền XSMB, XSMN, XSMT hôm nay. Cập nhật nhanh chóng, chính xác. Thống kê, soi cầu miễn phí.',
            'seo_keywords' => 'soi cầu 247, soi cầu, kết quả xổ số, xsmb, xsmn, xsmt, vietlott, power 6/55, mega 6/45, dự đoán xổ số, soi cầu',
            'og_title' => 'Soi Cầu 247 - Dự Đoán Xổ Số Chính Xác 3 Miền',
            'og_description' => 'Soi cầu 247 - Dự đoán xổ số chính xác 3 miền XSMB, XSMN, XSMT hôm nay. Cập nhật nhanh chóng, chính xác.',
            'canonical_url' => $baseUrl . '/'
        ];
    }

    /**
     * Generate SEO meta for Keno pages
     */
    public static function generateKenoMeta($latestResult = null, ?string $nextDrawTime = null, ?string $date = null): array
    {
        // Chỉ hiển thị ngày khi có date parameter từ URL, không lấy từ latestResult
        $formattedDate = null;
        $dateForCanonical = null;
        
        if ($date) {
            // Date từ parameter (format: yyyy-mm-dd)
            $formattedDate = date('d/m/Y', strtotime($date));
            $dateForCanonical = $date;
        }
        
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'date' => $formattedDate ?? '',
            'nextDrawTime' => $nextDrawTime ?? ''
        ];
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        
        // Canonical URL luôn trùng với URL hiện tại (giữ nguyên format)
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        // Loại bỏ query string nếu có
        if (($pos = strpos($currentUri, '?')) !== false) {
            $currentUri = substr($currentUri, 0, $pos);
        }
        
        // Kiểm tra xem URL có phải là Keno URL với date không
        if (preg_match('/ket-qua-xoso-keno-vietlott-[\d-]+\.html$/', $currentUri)) {
            // URL có date, giữ nguyên format của URL hiện tại
            $canonicalUrl = $baseUrl . $currentUri;
        } else {
            // URL không có date, dùng base URL
            $canonicalUrl = $baseUrl . "/ket-qua-xoso-keno-vietlott.html";
        }
        
        $customSeoData = $seoFieldManager->generateSeoData('keno', 'default', $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        $title = "Kết quả Keno - Xổ số Keno Việt Nam";
        $description = "Kết quả Keno mới nhất. Xem kết quả Keno trực tiếp, thống kê tần suất số. Cập nhật mỗi 8 phút.";

        // Chỉ thêm ngày vào title khi có date parameter
        if ($formattedDate) {
            $title = "Kết quả Keno - Xổ số Keno Việt Nam {$formattedDate}";
            // Description giữ nguyên không thay đổi theo ngày
        }

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => "kết quả keno, xổ số keno, keno việt nam, kết quả keno mới nhất, thống kê keno",
            'og_title' => $title,
            'og_description' => $description,
            'canonical_url' => $canonicalUrl
        ];
    }

     /**
      * Tạo meta tags cho trang thống kê
      */
    public static function generateStatisticsMeta($region, $type, $provinceName = null, $provinceCode = null)
    {
        // Xử lý Vietlott statistics (Mega645, Power655, etc.)
        if (in_array($region, ['Mega645', 'Power655']) || in_array($type, ['Mega645', 'Power655'])) {
            $lotteryType = in_array($region, ['Mega645', 'Power655']) ? $region : $type;
            return self::generateVietlottStatisticsMeta($lotteryType);
        }
        
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $typeNames = [
            'thongke' => 'Thống kê xổ số',
            'logan' => 'Lô gan',
            'dacbiet' => 'Giải đặc biệt',
            'dauduoi' => 'Đầu đuôi',
            'tansuat' => 'Tần suất',
            'statistics' => 'Thống kê'
        ];

        $regionName = $regionNames[$region] ?? $region;
        $typeName = $typeNames[$type] ?? $type;
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region,
            'provinceName' => $provinceName ?? '',
            'provinceCode' => $provinceCode ?? '',
            'typeName' => $typeName,
            'type' => $type
        ];
        
        $customSeoData = $seoFieldManager->generateSeoData('statistics', $type, $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'heading_title' => $customSeoData['h1_template'] ?? $customSeoData['title_template'] ?? '',
                'canonical_url' => $canonicalUrl,
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? ''
            ];
        }
        
        // Fallback to default data
        $canonicalUrl = $baseUrl . $currentUri;
         
         // Tạo title riêng biệt cho từng loại
         if ($provinceName && $provinceCode) {
             // SEO cho tỉnh cụ thể với format chuẩn
             switch ($type) {
                 case 'thongke':
                     $title = "Thống Kê {$provinceCode} - Thống Kê Xổ Số {$provinceName} Hôm Nay";
                     $description = "Thống kê {$provinceCode} chi tiết, chính xác nhất. Phân tích lô gan, tần suất, đầu đuôi giải đặc biệt xổ số kiến thiết {$provinceName} giúp soi cầu hiệu quả.";
                     break;
                 case 'logan':
                     $title = "Lô Gan {$provinceCode} - Thống Kê Lô Gan {$provinceName} Lâu Chưa Về";
                     $description = "Bảng lô gan {$provinceCode} chi tiết nhất. Xem số nào gan nhất, lâu chưa về, cặp khan AB-BA xổ số kiến thiết {$provinceName} để soi cầu chính xác.";
                     break;
                 case 'dacbiet':
                     $title = "Thống Kê Giải Đặc Biệt KQ{$provinceCode} - TK GĐB XSKT {$provinceName}";
                     $description = "Thống kê giải đặc biệt KQ{$provinceCode}. Phân tích 2 số cuối GĐB, tần suất xuất hiện, đầu đuôi xổ số kiến thiết {$provinceName} để dự đoán chính xác.";
                     break;
                 case 'dauduoi':
                     $title = "Thống Kê Đầu Đuôi KQ{$provinceCode} - TK Đầu Đuôi Lô Tô XSKT {$provinceName}";
                     $description = "Thống kê đầu đuôi KQ{$provinceCode} chi tiết. Phân tích tần suất đầu số, đuôi số lô tô xổ số kiến thiết {$provinceName} giúp soi cầu hiệu quả.";
                     break;
                 case 'tansuat':
                     $title = "Tần Suất {$provinceCode} - Thống Kê Tần Suất Xổ Số {$provinceName}";
                     $description = "Bảng tần suất {$provinceCode} chi tiết trong 100 kỳ gần nhất. Thống kê số lần về của các con lô xổ số kiến thiết {$provinceName} giúp dự đoán chính xác.";
                     break;
                 default:
                     $title = "{$typeName} {$provinceCode} - Xổ Số {$provinceName}";
                     $description = "Thống kê xổ số {$provinceName} chi tiết. Dữ liệu phân tích giúp bạn đưa ra quyết định chính xác.";
             }
             $keywords = "thống kê {$provinceCode}, {$typeName} {$provinceName}, {$provinceCode}, soi cầu {$provinceName}";
         } else if ($provinceName) {
             // Fallback nếu không có provinceCode
             switch ($type) {
                 case 'thongke':
                     $title = "Thống Kê Xổ Số {$provinceName} - TK XS {$provinceName} Hôm Nay";
                     $description = "Thống kê xổ số {$provinceName} chi tiết, chính xác nhất. Phân tích lô gan, tần suất, đầu đuôi giải đặc biệt giúp soi cầu hiệu quả.";
                     break;
                 case 'logan':
                     $title = "Lô Gan {$provinceName} - Thống Kê Lô Gan {$provinceName} Lâu Chưa Về";
                     $description = "Bảng lô gan xổ số {$provinceName} chi tiết nhất. Xem số nào gan nhất, lâu chưa về để soi cầu chính xác.";
                     break;
                 case 'dacbiet':
                     $title = "Thống Kê Giải Đặc Biệt {$provinceName} - TK GĐB {$provinceName}";
                     $description = "Thống kê giải đặc biệt xổ số {$provinceName}. Phân tích 2 số cuối GĐB, tần suất xuất hiện, đầu đuôi để dự đoán chính xác.";
                     break;
                 case 'dauduoi':
                     $title = "Thống Kê Đầu Đuôi {$provinceName} - TK Đầu Đuôi {$provinceName}";
                     $description = "Thống kê đầu đuôi xổ số {$provinceName} chi tiết. Phân tích tần suất đầu số, đuôi số lô tô giúp soi cầu hiệu quả.";
                     break;
                 case 'tansuat':
                     $title = "Tần Suất Xổ Số {$provinceName} - Bảng Tần Suất {$provinceName}";
                     $description = "Bảng tần suất xổ số {$provinceName} chi tiết trong 100 kỳ gần nhất. Thống kê số lần về của các con lô giúp dự đoán chính xác.";
                     break;
                 default:
                     $title = "{$typeName} Xổ Số {$provinceName}";
                     $description = "Thống kê xổ số {$provinceName} chi tiết. Dữ liệu phân tích giúp bạn đưa ra quyết định chính xác.";
             }
             $keywords = "thống kê xổ số {$provinceName}, {$typeName} {$provinceName}, soi cầu {$provinceName}";
         } else {
             // SEO cho region
             switch ($type) {
                 case 'thongke':
                     $title = "Thống Kê Xổ Số {$regionName} - TK {$region} Chi Tiết Nhất";
                     $description = "Thống kê xổ số {$regionName} chi tiết. Phân tích tần suất, lô gan, đầu đuôi để hỗ trợ soi cầu {$region} chính xác.";
                     break;
                 case 'logan':
                     $title = "Lô Gan {$regionName} - Thống Kê Lô Gan {$region} Lâu Chưa Về";
                     $description = "Bảng lô gan xổ số {$regionName} chi tiết. Xem số nào gan nhất, lâu chưa về để soi cầu {$region} chính xác.";
                     break;
                 case 'dacbiet':
                     $title = "Thống Kê Giải Đặc Biệt {$regionName} - TK GĐB {$region}";
                     $description = "Thống kê giải đặc biệt xổ số {$regionName}. Phân tích kết quả GĐB, tần suất xuất hiện các con số {$region}.";
                     break;
                 case 'dauduoi':
                     $title = "Thống Kê Đầu Đuôi {$regionName} - TK Đầu Đuôi {$region}";
                     $description = "Thống kê đầu đuôi xổ số {$regionName}. Phân tích tần suất xuất hiện đầu số và đuôi số {$region}.";
                     break;
                 case 'tansuat':
                     $title = "Tần Suất Xổ Số {$regionName} - Bảng Tần Suất {$region}";
                     $description = "Bảng tần suất xổ số {$regionName}. Thống kê chi tiết tần suất xuất hiện các con số {$region} trong 100 kỳ gần nhất.";
                     break;
                 default:
                     $title = "{$typeName} xổ số {$regionName}";
                     $description = "Thống kê chi tiết xổ số {$regionName}. Phân tích dữ liệu để hỗ trợ dự đoán chính xác.";
             }
             $keywords = "thống kê xổ số, {$typeName}, {$regionName}, {$region}";
         }

         return [
             'seo_title' => $title,
             'seo_description' => $description,
             'seo_keywords' => $keywords,
             'heading_title' => $title,
             'og_title' => $title,
             'og_description' => $description,
             'canonical_url' => $canonicalUrl
         ];
    }
    
    /**
     * Generate SEO meta for Vietlott Statistics pages
     */
    private static function generateVietlottStatisticsMeta($lotteryType)
    {
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        $fieldKey = 'vietlott_' . strtolower($lotteryType);
        $variables = [
            'typeName' => str_replace(['645', '655'], ['6/45', '6/55'], $lotteryType),
            'drawType' => $lotteryType
        ];
        
        // Try to get custom fields from SeoFieldManager
        $customSeoData = $seoFieldManager->generateSeoData('statistics', $fieldKey, $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'canonical_url' => $canonicalUrl,
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? ''
            ];
        }
        
        // Fallback to default data
        $defaultData = [
            'Mega645' => [
                'title' => 'Thống Kê Mega 6/45 Vietlott - Phân Tích Số May Mắn, Tần Suất Xuất Hiện',
                'description' => 'Thống kê Mega 6/45 Vietlott chi tiết. Phân tích tần suất số may mắn, số ít xuất hiện, cặp số liên tiếp. Dữ liệu được cập nhật liên tục.',
                'keywords' => 'thống kê mega 6/45, mega 6/45 vietlott, tần suất mega 6/45, số may mắn mega 6/45, phân tích mega 6/45'
            ],
            'Power655' => [
                'title' => 'Thống Kê Power 6/55 Vietlott - Phân Tích Số May Mắn, Tần Suất Xuất Hiện',
                'description' => 'Thống kê Power 6/55 Vietlott chi tiết. Phân tích tần suất số may mắn, số ít xuất hiện, cặp số liên tiếp. Dữ liệu được cập nhật liên tục.',
                'keywords' => 'thống kê power 6/55, power 6/55 vietlott, tần suất power 6/55, số may mắn power 6/55, phân tích power 6/55'
            ]
        ];
        
        $data = $defaultData[$lotteryType] ?? $defaultData['Mega645'];
        
        return [
            'seo_title' => $data['title'],
            'seo_description' => $data['description'],
            'seo_keywords' => $data['keywords'],
            'og_title' => $data['title'],
            'og_description' => $data['description'],
            'canonical_url' => $canonicalUrl
        ];
    }
    
    /**
     * Lấy SEO data cho trang live Vietlott
     */
    public static function getLiveVietlottSeoData($lotteryType)
    {
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'lotteryType' => $lotteryType,
            'typeName' => ucfirst(str_replace('645', '6/45', str_replace('655', '6/55', $lotteryType)))
        ];
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        $customSeoData = $seoFieldManager->generateSeoData('vietlott_live', $lotteryType, $variables);
        
        if (!empty($customSeoData)) {
            return [
                'title' => $customSeoData['title_template'] ?? '',
                'description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        $seoData = [
            'mega645' => [
                'title' => 'Trực tiếp kết quả xổ số Mega 6/45 Vietlott hôm nay - Live Mega 6/45',
                'description' => 'Xem trực tiếp kết quả xổ số Mega 6/45 Vietlott hôm nay. Cập nhật live kết quả Mega 6/45 mới nhất, chính xác và nhanh nhất.',
                'canonical_url' => $canonicalUrl
            ],
            'power655' => [
                'title' => 'Trực tiếp kết quả xổ số Power 6/55 Vietlott hôm nay - Live Power 6/55',
                'description' => 'Xem trực tiếp kết quả xổ số Power 6/55 Vietlott hôm nay. Cập nhật live kết quả Power 6/55 mới nhất, chính xác và nhanh nhất.',
                'canonical_url' => $canonicalUrl
            ],
            'max3d' => [
                'title' => 'Trực tiếp kết quả xổ số Max 3D Vietlott hôm nay - Live Max 3D',
                'description' => 'Xem trực tiếp kết quả xổ số Max 3D Vietlott hôm nay. Cập nhật live kết quả Max 3D mới nhất, chính xác và nhanh nhất.',
                'canonical_url' => $canonicalUrl
            ],
            'max3dpro' => [
                'title' => 'Trực tiếp kết quả xổ số Max 3D Pro Vietlott hôm nay - Live Max 3D Pro',
                'description' => 'Xem trực tiếp kết quả xổ số Max 3D Pro Vietlott hôm nay. Cập nhật live kết quả Max 3D Pro mới nhất, chính xác và nhanh nhất.',
                'canonical_url' => $canonicalUrl
            ]
        ];
        
        return $seoData[$lotteryType] ?? $seoData['mega645'];
    }

    /**
     * Generate SEO meta for Vietlott pages
     */
    public static function generateVietlottMeta(string $drawType, ?string $date = null): array
    {
        $baseUrl = self::getBaseUrl();

        $typeNames = [
            'Mega645' => 'Mega 6/45',
            'Power655' => 'Power 6/55',
            'MAX3D' => 'Max 3D',
            'MAX3DPRO' => 'Max 3D Pro'
        ];

        $typeName = $typeNames[$drawType] ?? $drawType;
        $dateStr = $date ? date('d/m/Y', strtotime($date)) : 'hôm nay';

        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'typeName' => $typeName,
            'date' => $dateStr,
            'drawType' => $drawType
        ];
        
        // Fallback to default data
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        // Map drawType to vietlott subtype
        $subtypeMapping = [
            'Mega645' => 'mega645',
            'Power655' => 'power655',
            'MAX3D' => 'max3d',
            'MAX3DPRO' => 'max3dpro'
        ];
        
        $subtype = $subtypeMapping[$drawType] ?? strtolower($drawType);
        
        // Check if this is a base page by examining the current URL
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $isBasePage = preg_match('/\/ket-qua-xoso-(mega-6-45|power-6-55|max-3d|max-3d-pro)-vietlott\.html$/', $currentUri);
        
        // Determine subtype for base pages
        if ($isBasePage) {
            if (strpos($currentUri, 'mega-6-45') !== false) {
                $subtype = 'base_mega645';
            } elseif (strpos($currentUri, 'power-6-55') !== false) {
                $subtype = 'base_power655';
            } elseif (strpos($currentUri, 'max-3d-pro') !== false) {
                $subtype = 'base_max3dpro';
            } elseif (strpos($currentUri, 'max-3d') !== false) {
                $subtype = 'base_max3d';
            }
        }
        
        $customSeoData = $seoFieldManager->generateSeoData('vietlott', $subtype, $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'canonical_url' => $canonicalUrl,
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? ''
            ];
        }

        // Use current URI for canonical URL (handles both base and date-specific pages)
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;

        return [
            'seo_title' => "Kết quả Vietlott {$typeName} {$dateStr} - Soi Cầu 247",
            'seo_description' => "Kết quả xổ số Vietlott {$typeName} {$dateStr}. Cập nhật nhanh chóng, chính xác. Thống kê, phân tích số may mắn.",
            'seo_keywords' => "vietlott, {$typeName}, kết quả xổ số, thống kê vietlott, số may mắn",
            'og_title' => "Kết quả Vietlott {$typeName} {$dateStr}",
            'og_description' => "Kết quả xổ số Vietlott {$typeName} {$dateStr}. Cập nhật nhanh chóng, chính xác.",
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Generate SEO meta for Quay thử xổ số pages
     */
    public static function generateQuaythuMeta(string $region, array $regions = []): array
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;
        
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region,
            'region' => $region
        ];
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        
        $customSeoData = $seoFieldManager->generateSeoData('quaythu', strtolower($region), $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        $baseUrl = self::getBaseUrl();

        return [
            'seo_title' => "Quay thử xổ số {$regionName} - Xổ số kiến thiết",
            'seo_description' => "Quay thử xổ số {$regionName} miễn phí. Trải nghiệm quay số may mắn với các tỉnh thành {$regionName}. Công cụ quay thử chính xác.",
            'seo_keywords' => "quay thử xổ số, {$regionName}, xsmb, xsmn, xsmt, quay số may mắn, thử vận may",
            'og_title' => "Quay thử xổ số {$regionName}",
            'og_description' => "Quay thử xổ số {$regionName} miễn phí. Trải nghiệm quay số may mắn.",
            'canonical_url' => $baseUrl . "/quay-thu-xsmb.html"
        ];
    }

    /**
     * Generate SEO data for Mega645 prediction page
     */
    public static function generateDudoanMega645Seo($date): array
    {
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'date' => $date,
            'typeName' => 'Mega 6/45'
        ];
        
        $customSeoData = $seoFieldManager->generateSeoData('predictions', 'vietlott_mega645', $variables);
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        $title = "Dự đoán số Mega 6/45 Vietlott ngày {$date} - Soi cầu Mega 6/45 chính xác";
        $description = "Dự đoán số Mega 6/45 Vietlott ngày {$date}. Phân tích thống kê, soi cầu Mega 6/45 chính xác nhất. Cập nhật dự đoán Mega 6/45 mới nhất.";
        $keywords = "dự đoán mega 6/45, soi cầu mega 6/45, dự đoán mega 6/45 ngày {$date}, thống kê mega 6/45, phân tích mega 6/45";

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Generate SEO data for Power655 prediction page
     */
    public static function generateDudoanPower655Seo($date): array
    {
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'date' => $date,
            'typeName' => 'Power 6/55'
        ];
        
        $customSeoData = $seoFieldManager->generateSeoData('predictions', 'vietlott_power655', $variables);
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        $title = "Dự đoán số Power 6/55 Vietlott ngày {$date} - Soi cầu Power 6/55 chính xác";
        $description = "Dự đoán số Power 6/55 Vietlott ngày {$date}. Phân tích thống kê, soi cầu Power 6/55 chính xác nhất. Cập nhật dự đoán Power 6/55 mới nhất.";
        $keywords = "dự đoán power 6/55, soi cầu power 6/55, dự đoán power 6/55 ngày {$date}, thống kê power 6/55, phân tích power 6/55";

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Generate SEO data for province prediction pages
     */
    public static function generateProvincePredictionMeta($region, $provinceName, $date = null): array
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;
        $dateFormatted = $date ? date('d/m/Y', strtotime($date)) : date('d/m/Y');
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $canonicalUrl = $baseUrl . $currentUri;

        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        $variables = [
            'regionName' => $regionName,
            'regionCode' => $region,
            'provinceName' => $provinceName,
            'date' => $dateFormatted
        ];
        
        $customSeoData = $seoFieldManager->generateSeoData('predictions', 'province', $variables);
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'canonical_url' => $canonicalUrl,
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? ''
            ];
        }

        // Fallback to default data
        $seoTitle = "Dự đoán xổ số {$provinceName} ngày {$dateFormatted} - Soi cầu {$region} chính xác";
        $description = "Dự đoán xổ số {$provinceName} ngày {$dateFormatted}. Soi cầu {$region} chính xác nhất với phân tích thống kê, lô gan, đầu đuôi. Cập nhật dự đoán {$provinceName} mới nhất.";
        $keywords = "dự đoán xổ số, soi cầu {$region}, dự đoán {$provinceName}, thống kê xổ số, phân tích {$region}, {$provinceName}";

        return [
            'seo_title' => $seoTitle,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'og_title' => $seoTitle,
            'og_description' => $description,
            'canonical_url' => $canonicalUrl
        ];
    }

    /**
     * Generate SEO data for main prediction page (dudoansoicau)
     */
    public static function generateDudoanSoiCauSeo(): array
    {
        // Check if this is a base page by examining the current URL
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $isBasePage = preg_match('/\/du-doan-xo-so-soi-cau\.html$/', $currentUri);
        
        // Try to get custom fields from SeoFieldManager
        $seoFieldManager = new \App\Library\SeoFieldManager();
        
        if ($isBasePage) {
            $customSeoData = $seoFieldManager->generateSeoData('predictions', 'base', []);
        } else {
            $customSeoData = $seoFieldManager->generateSeoData('predictions', 'listing', []);
        }
        
        // Fallback to default data
        $baseUrl = self::getBaseUrl();
        $canonicalUrl = $baseUrl . $currentUri;
        
        if (!empty($customSeoData)) {
            return [
                'seo_title' => $customSeoData['title_template'] ?? '',
                'seo_description' => $customSeoData['description_template'] ?? '',
                'seo_keywords' => $customSeoData['keywords_template'] ?? '',
                'og_title' => $customSeoData['title_template'] ?? '',
                'og_description' => $customSeoData['description_template'] ?? '',
                'canonical_url' => $canonicalUrl
            ];
        }

        // Fallback to default data
        if ($isBasePage) {
            $title = "Dự đoán xổ số soi cầu - Phân tích chuyên sâu số may mắn";
            $description = "Dự đoán xổ số soi cầu chính xác nhất. Phân tích thống kê, soi cầu chuyên sâu giúp bạn tìm ra con số may mắn cho các miền.";
            $keywords = "dự đoán xổ số, soi cầu, phân tích xổ số, số may mắn, dự đoán miền bắc, dự đoán miền nam, dự đoán miền trung";
        } else {
            $title = "Dự đoán xổ số - Soi cầu xổ số 3 miền chính xác";
            $description = "Dự đoán xổ số 3 miền XSMB, XSMN, XSMT hôm nay. Soi cầu chính xác với phân tích thống kê, lô gan, đầu đuôi. Cập nhật dự đoán mới nhất.";
            $keywords = "dự đoán xổ số, soi cầu xổ số, dự đoán XSMB, dự đoán XSMN, dự đoán XSMT, thống kê xổ số, phân tích xổ số";
        }

        return [
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => $keywords,
            'og_title' => $title,
            'og_description' => $description,
            'canonical_url' => $baseUrl . '/du-doan-xo-so-soi-cau.html'
        ];
    }
















    /**
     * Tạo structured data cho trang kết quả xổ số
     */
    public static function generateLotteryResultStructuredData($region, $date, $results, $provinceName = null)
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;
        $dateFormatted = date('d/m/Y', strtotime($date));

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => "Kết quả xổ số {$regionName} ngày {$dateFormatted}",
            'description' => "Kết quả xổ số kiến thiết {$regionName} ngày {$dateFormatted}",
            'dateModified' => $date,
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => self::getBaseUrl()
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Danh sách giải thưởng',
                'numberOfItems' => count($results),
                'itemListElement' => []
            ]
        ];

        if ($provinceName) {
            $structuredData['name'] = "Kết quả xổ số {$provinceName} ngày {$dateFormatted}";
            $structuredData['description'] = "Kết quả xổ số {$provinceName} ngày {$dateFormatted}";
        }

        // Thêm các giải thưởng vào structured data
        $prizeIndex = 1;
        foreach ($results as $prizeName => $numbers) {
            $structuredData['mainEntity']['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $prizeIndex++,
                'item' => [
                    '@type' => 'Thing',
                    'name' => $prizeName,
                    'description' => is_array($numbers) ? implode(', ', $numbers) : $numbers
                ]
            ];
        }

        return $structuredData;
    }


    /**
     * Generate structured data for homepage
     */
    public static function generateHomepageStructuredData(array $kqxsByRegion, $megaResult = null, $powerResult = null): array
    {
        $baseUrl = self::getBaseUrl();

        // Create ItemList for lottery results
        $lotteryItems = [];
        $position = 1;

        foreach ($kqxsByRegion as $region => $data) {
            if (!empty($data['rows'])) {
                $regionName = $region === 'XSMB' ? 'Miền Bắc' : ($region === 'XSMN' ? 'Miền Nam' : 'Miền Trung');
                $lotteryItems[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => "Kết quả xổ số {$regionName} ngày {$data['date']}",
                    'url' => $baseUrl . "/ket-qua-{$region}-xo-so-kien-thiet-{$regionName}"
                ];
                $position++;
            }
        }

        // Add Vietlott results if available
        if ($megaResult) {
            $lotteryItems[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => "Kết quả Vietlott Mega 6/45 ngày {$megaResult->draw_date}",
                'url' => $baseUrl . "/vietlott-mega-6-45"
            ];
            $position++;
        }

        if ($powerResult) {
            $lotteryItems[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => "Kết quả Vietlott Power 6/55 ngày {$powerResult->draw_date}",
                'url' => $baseUrl . "/vietlott-power-6-55"
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Kết quả xổ số kiến thiết hôm nay',
            'description' => 'Danh sách kết quả xổ số kiến thiết 3 miền và Vietlott mới nhất',
            'numberOfItems' => count($lotteryItems),
            'itemListElement' => $lotteryItems
        ];
    }
    /**
     * Generate structured data for Keno pages
     */
    public static function generateKenoStructuredData($latestResult = null, array $statistics = []): array
    {
        $baseUrl = self::getBaseUrl();

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => 'Kết quả Keno Việt Nam',
            'description' => 'Dữ liệu kết quả xổ số Keno Việt Nam',
            'dateModified' => date('c'),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => $baseUrl
            ],
            'distribution' => [
                '@type' => 'DataDownload',
                'contentUrl' => $baseUrl . '/ket-qua-keno.html',
                'encodingFormat' => 'text/html'
            ]
        ];

        if ($latestResult) {
            $structuredData['about'] = [
                '@type' => 'Thing',
                'name' => "Kết quả Keno ngày {$latestResult->draw_date}",
                'description' => "Kết quả xổ số Keno mới nhất"
            ];
        }

        return $structuredData;
    }

    /**
     * Tạo structured data cho Page kết quả xổ số theo thứ và theo tỉnh
     */
    public static function generateArchiveStructuredData(string $region, ?string $weekdaySlug = null, $province = null, array $sections = []): array
    {
        $baseUrl = self::getBaseUrl();

        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];

        $regionName = $regionNames[$region] ?? $region;

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => "Lịch sử kết quả xổ số {$regionName}",
            'description' => "Dữ liệu lịch sử kết quả xổ số {$regionName}",
            'dateModified' => date('c'),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => $baseUrl
            ]
        ];

        if ($province) {
            $structuredData['about'] = [
                '@type' => 'Thing',
                'name' => "Kết quả xổ số {$province->name}",
                'description' => "Lịch sử kết quả xổ số {$province->name} {$regionName}"
            ];
        }

        if (!empty($sections)) {
            $structuredData['distribution'] = [
                '@type' => 'DataDownload',
                'contentUrl' => $baseUrl . "/ket-qua-xo-so-{$region}" . ($weekdaySlug ? "-{$weekdaySlug}" : ""),
                'encodingFormat' => 'text/html'
            ];
        }

        return $structuredData;
    }
    
    /**
     * Tạo structured data cho trang live Vietlott
     */
    public static function generateLiveVietlottStructuredData($lotteryType, $lotteryName)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Trực tiếp kết quả xổ số ' . $lotteryName . ' Vietlott',
            'description' => 'Xem trực tiếp kết quả xổ số ' . $lotteryName . ' Vietlott hôm nay. Cập nhật live kết quả ' . $lotteryName . ' mới nhất.',
            'url' => self::getBaseUrl() . '/truc-tiep-ket-qua-xo-so-' . $lotteryType . '-vietlott',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => self::getBaseUrl()
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Kết quả xổ số ' . $lotteryName . ' Vietlott',
                'description' => 'Danh sách kết quả xổ số ' . $lotteryName . ' Vietlott trực tiếp'
            ]
        ];
    }

    /**
     * Tạo structured data cho kết quả xổ số
     */
    public static function generateLotteryStructuredData($region, $date, $results)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => 'Kết quả xổ số ' . $region,
            'description' => 'Kết quả xổ số kiến thiết ' . $region . ' ngày ' . $date,
            'dateModified' => $date,
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => self::getBaseUrl()
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Danh sách giải thưởng',
                'numberOfItems' => count($results),
                'itemListElement' => array_map(function($result, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => [
                            '@type' => 'Thing',
                            'name' => 'Giải ' . $result['prize_name'],
                            'description' => $result['numbers']
                        ]
                    ];
                }, $results, array_keys($results))
            ]
        ];
    }
    
    /**
     * Tạo structured data cho tin tức
     */
    public static function generateNewsStructuredData($title, $description, $publishedDate, $updatedDate = null)
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $title,
            'description' => $description,
            'datePublished' => $publishedDate,
            'author' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::getBaseUrl() . '/img/logo-soicau247.png'
                ]
            ]
        ];
        
        if ($updatedDate) {
            $data['dateModified'] = $updatedDate;
        }
        
        return $data;
    }
    
    /**
     * Tạo structured data cho tổ chức (CACHED)
     */
    public static function generateOrganizationStructuredData()
    {
        // Cache organization data vì nó không thay đổi
        static $cachedData = null;
        
        if ($cachedData === null) {
            $cachedData = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => self::getBaseUrl(),
                'logo' => self::getBaseUrl() . '/img/logo-soicau247.png',
                'description' => 'Trang web cung cấp kết quả xổ số kiến thiết 3 miền, dự đoán và thống kê xổ số',
                'foundingDate' => '2024',
                'sameAs' => [
                    'https://www.facebook.com/soicau247',
                    'https://www.youtube.com/soicau247'
                ]
            ];
        }
        
        return $cachedData;
    }

    /**
     * Generate organization structured data as JSON string (OPTIMIZED)
     */
    public static function generateOrganizationStructuredDataJson(): string
    {
        static $cachedJson = null;
        
        if ($cachedJson === null) {
            $cachedJson = json_encode(self::generateOrganizationStructuredData(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        return $cachedJson;
    }
    
    /**
     * Tạo breadcrumb structured data
     */
    public static function generateBreadcrumbStructuredData($breadcrumbs)
    {
        $items = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['title'],
                'item' => $breadcrumb['url']
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }
    
    /**
     * Tạo meta description tự động
     */
    public static function generateMetaDescription($content, $maxLength = 160)
    {
        // Loại bỏ HTML tags
        $text = strip_tags($content);
        
        // Loại bỏ khoảng trắng thừa
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Cắt độ dài phù hợp
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3) . '...';
        }
        
        return $text;
    }
    
    /**
     * Tạo title tự động
     */
    public static function generateTitle($baseTitle, $region = '', $date = '', $maxLength = 60)
    {
        $title = $baseTitle;
        
        if ($region) {
            $title .= ' ' . $region;
        }
        
        if ($date) {
            $title .= ' ' . $date;
        }
        
        // Cắt độ dài phù hợp
        if (strlen($title) > $maxLength) {
            $title = substr($title, 0, $maxLength - 3) . '...';
        }
        
        return $title;
    }
    
    
    
    
    
    
    
    /**
     * Tạo canonical URL
     */
    public static function generateCanonicalUrl($type, $region, $date = null, $subType = null)
    {
        $baseUrl = self::getBaseUrl();
        
        switch ($type) {
            case 'ket-qua':
                $regionSlug = strtolower($region);
                if ($date) {
                    $dateSlug = date('d-m-Y', strtotime($date));
                    return "{$baseUrl}/ket-qua-{$regionSlug}-{$dateSlug}";
                }
                // Map region to full URL
                $regionUrls = [
                    'xsmb' => '/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html',
                    'xsmn' => '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
                    'xsmt' => '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html'
                ];
                return $baseUrl . ($regionUrls[$regionSlug] ?? "/ket-qua-{$regionSlug}");
                
            case 'du-doan':
                $regionSlug = strtolower($region);
                if ($date) {
                    $dateSlug = date('d-m-Y', strtotime($date));
                    // Map Vietlott regions to full URL
                    $vietlottUrls = [
                        'mega645' => "/du-doan-soi-cau-xo-so-mega-6-45-vietlott-ngay-{$dateSlug}-co-nen-xuong-tay.html",
                        'power655' => "/du-doan-soi-cau-xo-so-power-6-55-vietlott-ngay-{$dateSlug}-co-nen-xuong-tay.html"
                    ];
                    if (isset($vietlottUrls[$regionSlug])) {
                        return $baseUrl . $vietlottUrls[$regionSlug];
                    }
                    return "{$baseUrl}/du-doan-{$regionSlug}-{$dateSlug}";
                }
                return "{$baseUrl}/du-doan-{$regionSlug}";
                
            case 'thong-ke':
                $regionSlug = strtolower($region);
                if ($subType) {
                    return "{$baseUrl}/thong-ke-{$regionSlug}-{$subType}";
                }
                // Map region to full URL
                $regionUrls = [
                    'xsmb' => '/thong-ke-xo-so-mien-bac-tk-xsmb.html',
                    'xsmn' => '/thong-ke-xo-so-mien-nam-tk-xsmn.html',
                    'xsmt' => '/thong-ke-xo-so-mien-trung-tk-xsmt.html',
                    'mega645' => '/thong-ke-xo-so-mega-6-45.html',
                    'power655' => '/thong-ke-xo-so-power-6-55.html'
                ];
                return $baseUrl . ($regionUrls[$regionSlug] ?? "/thong-ke-{$regionSlug}");
                
            case 'truc-tiep':
                $regionSlug = strtolower($region);
                $regionUrls = [
                    'xsmb' => '/truc-tiep-ket-qua-xo-so-mien-bac-ttxsmb-xsmb.html',
                    'xsmn' => '/truc-tiep-ket-qua-xo-so-mien-nam-ttxsmn-xsmn.html',
                    'xsmt' => '/truc-tiep-ket-qua-xo-so-mien-trung-ttxsmt-xsmt.html'
                ];
                return $baseUrl . ($regionUrls[$regionSlug] ?? "/truc-tiep-{$regionSlug}");
                
            case 'sitemap':
                $sitemapUrls = [
                    'main' => '/sitemap.xml',
                    'images' => '/sitemap-images.xml',
                    'news' => '/sitemap-news.xml',
                    'dudoan' => '/sitemap-dudoan-ket-qua-xo-so.xml'
                ];
                return $baseUrl . ($sitemapUrls[$region] ?? '/sitemap.xml');
                
            case 'robots':
                return $baseUrl . '/robots.txt';
                
            case 'ynghia':
                return $baseUrl . '/y-nghia-cac-con-so-tu-00-den-99-trong-lo-de';
                
            default:
                return $baseUrl;
        }
    }
    
    
    
    /**
     * Tạo structured data cho trang danh sách dự đoán
     */
    public static function generatePredictionListingStructuredData($region, $predictions = [])
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];
        
        $regionName = $regionNames[$region] ?? $region;
        $baseUrl = self::getBaseUrl();
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "Dự đoán xổ số {$regionName}",
            'description' => "Danh sách dự đoán xổ số {$regionName} mới nhất",
            'numberOfItems' => count($predictions),
            'itemListElement' => []
        ];
        
        $position = 1;
        foreach ($predictions as $prediction) {
            if (is_object($prediction)) {
                $structuredData['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $prediction->title ?? "Dự đoán {$region}",
                    'url' => $baseUrl . '/' . ($prediction->slug ?? ''),
                    'datePublished' => isset($prediction->created_at) ? date('c', strtotime($prediction->created_at)) : null
                ];
                $position++;
            }
        }
        
        return $structuredData;
    }
    
    
    
    /**
     * Tạo structured data cho trang dự đoán
     */
    public static function generatePredictionStructuredData($title, $description, $region, $date, $publishedDate = null)
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];
        
        $regionName = $regionNames[$region] ?? $region;
        $dateFormatted = date('d/m/Y', strtotime($date));
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'description' => $description,
            'datePublished' => $publishedDate ?: date('c'),
            'dateModified' => date('c'),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::getBaseUrl() . '/img/logo-soicau247.png'
                ]
            ],
            'about' => [
                '@type' => 'Thing',
                'name' => "Dự đoán xổ số {$regionName}",
                'description' => "Phân tích và dự đoán xổ số {$regionName} ngày {$dateFormatted}"
            ]
        ];
        
        return $structuredData;
    }
    
    /**
     * Tạo structured data cho trang thống kê
     */
    public static function generateStatisticsStructuredData($region, $type, $data = [])
    {
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];
        
        $typeNames = [
            'thongke' => 'Thống kê',
            'logan' => 'Lô gan',
            'dacbiet' => 'Giải đặc biệt',
            'dauduoi' => 'Đầu đuôi',
            'tansuat' => 'Tần suất'
        ];
        
        $regionName = $regionNames[$region] ?? $region;
        $typeName = $typeNames[$type] ?? $type;
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => "{$typeName} xổ số {$regionName}",
            'description' => "Dữ liệu thống kê chi tiết xổ số {$regionName}",
            'dateModified' => date('c'),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => self::getBaseUrl()
            ],
            'distribution' => [
                '@type' => 'DataDownload',
                'contentUrl' => self::getBaseUrl() . '/thong-ke-' . strtolower($region) . '-' . $type,
                'encodingFormat' => 'text/html'
            ]
        ];
        
        return $structuredData;
    }

    

    

    


    /**
     * Generate structured data for Vietlott pages
     */
    public static function generateVietlottStructuredData(string $drawType, $latestResult = null): array
    {
        $baseUrl = self::getBaseUrl();
        
        $typeNames = [
            'Mega645' => 'Mega 6/45',
            'Power655' => 'Power 6/55',
            'MAX3D' => 'Max 3D',
            'MAX3DPRO' => 'Max 3D Pro'
        ];
        
        $typeName = $typeNames[$drawType] ?? $drawType;
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => "Kết quả Vietlott {$typeName}",
            'description' => "Dữ liệu kết quả xổ số Vietlott {$typeName}",
            'dateModified' => date('c'),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => $baseUrl
            ],
            'distribution' => [
                '@type' => 'DataDownload',
                'contentUrl' => $baseUrl . "/vietlott-" . strtolower(str_replace(['/', ' '], ['-', '-'], $typeName)),
                'encodingFormat' => 'text/html'
            ]
        ];
        
        if ($latestResult) {
            $structuredData['about'] = [
                '@type' => 'Thing',
                'name' => "Kết quả Vietlott {$typeName} ngày " . ($latestResult->draw_date ?? 'N/A'),
                'description' => "Kết quả xổ số Vietlott {$typeName} mới nhất"
            ];
        }
        
        return $structuredData;
    }
    
    /**
     * Generate SEO meta for quaythu pages
     */
    

    /**
     * Generate structured data for quaythu pages
     */
    public static function generateQuaythuStructuredData(string $region): array
    {
        $baseUrl = self::getBaseUrl();
        
        $regionNames = [
            'XSMB' => 'Miền Bắc',
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung'
        ];
        
        $regionName = $regionNames[$region] ?? $region;
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => "Quay thử xổ số {$regionName}",
            'description' => "Công cụ quay thử xổ số {$regionName} miễn phí",
            'url' => $baseUrl . "/quay-thu-xsmb.html",
            'applicationCategory' => 'GameApplication',
            'operatingSystem' => 'Web Browser',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'VND'
            ]
        ];
    }

    

    

    /**
     * Generate structured data for dudoansoicau page
     */
    public static function generateDudoanSoiCauStructuredData(array $predictions = []): array
    {
        $baseUrl = self::getBaseUrl();
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Dự đoán soi cầu xổ số',
            'description' => 'Trang tổng hợp dự đoán soi cầu xổ số chính xác nhất',
            'url' => $baseUrl . '/du-doan-soi-cau-xo-so',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Soi Cầu 247',
                'url' => $baseUrl
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Danh sách dự đoán soi cầu',
                'description' => 'Tổng hợp các bài dự đoán soi cầu xổ số mới nhất'
            ]
        ];
        
        if (!empty($predictions)) {
            $structuredData['mainEntity']['numberOfItems'] = count($predictions);
            $structuredData['mainEntity']['itemListElement'] = [];
            
            foreach ($predictions as $index => $prediction) {
                $structuredData['mainEntity']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => [
                        '@type' => 'Article',
                        'headline' => $prediction->title ?? 'Dự đoán xổ số',
                        'description' => $prediction->excerpt ?? 'Dự đoán soi cầu xổ số',
                        'url' => $baseUrl . '/' . ($prediction->slug ?? ''),
                        'datePublished' => isset($prediction->created_at) ? date('c', strtotime($prediction->created_at)) : date('c')
                    ]
                ];
            }
        }
        
        return $structuredData;
    }
}
