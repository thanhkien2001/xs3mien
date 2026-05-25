<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Models\Provinces;
use App\Models\LotteryResults;
use App\Models\PredictionArticles;
use App\Library\KqxsHelper;
use App\Library\ArchiveHelper;
use App\Library\PerformanceHelper;
use App\Library\SchemaHelper;
class ArchiveController extends ControllerBase
{
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
        
        // Tách path và query string
        $parts = parse_url($path);
        $pathOnly = $parts['path'] ?? $path;
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        
        // Thêm .html nếu chưa có và không phải root
        if ($pathOnly !== '/' && substr($pathOnly, -1) !== '/' && substr($pathOnly, -5) !== '.html') {
            $pathOnly .= '.html';
        }
        
        return $baseUrl . $pathOnly . $query;
    }
    
    /**
     * Slugify function - tạo slug ổn định cho cache key
     */
    private function slugify($s)
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9\-]/', '-', $s);
        $s = preg_replace('/-+/', '-', $s);
        return trim($s, '-');
    }
    // ==========  A) ARCHIVE THEO THỨ (của 1 miền)  ==========
    public function regionWeekdayAction($slug)
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->custompagination = '/css/pagination.css';

        $region = strtoupper($this->dispatcher->getParam('region', null, 'XSMT'));
        $map = [
            'thu-2' => 1,
            'thu-3' => 2,
            'thu-4' => 3,
            'thu-5' => 4,
            'thu-6' => 5,
            'thu-7' => 6,
            'chu-nhat' => 7,
        ];

        if (!isset($map[$slug])) {
            return ArchiveHelper::notFound($this->response, $this->view);
        }
        $dow = $map[$slug];

        // Kiểm tra nếu URL có nhiều hơn 1 .html (ví dụ: .html?page=2.html) → 404
        $uri = $this->request->getURI();
        if (substr_count($uri, '.html') > 1) {
            return ArchiveHelper::notFound($this->response, $this->view);
        }
        
        $page = max(1, (int)$this->request->getQuery('page', 'int', 1));
        
        // Redirect 301 từ ?page=1 về URL không có query để tránh duplicate content
        if ($page === 1 && $this->request->getQuery('page') !== null) {
            $baseUri = strtok($this->request->getURI(), '?');
            return $this->response->redirect($baseUri, true, 301);
        }
        $perPage = 6;

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "archive_{$region}_{$slug}_p{$page}";
        $cachedContent = $viewCache->get($viewCacheKey);
        
        if ($cachedContent !== null) {
            $this->response->setContent($cachedContent);
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = "{$region}_{$slug}_p{$page}";

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            // Đảm bảo regionCode có sẵn trong cached result
            if (!isset($cachedResult['regionCode'])) {
                $cachedResult['regionCode'] = $region;
            }
            $this->view->setVars($cachedResult);
            
            $regionName = $region === 'XSMT' ? 'Xổ số Miền Trung' : ($region === 'XSMN' ? 'Xổ số Miền Nam' : 'Xổ số Miền Bắc');
            $regionUrl = ArchiveHelper::getTodayHref($region);
            
            // Canonical URL: loại bỏ ?page=1, giữ lại ?page=N cho page > 1
            $baseUri = strtok($this->request->getURI(), '?');
            $canonicalPath = $baseUri;
            if (($cachedResult['page'] ?? 1) > 1) {
                $canonicalPath .= '?page=' . ($cachedResult['page'] ?? 1);
            }
            
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? "Lịch sử xổ số {$region} theo thứ",
                'description' => $cachedResult['seo_description'] ?? "Lịch sử kết quả xổ số {$region} theo thứ",
                'url' => $this->getAbsoluteUrl($canonicalPath),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => $regionName, 'url' => $this->getAbsoluteUrl($regionUrl)],
                    ['name' => $region, 'url' => $this->getAbsoluteUrl()]
                ]
            ]);
            $this->view->setVar('canonical_url', $this->getAbsoluteUrl($canonicalPath)); // Thêm canonical URL với domain
            $this->view->setVar('noindex', $cachedResult['noindex'] ?? false);
            
            $this->view->pick('archive/region_weekday');
            return;
        }

        $provinces = Provinces::find([
            'conditions' => 'region = :r: AND FIND_IN_SET(:dow:, draw_days)',
            'bind'       => ['r' => $region, 'dow' => (string)$dow],
            'order'      => 'id ASC'
        ]);

        $provincesForView = [];
        $provinceIds = [];
        foreach ($provinces as $p) {
            $provincesForView[] = [
                'id' => (int)$p->id,
                'name' => (string)$p->name,
                'code' => (string)$p->code,
                'keyid' => (string)$p->keyid,
            ];
            $provinceIds[] = (int)$p->id;
        }

        if (empty($provinceIds)) {
            $emptyResult = [
                'region'          => $region,
                'regionCode'      => $region, // Thêm regionCode cho SEO
                'weekdaySlug'     => $slug,
                'weekdayText'     => '',
                'sections'        => [],
                'todayHref'       => ArchiveHelper::getTodayHref($region),
                'page'            => $page,
                'prevUrl'         => null,
                'nextUrl'         => null,
            ];

            $this->view->setVars($emptyResult);
            
            $regionName = $region === 'XSMT' ? 'Xổ số Miền Trung' : ($region === 'XSMN' ? 'Xổ số Miền Nam' : 'Xổ số Miền Bắc');
            $regionUrl = ArchiveHelper::getTodayHref($region);
            
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => "Lịch sử xổ số {$region} theo thứ",
                'description' => "Lịch sử kết quả xổ số {$region} theo thứ",
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => $regionName, 'url' => $this->getAbsoluteUrl($regionUrl)],
                    ['name' => $region, 'url' => $this->getAbsoluteUrl()]
                ]
            ]);
            $this->view->setVar('noindex', $page > 1);
            
            $this->view->pick('archive/region_weekday');
            return;
        }

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $today = new \DateTime('today', $tz);
        $delta = ((int)$today->format('N') - $dow + 7) % 7;
        $anchor = (clone $today)->modify("-{$delta} day");

        $maxWeeksBack = 100;
        $startDate = (clone $anchor)->modify("-{$maxWeeksBack} week");
        $endDate = clone $anchor;

        // Đếm số ngày distinct có kết quả để tính totalPages
        $allDatesResult = LotteryResults::find([
            'conditions' => 'draw_type = :t: AND province_id IN ({ids:array}) AND draw_date BETWEEN :start: AND :end: AND DAYOFWEEK(draw_date) = :dow:',
            'bind' => [
                't' => $region,
                'ids' => $provinceIds,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'dow' => $dow == 7 ? 1 : $dow + 1
            ],
            'columns' => 'DISTINCT draw_date',
            'order' => 'draw_date DESC'
        ]);
        
        $totalDates = count($allDatesResult);
        $totalPages = max(1, (int)ceil($totalDates / $perPage));
        
        // Nếu page vượt quá totalPages, trả về 404
        if ($page > $totalPages) {
            return ArchiveHelper::notFound($this->response, $this->view);
        }

        if ($page > 1) {
            $weeksBack = ($page - 1) * $perPage;
            $anchor->modify("-{$weeksBack} week");
        }

        $availableDates = LotteryResults::find([
            'conditions' => 'draw_type = :t: AND province_id IN ({ids:array}) AND draw_date BETWEEN :start: AND :end: AND DAYOFWEEK(draw_date) = :dow:',
            'bind' => [
                't' => $region,
                'ids' => $provinceIds,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'dow' => $dow == 7 ? 1 : $dow + 1
            ],
            'columns' => 'DISTINCT draw_date',
            'order' => 'draw_date DESC',
            'limit' => $perPage
        ]);

        $dates = [];
        foreach ($availableDates as $result) {
            $dates[] = new \DateTime($result->draw_date, $tz);
            if (count($dates) >= $perPage) break;
        }

        if (empty($dates)) {
            [$thuText] = KqxsHelper::weekdayInfo($anchor);
            $emptyResult = [
                'region'          => $region,
                'regionCode'      => $region, // Thêm regionCode cho SEO
                'weekdaySlug'     => $slug,
                'weekdayText'     => $thuText,
                'sections'        => [],
                'todayHref'       => ArchiveHelper::getTodayHref($region),
                'page'            => $page,
                'prevUrl'         => $page > 1 ? ArchiveHelper::makePageUrl($this->request->getURI(), $page - 1) : null,
                'nextUrl'         => null,
            ];

            $this->view->setVars($emptyResult);
            $this->view->pick('archive/region_weekday');
            return;
        }

        $dateStrings = array_map(fn($date) => $date->format('Y-m-d'), $dates);

        $allResults = LotteryResults::find([
            'conditions' => 'draw_type = :t: AND draw_date IN ({dates:array}) AND province_id IN ({pids:array})',
            'bind' => [
                't' => $region,
                'dates' => $dateStrings,
                'pids' => $provinceIds
            ],
            'order' => 'draw_date DESC, province_id ASC'
        ]);

        $resultsByDateProvince = [];
        foreach ($allResults as $res) {
            $resultsByDateProvince[$res->draw_date][$res->province_id] = $res;
        }

        $sections = [];
        foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');
            $resultsForView = [];

            foreach ($provincesForView as $pv) {
                $res = $resultsByDateProvince[$dateStr][$pv['id']] ?? null;

                if (!$res) {
                    $resultsForView[$pv['id']] = [
                        'prizes' => null,
                        'dau'    => array_fill(0, 10, []),
                    ];
                    continue;
                }

                $prizes = ArchiveHelper::buildPrizesArray($res);
                $flat = ArchiveHelper::buildFlatPrizes($res, $prizes);
                $two = KqxsHelper::collectAllTwoDigits($flat);
                $dauDuoi = KqxsHelper::buildDauDuoi($two);

                $resultsForView[$pv['id']] = [
                    'prizes' => $prizes,
                    'dau'    => $dauDuoi['dau'],
                    'duoi'   => $dauDuoi['duoi'],
                    'lv'     => KqxsHelper::toArray($res->lv),
                ];
            }

            $sections[] = [
                'date'           => $date,
                'provinces'      => $provincesForView,
                'resultsForView' => $resultsForView,
            ];
        }

        [$thuText] = KqxsHelper::weekdayInfo($anchor);
        $todayHref = ArchiveHelper::getTodayHref($region);
        
        // Tạo base URL không có query params cho canonical
        $baseUri = strtok($this->request->getURI(), '?');

        $finalResult = [
            'region'          => $region,
            'regionCode'      => $region, // Thêm regionCode cho SEO
            'weekdaySlug'     => $slug,
            'weekdayText'     => $thuText,
            'sections'        => $sections,
            'todayHref'       => $todayHref,
            'page'            => $page,
            'totalPages'      => $totalPages, // Thêm totalPages để view sử dụng
            'prevUrl'         => $page > 1 ? ArchiveHelper::makePageUrl($baseUri, $page - 1) : null,
            'nextUrl'         => $page < $totalPages ? ArchiveHelper::makePageUrl($baseUri, $page + 1) : null,
        ];

        $seoData = PerformanceHelper::generateCachedSeoData('archive', $region, null, [
            'weekdaySlug' => $slug,
            'weekdayText' => $thuText,
            'sections' => $sections,
            'page' => $page
        ], $this->cache);
        
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ArchiveHelper::getSmartCacheLifetime($dates);
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);
        
        $regionName = $region === 'XSMT' ? 'Xổ số Miền Trung' : ($region === 'XSMN' ? 'Xổ số Miền Nam' : 'Xổ số Miền Bắc');
        $regionUrl = ArchiveHelper::getTodayHref($region);
        
        // Canonical URL: loại bỏ ?page=1, giữ lại ?page=N cho page > 1
        $canonicalPath = $baseUri;
        if ($page > 1) {
            $canonicalPath .= '?page=' . $page;
        }
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? "Lịch sử xổ số {$region} theo thứ",
            'description' => $finalResult['seo_description'] ?? "Lịch sử kết quả xổ số {$region} theo thứ",
            'url' => $this->getAbsoluteUrl($canonicalPath),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => $regionName, 'url' => $this->getAbsoluteUrl($regionUrl)],
                ['name' => $region, 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        $this->view->setVar('canonical_url', $this->getAbsoluteUrl($canonicalPath)); // Thêm canonical URL với domain
        $this->view->setVar('noindex', $finalResult['noindex'] ?? false);
        
        $this->view->pick('archive/region_weekday');
        
        ob_start();
        $this->view->render('archive', 'region_weekday');
        $content = ob_get_clean();
        
        $viewCache->set($viewCacheKey, $content, 86000);
        
        $this->response->setContent($content);
        $this->response->setHeader('X-Cache', 'MISS');
        $this->response->setHeader('X-Cache-Key', $viewCacheKey);
        return $this->response;
    }

    // ==========  B) ARCHIVE THEO TỈNH  ==========
    public function provinceAction($nameSlug, $alias)
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->custompagination = '/css/pagination.css';
        
        // Kiểm tra nếu URL có nhiều hơn 1 .html (ví dụ: .html?page=2.html) → 404
        $uri = $this->request->getURI();
        if (substr_count($uri, '.html') > 1) {
            return ArchiveHelper::notFound($this->response, $this->view);
        }
        
        $page = max(1, (int)$this->request->getQuery('page', 'int', 1));
        
        // Redirect 301 từ ?page=1 về URL không có query để tránh duplicate content
        if ($page === 1 && $this->request->getQuery('page') !== null) {
            $baseUri = strtok($this->request->getURI(), '?');
            return $this->response->redirect($baseUri, true, 301);
        }
        
        $perPage = 5;
        
        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "archive_province_" . $this->slugify($alias) . "_p{$page}";
        $cachedContent = $viewCache->get($viewCacheKey);
        
        if ($cachedContent !== null) {
            $this->response->setContent($cachedContent);
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }
        
        $modelsCache = $this->di->get('modelsCache');
        $provinceLookupKey = "province_lookup_" . $this->slugify($nameSlug) . "_" . $this->slugify($alias);
        $province = $modelsCache->get($provinceLookupKey);
        
        if ($province === null) {
            // Ưu tiên tìm bằng cả nameSlug và alias để tránh nhầm lẫn
            $province = ArchiveHelper::findProvinceByNameSlugAndAlias($nameSlug, $alias);
            
            // Nếu không tìm thấy, thử tìm bằng nameSlug hoặc alias (fallback)
            if (!$province) {
                $province = ArchiveHelper::findProvinceByAlias($alias) ?? ArchiveHelper::findProvinceByNameSlug($nameSlug);
            }
            
            if (!$province) {
                return ArchiveHelper::notFound($this->response, $this->view);
            }
            
            // Kiểm tra canonical URL - CHỈ chấp nhận các URL trong header
            $canonicalUrl = ArchiveHelper::getCanonicalProvinceUrl($province);
            if ($canonicalUrl === null) {
                // Tỉnh này không có trong danh sách canonical URLs → 404
                return ArchiveHelper::notFound($this->response, $this->view);
            }
            
            // So sánh URL hiện tại với canonical URL
            $currentPath = $this->request->getURI();
            $currentPath = strtok($currentPath, '?'); // Remove query params
            $canonicalPath = $canonicalUrl;
            
            // Nếu URL hiện tại KHÔNG phải canonical URL → 404
            // Ví dụ: /ket-qua-xo-so-binh-dinh-xsbd.html ≠ /ket-qua-xo-so-binh-dinh-xsbdinh.html → 404
            if ($currentPath !== $canonicalPath && rtrim($currentPath, '/') !== rtrim($canonicalPath, '/')) {
                return ArchiveHelper::notFound($this->response, $this->view);
            }
            
            $provinceData = [
                'id' => $province->id,
                'name' => $province->name,
                'code' => $province->code,
                'keyid' => $province->keyid,
                'region' => $province->region,
                'canonicalUrl' => $canonicalUrl
            ];
            $modelsCache->set($provinceLookupKey, $provinceData, 86400);
        } else {
            // Province từ cache có thể là array hoặc object
            // Cần convert về Provinces object để sử dụng sau
            if (is_array($province)) {
                $provinceObj = Provinces::findFirst(['conditions' => 'id = :id:', 'bind' => ['id' => $province['id']]]);
                if (!$provinceObj) {
                    return ArchiveHelper::notFound($this->response, $this->view);
                }
                $province = $provinceObj;
            } else {
                // Nếu là stdClass object từ cache, tìm lại từ DB
                if (!is_a($province, 'App\Models\Provinces')) {
                    $provinceObj = Provinces::findFirst(['conditions' => 'id = :id:', 'bind' => ['id' => $province->id]]);
                    if (!$provinceObj) {
                        return ArchiveHelper::notFound($this->response, $this->view);
                    }
                    $province = $provinceObj;
                }
            }
            
            // Kiểm tra lại canonical URL từ cache
            $canonicalUrl = ArchiveHelper::getCanonicalProvinceUrl($province);
            if ($canonicalUrl === null) {
                return ArchiveHelper::notFound($this->response, $this->view);
            }
            
            $currentPath = $this->request->getURI();
            $currentPath = strtok($currentPath, '?');
            if ($currentPath !== $canonicalUrl && rtrim($currentPath, '/') !== rtrim($canonicalUrl, '/')) {
                return ArchiveHelper::notFound($this->response, $this->view);
            }
        }
        
        $region = strtoupper($province->region);
        
        $cache = $this->di->get('modelsCache');
        $cacheKey = "{$province->region}_{$province->keyid}_p{$page}";
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            // Đảm bảo provinceCode và regionCode có sẵn trong cached result
            if (!isset($cachedResult['provinceCode'])) {
                $cachedResult['provinceCode'] = strtoupper($province->code);
            }
            if (!isset($cachedResult['regionCode'])) {
                $cachedResult['regionCode'] = $region;
            }
            $this->view->setVars($cachedResult);
            
            $regionName = $region === 'XSMT' ? 'Xổ số Miền Trung' : ($region === 'XSMN' ? 'Xổ số Miền Nam' : 'Xổ số Miền Bắc');
            $regionUrl = ArchiveHelper::getTodayHref($region);
            
            // Canonical URL: loại bỏ ?page=1, giữ lại ?page=N cho page > 1
            $canonicalPath = strtok($this->request->getURI(), '?');
            if ($cachedResult['page'] > 1) {
                $canonicalPath .= '?page=' . $cachedResult['page'];
            }
            
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? "Lịch sử xổ số {$province->name}",
                'description' => $cachedResult['seo_description'] ?? "Lịch sử kết quả xổ số {$province->name}",
                'url' => $this->getAbsoluteUrl($canonicalPath),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => $regionName, 'url' => $this->getAbsoluteUrl($regionUrl)],
                    ['name' => $province->name, 'url' => $this->getAbsoluteUrl()]
                ]
            ]);
            $this->view->setVar('canonical_url', $this->getAbsoluteUrl($canonicalPath)); // Thêm canonical URL với domain
            $this->view->setVar('noindex', $cachedResult['noindex'] ?? false);
            
            $this->view->pick('archive/province');
            return;
        }

        $offset = ($page - 1) * $perPage;

        // Tính tổng số records để tính totalPages chính xác
        $totalRecords = LotteryResults::count([
            'conditions' => 'province_id = :pid:',
            'bind'       => ['pid' => (int)$province->id],
        ]);
        
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        
        // Nếu page vượt quá totalPages, trả về 404
        if ($page > $totalPages) {
            return ArchiveHelper::notFound($this->response, $this->view);
        }

        $rows = LotteryResults::find([
            'conditions' => 'province_id = :pid:',
            'bind'       => ['pid' => (int)$province->id],
            'order'      => 'draw_date DESC, id DESC',
            'limit'      => $perPage,
            'offset'     => $offset,
        ]);

        $items = [];
        foreach ($rows as $res) {
            $prizes = ArchiveHelper::buildPrizesArray($res);
            $flat = ArchiveHelper::buildFlatPrizes($res, $prizes);
            $two = KqxsHelper::collectAllTwoDigits($flat);
            $dauDuoi = KqxsHelper::buildDauDuoi($two);

            $items[] = [
                'date'   => new \DateTime($res->draw_date),
                'prizes' => $prizes,
                'dau'    => $dauDuoi['dau'],
                'duoi'   => $dauDuoi['duoi'],
                'lv'     => KqxsHelper::toArray($res->lv),
            ];
        }

        $region = strtoupper($province->region);
        $todayHref = ArchiveHelper::getTodayHref($region);
        
        // Tạo base URL không có query params cho canonical
        $baseUri = strtok($this->request->getURI(), '?');

        $finalResult = [
            'province'     => $province,
            'provinceCode' => strtoupper($province->code), // Thêm provinceCode cho SEO
            'region'       => $region,
            'regionCode'   => $region, // Thêm regionCode cho SEO
            'items'        => $items,
            'todayHref'    => $todayHref,
            'page'         => $page,
            'totalPages'   => $totalPages, // Thêm totalPages để view sử dụng
            'prevUrl'      => $page > 1 ? ArchiveHelper::makePageUrl($baseUri, $page - 1) : null,
            'nextUrl'      => $page < $totalPages ? ArchiveHelper::makePageUrl($baseUri, $page + 1) : null,
        ];

        $seoData = PerformanceHelper::generateCachedSeoData('archive', $region, null, [
            'province' => $province,
            'items' => $items,
            'page' => $page
        ], $this->cache);
        
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ArchiveHelper::getSmartCacheLifetime($items, 'date');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);
        
        $regionName = $region === 'XSMT' ? 'Xổ số Miền Trung' : ($region === 'XSMN' ? 'Xổ số Miền Nam' : 'Xổ số Miền Bắc');
        $regionUrl = ArchiveHelper::getTodayHref($region);
        
        // Canonical URL: loại bỏ ?page=1, giữ lại ?page=N cho page > 1
        $canonicalPath = $baseUri;
        if ($page > 1) {
            $canonicalPath .= '?page=' . $page;
        }
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? "Lịch sử xổ số {$province->name}",
            'description' => $finalResult['seo_description'] ?? "Lịch sử kết quả xổ số {$province->name}",
            'url' => $this->getAbsoluteUrl($canonicalPath),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => $regionName, 'url' => $this->getAbsoluteUrl($regionUrl)],
                ['name' => $province->name, 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        $this->view->setVar('canonical_url', $this->getAbsoluteUrl($canonicalPath)); // Thêm canonical URL với domain
        $this->view->setVar('noindex', $finalResult['noindex'] ?? false);
        
        $this->view->pick('archive/province');
        
        $content = $this->view->getRender('archive', 'province', $this->view->getParamsToView());
        
        $viewCache->set($viewCacheKey, $content, 86000);
        
        $this->response->setContent($content);
        $this->response->setHeader('X-Cache', 'MISS');
        $this->response->setHeader('X-Cache-Key', $viewCacheKey);
        return $this->response;
    }
}
