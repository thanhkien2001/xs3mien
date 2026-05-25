<?php

declare(strict_types=1);

namespace App\Controllers;
use Phalcon\Mvc\Controller;
use App\Library\KqxsHelper;
use App\Models\LotteryResults;
use App\Models\PredictionArticles;
use App\Models\Provinces;
use App\Library\CacheHelper;
use App\Library\PerformanceHelper;
use App\Library\SeoHelper;
use App\Library\SchemaHelper;

class KetquaxsmbController extends ControllerBase
{
    private $tz;

    public function initialize()
    {
        $this->setViewStyles();
        $this->cache = $this->getCache();
        $this->tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        
    }

    /**
     * Helper method to get absolute URL for breadcrumb
     */
    private function getAbsoluteUrl($path = '')
    {
        $config = $this->getDI()->get('config');
        $baseUrl = $config->application->baseUrl;
        
        // Nếu path rỗng, lấy URI hiện tại
        if (empty($path)) {
            $path = $this->request->getURI();
        }
        
        return $baseUrl . $path;
    }

    public function indexAction()
    {
        $date = new \DateTime('today', $this->tz);
        return $this->forwardToDate($date);
    }

    public function shortXsmbAction()
    {
        $date = new \DateTime('today', $this->tz);
        return $this->forwardToDate($date, true);
    }

    public function byDateAction($d1, $m1, $d2, $m2, $y)
    {
        $uri = $this->request->getURI();

        // if (preg_match('/\/xsmb-0[1-9]-|ngay-0[1-9]-/i', $uri)) {
        //     $this->response->setStatusCode(404, 'Not Found');
        //     $this->view->pick('errors/404');
        //     return;
        // }

        $dateStr = sprintf('%04d-%02d-%02d', (int)$y, (int)$m2, (int)$d2);

        try {
            $date = new \DateTime($dateStr, $this->tz);
        } catch (\Exception $e) {
            $this->response->setStatusCode(404, 'Not Found');
            $this->view->pick('errors/404');
            return;
        }

        // Kiểm tra nếu ngày lớn hơn ngày hiện tại → 404
        $today = new \DateTime('today', $this->tz);
        if ($date > $today) {
            $this->response->setStatusCode(404, 'Not Found');
            $this->view->pick('errors/404');
            return;
        }

        return $this->forwardToDate($date);
    }

    private function getXsmbRepresentativeProvinceId(): int
    {
        // Tự động lấy province đại diện theo ngày quay (thứ trong tuần)
        $dow = (int)(new \DateTime('now', $this->tz))->format('N');
        $province = \App\Models\Provinces::findFirst([
            'conditions' => "region = 'XSMB' AND FIND_IN_SET(:dow:, draw_days)",
            'bind'       => ['dow' => (string)$dow],
            'order'      => 'id ASC'
        ]);
        if ($province) {
            return (int)$province->key_id;
        }
        // Fallback nếu không có: mặc định 49 (thường là Hà Nội)
        return 49;
    }

    private function convertProvincesToAssociativeArray($provinces): array
    {
        $result = [];
        foreach ($provinces as $province) {
            // Use key_id as key for JavaScript mapping with live data
            $result[$province['keyid']] = [
                'id' => (int)$province['id'],
                'name' => (string)$province['name'],
                'code' => (string)$province['code'],
                'keyid' => (string)$province['keyid'],
            ];
        }
        return $result;
    }

    private function isWithinLiveWindow(): bool
    {
        $now = new \DateTime('now', $this->tz);
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        $current = $hour * 60 + $minute;

        // XSMB: 17h15-23h59 (như XSMT)
        $start = 1 * 60 + 15; // 17:15
        $end = 23 * 60 + 59; // 23:59

        return $current >= $start && $current < $end;
    }

