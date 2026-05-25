<?php

declare(strict_types=1);

namespace App\Controllers;
use Phalcon\Mvc\Controller;
use App\Library\KqxsHelper;
use App\Models\LotteryResults;
use App\Models\Provinces;
use App\Models\PredictionArticles;
use App\Library\CacheHelper;
use App\Library\PerformanceHelper;
use App\Library\SeoHelper;
use App\Library\SchemaHelper;
class KetquaxsmtController extends ControllerBase
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

    public function shortXsmtAction()
    {
        $date = new \DateTime('today', $this->tz);
        return $this->forwardToDate($date, true);
    }

    public function byDateAction($d1, $m1, $d2, $m2, $y)
    {
        $uri = $this->request->getURI();
        if (preg_match('/\/xsmt-0[1-9]-|ngay-0[1-9]-/i', $uri)) {
            $this->response->setStatusCode(404, 'Not Found');
            $this->view->pick('errors/404');
            return;
        }

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
        
        // XSMT: 17h15-18h00
        $start = 0 * 60 + 15; // 17:15
        $end = 23 * 60 + 59; // 23:59
        
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
        $isBasePage = preg_match('/(\/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt\.html|\/xo-so-mien-trung-xsmt\.html|\/xo-so-mien-trung-xsmt)$/', $currentUri);
        if ($isShortUrl) $isBasePage = true;

        $cacheKey = CacheHelper::generateKey('XSMT_full_page', ['date' => $dateStr]);
        
        // Bypass cache nếu đang trong khung giờ quay số để live update hoạt động
        $cachedResult = null;
        if (!$this->isWithinLiveWindow()) {
            $cachedResult = $this->cache->get($cacheKey);
        }

        if ($cachedResult !== null) {
            if ($isBasePage) {
                $seoData = \App\Library\SeoHelper::getSeoData('lottery_results_base_xsmt');
            } else {
                $seoData = PerformanceHelper::generateCachedSeoData('lottery_result', 'XSMT', $dateStr, [
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
                    'title' => $cachedResult['seo_title'] ?? 'Kết quả XSMT - Xổ số Miền Trung',
                    'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Miền Trung hôm nay',
                    'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html'),
                    'breadcrumbs' => [
                        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                        ['name' => 'Kết quả XSMT', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html')]
                    ]
                ]);
            } else {
                // Breadcrumb for byDate page: thêm 1 cấp ngày
                $day = (int)$date->format('j');
                $month = (int)$date->format('n');
                $year = (int)$date->format('Y');
                $dateUrl = sprintf('xsmt-%d-%d-ket-qua-xo-so-mien-trung-ngay-%d-%d-%d.html', $day, $month, $day, $month, $year);
                
                $this->view->setVar('page_schema_data', [
                    'title' => $cachedResult['seo_title'] ?? 'Kết quả XSMT - Xổ số Miền Trung',
                    'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Miền Trung hôm nay',
                    'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                    'breadcrumbs' => [
                        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                        ['name' => 'Kết quả XSMT', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html')],
                        ['name' => 'XSMT ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                    ]
                ]);
            }
            
            $this->view->setVars($cachedResult);
            if ($isShortUrl) {
                $this->view->pick('ketquaxsmt/shorturl');
            } else {
                $this->view->pick('ketquaxsmt/index');
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
                'conditions' => "region = 'XSMT' AND FIND_IN_SET(:dow:, draw_days)",
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
            $resultsForProvinces = $this->getCachedXSMTResultsForDate($date);
        }
        $predictions = $this->getCachedPredictions($date);
        [$thuText, $thuSlug] = KqxsHelper::weekdayInfo($date);
        $otherLinks = $this->getCachedOtherLinks($date);
        $latestResults = $this->getCachedLatestResults($dateStr);

        $thuLink = "/xsmt-{$thuSlug}-ket-qua-xo-so-mien-trung.html";
        
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
            'todayHref'         => '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
            'latestResults'     => $latestResults,
            'isBasePage'        => false,
            'provinces'         => $this->convertProvincesToAssociativeArray($provinces),
        ];

        $finalResult['isBasePage'] = $isBasePage;

        $cacheLifetime = $this->calculateCacheLifetime($date);
        $this->cache->set($cacheKey, $finalResult, $cacheLifetime);

        if ($isBasePage) {
            $seoData = \App\Library\SeoHelper::getSeoData('lottery_results_base_xsmt');
        } else {
            $seoData = PerformanceHelper::generateCachedSeoData('lottery_result', 'XSMT', $dateStr, [
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
                'title' => $finalResult['seo_title'] ?? 'Kết quả XSMT - Xổ số Miền Trung',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Miền Trung hôm nay',
                'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html'),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Kết quả XSMT', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html')]
                ]
            ]);
        } else {
            // Breadcrumb for byDate page: thêm 1 cấp ngày
            $day = (int)$date->format('j');
            $month = (int)$date->format('n');
            $year = (int)$date->format('Y');
            $dateUrl = sprintf('xsmt-%d-%d-ket-qua-xo-so-mien-trung-ngay-%d-%d-%d.html', $day, $month, $day, $month, $year);
            
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? 'Kết quả XSMT - Xổ số Miền Trung',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Miền Trung hôm nay',
                'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Kết quả XSMT', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html')],
                    ['name' => 'XSMT ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                ]
            ]);
        }

        $this->view->setVars($finalResult);
        if ($isShortUrl) {
            $this->view->pick('ketquaxsmt/shorturl');
        } else {
            $this->view->pick('ketquaxsmt/index');
        }
    }

    private function getCachedXSMTResultsForDate(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $dow = (int)$date->format('N'); // 1..7
        $cacheKey = "XSMT_results_for_date_{$dateStr}";
        // $cached = $this->cache->get($cacheKey);

        // if ($cached !== null) {
        //     return $cached;
        // }

        $provinces = Provinces::find([
            'conditions' => "region = 'XSMT' AND FIND_IN_SET(:dow:, draw_days)",
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
                'bind'       => ['t' => 'XSMT', 'd' => $dateStr, 'pid' => $pv['id']],
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
        // $cacheKey = "XSMT_predictions_batch_{$target}";

        // $cached = $this->cache->get($cacheKey);
        // if ($cached !== null) {
        //     return $cached;
        // }

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

    // 🔥 OPTIMIZED: Cache other links
    private function getCachedOtherLinks(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        // $cacheKey = "XSMT_other_links_{$dateStr}";

        // $cached = $this->cache->get($cacheKey);
        // if ($cached !== null) {
        //     return $cached;
        // }

        $otherLinks = KqxsHelper::otherDaysLinksXSMT($date, 12);

        // Cache for 4 hours
        // $this->cache->set($cacheKey, $otherLinks, 14400);
        return $otherLinks;
    }

    // 🔥 NEW: Cache latest results for XSMT region - excluding current date
    private function getCachedLatestResults(?string $excludeDate = null): array
    {
        try {
            $cacheKey = "XSMT_latest_results_6" . ($excludeDate ? "_exclude_{$excludeDate}" : "");
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }

        $conditions = 'draw_type = :type:';
        $bind = ['type' => 'XSMT'];
        
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
                'conditions' => "region = 'XSMT' AND FIND_IN_SET(:dow:, draw_days)",
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
                    'bind'       => ['t' => 'XSMT', 'd' => $latestDate, 'pid' => $pv['id']],
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
            error_log("Error in getCachedLatestResults XSMT: " . $e->getMessage());
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
            return 86400; // 5 minutes for today/future
        } elseif ($date >= (clone $today)->modify('-1 day')) {
            return 86400; // 15 minutes for yesterday
        } else {
            return 86400; // 2 hours for older data
        }
    }
    public function listxsmtAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        // SEO data
        $seoData = SeoHelper::getSeoData('xsmt_list', null);

        $this->view->setVars($seoData);

        // Schema data for lottery results
        $this->view->setVar('page_schema_type', 'lottery_results');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get(),
            'region' => 'xsmt',
            'date' => date('d/m/Y'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Xổ số Miền Trung', 'url' => $this->url->get('/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt')]
            ]
        ]);

        $this->view->pick('ketquaxsmt/listxsmt');
    }
    private function setViewStyles()
    {
        $this->view->customindex = '/css/indexheader.css';
    }
}