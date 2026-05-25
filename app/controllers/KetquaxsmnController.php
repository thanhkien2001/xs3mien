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

class KetquaxsmnController extends ControllerBase
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

    public function shortXsmnAction()
    {
        $date = new \DateTime('today', $this->tz);
        return $this->forwardToDate($date, true);
    }

    public function byDateAction($d1, $m1, $d2, $m2, $y)
    {
        $uri = $this->request->getURI();
        
        // if (preg_match('/\/xsmn-0[1-9]-|ngay-0[1-9]-/i', $uri)) {
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

    private function isWithinLiveWindow(): bool
    {
        $now = new \DateTime('now', $this->tz);
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        $current = $hour * 60 + $minute;
        
        // XSMN: 16h15-17h00
        $start = 0 * 60 + 15; // 16:15
        $end = 23 * 60; // 17:00
        
        return $current >= $start && $current < $end;
    }

    private function convertProvincesToAssociativeArray($provinces): array
    {
        $result = [];
        foreach ($provinces as $province) {
            // Use key_id as key for JavaScript mapping with live data
            $result[$province->getKeyId()] = [
                'id' => (int)$province->getId(),
                'name' => (string)$province->getName(),
                'code' => (string)$province->getCode(),
                'keyid' => (string)$province->getKeyId(),
            ];
        }
        return $result;
    }

    private function forwardToDate(\DateTime $date, bool $isShortUrl = false)
    {
        $dateStr = $date->format('Y-m-d');
        
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $isBasePage = preg_match('/(\/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn\.html|\/xo-so-mien-nam-xsmn\.html|\/xo-so-mien-nam-xsmn)$/', $currentUri);
        if ($isShortUrl) $isBasePage = true;

        $cacheKey = CacheHelper::generateKey('XSMN_full_page', ['date' => $dateStr]);
        
        // Bypass cache nếu đang trong khung giờ quay số để live update hoạt động
        $cachedResult = null;
        if (!$this->isWithinLiveWindow()) {
            $cachedResult = $this->cache->get($cacheKey);
        }

        if ($cachedResult !== null) {
            if ($isBasePage) {
                $seoData = \App\Library\SeoHelper::getSeoData('lottery_results_base_xsmn');
            } else {
                $seoData = PerformanceHelper::generateCachedSeoData('lottery_result', 'XSMN', $dateStr, [
                    'results' => $cachedResult['resultsForView'] ?? null,
                    'provinceName' => null
                ], $this->cache);
            }
            
            $cachedResult['isBasePage'] = $isBasePage;
            
            $this->view->setVars($seoData);
            
            // Set breadcrumb schema
            $this->view->setVar('page_schema_type', 'webpage');
            if ($isBasePage) {
                $this->view->setVar('page_schema_data', [
                    'title' => $cachedResult['seo_title'] ?? 'Kết quả XSMN - Xổ số Miền Nam',
                    'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Miền Nam hôm nay',
                    'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html'),
                    'breadcrumbs' => [
                        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                        ['name' => 'Kết quả XSMN', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html')]
                    ]
                ]);
            } else {
                // Breadcrumb for byDate page: thêm 1 cấp ngày
                $day = (int)$date->format('j');
                $month = (int)$date->format('n');
                $year = (int)$date->format('Y');
                $dateUrl = sprintf('xsmn-%d-%d-ket-qua-xo-so-mien-nam-ngay-%d-%d-%d.html', $day, $month, $day, $month, $year);
                
                $this->view->setVar('page_schema_data', [
                    'title' => $cachedResult['seo_title'] ?? 'Kết quả XSMN - Xổ số Miền Nam',
                    'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Miền Nam hôm nay',
                    'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                    'breadcrumbs' => [
                        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                        ['name' => 'Kết quả XSMN', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html')],
                        ['name' => 'XSMN ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                    ]
                ]);
            }
            
            $this->view->setVars($cachedResult);
            if ($isShortUrl) {
                $this->view->pick('ketquaxsmn/shorturl');
            } else {
                $this->view->pick('ketquaxsmn/index');
            }
            return;
        }

        // Nếu đang trong live window VÀ là ngày hôm nay → không lấy dữ liệu từ DB cho bảng chính
        // Để WebSocket live update hoạt động
        $today = new \DateTime('today', $this->tz);
        $isLiveToday = $this->isWithinLiveWindow() && $date->format('Y-m-d') === $today->format('Y-m-d');
        
        if ($isLiveToday) {
            // Chỉ lấy provinces để hiển thị header, không lấy results từ DB
            // Tạo empty structure với provinces nhưng không có prizes
            $dow = (int)$date->format('N');
            $provinces = Provinces::find([
                'conditions' => "region = 'XSMN' AND FIND_IN_SET(:dow:, draw_days)",
                'bind'       => ['dow' => (string)$dow],
                'order'      => 'id ASC',
            ]);

            $provincesForView = [];
            foreach ($provinces as $p) {
                $provincesForView[] = [
                    'id'    => (int)$p->id,
                    'name'  => (string)$p->name,
                    'code'  => (string)$p->code,
                    'keyid' => (string)$p->keyid,
                ];
            }

            // Tạo empty resultsForView với prizes = null cho mỗi province
            $resultsForView = [];
            foreach ($provincesForView as $pv) {
                $resultsForView[$pv['id']] = [
                    'prizes' => null,
                    'dau'    => array_fill(0, 10, []),
                ];
            }

            $resultsForProvinces = [
                'provincesForView' => $provincesForView,
                'resultsForView' => $resultsForView,
                'provinces' => $this->convertProvincesToAssociativeArray($provinces),
            ];
        } else {
            // Ngoài live window hoặc không phải ngày hôm nay → lấy dữ liệu từ DB như bình thường
            $resultsForProvinces = $this->getCachedXSMNResultsForDate($date);
        }
        $predictions = $this->getCachedPredictions($date);
        [$thuText, $thuSlug] = KqxsHelper::weekdayInfo($date);
        $otherLinks = $this->getCachedOtherLinks($date);
        $latestResults = $this->getCachedLatestResults($dateStr);

        $thuLink = "/xsmn-{$thuSlug}-ket-qua-xo-so-mien-nam.html";
        
        $active = $thuSlug;

        $finalResult = [
            'pageDate'          => $date,
            'currentDayName'    => $thuText,
            'thuText'           => $thuText,
            'thuSlug'           => $thuSlug,
            'thuLink'           => $thuLink,
            'active'            => $active,
            'otherLinks'        => $otherLinks,
            'predictions'       => $predictions,
            'provincesForView'  => $resultsForProvinces['provincesForView'],
            'resultsForView'    => $resultsForProvinces['resultsForView'],
            'currentVal'        => $dateStr,
            'todayMax'          => (new \DateTime('today', $this->tz))->format('Y-m-d'),
            'todayHref'         => '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html', // Specific for XSMN
            'latestResults'     => $latestResults, // 🔥 NEW: Thêm kết quả gần nhất
            'isBasePage'        => false, // Default: not base page
            'provinces'         => $this->convertProvincesToAssociativeArray($provinces),
        ];

        $finalResult['isBasePage'] = $isBasePage;

        $cacheLifetime = $this->calculateCacheLifetime($date);
        $this->cache->set($cacheKey, $finalResult, $cacheLifetime);

        if ($isBasePage) {
            $seoData = \App\Library\SeoHelper::getSeoData('lottery_results_base_xsmn');
        } else {
            $seoData = PerformanceHelper::generateCachedSeoData('lottery_result', 'XSMN', $dateStr, [
                'results' => $resultsForProvinces['normalized'],
                'provinceName' => null
            ], $this->cache);
        }
        
        $seoData = array_merge($seoData, $finalResult);
        $this->view->setVars($seoData);

        // Set breadcrumb schema
        $this->view->setVar('page_schema_type', 'webpage');
        if ($isBasePage) {
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? 'Kết quả XSMN - Xổ số Miền Nam',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Miền Nam hôm nay',
                'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html'),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Kết quả XSMN', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html')]
                ]
            ]);
        } else {
            // Breadcrumb for byDate page: thêm 1 cấp ngày
            $day = (int)$date->format('j');
            $month = (int)$date->format('n');
            $year = (int)$date->format('Y');
            $dateUrl = sprintf('xsmn-%d-%d-ket-qua-xo-so-mien-nam-ngay-%d-%d-%d.html', $day, $month, $day, $month, $year);
            
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? 'Kết quả XSMN - Xổ số Miền Nam',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Miền Nam hôm nay',
                'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Kết quả XSMN', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html')],
                    ['name' => 'XSMN ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                ]
            ]);
        }

        $this->view->setVars($finalResult);
        if ($isShortUrl) {
            $this->view->pick('ketquaxsmn/shorturl');
        } else {
            $this->view->pick('ketquaxsmn/index');
        }
    }

    private function getCachedXSMNResultsForDate(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $dow = (int)$date->format('N'); // 1..7
        $cacheKey = "XSMN_results_for_date_{$dateStr}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }
        $provinces = Provinces::find([
            'conditions' => "region = 'XSMN' AND FIND_IN_SET(:dow:, draw_days)",
            'bind'       => ['dow' => (string)$dow],
            'order'      => 'id ASC',
        ]);

        $provincesForView = [];
        foreach ($provinces as $p) {
            $provincesForView[] = [
                'id'    => (int)$p->id,
                'name'  => (string)$p->name,
                'code'  => (string)$p->code,
                'keyid' => (string)$p->keyid,
            ];
        }

        $resultsForView = [];
        foreach ($provincesForView as $pv) {
            $res = LotteryResults::findFirst([
                'conditions' => 'draw_type=:t: AND draw_date=:d: AND province_id=:pid:',
                'bind'       => ['t' => 'XSMN', 'd' => $dateStr, 'pid' => $pv['id']],
                'order'      => 'id DESC',
            ]);

            if (!$res) {
                $resultsForView[$pv['id']] = [
                    'prizes' => null,
                    'dau'    => array_fill(0, 10, []),
                ];
                continue;
            }

            $prizes = [
                1 => [$res->special_prize],
                2 => [$res->first_prize],
                3 => KqxsHelper::toArray($res->second_prize),
                4 => KqxsHelper::toArray($res->third_prize),
                5 => KqxsHelper::toArray($res->fourth_prize),
                6 => KqxsHelper::toArray($res->fifth_prize),
                7 => KqxsHelper::toArray($res->sixth_prize),
                8 => KqxsHelper::toArray($res->seventh_prize),
                9 => KqxsHelper::toArray($res->eighth_prize),
            ];

            $flat = [
                'special_prize' => (string)$res->special_prize,
                'first_prize'   => (string)$res->first_prize,
                'second_prize'  => $prizes[3],
                'third_prize'   => $prizes[4],
                'fourth_prize'  => $prizes[5],
                'fifth_prize'   => $prizes[6],
                'sixth_prize'   => $prizes[7],
                'seventh_prize' => $prizes[8],
                'eighth_prize'  => $prizes[9],
            ];
            $two = KqxsHelper::collectAllTwoDigits($flat);
            $dauDuoi = KqxsHelper::buildDauDuoi($two);

            $resultsForView[$pv['id']] = [
                'prizes' => $prizes,
                'dau'    => $dauDuoi['dau'],
            ];
        }

        $resultData = [
            'provincesForView' => $provincesForView,
            'resultsForView' => $resultsForView,
        ];

        $cacheTime = strtotime($dateStr) < strtotime('today') ? 7200 : 300; // 2h for past, 5min for today
        // $this->cache->set($cacheKey, $resultData, $cacheTime);

        return $resultData;
    }

    // ✅ FIXED: Thêm fallback để lấy bài cũ nếu không có bài mới
    private function getCachedPredictions(\DateTime $pageDate): array
    {
        $target = (clone $pageDate)->modify('+1 day')->format('Y-m-d');
        $cacheKey = "XSMN_predictions_batch_{$target}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Thử tìm bài dự đoán cho ngày hôm nay/tương lai trước
        $allPredictions = PredictionArticles::find([
            'conditions' => 'region IN ({regions:array}) AND prediction_date >= :target:',
            'bind' => [
                'regions' => ['XSMN', 'XSMT', 'XSMB'],
                'target' => $target
            ],
            'order' => 'region ASC, prediction_date ASC, id DESC',
            'limit' => 9  // 3 miền x 3 bài
        ]);

        // Nếu không có bài tương lai, lấy 9 bài gần nhất
        if (count($allPredictions) === 0) {
            $allPredictions = PredictionArticles::find([
                'conditions' => 'region IN ({regions:array})',
                'bind' => [
                    'regions' => ['XSMN', 'XSMT', 'XSMB']
                ],
                'order' => 'prediction_date DESC, id DESC',
                'limit' => 9
            ]);
        }

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

        // $this->cache->set($cacheKey, $items, 1800);
        return $items;
    }

    private function getCachedOtherLinks(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $cacheKey = "XSMN_other_links_{$dateStr}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $otherLinks = KqxsHelper::otherDaysLinksXSMN($date, 12);

        // $this->cache->set($cacheKey, $otherLinks, 14400);
        return $otherLinks;
    }

    private function getCachedLatestResults(?string $excludeDate = null): array
    {
        try {
            $cacheKey = "XSMN_latest_results_6" . ($excludeDate ? "_exclude_{$excludeDate}" : "");
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }

        $conditions = 'draw_type = :type:';
        $bind = ['type' => 'XSMN'];
        
        if ($excludeDate) {
            $conditions .= ' AND draw_date != :exclude_date:';
            $bind['exclude_date'] = $excludeDate;
        }
        $uniqueDates = [];
        $latestResults = LotteryResults::find([
            'conditions' => $conditions,
            'bind' => $bind,
            'order' => 'draw_date DESC',
            'limit' => 30
        ]);

        if (empty($latestResults)) {
            return [];
        }

        foreach ($latestResults as $result) {
            $date = $result->draw_date;
            if (!in_array($date, $uniqueDates)) {
                $uniqueDates[] = $date;
                if (count($uniqueDates) >= 6) {
                    break;
                }
            }
        }

        if (empty($uniqueDates)) {
            return [];
        }

        $allResults = [];
        foreach ($uniqueDates as $latestDate) {
        
            $dow = (int)(new \DateTime($latestDate, $this->tz))->format('N');
            
            $provinces = Provinces::find([
                'conditions' => "region = 'XSMN' AND FIND_IN_SET(:dow:, draw_days)",
                'bind'       => ['dow' => (string)$dow],
                'order'      => 'id ASC',
            ]);

            $provincesForView = [];
            foreach ($provinces as $p) {
                $provincesForView[] = [
                    'id'    => (int)$p->id,
                    'name'  => (string)$p->name,
                    'code'  => (string)$p->code,
                    'keyid' => (string)$p->keyid,
                ];
            }

            $resultsForView = [];
            foreach ($provincesForView as $pv) {
                $res = LotteryResults::findFirst([
                    'conditions' => 'draw_type=:t: AND draw_date=:d: AND province_id=:pid:',
                    'bind'       => ['t' => 'XSMN', 'd' => $latestDate, 'pid' => $pv['id']],
                    'order'      => 'id DESC',
                ]);

                if (!$res) {
                    $resultsForView[$pv['id']] = [
                        'prizes' => null,
                        'dau'    => array_fill(0, 10, []),
                    ];
                    continue;
                }

                $prizes = [
                    1 => [$res->special_prize],
                    2 => [$res->first_prize],
                    3 => KqxsHelper::toArray($res->second_prize),
                    4 => KqxsHelper::toArray($res->third_prize),
                    5 => KqxsHelper::toArray($res->fourth_prize),
                    6 => KqxsHelper::toArray($res->fifth_prize),
                    7 => KqxsHelper::toArray($res->sixth_prize),
                    8 => KqxsHelper::toArray($res->seventh_prize),
                    9 => KqxsHelper::toArray($res->eighth_prize),
                ];

                $flat = [
                    'special_prize' => (string)$res->special_prize,
                    'first_prize'   => (string)$res->first_prize,
                    'second_prize'  => $prizes[3],
                    'third_prize'   => $prizes[4],
                    'fourth_prize'  => $prizes[5],
                    'fifth_prize'   => $prizes[6],
                    'sixth_prize'   => $prizes[7],
                    'seventh_prize' => $prizes[8],
                    'eighth_prize'  => $prizes[9],
                ];
                $two = KqxsHelper::collectAllTwoDigits($flat);
                $dauDuoi = KqxsHelper::buildDauDuoi($two);

                $resultsForView[$pv['id']] = [
                    'prizes' => $prizes,
                    'dau'    => $dauDuoi['dau'],
                ];
            }

            $allResults[] = [
                'provincesForView' => $provincesForView,
                'resultsForView' => $resultsForView,
                'draw_date' => $latestDate,
            ];
        }

            $this->cache->set($cacheKey, $allResults, 86000);
            
            return $allResults;
        } catch (\Exception $e) {
            error_log("Error in getCachedLatestResults XSMN: " . $e->getMessage());
            return [];
        }
    }

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

        return $predictions[0];
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

    private function buildPredictionUrl(string $slug, string $region): string
    {
        static $baseUrls = [
            'XSMN' => '/du-doan-',
            'XSMT' => '/du-doan-',
            'XSMB' => '/du-doan-',
        ];

        return ($baseUrls[$region] ?? $baseUrls['XSMB']) . $slug;
    }

    private function calculateCacheLifetime(\DateTime $date): int
    {
        $today = new \DateTime('today', $this->tz);

        if ($date >= $today) {
            return 86400;
        } elseif ($date >= (clone $today)->modify('-1 day')) {
            return 86400; 
        } else {
            return 86400; 
        }
    }
    public function listxsmnAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $seoData = SeoHelper::getSeoData('xsmn_list', null);

        $this->view->setVars($seoData);
        

        $this->view->setVar('page_schema_type', 'lottery_results');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get(),
            'region' => 'xsmn',
            'date' => date('d/m/Y'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Xổ số Miền Nam', 'url' => $this->url->get('/xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn')]
            ]
        ]);

        $this->view->pick('ketquaxsmn/listxsmn');
    }

    private function setViewStyles()
    {
        $this->view->customindex = '/css/indexheader.css';
    }
}