    private function forwardToDate(\DateTime $date, bool $isShortUrl = false)
    {
        $dateStr = $date->format('Y-m-d');

        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $isBasePage = preg_match('/(\/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb\.html|\/xo-so-mien-bac-xsmb\.html|\/xo-so-mien-bac-xsmb)$/', $currentUri);
        if ($isShortUrl) $isBasePage = true;

        $cacheKey = CacheHelper::generateKey('XSMB_full_page', ['date' => $dateStr]);
        
        // Bypass cache nếu đang trong khung giờ quay số để live update hoạt động
        $cachedResult = null;
        if (!$this->isWithinLiveWindow()) {
            $cachedResult = $this->cache->get($cacheKey);
        }

        if ($cachedResult !== null) {
            if ($isBasePage) {
                $seoData = \App\Library\SeoHelper::getSeoData('lottery_results_base_xsmb');
            } else {
                $seoData = PerformanceHelper::generateCachedSeoData('lottery_result', 'XSMB', $dateStr, [
                    'results' => $cachedResult['result'] ?? null,
                    'provinceName' => $cachedResult['provinceName'] ?? 'Miền Bắc'
                ], $this->cache);
            }

            // Thêm thống kê động nếu chưa có trong cache
            if (!isset($cachedResult['dynamicStats'])) {
                $cachedResult['dynamicStats'] = $this->calculateDynamicStatistics([
                    'normalized' => $cachedResult['result'],
                    'dauDuoi' => [
                        'dau' => $cachedResult['dau'] ?? array_fill(0, 10, []),
                        'duoi' => $cachedResult['duoi'] ?? array_fill(0, 10, [])
                    ]
                ], $date);
            }

            $cachedResult['isBasePage'] = $isBasePage;

            $cachedResult = array_merge($cachedResult, $seoData);
            
            // Set breadcrumb schema
            $this->view->setVar('page_schema_type', 'webpage');
            if ($isBasePage) {
                $this->view->setVar('page_schema_data', [
                    'title' => $cachedResult['seo_title'] ?? 'Kết quả XSMB - Xổ số Miền Bắc',
                    'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Miền Bắc hôm nay',
                    'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html'),
                    'breadcrumbs' => [
                        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                        ['name' => 'Kết quả XSMB', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html')]
                    ]
                ]);
            } else {
                // Breadcrumb for byDate page: thêm 1 cấp ngày
                $day = (int)$date->format('j');
                $month = (int)$date->format('n');
                $year = (int)$date->format('Y');
                $dateUrl = sprintf('xsmb-%d-%d-ket-qua-xo-so-mien-bac-ngay-%d-%d-%d.html', $day, $month, $day, $month, $year);
                
                $this->view->setVar('page_schema_data', [
                    'title' => $cachedResult['seo_title'] ?? 'Kết quả XSMB - Xổ số Miền Bắc',
                    'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Miền Bắc hôm nay',
                    'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                    'breadcrumbs' => [
                        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                        ['name' => 'Kết quả XSMB', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html')],
                        ['name' => 'XSMB ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                    ]
                ]);
            }
            
            $this->view->setVars($cachedResult);
            if ($isShortUrl) {
                $this->view->pick('ketquaxsmb/shorturl');
            } else {
                $this->view->pick('ketquaxsmb/byDate');
            }
            return;
        }

        // Nếu đang trong live window VÀ là ngày hôm nay → không lấy dữ liệu từ DB cho bảng chính
        // Để WebSocket live update hoạt động
        $today = new \DateTime('today', $this->tz);
        $isLiveToday = $this->isWithinLiveWindow() && $date->format('Y-m-d') === $today->format('Y-m-d');
        
        if ($isLiveToday) {
            // Không lấy dữ liệu từ DB cho bảng chính, để null để view hiển thị spinner
            $result = [
                'normalized' => null,
                'dauDuoi' => ['dau' => array_fill(0, 10, []), 'duoi' => array_fill(0, 10, [])],
                'provinceName' => 'Miền Bắc'
            ];
        } else {
            // Ngoài live window hoặc không phải ngày hôm nay → lấy dữ liệu từ DB như bình thường
            $result = $this->getCachedLotteryResult($dateStr);
        }
        $predictions = $this->getCachedPredictions($date);
        [$thuText, $thuSlug] = KqxsHelper::weekdayInfo($date);
        $otherLinks = $this->getCachedOtherLinks($date);
        try {
            $latestResults = $this->getCachedLatestResults($dateStr);
        } catch (\Exception $e) {
            error_log("Error getting latest results for XSMB: " . $e->getMessage());
            $latestResults = [];
        }

        // Tính toán thống kê động
        $dynamicStats = $this->calculateDynamicStatistics($result, $date);
        
        $thuLink = "/xsmb-{$thuSlug}-ket-qua-xo-so-mien-bac.html";

        $active = $isBasePage ? 'xsmb' : $thuSlug;

        // Provinces data for live updates (XSMB uses one representative province)
        $repProvincekeyId = $this->getXsmbRepresentativeProvinceId();
        $provinces = [
            (string)$repProvincekeyId => [
                'id' => 6, // Có thể thay đổi theo province thực
                'name' => 'Hải Phòng', // Có thể thay đổi theo province thực
                'code' => 'XSND', // Có thể thay đổi theo province thực
                'keyid' => (string)$repProvincekeyId
            ]
        ];

        $finalResult = [
            'pageDate'     => $date,
            'result'       => $result['normalized'] ?? null,
            'dau'          => $result['dauDuoi']['dau'] ?? array_fill(0, 10, []),
            'duoi'         => $result['dauDuoi']['duoi'] ?? array_fill(0, 10, []),
            'thuText'      => $thuText,
            'thuLink'      => $thuLink,
            'active'       => $active,
            'otherLinks'   => $otherLinks,
            'serials'      => [],
            'predictions'  => $predictions,
            'provinceName' => $result['provinceName'] ?? 'Miền Bắc',
            'currentVal'   => $dateStr,
            'todayMax'     => (new \DateTime('today', $this->tz))->format('Y-m-d'),
            'latestResults' => $latestResults,
            'isBasePage'   => false,
            'provinces'    => $this->convertProvincesToAssociativeArray($provinces),
            'repProvinceId' => $this->getXsmbRepresentativeProvinceId(),
            'dynamicStats' => $dynamicStats,
        ];

        $finalResult['isBasePage'] = $isBasePage;

        $cacheLifetime = $this->calculateCacheLifetime($date);
        // Lưu cache với dynamicStats để tránh tính toán lại
        $this->cache->set($cacheKey, $finalResult, $cacheLifetime);

        if ($isBasePage) {
            $seoData = \App\Library\SeoHelper::getSeoData('lottery_results_base_xsmb');
        } else {
            $seoData = PerformanceHelper::generateCachedSeoData('lottery_result', 'XSMB', $dateStr, [
                'results' => $result['normalized'] ?? null,
                'provinceName' => $result['provinceName'] ?? 'Miền Bắc'
            ], $this->cache);
        }
        
        $finalResult = array_merge($finalResult, $seoData);

        // Set breadcrumb schema
        $this->view->setVar('page_schema_type', 'webpage');
        if ($isBasePage) {
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? 'Kết quả XSMB - Xổ số Miền Bắc',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Miền Bắc hôm nay',
                'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html'),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Kết quả XSMB', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html')]
                ]
            ]);
        } else {
            // Breadcrumb for byDate page: thêm 1 cấp ngày
            $day = (int)$date->format('j');
            $month = (int)$date->format('n');
            $year = (int)$date->format('Y');
            $dateUrl = sprintf('xsmb-%d-%d-ket-qua-xo-so-mien-bac-ngay-%d-%d-%d.html', $day, $month, $day, $month, $year);
            
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? 'Kết quả XSMB - Xổ số Miền Bắc',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Miền Bắc hôm nay',
                'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Kết quả XSMB', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html')],
                    ['name' => 'XSMB ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                ]
            ]);
        }

        $this->view->setVars($finalResult);
        if ($isShortUrl) {
            $this->view->pick('ketquaxsmb/shorturl');
        } else {
            $this->view->pick('ketquaxsmb/byDate');
        }
    }
    private function getCachedLotteryResult(string $dateStr): array
    {
        $cacheKey = "XSMB_result_{$dateStr}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = \App\Models\LotteryResults::findFirst([
            'conditions' => 'draw_type = :type: AND draw_date = :date:',
            'bind' => ['type' => 'XSMB', 'date' => $dateStr],
            'order' => 'id DESC'
        ]);
        
        if (!$result) {
            error_log("No lottery result found for XSMB on date: " . $dateStr);
        }

        $normalized = null;
        $dauDuoi = ['dau' => array_fill(0, 10, []), 'duoi' => array_fill(0, 10, [])];
        $provinceName = 'Miền Bắc';

        if ($result) {
            $normalized = [
                'id'            => $result->id,
                'special_prize' => (string)$result->special_prize,
                'first_prize'   => (string)$result->first_prize,
                'second_prize'  => KqxsHelper::toArray($result->second_prize),
                'third_prize'   => KqxsHelper::toArray($result->third_prize),
                'fourth_prize'  => KqxsHelper::toArray($result->fourth_prize),
                'fifth_prize'   => KqxsHelper::toArray($result->fifth_prize),
                'sixth_prize'   => KqxsHelper::toArray($result->sixth_prize),
                'seventh_prize' => KqxsHelper::toArray($result->seventh_prize),
                'eighth_prize'  => KqxsHelper::toArray($result->eighth_prize),
                'lv'            => KqxsHelper::toArray($result->lv),
            ];

            $two = KqxsHelper::collectAllTwoDigits($normalized);
            $dauDuoi = KqxsHelper::buildDauDuoi($two);
            
            $provinceName = 'Miền Bắc';
        }

        $resultData = [
            'normalized' => $normalized,
            'dauDuoi' => $dauDuoi,
            'provinceName' => $provinceName
        ];

        // Cache lottery result for longer time based on date
        // $cacheTime = strtotime($dateStr) < strtotime('today') ? 7200 : 300; // 2h for past, 5min for today
        // $this->cache->set($cacheKey, $resultData, $cacheTime);

        return $resultData;
    }

    private function getCachedPredictions(\DateTime $pageDate): array
    {
        $target = (clone $pageDate)->modify('+1 day')->format('Y-m-d');
        $cacheKey = "XSMB_predictions_batch_{$target}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $allPredictions = PredictionArticles::find([
            'conditions' => 'region IN ({regions:array}) AND (prediction_date = :target: OR prediction_date > :target:)',
            'bind' => [
                'regions' => ['XSMN', 'XSMT', 'XSMB'],
                'target' => $target
            ],
            'order' => 'region ASC, prediction_date ASC, id DESC'
        ]);

        $groupedPredictions = [];
        foreach ($allPredictions as $pred) {
            $region = $pred->region;
            if (!isset($groupedPredictions[$region])) {
                $groupedPredictions[$region] = [];
            }
            $groupedPredictions[$region][] = $pred;
        }

        $items = [];
        $categoryConfig = $this->getPredictionCategoryConfig();

        foreach (['XSMN', 'XSMT', 'XSMB'] as $region) {
            $art = $this->selectBestPrediction($groupedPredictions[$region] ?? [], $target);

            if ($art) {
                $items[] = [
                    'region'         => $region,
                    'title'          => $art->title,
                    'slug'           => $art->slug,
                    'url'            => $this->buildPredictionUrl($art->slug, $region),
                    'image_url'      => $art->image_url ?: '/images/no-image.jpg',
                    'category_href'  => $categoryConfig[$region]['href'],
                    'category_label' => $categoryConfig[$region]['label'],
                ];
            }
        }

        // Cache for 30 minutes
        // $this->cache->set($cacheKey, $items, 1800);
        return $items;
    }


    private function getCachedOtherLinks(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $cacheKey = "XSMB_other_links_{$dateStr}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $otherLinks = KqxsHelper::otherDaysLinksXSMB($date, 12);

        // Cache for 4 hours
        // $this->cache->set($cacheKey, $otherLinks, 14400);
        return $otherLinks;
    }

    // 🔥 NEW: Cache latest results for XSMB (4 results) - excluding current date
    private function getCachedLatestResults(?string $excludeDate = null): array
    {
        try {
            $cacheKey = "XSMB_latest_results_6" . ($excludeDate ? "_exclude_{$excludeDate}" : "");
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }

            $conditions = 'draw_type = :type:';
            $bind = ['type' => 'XSMB'];
            
            if ($excludeDate) {
                $conditions .= ' AND draw_date != :exclude_date:';
                $bind['exclude_date'] = $excludeDate;
            }

            $latestResults = LotteryResults::find([
                'conditions' => $conditions,
                'bind' => $bind,
                'order' => 'draw_date DESC, id DESC',
                'limit' => 6
            ]);
            
            if (empty($latestResults)) {
                return [];
            }

            $results = [];
            foreach ($latestResults as $latestResult) {
                $normalized = [
                    'id'            => $latestResult->id,
                    'special_prize' => (string)$latestResult->special_prize,
                    'first_prize'   => (string)$latestResult->first_prize,
                    'second_prize'  => KqxsHelper::toArray($latestResult->second_prize),
                    'third_prize'   => KqxsHelper::toArray($latestResult->third_prize),
                    'fourth_prize'  => KqxsHelper::toArray($latestResult->fourth_prize),
                    'fifth_prize'   => KqxsHelper::toArray($latestResult->fifth_prize),
                    'sixth_prize'   => KqxsHelper::toArray($latestResult->sixth_prize),
                    'seventh_prize' => KqxsHelper::toArray($latestResult->seventh_prize),
                    'eighth_prize'  => KqxsHelper::toArray($latestResult->eighth_prize),
                    'draw_date'     => $latestResult->draw_date,
                    'lv'            => KqxsHelper::toArray($latestResult->lv),
                ];

                $two = KqxsHelper::collectAllTwoDigits($normalized);
                $dauDuoi = KqxsHelper::buildDauDuoi($two);

                $results[] = [
                    'normalized' => $normalized,
                    'dauDuoi' => $dauDuoi,
                    'provinceName' => 'Miền Bắc',
                    'draw_date' => $latestResult->draw_date,
                ];
            }

            $this->cache->set($cacheKey, $results, 86000);
            
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    // Helper methods
    private function selectBestPrediction(array $predictions, string $target)
    {
        if (empty($predictions)) {
            return null;
        }

        foreach ($predictions as $pred) {
            if ($pred->prediction_date === $target) {
                return $pred;
            }
        }

        foreach ($predictions as $pred) {
            if ($pred->prediction_date > $target) {
                return $pred;
            }
        }

        return $predictions[0]; // Latest
    }

    private function getPredictionCategoryConfig(): array
    {
        static $config = [
            'XSMN' => ['href' => 'du-doan-xsmn', 'label' => 'Dự đoán XSMN'],
            'XSMT' => ['href' => 'du-doan-xsmt', 'label' => 'Dự đoán XSMT'],
            'XSMB' => ['href' => 'du-doan-xsmb', 'label' => 'Dự đoán XSMB'],
        ];
        return $config;
    }

    protected static $baseUrls = [
        'XSMN' => '/du-doan-',
        'XSMT' => '/du-doan-',
        'XSMB' => '/du-doan-',
    ];

    protected function buildPredictionUrl(string $slug, string $region): string
    {
        return (self::$baseUrls[$region] ?? self::$baseUrls['XSMB']) . $slug;
    }

    private function calculateCacheLifetime(\DateTime $date): int
    {
        $today = new \DateTime('today', $this->tz);

        if ($date >= $today) {
            return 86400;
        } elseif ($date >= $today->modify('-1 day')) {
            return 86400; 
        } else {
            return 86400;
        }
    }
    public function listxsmbAction(){
        $this->view->customindex = '/css/indexheader.css';
        
        $seoData = SeoHelper::getSeoData('xsmb_list', null);
        
        $this->view->setVars($seoData);
        // Schema data for lottery results
        $this->view->setVar('page_schema_type', 'lottery_results');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get(),
            'region' => 'xsmb',
            'date' => date('d/m/Y'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Xổ số Miền Bắc', 'url' => $this->url->get('/xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html')]
            ]
        ]);

        $this->view->pick('ketquaxsmb/listxsmb');
    }

    /**
     * AJAX endpoint for loading more XSMB results
     * URL: /xo-so-mien-bac-xsmb/page/{pageNumber}
     */
    public function pageAction($pageNumber = 2)
    {
        $page = max(2, (int)$pageNumber);
        $perPage = 3; // Number of results per page
        $offset = ($page - 1) * $perPage;

        // Get results from database
        $results = LotteryResults::find([
            'conditions' => 'province_id = 1 AND status = 1',
            'order' => 'draw_date DESC',
            'limit' => $perPage,
            'offset' => $offset
        ]);

        if (count($results) === 0) {
            // No more results
            $this->response->setContent('');
            return $this->response;
        }

        // Prepare data for each result
        $latestResults = [];
        foreach ($results as $result) {
            $normalized = [
                'id'            => $result->id,
                'special_prize' => (string)$result->special_prize,
                'first_prize'   => (string)$result->first_prize,
                'second_prize'  => KqxsHelper::toArray($result->second_prize),
                'third_prize'   => KqxsHelper::toArray($result->third_prize),
                'fourth_prize'  => KqxsHelper::toArray($result->fourth_prize),
                'fifth_prize'   => KqxsHelper::toArray($result->fifth_prize),
                'sixth_prize'   => KqxsHelper::toArray($result->sixth_prize),
                'seventh_prize' => KqxsHelper::toArray($result->seventh_prize),
                'eighth_prize'  => KqxsHelper::toArray($result->eighth_prize),
                'draw_date'     => $result->draw_date,
                'lv'            => KqxsHelper::toArray($result->lv),
            ];

            $two = KqxsHelper::collectAllTwoDigits($normalized);
            $dauDuoi = KqxsHelper::buildDauDuoi($two);

            $latestResults[] = [
                'normalized' => $normalized,
                'dauDuoi' => $dauDuoi,
                'draw_date' => $result->draw_date,
            ];
        }

        // Disable layout for AJAX response
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        
        // Pass data to partial view
        $this->view->setVars([
            'latestResults' => $latestResults
        ]);

        // Use a simple view that just loops through results
        $this->view->pick('ketquaxsmb/page');
    }

    /**
     * Tính toán thống kê động cho XSMB
     */
    private function calculateDynamicStatistics(array $result, \DateTime $date): array
    {
        // Nếu không có kết quả, trả về thống kê mẫu
        if (!$result['normalized']) {
            return $this->getSampleStatistics();
        }

        $normalized = $result['normalized'];

        // Lấy tất cả số 2 chữ số từ kết quả
        $allTwoDigits = [];
        if ($normalized['special_prize']) {
            $allTwoDigits[] = substr($normalized['special_prize'], -2);
        }
        if ($normalized['first_prize']) {
            $allTwoDigits[] = substr($normalized['first_prize'], -2);
        }

        // Từ các giải khác
        $prizes = ['second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'];
        foreach ($prizes as $prize) {
            if (!empty($normalized[$prize])) {
                $prizeArray = is_array($normalized[$prize]) ? $normalized[$prize] : KqxsHelper::toArray($normalized[$prize]);
                foreach ($prizeArray as $num) {
                    if (strlen($num) >= 2) {
                        $allTwoDigits[] = substr($num, -2);
                    }
                }
            }
        }

        $allTwoDigits = array_unique($allTwoDigits);

        // Tính toán các thống kê
        $stats = $this->analyzeLotteryNumbers($allTwoDigits);

        return $stats;
    }

    /**
     * Phân tích các số lô tô để tạo thống kê
     */
    private function analyzeLotteryNumbers(array $numbers): array
    {
        if (empty($numbers)) {
            return $this->getSampleStatistics();
        }

        // Tính đầu và đuôi
        $dauCount = array_fill(0, 10, 0);
        $duoiCount = array_fill(0, 10, 0);

        foreach ($numbers as $num) {
            if (strlen($num) >= 2) {
                $dau = (int)substr($num, 0, 1);
                $duoi = (int)substr($num, 1, 1);
                $dauCount[$dau]++;
                $duoiCount[$duoi]++;
            }
        }

        // Tìm đầu và đuôi về nhiều nhất
        $maxDau = array_keys($dauCount, max($dauCount));
        $maxDuoi = array_keys($duoiCount, max($duoiCount));

        // Bạch thủ đề (random từ các số có trong kết quả)
        $randomNum = $numbers[array_rand($numbers)];
        $bachThuDau = strlen($randomNum) >= 2 ? (int)substr($randomNum, 0, 1) : rand(0, 9);
        $bachThuDuoi = strlen($randomNum) >= 2 ? (int)substr($randomNum, 1, 1) : rand(0, 9);
        $bachThuTong = ($bachThuDau + $bachThuDuoi) % 10;

        // Tạo lô tô lộn về cả cặp (random 3 cặp)
        $lonPairs = [];
        for ($i = 0; $i < 3; $i++) {
            $num1 = $numbers[array_rand($numbers)];
            $num2 = $numbers[array_rand($numbers)];
            $lonPairs[] = $num1 . ' - ' . $num2;
        }

        // Lô kép (số có 2 chữ số giống nhau)
        $kepNumbers = [];
        foreach ($numbers as $num) {
            if (strlen($num) >= 2 && substr($num, 0, 1) === substr($num, 1, 1)) {
                $kepNumbers[] = $num;
            }
        }
        // Nếu không có lô kép thật, tạo random
        if (empty($kepNumbers)) {
            $kepNumbers = [rand(0, 9) * 11, rand(0, 9) * 11 + rand(0, 9)];
        }

        // Lô nháy (random)
        $nhay2 = [];
        $nhay3 = [];
        for ($i = 0; $i < 2; $i++) {
            $nhay2[] = $numbers[array_rand($numbers)];
        }
        for ($i = 0; $i < 3; $i++) {
            $nhay3[] = $numbers[array_rand($numbers)];
        }

        // Đầu và đuôi câm (không về)
        $dauCam = [];
        $duoiCam = [];
        for ($i = 0; $i <= 9; $i++) {
            if ($dauCount[$i] == 0) {
                $dauCam[] = $i;
            }
            if ($duoiCount[$i] == 0) {
                $duoiCam[] = $i;
            }
        }

        return [
            'bach_thu' => [
                'dau' => $bachThuDau,
                'duoi' => $bachThuDuoi,
                'tong' => $bachThuTong
            ],
            'lo_lon' => $lonPairs,
            'lo_kep' => array_slice($kepNumbers, 0, 3),
            'lo_nhay_2' => $nhay2,
            'lo_nhay_3' => $nhay3,
            'dau_cam' => $dauCam,
            'duoi_cam' => $duoiCam,
            'dau_max' => $maxDau,
            'duoi_max' => $maxDuoi
        ];
    }

    /**
     * Trả về thống kê mẫu khi không có dữ liệu
     */
    private function getSampleStatistics(): array
    {
        return [
            'bach_thu' => [
                'dau' => rand(0, 9),
                'duoi' => rand(0, 9),
                'tong' => rand(0, 9)
            ],
            'lo_lon' => [
                rand(10, 99) . ' - ' . rand(10, 99),
                rand(10, 99) . ' - ' . rand(10, 99),
                rand(10, 99) . ' - ' . rand(10, 99)
            ],
            'lo_kep' => [rand(0, 9) * 11, rand(0, 9) * 11 + rand(0, 9)],
            'lo_nhay_2' => [rand(10, 99), rand(10, 99)],
            'lo_nhay_3' => [rand(10, 99), rand(10, 99), rand(10, 99)],
            'dau_cam' => [],
            'duoi_cam' => [],
            'dau_max' => [rand(0, 9)],
            'duoi_max' => [rand(0, 9)]
        ];
    }

    protected function setViewStyles()
    {
        $this->view->customindex = '/css/indexheader.css';
    }
}
