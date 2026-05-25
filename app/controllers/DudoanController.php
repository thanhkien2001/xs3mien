<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Models\VietlottResults;
use App\Models\PredictionArticles;
use App\Models\LotteryResults;
use App\Models\Provinces;
use App\Library\DudoanHelper;
use App\Library\PerformanceHelper;
use App\Library\SchemaHelper;
use App\Library\SeoHelper;
use App\Library\KqxsHelper;
use App\Library\LotteryHelper;

class DudoanController extends ControllerBase
{
    public function duDoanXsmnWapAction()
    {
        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');

        $dateObj = new \DateTime();
        $this->view->setVar('currentDate', $dateObj->format('d/m/Y'));

        $dow = $dateObj->format('N');
        $dowMap = [1 => '2', 2 => '3', 3 => '4', 4 => '5', 5 => '6', 6 => '7', 7 => 'CN'];
        $this->view->setVar('currentDayOfWeek', $dowMap[$dow] ?? ($dow + 1));

        // Find the latest XSMN result
        $latestResult = LotteryResults::findFirst([
            'conditions' => 'draw_type = "XSMN"',
            'order' => 'draw_date DESC'
        ]);

        $result = [];
        if ($latestResult) {
            $result = $latestResult->toArray();

            // Parse prize strings (which are stored as JSON-like strings in DB)
            $prizes = ['special_prize', 'first_prize', 'second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'];
            foreach ($prizes as $prize) {
                if (isset($result[$prize]) && is_string($result[$prize])) {
                    // Clean up the string format ["123","456"] -> array
                    $clean = trim($result[$prize], "[]\"");
                    $clean = str_replace(['""', '"'], [',', ''], $clean);
                    $result[$prize] = array_filter(array_map('trim', explode(',', $clean)));
                }
            }

            // Format result date
            if (!empty($result['draw_date'])) {
                $rDate = strtotime($result['draw_date']);
                $result['date'] = date('d/m/Y', $rDate);
                $rDow = date('N', $rDate);
                $result['weekday'] = $dowMap[$rDow] ?? ($rDow + 1);
            }
        }

        $this->view->setVar('result', $result);
        $this->view->pick('dudoan/du-doan-xsmn-wap');
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

        // Thêm .html nếu chưa có và không phải root
        if ($path !== '/' && substr($path, -1) !== '/' && substr($path, -5) !== '.html') {
            $path .= '.html';
        }

        return $baseUrl . $path;
    }

    /**
     * Helper method to render and cache view
     */
    private function renderAndCacheView($viewCache, $viewCacheKey, $viewPath, $viewName)
    {
        $this->view->pick($viewPath);

        $this->view->start();
        $this->view->render('dudoan', $viewName);
        $html = $this->view->getContent();
        $this->view->finish();

        $viewCache->set($viewCacheKey, $html, 86000);

        $this->response->setContent($html);
        $this->response->setHeader('X-Cache', 'MISS');
        $this->response->setHeader('X-Cache-Key', $viewCacheKey);
        return $this->response;
    }


    /* ===========================
     *    ACTION: DETAIL BY SLUG
     * =========================== */
    public function detailBySlugAction($slug)
    {
        $this->view->customindex = '/css/indexheader.css';

        // Remove .html extension if present
        $slug = str_replace('.html', '', $slug);

        // ===== KIỂM TRA VIETLOTT PATTERNS =====
        // Check if this is a Vietlott Mega 6/45 prediction URL
        if (preg_match('/^soi-cau-xo-so-mega-6-45-vietlott-ngay-([0-9\-]+)-co-nen-xuong-tay$/', $slug, $matches)) {
            return $this->dispatcher->forward([
                'controller' => 'dudoan',
                'action' => 'dudoanMega645',
                'params' => [$matches[1]]
            ]);
        }

        // Check if this is a Vietlott Power 6/55 prediction URL
        if (preg_match('/^soi-cau-xo-so-power-6-55-vietlott-ngay-([0-9\-]+)-co-nen-xuong-tay$/', $slug, $matches)) {
            return $this->dispatcher->forward([
                'controller' => 'dudoan',
                'action' => 'dudoanPower655',
                'params' => [$matches[1]]
            ]);
        }

        // The slug from route pattern /du-doan-{slug} already has 'du-doan-' prefix
        // So for '/du-doan-xsmt-c60.html', $slug will be 'xsmt'
        // For '/du-doan-soi-cau-xo-so-mega...', $slug will be 'soi-cau-xo-so-mega...'
        // So we need to reconstruct the full slug
        $fullSlug = 'du-doan-' . $slug;

        // Check if this is a category page URL - forward to appropriate category action
        $categoryPages = [
            'du-doan-xsmb-c59' => 'categoryXsmb',
            'du-doan-xsmn-c61' => 'categoryXsmn',
            'du-doan-xsmt-c60' => 'categoryXsmt'
        ];

        if (isset($categoryPages[$fullSlug])) {
            return $this->dispatcher->forward([
                'controller' => 'dudoan',
                'action' => $categoryPages[$fullSlug]
            ]);
        }

        // Check if this is a listing page URL - forward to appropriate listing action
        $listingPages = [
            'du-doan-xsmb' => 'xsmbdudoan',
            'du-doan-xsmn' => 'xsmndudoan',
            'du-doan-xsmt' => 'xsmtdudoan',
            'du-doan-xo-so-soi-cau' => 'dudoansoicau'
        ];

        if (isset($listingPages[$fullSlug])) {
            return $this->dispatcher->forward([
                'controller' => 'dudoan',
                'action' => $listingPages[$fullSlug]
            ]);
        }

        // Check if this looks like a province code (e.g., xskt, xskh, xsdn, etc.)
        // Province codes typically start with 'xs' and are short (2-7 characters)
        // If the slug is short and starts with 'xs', it's likely a province code
        if (strlen($slug) <= 7 && strpos($slug, 'xs') === 0 && strpos($slug, '-') === false) {
            // This is likely a province code, return 404 to let provinceDudoan route handle it
            return $this->response->setStatusCode(404, 'Not Found');
        }

        // Try to find the prediction by slug (slug in DB already has 'du-doan-' prefix)
        $prediction = PredictionArticles::findFirst([
            'conditions' => 'slug = :slug:',
            'bind'       => ['slug' => $fullSlug]
        ]);

        if (!$prediction) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $region = $prediction->region;

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "detail_{$region}_{$fullSlug}";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'dudoan_detail', ['slug' => $fullSlug]);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            // Ensure previousResult is available in cached result
            if (!isset($cachedResult['previousResult'])) {
                $prevDateYmd = date('Y-m-d', strtotime($cachedResult['prediction']->prediction_date . ' -1 day'));
                $previousResult = null;

                // Check if this is a province-specific prediction
                if ($cachedResult['prediction']->province_id && $cachedResult['prediction']->province_id > 0) {
                    // This is a province-specific prediction - use findPrevWeekResult
                    [$usedPrevDate, $previousResult] = DudoanHelper::findPrevWeekResult($region, $cachedResult['prediction']->province_id, $cachedResult['prediction']->prediction_date);
                } else {
                    // This is a region-wide prediction
                    if ($region === 'XSMB') {
                        $previousResult = LotteryResults::findFirst([
                            'conditions' => 'draw_type = "XSMB" AND draw_date < :d:',
                            'bind'       => ['d' => $cachedResult['prediction']->prediction_date],
                            'order'      => 'draw_date DESC'
                        ]);
                    } elseif ($region === 'XSMN') {
                        $previousResult = LotteryResults::findFirst([
                            'conditions' => 'draw_type = "XSMN" AND draw_date < :d:',
                            'bind'       => ['d' => $cachedResult['prediction']->prediction_date],
                            'order'      => 'draw_date DESC'
                        ]);
                    } elseif ($region === 'XSMT') {
                        $previousResult = LotteryResults::findFirst([
                            'conditions' => 'draw_type = "XSMT" AND draw_date < :d:',
                            'bind'       => ['d' => $cachedResult['prediction']->prediction_date],
                            'order'      => 'draw_date DESC'
                        ]);
                    }
                }
                $cachedResult['previousResult'] = $previousResult;
            }

            // Ensure statistics variables are available for XSMB
            if ($region === 'XSMB' && !isset($cachedResult['chotSo'])) {
                $predYmd = date('Y-m-d', strtotime($cachedResult['prediction']->prediction_date));
                $dow = DudoanHelper::getDayOfWeek($predYmd);

                $province = Provinces::findFirst([
                    'conditions' => 'region = "XSMB" AND FIND_IN_SET(:day:, draw_days)',
                    'bind'       => ['day' => $dow]
                ]);

                if ($province) {
                    [$specialPrize10, $specialPrize30] = DudoanHelper::fetchSpecialPrizeSeries($region, $province->id, $predYmd);
                    [$frequentNumbers, $ganNumbers] = DudoanHelper::computeFreqAndGan($region, $province->id, $predYmd);

                    $cachedResult['specialPrize10'] = $specialPrize10;
                    $cachedResult['specialPrize30'] = $specialPrize30;
                    $cachedResult['frequentNumbers'] = $frequentNumbers;
                    $cachedResult['ganNumbers'] = $ganNumbers;
                    $cachedResult['chotSo'] = DudoanHelper::buildChotSo($cachedResult['previousResult'] ?? null, $frequentNumbers);
                    $cachedResult['soiCau'] = DudoanHelper::buildSoiCau($cachedResult['previousResult'] ?? null, $frequentNumbers, $ganNumbers);
                    $cachedResult['provinceName'] = $province->name;
                }
            }

            // Ensure perProvince is available for XSMT/XSMN
            if (($region === 'XSMT' || $region === 'XSMN') && !isset($cachedResult['perProvince'])) {
                $perProvince = [];

                // Check if this is a province-specific prediction
                if ($cachedResult['prediction']->province_id && $cachedResult['prediction']->province_id > 0) {
                    // This is a province-specific prediction - only show that province
                    $province = Provinces::findFirstById($cachedResult['prediction']->province_id);
                    if ($province) {
                        [$usedPrevDate, $provincePreviousResult] = DudoanHelper::findPrevWeekResult($region, $province->id, $cachedResult['prediction']->prediction_date);

                        $perProvince[$province->id] = [
                            'province' => $province,
                            'previousResult' => $provincePreviousResult
                        ];
                    }
                } else {
                    // This is a region-wide prediction - show all provinces
                    $provinces = Provinces::find([
                        'conditions' => 'region = :region:',
                        'bind' => ['region' => $region],
                        'order' => 'name ASC'
                    ]);

                    foreach ($provinces as $province) {
                        // Get previous result for each province using findPrevWeekResult
                        [$usedPrevDate, $provincePreviousResult] = DudoanHelper::findPrevWeekResult($region, $province->id, $cachedResult['prediction']->prediction_date);

                        $perProvince[$province->id] = [
                            'province' => $province,
                            'previousResult' => $provincePreviousResult
                        ];
                    }
                }
                $cachedResult['perProvince'] = $perProvince;
            }

            // Ensure resultDate is available in cached result
            if (!isset($cachedResult['resultDate'])) {
                $fullSlug = 'du-doan-' . $slug;
                $cachedResult['resultDate'] = $this->extractDateFromSlug($fullSlug);
                if (!$cachedResult['resultDate']) {
                    $cachedResult['resultDate'] = date('d-m-Y', strtotime($cachedResult['prediction']->prediction_date));
                }
            }

            $this->view->setVars($cachedResult);

            $this->view->setVar('page_schema_type', 'prediction');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Dự đoán ' . $region,
                'description' => $cachedResult['seo_description'] ?? 'Dự đoán kết quả xổ số',
                'url' => $this->url->get(),
                'content' => $cachedResult['prediction']->content ?? '',
                'author' => 'soicau247.com',
                'publishDate' => isset($cachedResult['prediction']->created_at) ? date('c', strtotime($cachedResult['prediction']->created_at)) : null,
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Dự đoán ' . $region, 'url' => $this->getAbsoluteUrl('/du-doan-' . strtolower($region))],
                    ['name' => $cachedResult['prediction']->title ?? 'Dự đoán', 'url' => $this->getAbsoluteUrl()]
                ]
            ]);

            $viewPath = $region === 'XSMB' ? "dudoan/detailxsmb" : "dudoan/detailxsmtmn";
            $viewName = $region === 'XSMB' ? "detailxsmb" : "detailxsmtmn";

            return $this->renderAndCacheView($viewCache, $viewCacheKey, $viewPath, $viewName);
        }

        $currentDate = date('d/m/Y', strtotime($prediction->prediction_date));
        $regionName  = DudoanHelper::getRegionName($region);
        $predYmd     = date('Y-m-d', strtotime($prediction->prediction_date));
        $dow         = DudoanHelper::getDayOfWeek($predYmd);

        // Extract actual date from slug for result links (format: dd-mm-yyyy)
        $resultDate = $this->extractDateFromSlug($fullSlug);
        if (!$resultDate) {
            $resultDate = date('d-m-Y', strtotime($prediction->prediction_date));
        }

        // Get previous result for statistics using the same logic as old actions
        $prevDateYmd = date('Y-m-d', strtotime($prediction->prediction_date . ' -1 day'));
        $previousResult = null;

        // Check if this is a province-specific prediction
        if ($prediction->province_id && $prediction->province_id > 0) {
            // This is a province-specific prediction - use findPrevWeekResult
            [$usedPrevDate, $previousResult] = DudoanHelper::findPrevWeekResult($region, $prediction->province_id, $predYmd);
        } else {
            // This is a region-wide prediction
            if ($region === 'XSMB') {
                $previousResult = LotteryResults::findFirst([
                    'conditions' => 'draw_type = "XSMB" AND draw_date < :d:',
                    'bind'       => ['d' => $prediction->prediction_date],
                    'order'      => 'draw_date DESC'
                ]);
            } elseif ($region === 'XSMN') {
                $previousResult = LotteryResults::findFirst([
                    'conditions' => 'draw_type = "XSMN" AND draw_date < :d:',
                    'bind'       => ['d' => $prediction->prediction_date],
                    'order'      => 'draw_date DESC'
                ]);
            } elseif ($region === 'XSMT') {
                $previousResult = LotteryResults::findFirst([
                    'conditions' => 'draw_type = "XSMT" AND draw_date < :d:',
                    'bind'       => ['d' => $prediction->prediction_date],
                    'order'      => 'draw_date DESC'
                ]);
            }
        }

        // Use the same logic as detailXsmtmnAction
        $isAggregate = DudoanHelper::isAggregateArticle($prediction);
        $perProvince = [];
        $provincesToday = [];
        $aggregateChotSo = null;
        $provinceName = null;

        // Initialize variables for all regions
        $specialPrize10 = [];
        $specialPrize30 = [];
        $frequentNumbers = [];
        $ganNumbers = [];
        $chotSo = [];
        $soiCau = [];

        if ($region === 'XSMB') {
            // Handle XSMB - find province and calculate statistics
            $province = Provinces::findFirst([
                'conditions' => 'region = "XSMB" AND FIND_IN_SET(:day:, draw_days)',
                'bind'       => ['day' => $dow]
            ]);

            if ($province) {
                $provinceName = $province->name;
                $provinceCacheKey = "{$region}_dudoan_detail_stats_{$province->id}_{$predYmd}";
                $statsData = $cache->get($provinceCacheKey);

                if ($statsData === null) {
                    [$specialPrize10, $specialPrize30Province] = DudoanHelper::fetchSpecialPrizeSeries($region, $province->id, $predYmd);
                    [$frequentNumbers, $ganNumbers] = DudoanHelper::computeFreqAndGan($region, $province->id, $predYmd);

                    $statsData = [
                        'specialPrize10' => $specialPrize10,
                        'frequentNumbers' => $frequentNumbers,
                        'ganNumbers' => $ganNumbers,
                    ];

                    $cache->set($provinceCacheKey, $statsData, 86000);
                } else {
                    $specialPrize10 = $statsData['specialPrize10'];
                    $frequentNumbers = $statsData['frequentNumbers'];
                    $ganNumbers = $statsData['ganNumbers'];
                }

                // Lấy tất cả kết quả XSMB trong 30 ngày gần nhất (không phân biệt tỉnh) - cache riêng
                $specialPrize30CacheKey = "{$region}_special_prize_30_all_{$predYmd}";
                $specialPrize30 = $cache->get($specialPrize30CacheKey);
                
                if ($specialPrize30 === null) {
                    $specialPrize30 = LotteryResults::find([
                        'conditions' => 'draw_type = :r: AND draw_date <= :d:',
                        'bind'       => ['r' => $region, 'd' => $predYmd],
                        'order'      => 'draw_date DESC',
                        'limit'      => 30
                    ]);
                    $cache->set($specialPrize30CacheKey, $specialPrize30, 86000);
                }

                $chotSo = DudoanHelper::buildChotSo($previousResult, $frequentNumbers);
                $soiCau = DudoanHelper::buildSoiCau($previousResult, $frequentNumbers, $ganNumbers);
            }
        } elseif ($region === 'XSMT' || $region === 'XSMN') {
            if ($isAggregate) {
                $provincesToday = DudoanHelper::getProvincesByRegionAndDow($region, $dow);

                $poolFreq = [];

                foreach ($provincesToday as $p) {
                    $provinceCacheKey = "{$region}_dudoan_detail_province_{$p->id}_{$predYmd}";
                    $provinceData = $cache->get($provinceCacheKey);

                    if ($provinceData === null) {
                        [$usedPrevDate, $previousResult] = DudoanHelper::findPrevWeekResult($region, (int)$p->id, $predYmd);

                        [$specialPrize10, $specialPrize30] = DudoanHelper::fetchSpecialPrizeSeries($region, (int)$p->id, $predYmd);
                        [$frequentNumbers, $ganNumbers]    = DudoanHelper::computeFreqAndGan($region, (int)$p->id, $predYmd);

                        $chotSo = DudoanHelper::buildChotSo($previousResult, $frequentNumbers);
                        $soiCau = DudoanHelper::buildSoiCau($previousResult, $frequentNumbers, $ganNumbers);

                        $provinceData = [
                            'usedPrevDate'    => $usedPrevDate,
                            'previousResult'  => $previousResult,
                            'specialPrize10'  => $specialPrize10,
                            'specialPrize30'  => $specialPrize30,
                            'frequentNumbers' => $frequentNumbers,
                            'ganNumbers'      => $ganNumbers,
                            'chotSo'          => $chotSo,
                            'soiCau'          => $soiCau,
                        ];

                        $cache->set($provinceCacheKey, $provinceData, 86000);
                    } else {
                        $usedPrevDate = $provinceData['usedPrevDate'];
                        $previousResult = $provinceData['previousResult'];
                        $specialPrize10 = $provinceData['specialPrize10'];
                        $specialPrize30 = $provinceData['specialPrize30'];
                        $frequentNumbers = $provinceData['frequentNumbers'];
                        $ganNumbers = $provinceData['ganNumbers'];
                        $chotSo = $provinceData['chotSo'];
                        $soiCau = $provinceData['soiCau'];
                    }

                    foreach ($frequentNumbers as $o) {
                        $poolFreq[$o->number] = ($poolFreq[$o->number] ?? 0) + $o->count;
                    }

                    $perProvince[$p->id] = [
                        'province'        => $p,
                        'usedPrevDate'    => $usedPrevDate,
                        'previousResult'  => $previousResult,
                        'specialPrize10'  => $specialPrize10,
                        'specialPrize30'  => $specialPrize30,
                        'frequentNumbers' => $frequentNumbers,
                        'ganNumbers'      => $ganNumbers,
                        'chotSo'          => $chotSo,
                        'soiCau'          => $soiCau,
                    ];
                }

                if (!empty($poolFreq)) {
                    arsort($poolFreq);
                    $top = array_slice($poolFreq, 0, 4, true);
                    $topKeys = array_keys($top);

                    // Tạo 3 càng từ giải ĐB các tỉnh hôm trước
                    $baCang = [];
                    foreach ($perProvince as $pData) {
                        if (!empty($pData['previousResult']) && !empty($pData['previousResult']->special_prize)) {
                            $de = substr($pData['previousResult']->special_prize, -2); // 2 số cuối GĐB
                            $digit1 = (int)substr($de, 0, 1);
                            $digit2 = (int)substr($de, 1, 1);
                            $tong = ($digit1 + $digit2) % 10; // Lấy số cuối của tổng

                            // Bóng dương: 1→6, 2→7, 3→8, 4→9, 5→0
                            $bongDuong = [1 => 6, 2 => 7, 3 => 8, 4 => 9, 5 => 0, 6 => 1, 7 => 2, 8 => 3, 9 => 4, 0 => 5];

                            // Ghép: Tổng + Đề
                            $baCang[] = $tong . $de;

                            // Chỉ lấy 3 số thôi
                            if (count($baCang) >= 3) break;
                        }
                    }

                    $aggregateChotSo = [
                        'bao_lo'   => implode(' - ', $topKeys),
                        'xien_2'   => implode(' - ', array_slice($topKeys, 0, 2)),
                        'ba_cang'  => implode(' - ', $baCang),
                    ];
                }
            } else {
                $province = null;

                if (!empty($prediction->province_id)) {
                    $province = Provinces::findFirstById((int)$prediction->province_id);
                }
                if (!$province) {
                    $province = DudoanHelper::findProvinceFromSlug($region, $slug);
                }
                if (!$province) {
                    $provinces = DudoanHelper::getProvincesByRegionAndDow($region, $dow);
                    $province = !empty($provinces) ? (object)$provinces[0] : null;
                }

                $provinceName = $province ? $province->name : 'Không xác định';

                $provinceCacheKey = "{$region}_dudoan_detail_province_{$province->id}_{$predYmd}";
                $provinceData = $cache->get($provinceCacheKey);

                if ($provinceData === null) {
                    [$usedPrevDate, $previousResult] = DudoanHelper::findPrevWeekResult($region, (int)$province->id, $predYmd);
                    [$specialPrize10, $specialPrize30] = DudoanHelper::fetchSpecialPrizeSeries($region, (int)$province->id, $predYmd);
                    [$frequentNumbers, $ganNumbers]    = DudoanHelper::computeFreqAndGan($region, (int)$province->id, $predYmd);
                    $chotSo = DudoanHelper::buildChotSo($previousResult, $frequentNumbers);
                    $soiCau = DudoanHelper::buildSoiCau($previousResult, $frequentNumbers, $ganNumbers);

                    $provinceData = [
                        'usedPrevDate'    => $usedPrevDate,
                        'previousResult'  => $previousResult,
                        'specialPrize10'  => $specialPrize10,
                        'specialPrize30'  => $specialPrize30,
                        'frequentNumbers' => $frequentNumbers,
                        'ganNumbers'      => $ganNumbers,
                        'chotSo'          => $chotSo,
                        'soiCau'          => $soiCau,
                    ];

                    $cache->set($provinceCacheKey, $provinceData, 86000);
                } else {
                    $usedPrevDate = $provinceData['usedPrevDate'];
                    $previousResult = $provinceData['previousResult'];
                    $specialPrize10 = $provinceData['specialPrize10'];
                    $specialPrize30 = $provinceData['specialPrize30'];
                    $frequentNumbers = $provinceData['frequentNumbers'];
                    $ganNumbers = $provinceData['ganNumbers'];
                    $chotSo = $provinceData['chotSo'];
                    $soiCau = $provinceData['soiCau'];
                }

                $perProvince[(int)$province->id] = [
                    'province'        => $province,
                    'usedPrevDate'    => $usedPrevDate,
                    'previousResult'  => $previousResult,
                    'specialPrize10'  => $specialPrize10,
                    'specialPrize30'  => $specialPrize30,
                    'frequentNumbers' => $frequentNumbers,
                    'ganNumbers'      => $ganNumbers,
                    'chotSo'          => $chotSo,
                    'soiCau'          => $soiCau,
                ];
            }
        }

        // Lấy bài dự đoán liên quan
        $relatedCacheKey = "{$region}_dudoan_detail_related_{$prediction->id}";
        $relatedPredictions = $cache->get($relatedCacheKey);
        if ($relatedPredictions === null) {
            // Đối với XSMB, lấy bài XSMT mới nhất không có province_id
            if ($region === 'XSMB') {
                // Lấy cả bài XSMT lẫn XSMN (không có province_id), giới hạn tổng là 3 bài mới nhất
                $relatedPredictions = PredictionArticles::find([
                    'conditions' => '(region = "XSMT" OR region = "XSMN") AND province_id IS NULL',
                    'order'      => 'prediction_date DESC, created_at DESC',
                    'limit'      => 2
                ]);
            } else {
                $relatedPredictions = DudoanHelper::getRelatedPredictions($prediction->id, $region, 3);
            }
            $cache->set($relatedCacheKey, $relatedPredictions, 100);
        }

        $finalResult = [
            'prediction' => $prediction,
            'region' => $region,
            'regionName' => $regionName,
            'currentDate' => $currentDate,
            'predYmd' => $predYmd,
            'dow' => $dow,
            'previousResult' => $previousResult,
            'isAggregate' => $isAggregate,
            'provincesToday' => $provincesToday,
            'perProvince' => $perProvince,
            'aggregateChotSo' => $aggregateChotSo,
            'provinceName' => $provinceName,
            'specialPrize10' => $specialPrize10,
            'specialPrize30' => $specialPrize30,
            'frequentNumbers' => $frequentNumbers,
            'ganNumbers' => $ganNumbers,
            'chotSo' => $chotSo,
            'soiCau' => $soiCau,
            'relatedPredictions' => $relatedPredictions,
            'resultDate' => $resultDate,
        ];

        // Generate SEO data using SeoHelper with custom fields (same as old actions)
        $seoData = PerformanceHelper::generateCachedSeoData('prediction', $region, $prediction->prediction_date, [
            'title' => $prediction->title,
            'description' => $prediction->description ?? '',
            'provinceName' => $provinceName,
            'publishedDate' => $prediction->created_at
        ], $cache);

        // Merge SEO data with final result
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = 3600; // 1 hour
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        $this->view->setVar('page_schema_type', 'prediction');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Dự đoán ' . $region,
            'description' => $finalResult['seo_description'] ?? 'Dự đoán kết quả xổ số',
            'url' => $this->url->get(),
            'content' => $finalResult['prediction']->content ?? '',
            'author' => 'soicau247.com',
            'publishDate' => isset($finalResult['prediction']->created_at) ? date('c', strtotime($finalResult['prediction']->created_at)) : null,
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán ' . $region, 'url' => $this->getAbsoluteUrl('/du-doan-' . strtolower($region))],
                ['name' => $finalResult['prediction']->title ?? 'Dự đoán', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $viewPath = $region === 'XSMB' ? "dudoan/detailxsmb" : "dudoan/detailxsmtmn";
        $viewName = $region === 'XSMB' ? "detailxsmb" : "detailxsmtmn";

        return $this->renderAndCacheView($viewCache, $viewCacheKey, $viewPath, $viewName);
    }

    /* ===========================
     *       ACTION: XSMB
     * =========================== */
    public function detailXsmbAction($slug)
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMB';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "detailxsmb_{$slug}";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'dudoan_detail', ['slug' => $slug]);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);

            $this->view->setVar('page_schema_type', 'prediction');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMB',
                'description' => $cachedResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Bắc',
                'url' => $this->url->get(),
                'content' => $cachedResult['prediction']->content ?? '',
                'author' => 'soicau247.com',
                'publishDate' => isset($cachedResult['prediction']->created_at) ? date('c', strtotime($cachedResult['prediction']->created_at)) : null,
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Dự đoán XSMB', 'url' => $this->getAbsoluteUrl('/du-doan-xsmb')],
                    ['name' => $cachedResult['prediction']->title ?? 'Dự đoán', 'url' => $this->getAbsoluteUrl()]
                ]
            ]);

            return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/detailxsmb", "detailxsmb");
        }

        $prediction = PredictionArticles::findFirst([
            'conditions' => 'slug = :slug: AND region = :region:',
            'bind'       => ['slug' => $slug, 'region' => $region]
        ]);
        if (!$prediction) return $this->response->setStatusCode(404, 'Not Found');

        $currentDate = date('d/m/Y', strtotime($prediction->prediction_date));
        $regionName  = DudoanHelper::getRegionName($region);

        $prevDateYmd = date('Y-m-d', strtotime($prediction->prediction_date . ' -1 day'));
        $dayOfWeek = DudoanHelper::getDayOfWeek($prediction->prediction_date);

        $province = Provinces::findFirst([
            'conditions' => 'region = "XSMB" AND FIND_IN_SET(:day:, draw_days)',
            'bind'       => ['day' => $dayOfWeek]
        ]);

        $provinceName = $province ? $province->name : 'Hà Nội';

        if ($province) {
            $previousResult = LotteryResults::findFirst([
                'conditions' => 'draw_type = "XSMB" AND province_id = :pid: AND draw_date = :d:',
                'bind'       => ['pid' => $province->id, 'd' => $prevDateYmd],
                'order'      => 'draw_date DESC'
            ]);
            if (!$previousResult) {
                $previousResult = LotteryResults::findFirst([
                    'conditions' => 'draw_type = "XSMB" AND province_id = :pid: AND draw_date < :d:',
                    'bind'       => ['pid' => $province->id, 'd' => $prediction->prediction_date],
                    'order'      => 'draw_date DESC'
                ]);
            }
        } else {
            $previousResult = LotteryResults::findFirst([
                'conditions' => 'draw_type = "XSMB" AND draw_date < :d:',
                'bind'       => ['d' => $prediction->prediction_date],
                'order'      => 'draw_date DESC'
            ]);
        }

        if ($province) {
            $statsCacheKey = "{$region}_dudoan_detail_stats_{$province->id}_{$prediction->prediction_date}";
            $statsData = $cache->get($statsCacheKey);

            if ($statsData === null) {
                [$specialPrize10, $specialPrize30] = DudoanHelper::fetchSpecialPrizeSeries($region, $province->id, $prediction->prediction_date);
                [$frequentNumbers, $ganNumbers]    = DudoanHelper::computeFreqAndGan($region, $province->id, $prediction->prediction_date);

                $statsData = [
                    'specialPrize10'  => $specialPrize10,
                    'specialPrize30'  => $specialPrize30,
                    'frequentNumbers' => $frequentNumbers,
                    'ganNumbers'      => $ganNumbers,
                ];

                $cache->set($statsCacheKey, $statsData, 86000);
            } else {
                $specialPrize10 = $statsData['specialPrize10'];
                $specialPrize30 = $statsData['specialPrize30'];
                $frequentNumbers = $statsData['frequentNumbers'];
                $ganNumbers = $statsData['ganNumbers'];
            }
        } else {
            $specialPrize10 = $specialPrize30 = $frequentNumbers = $ganNumbers = [];
        }

        $chotSo = DudoanHelper::buildChotSo($previousResult, $frequentNumbers);
        $soiCau = DudoanHelper::buildSoiCau($previousResult, $frequentNumbers, $ganNumbers);

        $relatedCacheKey = "{$region}_dudoan_detail_related_{$prediction->id}";
        $relatedPredictions = $cache->get($relatedCacheKey);
        if ($relatedPredictions === null) {
            $relatedPredictions = DudoanHelper::getRelatedPredictions($prediction->id, $region, 3);
            $cache->set($relatedCacheKey, $relatedPredictions, 86000);
        }

        $finalResult = [
            'prediction'        => $prediction,
            'currentDate'       => $currentDate,
            'regionName'        => $regionName,
            'provinceName'      => $provinceName,
            'previousResult'    => $previousResult,
            'specialPrize10'    => $specialPrize10,
            'specialPrize30'    => $specialPrize30,
            'frequentNumbers'   => $frequentNumbers,
            'ganNumbers'        => $ganNumbers,
            'chotSo'            => $chotSo,
            'soiCau'            => $soiCau,
            'relatedPredictions' => $relatedPredictions,
        ];

        $seoData = PerformanceHelper::generateCachedSeoData('prediction', $region, $prediction->prediction_date, [
            'title' => $prediction->title,
            'description' => $prediction->description,
            'provinceName' => $provinceName,
            'publishedDate' => $prediction->created_at
        ], $cache);

        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = DudoanHelper::getSmartCacheLifetime([$previousResult], 'draw_date');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        $this->view->setVar('page_schema_type', 'prediction');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Dự đoán XSMB',
            'description' => $finalResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Bắc',
            'url' => $this->url->get(),
            'content' => $finalResult['prediction']->content ?? '',
            'author' => 'soicau247.com',
            'publishDate' => isset($finalResult['prediction']->created_at) ? date('c', strtotime($finalResult['prediction']->created_at)) : null,
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán XSMB', 'url' => $this->getAbsoluteUrl('/du-doan-xsmb')],
                ['name' => $finalResult['prediction']->title ?? 'Dự đoán', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/detailxsmb", "detailxsmb");
    }

    /* ===========================
     *    ACTION: XSMT / XSMN
     * =========================== */
    public function detailXsmtmnAction($slug)
    {
        $this->view->customindex    = '/css/indexheader.css';

        $region = 'XSMN';

        $xsmtProvinces = [
            'quang-binh',
            'quang-nam',
            'quang-ngai',
            'quang-tri',
            'da-nang',
            'thua-thien-hue',
            'khanh-hoa',
            'binh-dinh',
            'phu-yen',
            'dak-lak',
            'dak-nong',
            'gia-lai',
            'kon-tum',
            'ninh-thuan',
            'xsmt'
        ];

        foreach ($xsmtProvinces as $province) {
            if (stripos($slug, $province) !== false) {
                $region = 'XSMT';
                break;
            }
        }

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "detail" . strtolower($region) . "_{$slug}";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'dudoan_detail', ['slug' => $slug]);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);
            return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/detailxsmtmn", "detailxsmtmn");
        }

        $prediction = PredictionArticles::findFirst([
            'conditions' => 'slug = :slug: AND region = :region:',
            'bind'       => ['slug' => $slug, 'region' => $region]
        ]);
        if (!$prediction) return $this->response->setStatusCode(404, 'Not Found');

        $currentDate = date('d/m/Y', strtotime($prediction->prediction_date));
        $regionName  = DudoanHelper::getRegionName($region);
        $predYmd     = date('Y-m-d', strtotime($prediction->prediction_date));
        $dow         = DudoanHelper::getDayOfWeek($predYmd);

        $isAggregate = DudoanHelper::isAggregateArticle($prediction);
        $perProvince = [];
        $provincesToday = [];
        $aggregateChotSo = null;
        $provinceName = null; // nếu là trang tỉnh riêng

        if ($isAggregate) {
            $provincesToday = DudoanHelper::getProvincesByRegionAndDow($region, $dow);

            $poolFreq = [];

            foreach ($provincesToday as $p) {
                $provinceCacheKey = "{$region}_dudoan_detail_province_{$p->id}_{$predYmd}";
                $provinceData = $cache->get($provinceCacheKey);

                if ($provinceData === null) {
                    [$usedPrevDate, $previousResult] = DudoanHelper::findPrevWeekResult($region, (int)$p->id, $predYmd);

                    [$specialPrize10, $specialPrize30] = DudoanHelper::fetchSpecialPrizeSeries($region, (int)$p->id, $predYmd);
                    [$frequentNumbers, $ganNumbers]    = DudoanHelper::computeFreqAndGan($region, (int)$p->id, $predYmd);

                    $chotSo = DudoanHelper::buildChotSo($previousResult, $frequentNumbers);
                    $soiCau = DudoanHelper::buildSoiCau($previousResult, $frequentNumbers, $ganNumbers);

                    $provinceData = [
                        'usedPrevDate'    => $usedPrevDate,
                        'previousResult'  => $previousResult,
                        'specialPrize10'  => $specialPrize10,
                        'specialPrize30'  => $specialPrize30,
                        'frequentNumbers' => $frequentNumbers,
                        'ganNumbers'      => $ganNumbers,
                        'chotSo'          => $chotSo,
                        'soiCau'          => $soiCau,
                    ];

                    $cache->set($provinceCacheKey, $provinceData, 86000);
                } else {
                    $usedPrevDate = $provinceData['usedPrevDate'];
                    $previousResult = $provinceData['previousResult'];
                    $specialPrize10 = $provinceData['specialPrize10'];
                    $specialPrize30 = $provinceData['specialPrize30'];
                    $frequentNumbers = $provinceData['frequentNumbers'];
                    $ganNumbers = $provinceData['ganNumbers'];
                    $chotSo = $provinceData['chotSo'];
                    $soiCau = $provinceData['soiCau'];
                }

                foreach ($frequentNumbers as $o) {
                    $poolFreq[$o->number] = ($poolFreq[$o->number] ?? 0) + $o->count;
                }

                $perProvince[$p->id] = [
                    'province'        => $p,
                    'usedPrevDate'    => $usedPrevDate,
                    'previousResult'  => $previousResult,
                    'specialPrize10'  => $specialPrize10,
                    'specialPrize30'  => $specialPrize30,
                    'frequentNumbers' => $frequentNumbers,
                    'ganNumbers'      => $ganNumbers,
                    'chotSo'          => $chotSo,
                    'soiCau'          => $soiCau,
                ];
            }

            if (!empty($poolFreq)) {
                arsort($poolFreq);
                $top = array_slice($poolFreq, 0, 4, true); // vd lấy 4 số "bao lô 4 đài"
                $topKeys = array_keys($top);

                // Tạo 3 càng từ giải ĐB các tỉnh hôm trước
                $baCang = [];
                foreach ($perProvince as $pData) {
                    if (!empty($pData['previousResult']) && !empty($pData['previousResult']->special_prize)) {
                        $de = substr($pData['previousResult']->special_prize, -2); // 2 số cuối GĐB
                        $digit1 = (int)substr($de, 0, 1);
                        $digit2 = (int)substr($de, 1, 1);
                        $tong = ($digit1 + $digit2) % 10; // Lấy số cuối của tổng

                        // Bóng dương: 1→6, 2→7, 3→8, 4→9, 5→0
                        $bongDuong = [1 => 6, 2 => 7, 3 => 8, 4 => 9, 5 => 0, 6 => 1, 7 => 2, 8 => 3, 9 => 4, 0 => 5];

                        // Ghép: Tổng + Đề
                        $baCang[] = $tong . $de;

                        // Chỉ lấy 3 số thôi
                        if (count($baCang) >= 3) break;
                    }
                }

                $aggregateChotSo = [
                    'bao_lo'   => implode(' - ', $topKeys),
                    'xien_2'   => implode(' - ', array_slice($topKeys, 0, 2)),
                    'ba_cang'  => implode(' - ', $baCang),
                ];
            }
        } else {
            $province = null;

            if (!empty($prediction->province_id)) {
                $province = Provinces::findFirstById((int)$prediction->province_id);
            }
            if (!$province) {
                $province = DudoanHelper::findProvinceFromSlug($region, $slug);
            }
            if (!$province) {
                $provinces = DudoanHelper::getProvincesByRegionAndDow($region, $dow);
                $province = !empty($provinces) ? (object)$provinces[0] : null;
            }

            $provinceName = $province ? $province->name : 'Không xác định';

            $provinceCacheKey = "{$region}_dudoan_detail_province_{$province->id}_{$predYmd}";
            $provinceData = $cache->get($provinceCacheKey);

            if ($provinceData === null) {
                [$usedPrevDate, $previousResult] = DudoanHelper::findPrevWeekResult($region, (int)$province->id, $predYmd);
                [$specialPrize10, $specialPrize30] = DudoanHelper::fetchSpecialPrizeSeries($region, (int)$province->id, $predYmd);
                [$frequentNumbers, $ganNumbers]    = DudoanHelper::computeFreqAndGan($region, (int)$province->id, $predYmd);
                $chotSo = DudoanHelper::buildChotSo($previousResult, $frequentNumbers);
                $soiCau = DudoanHelper::buildSoiCau($previousResult, $frequentNumbers, $ganNumbers);

                $provinceData = [
                    'usedPrevDate'    => $usedPrevDate,
                    'previousResult'  => $previousResult,
                    'specialPrize10'  => $specialPrize10,
                    'specialPrize30'  => $specialPrize30,
                    'frequentNumbers' => $frequentNumbers,
                    'ganNumbers'      => $ganNumbers,
                    'chotSo'          => $chotSo,
                    'soiCau'          => $soiCau,
                ];

                $cache->set($provinceCacheKey, $provinceData, 86000);
            } else {
                $usedPrevDate = $provinceData['usedPrevDate'];
                $previousResult = $provinceData['previousResult'];
                $specialPrize10 = $provinceData['specialPrize10'];
                $specialPrize30 = $provinceData['specialPrize30'];
                $frequentNumbers = $provinceData['frequentNumbers'];
                $ganNumbers = $provinceData['ganNumbers'];
                $chotSo = $provinceData['chotSo'];
                $soiCau = $provinceData['soiCau'];
            }

            $perProvince[(int)$province->id] = [
                'province'        => $province,
                'usedPrevDate'    => $usedPrevDate,
                'previousResult'  => $previousResult,
                'specialPrize10'  => $specialPrize10,
                'specialPrize30'  => $specialPrize30,
                'frequentNumbers' => $frequentNumbers,
                'ganNumbers'      => $ganNumbers,
                'chotSo'          => $chotSo,
                'soiCau'          => $soiCau,
            ];
        }

        $relatedCacheKey = "{$region}_dudoan_detail_related_{$prediction->id}";
        $relatedPredictions = $cache->get($relatedCacheKey);
        if ($relatedPredictions === null) {
            $relatedPredictions = DudoanHelper::getRelatedPredictions($prediction->id, $region, 3);
            $cache->set($relatedCacheKey, $relatedPredictions, 86000);
        }

        $finalResult = [
            'prediction'         => $prediction,
            'currentDate'        => $currentDate,
            'region'             => $region,
            'regionName'         => $regionName,
            'isAggregate'        => $isAggregate,
            'provincesToday'     => $provincesToday,
            'perProvince'        => $perProvince,
            'aggregateChotSo'    => $aggregateChotSo,
            'provinceName'       => $provinceName,
            'relatedPredictions' => $relatedPredictions,
        ];

        // Generate SEO data using SeoHelper with custom fields
        $seoData = PerformanceHelper::generateCachedSeoData('prediction', $region, $prediction->prediction_date, [
            'title' => $prediction->title,
            'description' => $prediction->description,
            'provinceName' => $provinceName,
            'publishedDate' => $prediction->created_at
        ], $cache);

        // Merge SEO data with final result
        $finalResult = array_merge($finalResult, $seoData);

        // Cache with smart lifetime
        $cacheLifetime = DudoanHelper::getSmartCacheLifetime([$prediction], 'prediction_date');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);
        return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/detailxsmtmn", "detailxsmtmn");
    }

    /**
     * Extract date from slug (format: dd-mm-yyyy)
     */
    private function extractDateFromSlug(string $slug): ?string
    {
        // Pattern: du-doan-xsmn-dd-mm-yyyy-soi-cau-xo-so-mien-nam-dd-mm-yyyy
        // Try to match date patterns in slug
        if (preg_match('/(\d{2}-\d{2}-\d{4})/', $slug, $matches)) {
            $date = $matches[1];
            // Validate date format
            $dateObj = \DateTime::createFromFormat('d-m-Y', $date);
            if ($dateObj && $dateObj->format('d-m-Y') === $date) {
                return $date;
            }
        }

        return null;
    }

    public function dudoansoicauAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "dudoansoicau_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = 'dudoan_soicau_list';

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $seoData = SeoHelper::generateDudoanSoiCauSeo();
            $cachedResult = array_merge($cachedResult, $seoData);

            $this->view->setVars($cachedResult);
            return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/dudoansoicau", "dudoansoicau");
        }

        $predictions = PredictionArticles::find([
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 10
        ]);

        $lastId = $predictions->count() > 0 ? $predictions->getLast()->id : 0;

        $finalResult = [
            'predictions' => $predictions,
            'lastId' => $lastId,
        ];

        $seoData = SeoHelper::generateDudoanSoiCauSeo();
        $finalResult = array_merge($finalResult, $seoData);

        $cache->set($cacheKey, $finalResult, 43000);
        $this->view->setVars($finalResult);
        return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/dudoansoicau", "dudoansoicau");
    }


    public function xsmbdudoanAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMB';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "xsmbdudoan_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'list10');

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMB',
                'description' => $cachedResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Bắc',
                'url' => $this->url->get(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                    ['name' => 'Dự đoán XSMB', 'url' => $this->getAbsoluteUrl()]
                ]
            ]);

            return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/xsmbdudoan", "xsmbdudoan");
        }

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region:',
            'bind' => ['region' => $region],
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 10
        ]);

        $finalResult = [
            'predictions' => $predictions,
            'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
        ];

        $seoData = PerformanceHelper::generateCachedSeoData('prediction_listing', $region, null, [
            'predictions' => $predictions,
            'region' => $region
        ], $cache);

        $finalResult = array_merge($finalResult, $seoData);

        $cache->set($cacheKey, $finalResult, 86000);

        $this->view->setVars($finalResult);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Dự đoán XSMB',
            'description' => $finalResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Bắc',
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                ['name' => 'Dự đoán XSMB', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/xsmbdudoan", "xsmbdudoan");
    }
    public function xsmndudoanAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMN';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "xsmndudoan_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'list10');

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMN',
                'description' => $cachedResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Nam',
                'url' => $this->url->get(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                    ['name' => 'Dự đoán XSMN', 'url' => $this->getAbsoluteUrl()]
                ]
            ]);

            return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/xsmndudoan", "xsmndudoan");
        }

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

        $seoData = PerformanceHelper::generateCachedSeoData('prediction_listing', $region, null, [
            'predictions' => $predictions,
            'region' => $region
        ], $cache);

        $finalResult = array_merge($finalResult, $seoData);

        $cache->set($cacheKey, $finalResult, 86000);

        $this->view->setVars($finalResult);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Dự đoán XSMN',
            'description' => $finalResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Nam',
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                ['name' => 'Dự đoán XSMN', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/xsmndudoan", "xsmndudoan");
    }
    public function xsmtdudoanAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMT';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "xsmtdudoan_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'list10');

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMT',
                'description' => $cachedResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Trung',
                'url' => $this->url->get(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                    ['name' => 'Dự đoán XSMT', 'url' => $this->getAbsoluteUrl()]
                ]
            ]);

            return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/xsmtdudoan", "xsmtdudoan");
        }

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

        $seoData = PerformanceHelper::generateCachedSeoData('prediction_listing', $region, null, [
            'predictions' => $predictions,
            'region' => $region
        ], $cache);

        $finalResult = array_merge($finalResult, $seoData);

        $cache->set($cacheKey, $finalResult, 86000);

        $this->view->setVars($finalResult);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Dự đoán XSMT',
            'description' => $finalResult['seo_description'] ?? 'Dự đoán kết quả xổ số Miền Trung',
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                ['name' => 'Dự đoán XSMT', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        return $this->renderAndCacheView($viewCache, $viewCacheKey, "dudoan/xsmtdudoan", "xsmtdudoan");
    }

    public function loadMoreAction()
    {
        $this->view->disable();

        $lastId = (int) $this->request->getPost('last_id', 'int', 0);
        $mien   = $this->request->getPost('mien', 'string');
        $provinceId = (int) $this->request->getPost('province_id', 'int', 0);

        $regionMap = ['11' => 'XSMB', '12' => 'XSMN', '13' => 'XSMT'];
        $region    = $regionMap[$mien] ?? 'XSMB';

        $conditions = 'region = :region:';
        $bind       = ['region' => $region];

        if ($provinceId > 0) {
            $conditions .= ' AND province_id = :province_id:';
            $bind['province_id'] = $provinceId;
        }

        if ($lastId > 0) {
            $conditions .= ' AND id < :last_id:';
            $bind['last_id'] = $lastId;
        }

        $predictions = PredictionArticles::find([
            'conditions' => $conditions,
            'bind'       => $bind,
            'order'      => 'prediction_date DESC, id DESC',
            'limit'      => 5
        ]);

        $data = [];
        foreach ($predictions as $pred) {
            $data[] = [
                'id'             => $pred->id,
                'title'          => $pred->title,
                'slug'           => $pred->slug,
                'content'        => mb_substr(strip_tags($pred->content), 0, 150),
                'image_url'      => $pred->image_url ?? '/img/soicaududoan/xsmb/dudoanxsmb.png',
                'prediction_date' => $pred->prediction_date,
            ];
        }

        $newLastId = $predictions->count() > 0
            ? $predictions[$predictions->count() - 1]->id
            : 0;

        $response = new \Phalcon\Http\Response();
        $response->setHeader('Content-Type', 'application/json; charset=utf-8');
        $response->setJsonContent([
            'status' => 'success',
            'data'   => $data,
            'lastId' => $newLastId
        ]);
        return $response;
    }

    public function loadMoreSoiCauAction()
    {
        $this->view->disable();

        $rawBody = $this->request->getRawBody();
        $jsonData = json_decode($rawBody, true);
        $lastId = isset($jsonData['last_id']) ? (int)$jsonData['last_id'] : 0;


        if ($lastId > 0) {
            $conditions = 'id < :last_id:';
            $bind = ['last_id' => $lastId];
        } else {
            $conditions = '1=0';
            $bind = [];
        }

        $predictions = PredictionArticles::find([
            'conditions' => $conditions,
            'bind'       => $bind,
            'order'      => 'prediction_date DESC, id DESC',
            'limit'      => 10
        ]);

        $data = [];
        foreach ($predictions as $pred) {
            $data[] = [
                'id'             => $pred->id,
                'title'          => $pred->title,
                'slug'           => $pred->slug,
                'region'         => $pred->region,
                'content'        => mb_substr(strip_tags($pred->content), 0, 150),
                'image_url'      => $pred->image_url ?? '/img/soicaududoan/xsmb/dudoanxsmb.png',
                'prediction_date' => $pred->prediction_date,
            ];
        }

        $newLastId = $predictions->count() > 0
            ? $predictions[$predictions->count() - 1]->id
            : 0;

        $response = new \Phalcon\Http\Response();
        $response->setHeader('Content-Type', 'application/json; charset=utf-8');
        $response->setJsonContent([
            'status' => 'success',
            'data'   => $data,
            'lastId' => $newLastId,
            'hasMore' => $predictions->count() === 10
        ]);
        return $response;
    }

    public function dudoanMega645Action(?string $date = null)
    {
        $this->view->customindex = '/css/indexheader.css';
        $today = new \DateTime('now', new \DateTimeZone('+0700'));
        $currentDayOfWeek = (int)$today->format('N');
        $daysToAdd = 0;

        if ($currentDayOfWeek == 1) {
            $daysToAdd = 2;
        } elseif ($currentDayOfWeek == 2) {
            $daysToAdd = 1;
        } elseif ($currentDayOfWeek == 3) {
            $daysToAdd = 0;
        } elseif ($currentDayOfWeek == 4) {
            $daysToAdd = 1;
        } elseif ($currentDayOfWeek == 5) {
            $daysToAdd = 0;
        } elseif ($currentDayOfWeek == 6) {
            $daysToAdd = 1;
        } elseif ($currentDayOfWeek == 7) {
            $daysToAdd = 0;
        }

        $nextDrawDate = clone $today;
        $nextDrawDate->modify("+$daysToAdd days");

        $latest = DudoanHelper::getLatestDraw645();
        if (!$latest) {
            $latest = new \stdClass();
            $latest->draw_number = 'N/A';
            $latest->draw_date = date('Y-m-d');
            $latest->numbers = '';
        }

        $this->view->latest = $latest;
        $this->view->analysis = DudoanHelper::analyzePatterns645();
        $this->view->frequentNumbers = DudoanHelper::getNumberFrequency645(20, 'DESC');
        $this->view->rareNumbers = DudoanHelper::getNumberFrequency645(20, 'ASC');
        $this->view->prediction = DudoanHelper::predictNumbers645();
        $this->view->currentDate = $today->format('d/m/Y');
        $this->view->nextDrawDate = $nextDrawDate->format('d/m/Y');
        $this->view->nextDrawUrl = $nextDrawDate->format('d-m-Y');
        $seoData = \App\Library\SeoHelper::generateDudoanMega645Seo($nextDrawDate->format('d-m-Y'));
        $this->view->seo_title = $seoData['seo_title'];
        $this->view->seo_description = $seoData['seo_description'];
        $this->view->seo_keywords = $seoData['seo_keywords'];
        $this->view->canonical_url = $seoData['canonical_url'];
    }



    public function dudoanPower655Action(?string $date = null)
    {
        $this->view->customindex = '/css/indexheader.css';
        $today = new \DateTime('now', new \DateTimeZone('+0700'));
        $currentDayOfWeek = (int)$today->format('N'); // 1 (Thứ Hai) đến 7 (Chủ Nhật)
        $daysToAdd = 0;

        if ($currentDayOfWeek == 1) {
            $daysToAdd = 1;
        } elseif ($currentDayOfWeek == 2) {
            $daysToAdd = 0;
        } elseif ($currentDayOfWeek == 3) {
            $daysToAdd = 1;
        } elseif ($currentDayOfWeek == 4) {
            $daysToAdd = 0;
        } elseif ($currentDayOfWeek == 5) {
            $daysToAdd = 1;
        } elseif ($currentDayOfWeek == 6) {
            $daysToAdd = 0;
        } elseif ($currentDayOfWeek == 7) {
            $daysToAdd = 2;
        }

        $nextDrawDate = clone $today;
        $nextDrawDate->modify("+$daysToAdd days");

        $latest = DudoanHelper::getLatestDraw655();
        if (!$latest) {
            $latest = new \stdClass();
            $latest->draw_number = 'N/A';
            $latest->draw_date = date('Y-m-d');
            $latest->numbers = '';
        }

        $this->view->latest = $latest;
        $this->view->analysis = DudoanHelper::analyzePatterns655();
        $this->view->frequentNumbers = DudoanHelper::getNumberFrequency655(20, 'DESC');
        $this->view->rareNumbers = DudoanHelper::getNumberFrequency655(20, 'ASC');
        $this->view->prediction = DudoanHelper::predictNumbers655();
        $this->view->currentDate = $today->format('d/m/Y');
        $this->view->nextDrawDate = $nextDrawDate->format('d/m/Y');
        $this->view->nextDrawUrl = $nextDrawDate->format('d-m-Y');

        $seoData = \App\Library\SeoHelper::generateDudoanPower655Seo($nextDrawDate->format('d-m-Y'));
        $this->view->seo_title = $seoData['seo_title'];
        $this->view->seo_description = $seoData['seo_description'];
        $this->view->seo_keywords = $seoData['seo_keywords'];
        $this->view->canonical_url = $seoData['canonical_url'];
    }

    public function testProvinceAction()
    {
        $provinceSlug = $this->dispatcher->getParam('province');
        $daySlug = $this->dispatcher->getParam('day');

        echo "<h1>Test Province Route với Database</h1>";
        echo "<p>Province Slug: " . htmlspecialchars($provinceSlug) . "</p>";
        echo "<p>Day Slug: " . htmlspecialchars($daySlug) . "</p>";
        echo "<p>URL: " . htmlspecialchars($this->request->getURI()) . "</p>";
        echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

        echo "<h3>Debug Parameters:</h3>";
        echo "<pre>";
        print_r($this->dispatcher->getParams());
        echo "</pre>";

        $provinceNameMap = [
            'xscm' => 'Cà Mau',
            'xshcm' => 'TP. Hồ Chí Minh',
            'xsdthap' => 'Đồng Tháp',
            'xsblieu' => 'Bạc Liêu',
            'xsbtr' => 'Bến Tre',
            'xsvt' => 'Vũng Tàu',
            'xsct' => 'Cần Thơ',
            'xsst' => 'Sóc Trăng',
            'xsdn' => 'Đồng Nai',
            'xsag' => 'An Giang',
            'xsbthuan' => 'Bình Thuận',
            'xstn' => 'Tây Ninh',
            'xsbduong' => 'Bình Dương',
            'xstv' => 'Trà Vinh',
            'xsvl' => 'Vĩnh Long',
            'xsbp' => 'Bình Phước',
            'xshg' => 'Hậu Giang',
            'xsla' => 'Long An',
            'xskg' => 'Kiên Giang',
            'xstg' => 'Tiền Giang',
            'xsdl' => 'Đà Lạt'
        ];

        $provinceName = $provinceNameMap[$provinceSlug] ?? 'Không xác định';
        echo "<p>Province Name: " . htmlspecialchars($provinceName) . "</p>";

        $province = Provinces::findFirst([
            'conditions' => 'name = :name: AND region = :region:',
            'bind' => ['name' => $provinceName, 'region' => 'XSMN']
        ]);

        if (!$province) {
            echo "<p style='color: red;'>❌ Không tìm thấy tỉnh trong database!</p>";
            echo "<h3>Danh sách tỉnh XSMN có trong DB:</h3>";
            $allProvinces = Provinces::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => 'XSMN']
            ]);
            echo "<ul>";
            foreach ($allProvinces as $p) {
                echo "<li>ID: {$p->id} - Name: {$p->name}</li>";
            }
            echo "</ul>";
            exit;
        }

        echo "<p style='color: green;'>✅ Tìm thấy tỉnh: ID={$province->id}, Name={$province->name}</p>";

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region: AND province_id = :province_id:',
            'bind' => ['region' => 'XSMN', 'province_id' => $province->id],
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 5
        ]);

        echo "<h3>Bài dự đoán cho tỉnh {$provinceName} ({$predictions->count()} bài):</h3>";

        if ($predictions->count() > 0) {
            echo "<ul>";
            foreach ($predictions as $pred) {
                echo "<li>";
                echo "<strong>ID:</strong> {$pred->id} | ";
                echo "<strong>Title:</strong> " . htmlspecialchars($pred->title) . " | ";
                echo "<strong>Date:</strong> {$pred->prediction_date} | ";
                echo "<strong>Slug:</strong> " . htmlspecialchars($pred->slug);
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Không có bài dự đoán nào cho tỉnh này</p>";
        }

        $testData = [
            'province_slug' => $provinceSlug,
            'province_name' => $provinceName,
            'province_id' => $province->id,
            'day_slug' => $daySlug,
            'predictions_count' => $predictions->count(),
            'url' => $this->request->getURI(),
            'timestamp' => time()
        ];

        echo "<h3>Test Data Summary:</h3>";
        echo "<pre>" . print_r($testData, true) . "</pre>";

        exit;
    }

    public function provinceDudoanAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $provinceSlug = $this->dispatcher->getParam('province');
        $daySlug = $this->dispatcher->getParam('day', null); // Allow null for new short format

        if (in_array($provinceSlug, ['xsmn', 'xsmb', 'xsmt'])) {
            switch ($provinceSlug) {
                case 'xsmn':
                    return $this->dispatcher->forward([
                        'controller' => 'dudoan',
                        'action' => 'xsmndudoan'
                    ]);
                case 'xsmb':
                    return $this->dispatcher->forward([
                        'controller' => 'dudoan',
                        'action' => 'xsmbdudoan'
                    ]);
                case 'xsmt':
                    return $this->dispatcher->forward([
                        'controller' => 'dudoan',
                        'action' => 'xsmtdudoan'
                    ]);
            }
        }

        $xsmnProvinceMap = [
            'xscm' => 'Cà Mau',
            'xshcm' => 'TP. Hồ Chí Minh',
            'xsdthap' => 'Đồng Tháp',
            'xsblieu' => 'Bạc Liêu',
            'xsbtr' => 'Bến Tre',
            'xsvt' => 'Vũng Tàu',
            'xsct' => 'Cần Thơ',
            'xsst' => 'Sóc Trăng',
            'xsdn' => 'Đồng Nai',
            'xsag' => 'An Giang',
            'xsbthuan' => 'Bình Thuận',
            'xstn' => 'Tây Ninh',
            'xsbduong' => 'Bình Dương',
            'xstv' => 'Trà Vinh',
            'xsvl' => 'Vĩnh Long',
            'xsbp' => 'Bình Phước',
            'xshg' => 'Hậu Giang',
            'xsla' => 'Long An',
            'xskg' => 'Kiên Giang',
            'xstg' => 'Tiền Giang',
            'xsdl' => 'Đà Lạt'
        ];

        $xsmtProvinceMap = [
            'xsh' => 'Thừa Thiên Huế',
            'xspy' => 'Phú Yên',
            'xsdlk' => 'Đắk Lắk',
            'xsqn' => 'Quảng Nam',
            'xsdng' => 'Đà Nẵng',
            'xskh' => 'Khánh Hòa',
            'xsbd' => 'Bình Định',
            'xsqb' => 'Quảng Bình',
            'xsqt' => 'Quảng Trị',
            'xsgl' => 'Gia Lai',
            'xsnt' => 'Ninh Thuận',
            'xsdnong' => 'Đắk Nông',
            'xsqng' => 'Quảng Ngãi',
            'xskt' => 'Kon Tum'
        ];

        $region = null;
        $provinceName = null;

        if (isset($xsmnProvinceMap[$provinceSlug])) {
            $region = 'XSMN';
            $provinceName = $xsmnProvinceMap[$provinceSlug];
        } elseif (isset($xsmtProvinceMap[$provinceSlug])) {
            $region = 'XSMT';
            $provinceName = $xsmtProvinceMap[$provinceSlug];
        }

        if (!$region || !$provinceName) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $province = Provinces::findFirst([
            'conditions' => 'name = :name: AND region = :region:',
            'bind' => ['name' => $provinceName, 'region' => $region]
        ]);

        if (!$province) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'province_list', ['province_id' => $province->id]);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $seoData = SeoHelper::generateProvincePredictionMeta($region, $provinceName);
            $cachedResult['seoData'] = $seoData;

            $this->view->setVar('seo_title', $seoData['seo_title']);
            $this->view->setVar('seo_description', $seoData['seo_description']);
            $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
            $this->view->setVar('canonical_url', $seoData['canonical_url']);

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $seoData['seo_title'] ?? 'Dự đoán ' . $region . ' ' . $provinceName,
                'description' => $seoData['seo_description'] ?? 'Dự đoán kết quả xổ số ' . ($region === 'XSMN' ? 'Miền Nam' : 'Miền Trung') . ' ' . $provinceName,
                'url' => $this->url->get(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                    ['name' => 'Dự đoán ' . $provinceName, 'url' => $this->getAbsoluteUrl()]
                ]
            ]);

            $this->view->setVars($cachedResult);
            $this->view->pick("dudoan/" . strtolower($region) . "province");
            return;
        }

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region: AND province_id = :province_id:',
            'bind' => ['region' => $region, 'province_id' => $province->id],
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 10
        ]);

        $seoData = SeoHelper::generateProvincePredictionMeta($region, $provinceName);

        $finalResult = [
            'predictions' => $predictions,
            'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
            'provinceName' => $provinceName,
            'provinceSlug' => $provinceSlug,
            'daySlug' => $daySlug ?? 'today',
            'province' => $province,
            'seoData' => $seoData
        ];

        $cache->set($cacheKey, $finalResult, 86000);

        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'] ?? 'Dự đoán ' . $region . ' ' . $provinceName,
            'description' => $seoData['seo_description'] ?? 'Dự đoán kết quả xổ số ' . ($region === 'XSMN' ? 'Miền Nam' : 'Miền Trung') . ' ' . $provinceName,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán xổ số', 'url' => $this->getAbsoluteUrl('/du-doan-xo-so-soi-cau')],
                ['name' => 'Dự đoán ' . $provinceName, 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->setVars($finalResult);
        $this->view->pick("dudoan/" . strtolower($region) . "province");
    }

    public function xsmtProvinceDudoanAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $provinceSlug = $this->dispatcher->getParam('province');
        $daySlug = $this->dispatcher->getParam('day');

        $provinceNameMap = [
            'xsh' => 'Thừa Thiên Huế',
            'xspy' => 'Phú Yên',
            'xsdlk' => 'Đắk Lắk',
            'xsqn' => 'Quảng Nam',
            'xsdng' => 'Đà Nẵng',
            'xskh' => 'Khánh Hòa',
            'xsbd' => 'Bình Định',
            'xsqb' => 'Quảng Bình',
            'xsqt' => 'Quảng Trị',
            'xsgl' => 'Gia Lai',
            'xsnt' => 'Ninh Thuận',
            'xsdnong' => 'Đắk Nông',
            'xsqng' => 'Quảng Ngãi',
            'xskt' => 'Kon Tum'
        ];

        $provinceName = $provinceNameMap[$provinceSlug] ?? 'Không xác định';

        $province = Provinces::findFirst([
            'conditions' => 'name = :name: AND region = :region:',
            'bind' => ['name' => $provinceName, 'region' => 'XSMT']
        ]);

        if (!$province) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $region = 'XSMT';
        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'province_list', ['province_id' => $province->id]);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $seoData = SeoHelper::generateProvincePredictionMeta($region, $provinceName);
            $cachedResult['seoData'] = $seoData;

            $this->view->setVar('seo_title', $seoData['seo_title']);
            $this->view->setVar('seo_description', $seoData['seo_description']);
            $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
            $this->view->setVar('canonical_url', $seoData['canonical_url']);
            $this->view->setVars($cachedResult);
            $this->view->pick("dudoan/xsmtprovince");
            return;
        }

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region: AND province_id = :province_id:',
            'bind' => ['region' => $region, 'province_id' => $province->id],
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 10
        ]);

        $finalResult = [
            'predictions' => $predictions,
            'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
            'provinceName' => $provinceName,
            'provinceSlug' => $provinceSlug,
            'daySlug' => $daySlug,
            'province' => $province
        ];

        $cache->set($cacheKey, $finalResult, 86000);

        $this->view->setVars($finalResult);
        $this->view->pick("dudoan/xsmtprovince");
    }

    public function xsmnProvinceDudoanActionReal()
    {
        $this->view->customindex = '/css/indexheader.css';

        $provinceSlug = $this->dispatcher->getParam('province');
        $daySlug = $this->dispatcher->getParam('day');

        $provinceNameMap = [
            'xscm' => 'Cà Mau',
            'xshcm' => 'TP. Hồ Chí Minh',
            'xsdthap' => 'Đồng Tháp',
            'xsblieu' => 'Bạc Liêu',
            'xsbtr' => 'Bến Tre',
            'xsvt' => 'Vũng Tàu',
            'xsct' => 'Cần Thơ',
            'xsst' => 'Sóc Trăng',
            'xsdn' => 'Đồng Nai',
            'xsag' => 'An Giang',
            'xsbthuan' => 'Bình Thuận',
            'xstn' => 'Tây Ninh',
            'xsbduong' => 'Bình Dương',
            'xstv' => 'Trà Vinh',
            'xsvl' => 'Vĩnh Long',
            'xsbp' => 'Bình Phước',
            'xshg' => 'Hậu Giang',
            'xsla' => 'Long An',
            'xskg' => 'Kiên Giang',
            'xstg' => 'Tiền Giang',
            'xsdl' => 'Đà Lạt'
        ];

        $provinceName = $provinceNameMap[$provinceSlug] ?? 'Không xác định';

        $province = Provinces::findFirst([
            'conditions' => 'name = :name: AND region = :region:',
            'bind' => ['name' => $provinceName, 'region' => 'XSMN']
        ]);

        if (!$province) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $region = 'XSMN';
        $cache = $this->di->get('modelsCache');
        $cacheKey = DudoanHelper::buildCacheKey($region, 'province_list', ['province_id' => $province->id]);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);
            $this->view->pick("dudoan/xsmnprovince");
            return;
        }

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region: AND province_id = :province_id:',
            'bind' => ['region' => $region, 'province_id' => $province->id],
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 10
        ]);

        $finalResult = [
            'predictions' => $predictions,
            'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
            'provinceName' => $provinceName,
            'provinceSlug' => $provinceSlug,
            'daySlug' => $daySlug,
            'province' => $province
        ];

        $cache->set($cacheKey, $finalResult, 86000);

        $this->view->setVars($finalResult);
        $this->view->pick("dudoan/xsmnprovince");
    }

    /**
     * Helper method to extract two digits from a lottery result row
     */
    private function twoDigitsFromRow($row): array
    {
        $all = [];
        $push = static function (?string $val) use (&$all) {
            if (!$val) return;
            if (!preg_match('/^\d+$/', $val)) return;
            $all[] = str_pad(substr($val, -2), 2, '0', STR_PAD_LEFT);
        };

        $push($row->special_prize);
        $push($row->first_prize);

        $safeDecode = function (?string $src): array {
            if ($src === null) return [];
            $s = trim((string)$src);
            if (empty($s)) return [];
            if (strpos($s, '[') === 0) {
                $decoded = json_decode($s, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_map('strval', $decoded);
                }
            }
            if (strpos($s, ',') !== false) {
                return array_map('trim', explode(',', $s));
            }
            return [$s];
        };

        foreach ($safeDecode($row->second_prize)  as $v) $push($v);
        foreach ($safeDecode($row->third_prize)   as $v) $push($v);
        foreach ($safeDecode($row->fourth_prize)  as $v) $push($v);
        foreach ($safeDecode($row->fifth_prize)   as $v) $push($v);
        foreach ($safeDecode($row->sixth_prize)   as $v) $push($v);
        foreach ($safeDecode($row->seventh_prize) as $v) $push($v);
        foreach ($safeDecode($row->eighth_prize)  as $v) $push($v);

        return $all;
    }

    /**
     * Get lô gan statistics for XSMB
     */
    private function getLoGanStatistics($region, $limit = 5): array
    {
        $historyRows = LotteryResults::find([
            'conditions' => 'draw_type = :r:',
            'bind'       => ['r' => $region],
            'order'      => 'draw_date ASC',
        ]);

        if (!$historyRows || count($historyRows) === 0) {
            return [];
        }

        $lastSeenIndex = array_fill_keys(range(0, 99), null);
        $lastSeenDate  = array_fill_keys(range(0, 99), null);
        $prevSeenIndex = array_fill_keys(range(0, 99), null);
        $maxGap        = array_fill_keys(range(0, 99), 0);

        $N = count($historyRows);
        foreach ($historyRows as $i => $row) {
            $seenToday = array_fill_keys(range(0, 99), false);

            foreach ($this->twoDigitsFromRow($row) as $nn) {
                $k = (int)$nn;
                if ($seenToday[$k]) continue;
                $seenToday[$k] = true;

                if ($prevSeenIndex[$k] !== null) {
                    $gap = $i - $prevSeenIndex[$k] - 1;
                    if ($gap > $maxGap[$k]) $maxGap[$k] = $gap;
                }
                $prevSeenIndex[$k] = $i;
                $lastSeenIndex[$k] = $i;
                $lastSeenDate[$k]  = $row->draw_date;
            }
        }

        $missNow = [];
        foreach (range(0, 99) as $k) {
            $missNow[$k] = ($lastSeenIndex[$k] === null) ? $N : (($N - 1) - $lastSeenIndex[$k]);
        }

        arsort($missNow);
        $out = [];
        foreach ($missNow as $k => $miss) {
            $out[] = [
                'number'        => str_pad((string)$k, 2, '0', STR_PAD_LEFT),
                'miss'          => $miss,
                'last_appeared' => $lastSeenDate[$k],
                'max_gap'       => $maxGap[$k],
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    /**
     * Get đặc biệt statistics for XSMB
     */
    private function getDacBietStatistics($region, $limit = 10): array
    {
        $rows = LotteryResults::find([
            'conditions' => 'draw_type = :r:',
            'bind'       => ['r' => $region],
            'order'      => 'draw_date DESC',
            'limit'      => 100
        ]);

        if (!$rows || count($rows) === 0) {
            return [
                'top_frequent' => [],
                'top_least' => [],
                'special_prize_last2' => []
            ];
        }

        // Count frequency of 2-digit numbers (00-99)
        $countMap = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);

        foreach ($rows as $row) {
            foreach ($this->twoDigitsFromRow($row) as $nn) {
                if (isset($countMap[$nn])) {
                    $countMap[$nn]++;
                }
            }
        }

        // Top 10 frequent
        $arrFreq = [];
        foreach ($countMap as $num => $freq) {
            $arrFreq[] = (object)['number' => (string)$num, 'count' => $freq];
        }
        usort($arrFreq, fn($a, $b) => $b->count <=> $a->count ?: strcmp((string)$a->number, (string)$b->number));
        $topFrequent = array_slice($arrFreq, 0, $limit);

        // Top 10 least
        $arrLeast = $arrFreq;
        usort($arrLeast, fn($a, $b) => $a->count <=> $b->count ?: strcmp((string)$a->number, (string)$b->number));
        $topLeast = array_slice($arrLeast, 0, $limit);

        // Calculate 2 last digits of special prize frequency
        $last2Count = [];
        foreach ($rows as $row) {
            if (!empty($row->special_prize)) {
                $last2 = substr(preg_replace('/\D+/', '', $row->special_prize), -2);
                if (strlen($last2) === 2) {
                    $last2Count[$last2] = ($last2Count[$last2] ?? 0) + 1;
                }
            }
        }
        arsort($last2Count);
        $specialPrizeLast2 = array_slice(array_keys($last2Count), 0, 4);

        return [
            'top_frequent' => $topFrequent,
            'top_least' => $topLeast,
            'special_prize_last2' => $specialPrizeLast2
        ];
    }

    /**
     * Get previous draw result for XSMN/XSMT
     */
    private function getPreviousDrawResult($region): ?array
    {
        $today = date('Y-m-d');
        $previousResult = LotteryResults::findFirst([
            'conditions' => 'draw_type = :region: AND draw_date < :today:',
            'bind'       => ['region' => $region, 'today' => $today],
            'order'      => 'draw_date DESC'
        ]);

        if (!$previousResult) {
            return null;
        }

        // Get all provinces for this region on the same date
        $allResults = LotteryResults::find([
            'conditions' => 'draw_type = :region: AND draw_date = :date:',
            'bind'       => ['region' => $region, 'date' => $previousResult->draw_date],
            'order'      => 'province_id ASC'
        ]);

        $formatted = [];
        foreach ($allResults as $result) {
            $province = Provinces::findFirstById($result->province_id);
            if ($province) {
                // Build dau/duoi data
                $twoDigits = $this->twoDigitsFromRow($result);
                $dauDuoi = KqxsHelper::buildDauDuoi($twoDigits);

                // Format prizes
                $prizes = [
                    'eighth_prize' => LotteryHelper::parsePrizeData($result->eighth_prize),
                    'seventh_prize' => LotteryHelper::parsePrizeData($result->seventh_prize),
                    'sixth_prize' => LotteryHelper::parsePrizeData($result->sixth_prize),
                    'fifth_prize' => LotteryHelper::parsePrizeData($result->fifth_prize),
                    'fourth_prize' => LotteryHelper::parsePrizeData($result->fourth_prize),
                    'third_prize' => LotteryHelper::parsePrizeData($result->third_prize),
                    'second_prize' => LotteryHelper::parsePrizeData($result->second_prize),
                    'first_prize' => LotteryHelper::parsePrizeData($result->first_prize),
                    'special_prize' => LotteryHelper::parsePrizeData($result->special_prize),
                ];

                $formatted[] = [
                    'province_id' => $result->province_id,
                    'province_name' => $province->name,
                    'province_code' => $province->code ?? substr($province->name, 0, 3),
                    'result' => $result,
                    'prizes' => $prizes,
                    'dauDuoi' => $dauDuoi
                ];
            }
        }

        return [
            'draw_date' => $previousResult->draw_date,
            'provinces' => $formatted
        ];
    }

    /**
     * Category page for XSMB with statistics
     */
    public function categoryXsmbAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMB';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "category_xsmb_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = 'category_xsmb_data';

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult === null) {
            // Get predictions (19 first articles)
            $predictions = PredictionArticles::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => $region],
                'order' => 'prediction_date DESC, id DESC',
                'limit' => 19
            ]);

            // Get lô gan statistics
            $loGanStats = $this->getLoGanStatistics($region, 5);

            // Get đặc biệt statistics
            $dacBietStats = $this->getDacBietStatistics($region, 10);

            $cachedResult = [
                'predictions' => $predictions,
                'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
                'loGanStats' => $loGanStats,
                'dacBietStats' => $dacBietStats,
            ];

            // Generate SEO data
            $seoData = PerformanceHelper::generateCachedSeoData('custom', 'du_doan_xsmb_c59', null, [
                'region' => $region
            ], $cache);
            $cachedResult = array_merge($cachedResult, $seoData);

            $cache->set($cacheKey, $cachedResult, 86000);
        }

        $this->view->setVars($cachedResult);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMB - Dự đoán MB chính xác nhất hôm nay',
            'description' => $cachedResult['seo_description'] ?? 'Dự đoán KQXSMB - Dự đoán xổ số miền Bắc được các chuyên gia tổng hợp và phân tích để đưa ra những cặp số MB đẹp nhất có tỉ lệ về cao nhất trong ngày hôm nay.',
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán XSMB', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->pick("dudoan/category_xsmb");

        // Render and cache view
        $this->view->start();
        $this->view->render('dudoan', 'category_xsmb');
        $html = $this->view->getContent();
        $this->view->finish();

        $viewCache->set($viewCacheKey, $html, 86000);
        $this->response->setContent($html);
        $this->response->setHeader('X-Cache', 'MISS');
        return $this->response;
    }

    /**
     * Category page for XSMN with previous draw result
     */
    public function categoryXsmnAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMN';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "category_xsmn_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = 'category_xsmn_data';

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult === null) {
            // Get predictions (19 first articles)
            $predictions = PredictionArticles::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => $region],
                'order' => 'prediction_date DESC, province_id ASC, id DESC',
                'limit' => 19
            ]);

            // Get previous draw result
            $previousDraw = $this->getPreviousDrawResult($region);

            $cachedResult = [
                'predictions' => $predictions,
                'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
                'previousDraw' => $previousDraw,
            ];

            // Generate SEO data
            $seoData = PerformanceHelper::generateCachedSeoData('custom', 'du_doan_xsmn_c61', null, [
                'region' => $region
            ], $cache);
            $cachedResult = array_merge($cachedResult, $seoData);

            $cache->set($cacheKey, $cachedResult, 86000);
        }

        $this->view->setVars($cachedResult);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMN - Dự đoán MN chính xác nhất hôm nay',
            'description' => $cachedResult['seo_description'] ?? 'Dự đoán KQXSMN - Dự đoán xổ số miền Nam đưa ra những con số đẹp nhất có tỉ lệ về cao trong ngày.',
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán XSMN', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->pick("dudoan/category_xsmn");

        // Render and cache view
        $this->view->start();
        $this->view->render('dudoan', 'category_xsmn');
        $html = $this->view->getContent();
        $this->view->finish();

        $viewCache->set($viewCacheKey, $html, 86000);
        $this->response->setContent($html);
        $this->response->setHeader('X-Cache', 'MISS');
        return $this->response;
    }

    /**
     * Category page for XSMT with previous draw result
     */
    public function categoryXsmtAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMT';

        $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "category_xsmt_list";
        $cachedHtml = $viewCache->get($viewCacheKey);

        if ($cachedHtml !== null) {
            $this->response->setContent($cachedHtml);
            $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            return $this->response;
        }

        $cache = $this->di->get('modelsCache');
        $cacheKey = 'category_xsmt_data';

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult === null) {
            // Get predictions (19 first articles)
            $predictions = PredictionArticles::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => $region],
                'order' => 'prediction_date DESC, province_id ASC, id DESC',
                'limit' => 19
            ]);

            // Get previous draw result
            $previousDraw = $this->getPreviousDrawResult($region);

            $cachedResult = [
                'predictions' => $predictions,
                'lastId' => $predictions->count() > 0 ? $predictions->getLast()->id : 0,
                'previousDraw' => $previousDraw,
            ];

            // Generate SEO data
            $seoData = PerformanceHelper::generateCachedSeoData('custom', 'du_doan_xsmt_c60', null, [
                'region' => $region
            ], $cache);
            $cachedResult = array_merge($cachedResult, $seoData);

            $cache->set($cacheKey, $cachedResult, 86000);
        }

        $this->view->setVars($cachedResult);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $cachedResult['seo_title'] ?? 'Dự đoán XSMT - Dự đoán MT chính xác nhất hôm nay',
            'description' => $cachedResult['seo_description'] ?? 'Dự đoán KQXSMT - Dự đoán xổ số miền Trung đưa ra những con số đẹp nhất có tỉ lệ về cao trong ngày.',
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Dự đoán XSMT', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->pick("dudoan/category_xsmt");

        // Render and cache view
        $this->view->start();
        $this->view->render('dudoan', 'category_xsmt');
        $html = $this->view->getContent();
        $this->view->finish();

        $viewCache->set($viewCacheKey, $html, 86000);
        $this->response->setContent($html);
        $this->response->setHeader('X-Cache', 'MISS');
        return $this->response;
    }

    /**
     * Pagination action for category XSMB page
     */
    public function categoryXsmbPageAction()
    {
        $this->view->disable();

        $page = (int)$this->dispatcher->getParam('page', null, 2);
        $page = max(2, $page); // Start from page 2
        $limit = 19;
        $offset = ($page - 1) * $limit;

        $region = 'XSMB';

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region:',
            'bind' => ['region' => $region],
            'order' => 'prediction_date DESC, id DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);

        if ($predictions->count() === 0) {
            $this->response->setStatusCode(404, 'Not Found');
            return $this->response;
        }

        $html = '';
        foreach ($predictions as $pred) {
            $html .= '<div class="row">';
            $html .= '<div class="col-12 mt-3 mb-2">';
            $html .= '<a class="text-blue1 font-16 font-weight-bold" href="/' . htmlspecialchars($pred->slug) . '.html" title="' . htmlspecialchars($pred->title) . '">';
            $html .= htmlspecialchars($pred->title);
            $html .= '</a></div>';
            $html .= '<div class="col-4 pr-2">';
            $html .= '<a href="/' . htmlspecialchars($pred->slug) . '.html" title="' . htmlspecialchars($pred->title) . '">';
            $html .= '<img loading="lazy" width="410" height="215" src="' . htmlspecialchars($pred->image_url ?? '/img/soicaududoan/xsmb/dudoanxsmb.png') . '" alt="' . htmlspecialchars($pred->title) . '">';
            $html .= '</a></div>';
            $html .= '<div class="col-8 pl-2">';
            $html .= '<p class="text-black1 font-14 lh-18px line-3 text-justify">';
            $html .= htmlspecialchars(mb_substr(strip_tags($pred->content), 0, 200)) . '...';
            $html .= '</p></div></div>';
        }

        $this->response->setContent($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $this->response;
    }

    /**
     * Pagination action for category XSMN page
     */
    public function categoryXsmnPageAction()
    {
        $this->view->disable();

        $page = (int)$this->dispatcher->getParam('page', null, 2);
        $page = max(2, $page);
        $limit = 19;
        $offset = ($page - 1) * $limit;

        $region = 'XSMN';

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region:',
            'bind' => ['region' => $region],
            'order' => 'prediction_date DESC, province_id ASC, id DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);

        if ($predictions->count() === 0) {
            $this->response->setStatusCode(404, 'Not Found');
            return $this->response;
        }

        $html = '';
        foreach ($predictions as $pred) {
            $html .= '<div class="row">';
            $html .= '<div class="col-12 mt-3 mb-2">';
            $html .= '<a class="text-blue1 font-16 font-weight-bold" href="/' . htmlspecialchars($pred->slug) . '.html" title="' . htmlspecialchars($pred->title) . '">';
            $html .= htmlspecialchars($pred->title);
            $html .= '</a></div>';
            $html .= '<div class="col-4 pr-2">';
            $html .= '<a href="/' . htmlspecialchars($pred->slug) . '.html" title="' . htmlspecialchars($pred->title) . '">';
            $html .= '<img loading="lazy" width="410" height="215" src="' . htmlspecialchars($pred->image_url ?? '/img/soicaududoan/xsmn/dudoanxsmn.png') . '" alt="' . htmlspecialchars($pred->title) . '">';
            $html .= '</a></div>';
            $html .= '<div class="col-8 pl-2">';
            $html .= '<p class="text-black1 font-14 lh-18px line-3 text-justify">';
            $html .= htmlspecialchars(mb_substr(strip_tags($pred->content), 0, 200)) . '...';
            $html .= '</p></div></div>';
        }

        $this->response->setContent($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $this->response;
    }

    /**
     * Pagination action for category XSMT page
     */
    public function categoryXsmtPageAction()
    {
        $this->view->disable();

        $page = (int)$this->dispatcher->getParam('page', null, 2);
        $page = max(2, $page);
        $limit = 19;
        $offset = ($page - 1) * $limit;

        $region = 'XSMT';

        $predictions = PredictionArticles::find([
            'conditions' => 'region = :region:',
            'bind' => ['region' => $region],
            'order' => 'prediction_date DESC, province_id ASC, id DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);

        if ($predictions->count() === 0) {
            $this->response->setStatusCode(404, 'Not Found');
            return $this->response;
        }

        $html = '';
        foreach ($predictions as $pred) {
            $html .= '<div class="row">';
            $html .= '<div class="col-12 mt-3 mb-2">';
            $html .= '<a class="text-blue1 font-16 font-weight-bold" href="/' . htmlspecialchars($pred->slug) . '.html" title="' . htmlspecialchars($pred->title) . '">';
            $html .= htmlspecialchars($pred->title);
            $html .= '</a></div>';
            $html .= '<div class="col-4 pr-2">';
            $html .= '<a href="/' . htmlspecialchars($pred->slug) . '.html" title="' . htmlspecialchars($pred->title) . '">';
            $html .= '<img loading="lazy" width="410" height="215" src="' . htmlspecialchars($pred->image_url ?? '/img/soicaududoan/xsmt/dudoanxsmt.png') . '" alt="' . htmlspecialchars($pred->title) . '">';
            $html .= '</a></div>';
            $html .= '<div class="col-8 pl-2">';
            $html .= '<p class="text-black1 font-14 lh-18px line-3 text-justify">';
            $html .= htmlspecialchars(mb_substr(strip_tags($pred->content), 0, 200)) . '...';
            $html .= '</p></div></div>';
        }

        $this->response->setContent($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $this->response;
    }
}
