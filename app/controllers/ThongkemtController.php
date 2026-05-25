<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Db\Adapter\Pdo\Mysql;
use App\Library\ThongkeStatisticsHelper;
use App\Library\PerformanceHelper;
use Exception;

class ThongkemtController extends ControllerBase
{
    /**
     * Helper method to get absolute URL for breadcrumb
     */
    private function getAbsoluteUrl($path = '')
    {
        $config = $this->getDI()->get('config');
        $baseUrl = $config->application->baseUrl;

        if (empty($path)) {
            $path = $this->request->getURI();
        }

        if ($path !== '/' && substr($path, -1) !== '/' && substr($path, -5) !== '.html') {
            $path .= '.html';
        }

        return $baseUrl . $path;
    }

    public function thongKeXsmtAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMT');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region'];
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        // Debug logs
        error_log("MT DEBUG thongKeXsmtAction - POST: " . json_encode($_POST));
        error_log("MT DEBUG thongKeXsmtAction - Slug: " . $this->dispatcher->getParam('slug'));
        error_log("MT DEBUG thongKeXsmtAction - ProvinceId: " . $provinceId);
        error_log("MT DEBUG thongKeXsmtAction - IsRegionMode: " . ($isRegionMode ? 'true' : 'false'));
        error_log("MT DEBUG thongKeXsmtAction - RawProvince: " . $rawProvince);
        error_log("MT DEBUG thongKeXsmtAction - Region: " . $region);

        $regionConst = $region; // Định nghĩa regionConst từ region

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // Cache key ổn định với version để force clear cache cũ
        $cacheKey = 'thongke_xsmt_v2_' . md5($rawProvince . '_' . $totalDay);
        error_log("MT DEBUG thongKeXsmtAction - CacheKey: " . $cacheKey);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemt/thongkexsmt';
        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Trung';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
                $breadcrumbs[] = ['name' => 'Thống kê ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Thống kê XSMT',
                'description' => $cached['seo_description'] ?? 'Thống kê xổ số Miền Trung',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        // Load provinces cho Miền Trung
        $mtProvinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $specialGrid = [];
        $loGan = [];
        $topMost = [];
        $asOfDate = null;

        if ($isRegionMode) {
            $dates = ThongkeStatisticsHelper::getRecentDatesByRegion($regionConst, $db, $totalDay);
            if ($dates) {
                $asOfDate    = $dates[0];
                $rowsForGrid = ThongkeStatisticsHelper::getResultsByDates($regionConst, $db, $dates, false);
                $specialGrid = ThongkeStatisticsHelper::groupByDateForGrid($rowsForGrid);

                $rowsAll   = ThongkeStatisticsHelper::getResultsByDates($regionConst, $db, $dates, true);
                $dailySets = ThongkeStatisticsHelper::buildDailyLast2SetsAllPrizes($rowsAll);
                $loGan     = ThongkeStatisticsHelper::computeGanByDays($dailySets, 10, true);

                $topMost   = ThongkeStatisticsHelper::computeTopMost($rowsForGrid, 10);
            }
        } else {
            $rowsForGrid = ThongkeStatisticsHelper::getRecentByProvince($provinceId, $db, $totalDay, false);
            if ($rowsForGrid) {
                $asOfDate    = $rowsForGrid[0]['draw_date'];
                $specialGrid = ThongkeStatisticsHelper::groupByDateForGridSingleProvince($rowsForGrid, $provinceId, $db);

                $rowsFull   = ThongkeStatisticsHelper::getRecentByProvince($provinceId, $db, $totalDay, true);
                $dailySets  = ThongkeStatisticsHelper::buildDailyLast2SetsAllPrizes($rowsFull);
                $loGan      = ThongkeStatisticsHelper::computeGanByDays($dailySets, 10, true);

                $topMost    = ThongkeStatisticsHelper::computeTopMost($rowsForGrid, 10);
            }
        }

        $selectedProvinceName = 'Miền Trung';
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($mtProvinces as $p) if ((int)$p['id'] === (int)$provinceId) {
                $selectedProvinceName = $p['name'];
                break;
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $mtProvinces,
            'selectedProvinceRaw'  => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'         => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'             => $totalDay,
            'asOfDate'             => $asOfDate,
            'specialGrid'          => $specialGrid,
            'loGan'                => $loGan,
            'topMost'              => $topMost,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'thongke'),
            'currentPage'          => 'thongke',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($regionConst, 'thongke', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($specialGrid, 'date');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
            $breadcrumbs[] = ['name' => 'Thống kê ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Thống kê XSMT',
            'description' => $finalResult['seo_description'] ?? 'Thống kê xổ số Miền Trung',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }

    public function loganxsmtAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMT');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region'];
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $regionConst = $region;

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // Cache key ổn định với version để force clear cache cũ
        $cacheKey = 'logan_xsmt_v3_' . md5($rawProvince . '_' . $totalDay);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemt/loganxsmt';
        if ($cached = $cache->get($cacheKey)) {
            // Ensure CSS is set even on cache hit
            $this->view->customindex    = '/css/indexheader.css';
            $this->view->customthongke1 = '/css/thongke/thongke1.css';

            $this->view->setVars($cached);
            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Trung';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Lô gan XSMT', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
                $breadcrumbs[] = ['name' => 'Lô gan ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Lô gan XSMT',
                'description' => $cached['seo_description'] ?? 'Lô gan xổ số Miền Trung',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        $mtProvinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $asOfDate = null;
        $loGanTop = [];
        $khanPairsTop = [];
        $headGanDigits = [];
        $tailGanDigits = [];
        $allTimeMaxGanList = [];

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($regionConst, $db, $totalDay);
            if ($dates) {
                $asOfDate = $dates[0];

                $rowsAll   = $this->getResultsByDates($regionConst, $db, $dates, true);
                $dailySets = $this->buildDailyLast2SetsAllPrizes($rowsAll);
                $loGanTop  = $this->computeGanByDays($dailySets, 10, true);

                $khanPairsTop = $this->computeKhanPairsABBA($dailySets, 10);

                $rowsSp = $this->getResultsByDates($regionConst, $db, $dates, false);
                [$headSets, $tailSets] = $this->buildDailyHeadTailSetsSpecialPrize($rowsSp);
                $headGanDigits = $this->computeGanDigitsByDays($headSets);
                $tailGanDigits = $this->computeGanDigitsByDays($tailSets);

                $allRowsAllTime   = $this->getAllResultsAllTime($db, $regionConst, null);
                $allDailySets     = $this->buildDailyLast2SetsAllPrizes($allRowsAllTime);
                $allTimeMaxGanList = $this->computeAllTimeGanStats($allDailySets, 20);
            }
        } else {
            $rowsFull = $this->getRecentByProvinceFull($provinceId, $db, $totalDay);
            if ($rowsFull) {
                $asOfDate = $rowsFull[0]['draw_date'];

                $dailySets   = $this->buildDailyLast2SetsAllPrizesSingleProvince($rowsFull);
                $loGanTop    = $this->computeGanByDays($dailySets, 10, true);
                $khanPairsTop = $this->computeKhanPairsABBA($dailySets, 10);

                $rowsSp = [];
                foreach ($rowsFull as $r) {
                    $rowsSp[] = ['draw_date' => $r['draw_date'], 'province_id' => $provinceId, 'province_name' => '', 'special_prize' => $r['special_prize']];
                }
                [$headSets, $tailSets] = $this->buildDailyHeadTailSetsSpecialPrize($rowsSp);
                $headGanDigits = $this->computeGanDigitsByDays($headSets);
                $tailGanDigits = $this->computeGanDigitsByDays($tailSets);

                $allRowsAllTime   = $this->getAllResultsAllTime($db, null, $provinceId);
                $allDailySets     = $this->buildDailyLast2SetsAllPrizesSingleProvince($allRowsAllTime);
                $allTimeMaxGanList = $this->computeAllTimeGanStats($allDailySets, 20);
            }
        }

        $selectedProvinceName = 'Miền Trung';
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($mtProvinces as $p) if ((int)$p['id'] === (int)$provinceId) {
                $selectedProvinceName = $p['name'];
                break;
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $mtProvinces,
            'selectedProvinceRaw'  => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'         => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'             => $totalDay,
            'asOfDate'             => $asOfDate,
            'loGanTop'             => $loGanTop,
            'khanPairsTop'         => $khanPairsTop,
            'headGanDigits'        => $headGanDigits,
            'tailGanDigits'        => $tailGanDigits,
            'allTimeMaxGanList'    => $allTimeMaxGanList,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'logan'),
            'currentPage'          => 'logan',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'logan', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($loGanTop, 'last_seen');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Lô gan XSMT', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
            $breadcrumbs[] = ['name' => 'Lô gan ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Lô gan XSMT',
            'description' => $finalResult['seo_description'] ?? 'Lô gan xổ số Miền Trung',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }


    public function dacBietXsmtAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMT');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region'];
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // Cache key ổn định với version để force clear cache cũ
        $cacheKey = 'dacbiet_xsmt_v2_' . md5($rawProvince . '_' . $totalDay);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemt/dacbietxsmt';
        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Trung';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Đặc biệt XSMT', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
                $breadcrumbs[] = ['name' => 'Đặc biệt ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Đặc biệt XSMT',
                'description' => $cached['seo_description'] ?? 'Thống kê giải đặc biệt xổ số Miền Trung',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        $mtProvinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $asOfDate = null;
        $recentPairsRows4 = [];
        $topLast2Rows4 = [];
        $headGanDigits = [];
        $tailGanDigits = [];
        $sameDayPastRows4 = [];

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($region, $db, $totalDay);
            if ($dates) {
                $asOfDate = $dates[0];
                $rows     = $this->getResultsByDates($region, $db, $dates);

                $recentPairs      = $this->flattenRecentSpecialPairs($rows);
                $recentPairsRows4 = $this->chunkPairsBy4($recentPairs);

                $topMost       = $this->computeTopMost($rows, 24);
                $topLast2Rows4 = $this->asPairsRows4FromTopMost($topMost);

                $dailyHeadSets = $this->buildDailySpecialDigitSets($rows, 'head');
                $dailyTailSets = $this->buildDailySpecialDigitSets($rows, 'tail');
                $headGanDigits = $this->computeDigitGanByDays($dailyHeadSets);
                $tailGanDigits = $this->computeDigitGanByDays($dailyTailSets);

                $sameDayPastRows4 = $this->sameDayPastMulti($region, null, $asOfDate, $db);
            }
        } else {
            $rows = $this->getRecentByProvince($provinceId, $db, $totalDay);
            if ($rows) {
                $asOfDate = $rows[0]['draw_date'];

                $recentPairs      = $this->flattenRecentSpecialPairsSingleProvince($rows);
                $recentPairsRows4 = $this->chunkPairsBy4($recentPairs);

                $topMost       = $this->computeTopMostSingleProvince($rows, 24);
                $topLast2Rows4 = $this->asPairsRows4FromTopMost($topMost);

                $dailyHeadSets = $this->buildDailySpecialDigitSetsSingleProvince($rows, 'head');
                $dailyTailSets = $this->buildDailySpecialDigitSetsSingleProvince($rows, 'tail');
                $headGanDigits = $this->computeDigitGanByDays($dailyHeadSets);
                $tailGanDigits = $this->computeDigitGanByDays($dailyTailSets);

                $sameDayPastRows4 = $this->sameDayPastMulti($region, $provinceId, $asOfDate, $db);
            }
        }

        $selectedProvinceName = 'Miền Trung';
        if (!$isRegionMode && $provinceId) {
            foreach ($mtProvinces as $p) if ((int)$p['id'] === (int)$provinceId) {
                $selectedProvinceName = $p['name'];
                break;
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $mtProvinces,
            'selectedProvinceRaw'  => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'         => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'             => $totalDay,
            'asOfDate'             => $asOfDate,
            'recentPairsRows4'     => $recentPairsRows4,
            'topLast2Rows4'        => $topLast2Rows4,
            'headGanDigits'        => $headGanDigits,
            'tailGanDigits'        => $tailGanDigits,
            'sameDayPastRows4'     => $sameDayPastRows4,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'dacbiet'),
            'currentPage'          => 'dacbiet',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'dacbiet', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($recentPairsRows4);
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Đặc biệt XSMT', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
            $breadcrumbs[] = ['name' => 'Đặc biệt ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Đặc biệt XSMT',
            'description' => $finalResult['seo_description'] ?? 'Thống kê giải đặc biệt xổ số Miền Trung',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }


    public function dauDuoiXsmtAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMT');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region'];
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // Cache key ổn định với version để force clear cache cũ
        $cacheKey = 'dauduoi_xsmt_v2_' . md5($rawProvince . '_' . $totalDay);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemt/dauduoixsmt';
        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Trung';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Đầu đuôi XSMT', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
                $breadcrumbs[] = ['name' => 'Đầu đuôi ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Đầu đuôi XSMT',
                'description' => $cached['seo_description'] ?? 'Thống kê đầu đuôi xổ số Miền Trung',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        $mtProvinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $asOfDate = null;
        $headGridByDate = [];
        $tailGridByDate = [];
        $sumGridByDate  = [];
        $specialHeadCounts = [];
        $specialTailCounts = [];

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($region, $db, $totalDay);
            if (!empty($dates)) {
                $asOfDate = $dates[0];
                $rows = $this->getResultsByDatesAllPrizes($region, $db, $dates);
                $byDateAllNums = $this->buildAllLast2ByDate($rows, true);
                [$headGridByDate, $tailGridByDate, $sumGridByDate] = $this->buildHeadTailSumGrids($byDateAllNums);
                [$specialHeadCounts, $specialTailCounts] = $this->buildSpecialHeadTailCounts($rows);
            }
        } else {
            $rows = $this->getRecentByProvinceAllPrizes($provinceId, $db, $totalDay);
            if (!empty($rows)) {
                $asOfDate = $rows[0]['draw_date'];
                $byDateAllNums = $this->buildAllLast2ByDate($rows, false);
                [$headGridByDate, $tailGridByDate, $sumGridByDate] = $this->buildHeadTailSumGrids($byDateAllNums);
                [$specialHeadCounts, $specialTailCounts] = $this->buildSpecialHeadTailCounts($rows);
            }
        }

        $selectedProvinceName = 'Miền Trung';
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($mtProvinces as $p) if ((int)$p['id'] === (int)$provinceId) {
                $selectedProvinceName = $p['name'];
                break;
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'     => "REGION:{$region}",
            'provinceOptions'       => $mtProvinces,
            'selectedProvinceRaw'   => $rawProvince,
            'selectedProvinceName'  => $selectedProvinceName,
            'selectedProvinceId'    => $provinceId,
            'selectedProvinceCode'  => $provinceCode,
            'isRegionMode'          => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'              => $totalDay,
            'asOfDate'              => $asOfDate,
            'headGridByDate'        => $headGridByDate,
            'tailGridByDate'        => $tailGridByDate,
            'sumGridByDate'         => $sumGridByDate,
            'specialHeadCounts'     => $specialHeadCounts,
            'specialTailCounts'     => $specialTailCounts,
            'statisticsMenu'        => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'dauduoi'),
            'currentPage'           => 'dauduoi',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'dauduoi', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($headGridByDate, 'date_vn');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Đầu đuôi XSMT', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
            $breadcrumbs[] = ['name' => 'Đầu đuôi ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Đầu đuôi XSMT',
            'description' => $finalResult['seo_description'] ?? 'Thống kê đầu đuôi xổ số Miền Trung',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }

    public function tanSuatXsmtAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMT');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region'];
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // Cache key ổn định với version để force clear cache cũ
        $cacheKey = 'tansuat_xsmt_v2_' . md5($rawProvince . '_' . $totalDay);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemt/tansuatxsmt';
        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Trung';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Tần suất XSMT', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
                $breadcrumbs[] = ['name' => 'Tần suất ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Tần suất XSMT',
                'description' => $cached['seo_description'] ?? 'Thống kê tần suất xổ số Miền Trung',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        $mtProvinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $asOfDate = null;
        $dateHeaders = [];
        $numbers = array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99));
        $matrix  = [];
        $totals  = [];

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($region, $db, $totalDay);
            if ($dates) {
                $asOfDate   = $dates[0];
                $rows       = $this->getAllPrizeResultsByDates($region, $db, $dates);
                $dailySetsAsc = $this->buildDailyAllPrizeLast2Sets($rows);
                $dateHeaders  = $this->normalizeDateHeaders($dates);
                [$matrix, $totals] = $this->computeFrequencyMatrix($dailySetsAsc, $dateHeaders, $numbers);
            }
        } else {
            $rows = $this->getAllPrizeRecentByProvince($provinceId, $db, $totalDay);
            if ($rows) {
                $asOfDate = $rows[0]['draw_date'];
                $dates    = array_values(array_unique(array_column($rows, 'draw_date')));
                rsort($dates);
                $dateHeaders = $this->normalizeDateHeaders($dates);
                $dailySetsAsc = $this->buildDailyAllPrizeLast2Sets($rows);
                [$matrix, $totals] = $this->computeFrequencyMatrix($dailySetsAsc, $dateHeaders, $numbers);
            }
        }

        $selectedProvinceName = 'Miền Trung';
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($mtProvinces as $p) if ((int)$p['id'] === (int)$provinceId) {
                $selectedProvinceName = $p['name'];
                break;
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $mtProvinces,
            'selectedProvinceRaw'  => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'         => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'             => $totalDay,
            'asOfDate'             => $asOfDate,
            'numbers'              => $numbers,
            'dateHeaders'          => $dateHeaders,
            'matrix'               => $matrix,
            'totals'               => $totals,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'tansuat'),
            'currentPage'          => 'tansuat',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'tansuat', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($dateHeaders, 'date');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Tần suất XSMT', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Thống kê XSMT', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmt')];
            $breadcrumbs[] = ['name' => 'Tần suất ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Tần suất XSMT',
            'description' => $finalResult['seo_description'] ?? 'Thống kê tần suất xổ số Miền Trung',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }

    private function getAllPrizeResultsByDates(string $region, \Phalcon\Db\Adapter\Pdo\Mysql $db, array $dates): array
    {
        if (empty($dates)) return [];
        $placeholders = implode(',', array_fill(0, count($dates), '?'));
        $sql = "SELECT lr.draw_date, lr.province_id, p.name AS province_name,
                   lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                   lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize
            FROM lottery_results lr
            JOIN provinces p ON p.id = lr.province_id
            WHERE lr.draw_type = ? AND lr.draw_date IN ($placeholders)
            ORDER BY lr.draw_date DESC, p.name ASC";
        $stmt = $db->prepare($sql);
        $bind = array_merge([$region], $dates);
        $stmt->execute($bind);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getAllPrizeRecentByProvince(int $provinceId, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit): array
    {
        $sql = "SELECT lr.draw_date, lr.province_id,
                   lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                   lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize
            FROM lottery_results lr
            WHERE lr.province_id = :pid
            ORDER BY lr.draw_date DESC
            LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function normalizeDateHeaders(array $datesDesc): array
    {
        $tmp = $datesDesc;
        rsort($tmp);
        $out = [];
        foreach ($tmp as $d) {
            $out[] = ['date' => $d, 'date_vn' => date('d/m/Y', strtotime($d))];
        }
        return $out;
    }

    private function buildDailyAllPrizeLast2Sets(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($byDate[$d])) $byDate[$d] = [];
            foreach ($this->extractAllLast2FromRow($r) as $l2) {
                $byDate[$d][] = $l2;
            }
        }
        ksort($byDate);
        return $byDate;
    }

    private function extractAllLast2FromRow(array $r): array
    {
        $cols = [
            'special_prize',
            'first_prize',
            'second_prize',
            'third_prize',
            'fourth_prize',
            'fifth_prize',
            'sixth_prize',
            'seventh_prize',
            'eighth_prize'
        ];
        $vals = [];
        foreach ($cols as $c) {
            if (!isset($r[$c]) || $r[$c] === null || $r[$c] === '') continue;
            $vals = array_merge($vals, $this->explodePrizeField($r[$c]));
        }
        $out = [];
        foreach ($vals as $v) {
            $digits = preg_replace('/\D+/', '', (string)$v);
            if ($digits === '') continue;
            $out[] = substr(str_pad($digits, 2, '0', STR_PAD_LEFT), -2);
        }
        return $out;
    }

    private function explodePrizeField(string $raw): array
    {
        $raw = trim($raw);
        $j = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->flattenArray($j);
        }
        if (strpos($raw, ',') !== false) {
            return array_map('trim', explode(',', $raw));
        }
        return preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function flattenArray($v): array
    {
        $out = [];
        $it = function ($x) use (&$out, &$it) {
            if (is_array($x)) {
                foreach ($x as $y) $it($y);
            } else $out[] = (string)$x;
        };
        $it($v);
        return $out;
    }

    private function computeFrequencyMatrix(array $dailySetsAsc, array $dateHeadersDesc, array $numbers): array
    {
        // Map index cột: date => idx theo DESC
        $dateIndex = [];
        foreach ($dateHeadersDesc as $i => $dh) $dateIndex[$dh['date']] = $i;

        // Khởi tạo
        $cols = count($dateHeadersDesc);
        $matrix = [];
        $totals = array_fill_keys($numbers, 0);

        foreach ($numbers as $num) {
            $matrix[$num] = [
                'num'   => $num,
                'cells' => array_fill(0, $cols, 0),
            ];
        }

        foreach ($dailySetsAsc as $date => $arr) {
            if (!isset($dateIndex[$date])) continue;
            $col = $dateIndex[$date];
            foreach ($arr as $l2) {
                if (!isset($matrix[$l2])) continue;
                $matrix[$l2]['cells'][$col] += 1;
                $totals[$l2] += 1;
            }
        }

        $rows = [];
        foreach ($numbers as $num) $rows[] = $matrix[$num];

        return [$rows, $totals];
    }


    private function getResultsByDatesAllPrizes(string $region, \Phalcon\Db\Adapter\Pdo\Mysql $db, array $dates): array
    {
        if (empty($dates)) return [];
        $placeholders = implode(',', array_fill(0, count($dates), '?'));

        $sql = "SELECT lr.draw_date, lr.province_id,
                   lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                   lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize
            FROM lottery_results lr
            WHERE lr.draw_type = ? AND lr.draw_date IN ($placeholders)
            ORDER BY lr.draw_date DESC, lr.province_id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge([$region], $dates));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getRecentByProvinceAllPrizes(int $provinceId, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date,
                   special_prize, first_prize, second_prize, third_prize,
                   fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE province_id = :pid
            ORDER BY draw_date DESC
            LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function parsePrizeField($val): array
    {
        if ($val === null) return [];
        $s = trim((string)$val);
        if ($s === '') return [];
        if ($s[0] === '[') {
            $arr = json_decode($s, true);
            if (is_array($arr)) {
                return array_values(array_filter(array_map('strval', $arr), fn($x) => $x !== ''));
            }
        }
        preg_match_all('/\d+/', $s, $m);
        return $m[0] ?? [];
    }

    private function last2Strict(string $num): string
    {
        $digits = preg_replace('/\D+/', '', $num);
        if ($digits === '') return '00';
        return substr(str_pad($digits, 2, '0', STR_PAD_LEFT), -2);
    }

    private function buildAllLast2ByDate(array $rows, bool $isRegionMode): array
    {
        $tmp = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($tmp[$d])) $tmp[$d] = ['all_nums' => [], 'date_vn' => $this->vnDate($d)];
            $nums = $this->extractAllLast2FromRow($r);
            $tmp[$d]['all_nums'] = array_merge($tmp[$d]['all_nums'], $nums);
        }
        uksort($tmp, fn($a, $b) => strcmp($b, $a));
        return $tmp;
    }
    private function buildHeadTailSumGrids(array $byDateAllNums): array
    {
        $headGrid = [];
        $tailGrid = [];
        $sumGrid  = [];

        foreach ($byDateAllNums as $date => $obj) {
            $nums = $obj['all_nums'];
            $head = array_fill(0, 10, 0);
            $tail = array_fill(0, 10, 0);
            $sum  = array_fill(0, 10, 0);

            foreach ($nums as $l2) {
                $l2 = substr(str_pad($l2, 2, '0', STR_PAD_LEFT), -2);
                $h  = (int)$l2[0];
                $t  = (int)$l2[1];
                $s  = ($h + $t) % 10;

                $head[$h]++;
                $tail[$t]++;
                $sum[$s]++;
            }

            $headGrid[] = ['date_vn' => $obj['date_vn'], 'counts' => $head];
            $tailGrid[] = ['date_vn' => $obj['date_vn'], 'counts' => $tail];
            $sumGrid[]  = ['date_vn' => $obj['date_vn'], 'counts' => $sum];
        }

        return [$headGrid, $tailGrid, $sumGrid];
    }

    private function buildSpecialHeadTailCounts(array $rows): array
    {
        $head = array_fill(0, 10, 0);
        $tail = array_fill(0, 10, 0);

        foreach ($rows as $r) {
            $l2 = $this->last2Strict((string)$r['special_prize']);
            $h  = (int)$l2[0];
            $t  = (int)$l2[1];
            $head[$h]++;
            $tail[$t]++;
        }

        $headOut = [];
        $tailOut = [];
        for ($i = 0; $i < 10; $i++) {
            $headOut[] = ['digit' => $i, 'times' => (int)$head[$i]];
            $tailOut[] = ['digit' => $i, 'times' => (int)$tail[$i]];
        }

        usort($headOut, fn($a, $b) => $b['times'] <=> $a['times'] ?: $a['digit'] <=> $b['digit']);
        usort($tailOut, fn($a, $b) => $b['times'] <=> $a['times'] ?: $a['digit'] <=> $b['digit']);

        return [$headOut, $tailOut];
    }

    private function isProvinceInRegion(int $provinceId, string $region, \Phalcon\Db\Adapter\Pdo\Mysql $db): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM provinces WHERE id = :id AND region = :r");
        $stmt->bindValue(':id', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':r',  $region);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    private function flattenRecentSpecialPairs(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $num   = (string)$r['special_prize'];
            $out[] = [
                'date_vn' => $this->vnDate($r['draw_date']),
                'num'     => $num,
                'last2'   => $this->last2($num),
            ];
        }
        return $out;
    }

    private function flattenRecentSpecialPairsSingleProvince(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $num   = (string)$r['special_prize'];
            $out[] = [
                'date_vn' => $this->vnDate($r['draw_date']),
                'num'     => $num,
                'last2'   => $this->last2($num),
            ];
        }
        return $out;
    }

    private function chunkPairsBy4(array $pairs): array
    {
        $rows = [];
        $chunk = [];
        foreach ($pairs as $p) {
            $chunk[] = $p;
            if (count($chunk) === 4) {
                $rows[] = $chunk;
                $chunk = [];
            }
        }
        if (!empty($chunk)) $rows[] = $chunk;
        return $rows;
    }

    private function asPairsRows4FromTopMost(array $topMost): array
    {
        $pairs = [];
        foreach ($topMost as $t) {
            $pairs[] = ['num' => $t['num'], 'times' => (int)$t['times']];
        }
        return $this->chunkPairsBy4($pairs);
    }

    private function buildDailySpecialDigitSets(array $rows, string $mode = 'head'): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d  = $r['draw_date'];
            $sp = (string)$r['special_prize'];
            $digit = ($mode === 'head') ? $this->head($sp) : $this->tail($sp);
            if (!isset($byDate[$d])) $byDate[$d] = [];
            $byDate[$d][(int)$digit] = true; // dùng set
        }
        ksort($byDate);
        $out = [];
        foreach ($byDate as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }

    private function buildDailySpecialDigitSetsSingleProvince(array $rows, string $mode = 'head'): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $sp = (string)$r['special_prize'];
            $digit = ($mode === 'head') ? $this->head($sp) : $this->tail($sp);
            $byDate[$r['draw_date']] = [(int)$digit];
        }
        ksort($byDate);
        return $byDate;
    }

    private function computeDigitGanByDays(array $dailySets): array
    {
        $curStreak = array_fill(0, 10, 0);
        $maxStreak = array_fill(0, 10, 0);
        $lastSeen  = array_fill(0, 10, null);

        foreach ($dailySets as $date => $digitsPresent) {
            $present = array_fill_keys($digitsPresent, true);
            for ($d = 0; $d <= 9; $d++) {
                if (isset($present[$d])) {
                    $lastSeen[$d]  = $date;
                    $curStreak[$d] = 0;
                } else {
                    $curStreak[$d]++;
                    if ($curStreak[$d] > $maxStreak[$d]) $maxStreak[$d] = $curStreak[$d];
                }
            }
        }

        $rows = [];
        for ($d = 0; $d <= 9; $d++) {
            $rows[] = [
                'digit'     => (string)$d,
                'streak'    => $curStreak[$d],
                'last_seen' => $lastSeen[$d] ? $this->vnDate($lastSeen[$d]) : null,
                'max_streak' => $maxStreak[$d],
            ];
        }
        usort($rows, fn($a, $b) => strcmp($a['digit'], $b['digit']));
        return $rows;
    }

    private function sameDayPastMulti(string $region, ?int $provinceId, string $anchorYmd, \Phalcon\Db\Adapter\Pdo\Mysql $db): array
    {
        $dates = [
            ['label' => '1 tuần trước',  'date' => date('Y-m-d', strtotime($anchorYmd . ' -7 days'))],
            ['label' => '1 tháng trước', 'date' => date('Y-m-d', strtotime($anchorYmd . ' -1 month'))],
        ];
        for ($i = 1; $i <= 5; $i++) {
            $dates[] = ['label' => "$i năm trước", 'date' => date('Y-m-d', strtotime($anchorYmd . " -$i year"))];
        }

        $rows4 = [];
        foreach ($dates as $d) {
            $date = $d['date'];

            if ($provinceId) {
                $stmt = $db->prepare("SELECT special_prize FROM lottery_results WHERE province_id = :pid AND draw_date = :d LIMIT 1");
                $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
                $stmt->bindValue(':d',   $date);
                $stmt->execute();
                $sp = $stmt->fetchColumn();
                if (!$sp) continue;

                $rows4[] = [
                    'date_text' => $this->weekdayVN($date) . ', ' . $this->vnDate($date),
                    'items'     => [[
                        'num'   => (string)$sp,
                        'last2' => $this->last2((string)$sp),
                    ]],
                ];
            } else {
                $rs = $this->getResultsByDates($region, $db, [$date]);
                if (!$rs) continue;

                $items = [];
                foreach ($rs as $r) {
                    $num = (string)$r['special_prize'];
                    $items[] = ['num' => $num, 'last2' => $this->last2($num)];
                }
                $rows4[] = [
                    'date_text' => $this->weekdayVN($date) . ', ' . $this->vnDate($date),
                    'items'     => array_slice($items, 0, 4),
                ];
            }
        }
        return $rows4;
    }

    private function head(string $num): string
    {
        $s = ltrim(preg_replace('/\D+/', '', (string)$num), '0');
        if ($s === '') $s = '0';
        return substr($s, 0, 1) ?: '0';
    }

    private function tail(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '0';
        return substr($s, -1);
    }

    private function weekdayVN(string $ymd): string
    {
        $w = (int)date('N', strtotime($ymd));
        return [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ Nhật'][$w] ?? '';
    }



    private function getAllResultsAllTime(Mysql $db, ?string $region = null, ?int $provinceId = null): array
    {
        $cols = "lr.draw_date, lr.province_id, p.name AS province_name,
                 lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                 lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize";
        if ($provinceId !== null) {
            $sql = "SELECT $cols
                    FROM lottery_results lr
                    JOIN provinces p ON p.id = lr.province_id
                    WHERE lr.province_id = :pid
                    ORDER BY lr.draw_date ASC, p.name ASC";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $sql = "SELECT $cols
                FROM lottery_results lr
                JOIN provinces p ON p.id = lr.province_id
                WHERE lr.draw_type = :r
                ORDER BY lr.draw_date ASC, p.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':r', $region);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function buildDailyHeadTailSetsSpecialPrize(array $rows): array
    {
        $head = [];
        $tail = [];

        foreach ($rows as $r) {
            $d  = $r['draw_date'];
            $sp = isset($r['special_prize']) ? (string)$r['special_prize'] : '';
            $h  = $this->headDigit($sp);
            $t  = $this->tailDigit($sp);

            if (!isset($head[$d])) $head[$d] = [];
            if (!isset($tail[$d])) $tail[$d] = [];

            $head[$d][$h] = true;
            $tail[$d][$t] = true;
        }

        ksort($head);
        ksort($tail);

        $headSets = [];
        foreach ($head as $d => $set) $headSets[$d] = array_map('strval', array_keys($set)); // mảng ['0'..'9']
        $tailSets = [];
        foreach ($tail as $d => $set) $tailSets[$d] = array_map('strval', array_keys($set));

        return [$headSets, $tailSets];
    }

    private function headDigit(string $num): string
    {
        $s = ltrim(preg_replace('/\D+/', '', (string)$num), '0');
        if ($s === '') $s = '0';
        return substr($s, 0, 1) ?: '0';
    }

    private function tailDigit(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '0';
        return substr($s, -1);
    }

    private function computeGanDigitsByDays(array $dailySets, bool $excludeNeverSeen = false): array
    {
        $cur  = array_fill_keys(range(0, 9), 0);
        $seen = array_fill_keys(range(0, 9), null);
        $max  = array_fill_keys(range(0, 9), 0);

        foreach ($dailySets as $date => $digits) {
            $present = array_fill_keys($digits, true);
            foreach ($cur as $d => $streak) {
                $key = (string)$d;
                if (isset($present[$key])) {
                    $seen[$d] = $date;
                    $cur[$d]  = 0;
                } else {
                    $cur[$d]++;
                    if ($cur[$d] > $max[$d]) $max[$d] = $cur[$d];
                }
            }
        }

        $rows = [];
        foreach ($cur as $d => $streak) {
            if ($excludeNeverSeen && $seen[$d] === null) continue;
            $rows[] = [
                'digit'     => (string)$d,
                'streak'    => (int)$streak,
                'last_seen' => $seen[$d] ? $this->vnDate($seen[$d]) : null,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['digit'], $b['digit']);
            return $b['streak'] <=> $a['streak'];
        });

        return $rows;
    }

    private function computeKhanPairsABBA(array $dailySets, int $limit = 10): array
    {
        $pairs = [];
        foreach (range(0, 9) as $a) {
            foreach (range(0, 9) as $b) {
                if ($a >= $b) continue;
                $ab = sprintf('%d%d', $a, $b);
                $ba = sprintf('%d%d', $b, $a);
                $pairs["$ab-$ba"] = [
                    'keys'       => [$ab, $ba],
                    'streak'     => 0,
                    'max_streak' => 0,
                    'last_seen'  => null,
                ];
            }
        }

        foreach ($dailySets as $date => $nums) {
            $present = array_fill_keys($nums, true);
            foreach ($pairs as $label => &$info) {
                $ab = $info['keys'][0];
                $ba = $info['keys'][1];
                if (isset($present[$ab]) || isset($present[$ba])) {
                    $info['last_seen']  = $date;
                    $info['streak']     = 0;
                } else {
                    $info['streak']++;
                    if ($info['streak'] > $info['max_streak']) $info['max_streak'] = $info['streak'];
                }
            }
            unset($info);
        }

        $rows = [];
        foreach ($pairs as $label => $info) {
            $rows[] = [
                'pair'       => $label,
                'streak'     => (int)$info['streak'],
                'last_seen'  => $info['last_seen'] ? $this->vnDate($info['last_seen']) : null,
                'max_streak' => (int)$info['max_streak'],
            ];
        }
        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['pair'], $b['pair']);
            return $b['streak'] <=> $a['streak'];
        });

        return array_slice($rows, 0, $limit);
    }

    private function computeAllTimeGanStats(array $dailySets, int $limit = 20): array
    {
        $cur  = [];
        $max = [];
        $lastSeen = [];
        foreach (range(0, 99) as $n) {
            $k = str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            $cur[$k] = 0;
            $max[$k] = 0;
            $lastSeen[$k] = null;
        }

        foreach ($dailySets as $date => $nums) {
            $present = array_fill_keys($nums, true);
            foreach ($cur as $num => $streak) {
                if (isset($present[$num])) {
                    $lastSeen[$num] = $date;
                    $cur[$num] = 0;
                } else {
                    $cur[$num]++;
                    if ($cur[$num] > $max[$num]) $max[$num] = $cur[$num];
                }
            }
        }

        $rows = [];
        foreach ($cur as $num => $streakNow) {
            $rows[] = [
                'num'        => $num,
                'max_streak' => (int)$max[$num],
                'last_seen'  => $lastSeen[$num] ? $this->vnDate($lastSeen[$num]) : null,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['max_streak'] === $b['max_streak']) return strcmp($a['num'], $b['num']);
            return $b['max_streak'] <=> $a['max_streak'];
        });

        return array_slice($rows, 0, $limit);
    }


    private function getProvincesByRegion(string $region, Mysql $db): array
    {
        $sql = "SELECT id, name FROM provinces WHERE region = :r ORDER BY name";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':r', $region);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getRecentDatesByRegion(string $region, Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date
                FROM lottery_results
                WHERE draw_type = :r
                GROUP BY draw_date
                ORDER BY draw_date DESC
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':r', $region);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'draw_date');
    }

    private function getResultsByDates(string $region, Mysql $db, array $dates, bool $selectAllPrizes = false): array
    {
        if (empty($dates)) return [];

        $ph = implode(',', array_fill(0, count($dates), '?'));

        $cols = $selectAllPrizes
            ? "lr.draw_date, lr.province_id, p.name AS province_name,
               lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
               lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize"
            : "lr.draw_date, lr.province_id, p.name AS province_name, lr.special_prize";

        $sql = "SELECT $cols
                FROM lottery_results lr
                JOIN provinces p ON p.id = lr.province_id
                WHERE lr.draw_type = ? AND lr.draw_date IN ($ph)
                ORDER BY lr.draw_date DESC, p.name ASC";

        $stmt = $db->prepare($sql);
        $bind = array_merge([$region], $dates);
        $stmt->execute($bind);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getRecentByProvince(int $provinceId, Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date, special_prize
                FROM lottery_results
                WHERE province_id = :pid
                ORDER BY draw_date DESC
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getRecentByProvinceFull(int $provinceId, Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date,
                       special_prize, first_prize, second_prize, third_prize,
                       fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
                FROM lottery_results
                WHERE province_id = :pid
                ORDER BY draw_date DESC
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function last2(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '00';
        return substr(str_pad($s, 2, '0', STR_PAD_LEFT), -2);
    }

    private function vnDate(string $ymd): string
    {
        $ts = strtotime($ymd);
        if ($ts === false || $ts <= 0) return '';
        return date('d/m/Y', $ts);
    }

    private function groupByDateForGrid(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($out[$d])) $out[$d] = ['date' => $d, 'date_vn' => $this->vnDate($d), 'items' => []];
            $out[$d]['items'][] = [
                'province_id'   => (int)$r['province_id'],
                'province_name' => $r['province_name'],
                'num'           => $r['special_prize'],
                'last2'         => $this->last2($r['special_prize']),
            ];
        }
        krsort($out);
        return array_values($out);
    }

    private function groupByDateForGridSingleProvince(array $rows, int $provinceId, Mysql $db): array
    {
        static $nameCache = [];
        if (!isset($nameCache[$provinceId])) {
            $stmt = $db->prepare("SELECT name FROM provinces WHERE id = :id");
            $stmt->bindValue(':id', $provinceId, \PDO::PARAM_INT);
            $stmt->execute();
            $nameCache[$provinceId] = (string)($stmt->fetchColumn() ?: '---');
        }
        $pname = $nameCache[$provinceId];

        $out = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            $out[$d] = [
                'date'     => $d,
                'date_vn'  => $this->vnDate($d),
                'items'    => [[
                    'province_id'   => $provinceId,
                    'province_name' => $pname,
                    'num'           => $r['special_prize'],
                    'last2'         => $this->last2($r['special_prize']),
                ]],
            ];
        }
        krsort($out);
        return array_values($out);
    }

    private function decodeAllPrizesFromRow(array $r): array
    {
        $vals = [];

        foreach (['special_prize', 'first_prize'] as $c) {
            if (!empty($r[$c])) $vals[] = (string)$r[$c];
        }

        foreach (['second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'] as $c) {
            if (!isset($r[$c]) || $r[$c] === null || $r[$c] === '') continue;
            $vals = array_merge($vals, $this->decodePrizeField((string)$r[$c]));
        }

        return $vals;
    }

    private function decodePrizeField(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        if ($raw[0] === '[') {
            $arr = json_decode($raw, true);
            if (is_array($arr)) {
                return array_values(array_map(fn($x) => (string)$x, $arr));
            }
        }

        return [$raw];
    }

    private function buildDailyLast2SetsAllPrizes(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($byDate[$d])) $byDate[$d] = [];
            $allVals = $this->decodeAllPrizesFromRow($r);
            foreach ($allVals as $v) {
                $l2 = $this->last2($v);
                $byDate[$d][$l2] = true;
            }
        }
        ksort($byDate);
        $out = [];
        foreach ($byDate as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }

    private function buildDailyLast2SetsAllPrizesSingleProvince(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            $allVals = $this->decodeAllPrizesFromRow($r);
            $set = [];
            foreach ($allVals as $v) {
                $set[$this->last2($v)] = true;
            }
            $byDate[$d] = array_keys($set);
        }
        ksort($byDate);
        return $byDate;
    }

    private function computeGanByDays(array $dailySets, int $limit = 10, bool $excludeNeverSeen = false): array
    {
        $curStreak = [];
        $maxStreak = [];
        $lastSeen  = [];

        foreach (range(0, 99) as $n) {
            $key = str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            $curStreak[$key] = 0;
            $maxStreak[$key] = 0;
            $lastSeen[$key]  = null;
        }

        foreach ($dailySets as $date => $nums) {
            $present = array_fill_keys($nums, true);
            foreach ($curStreak as $num => $streak) {
                if (isset($present[$num])) {
                    $lastSeen[$num]  = $date;
                    $curStreak[$num] = 0;
                } else {
                    $curStreak[$num]++;
                    if ($curStreak[$num] > $maxStreak[$num]) $maxStreak[$num] = $curStreak[$num];
                }
            }
        }

        $rows = [];
        foreach ($curStreak as $num => $streak) {
            if ($excludeNeverSeen && $lastSeen[$num] === null) continue;
            $rows[] = [
                'num'        => $num,
                'streak'     => (int)$streak,
                'last_seen'  => $lastSeen[$num] ? $this->vnDate($lastSeen[$num]) : null,
                'max_streak' => (int)$maxStreak[$num],
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['num'], $b['num']);
            return $b['streak'] <=> $a['streak'];
        });

        return array_slice($rows, 0, $limit);
    }

    private function computeTopMost(array $rows, int $limit = 10): array
    {
        $cnt = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
        foreach ($rows as $r) {
            $cnt[$this->last2($r['special_prize'])]++;
        }
        arsort($cnt);
        $max = max($cnt) ?: 1;

        $out = [];
        foreach ($cnt as $num => $times) {
            if ($times <= 0) continue;
            $out[] = [
                'num'     => $num,
                'times'   => (int)$times,
                'percent' => round($times * 100 / $max, 2),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    private function computeTopMostSingleProvince(array $rows, int $limit = 10): array
    {
        $cnt = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
        foreach ($rows as $r) $cnt[$this->last2($r['special_prize'])]++;
        arsort($cnt);
        $max = max($cnt) ?: 1;

        $out = [];
        foreach ($cnt as $num => $times) {
            if ($times <= 0) continue;
            $out[] = [
                'num'     => $num,
                'times'   => (int)$times,
                'percent' => round($times * 100 / $max, 2),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    /**
     * Helper method để xử lý slug từ URL thành province_id
     */
    private function processSlugToProvinceId($defaultRegion = 'XSMT')
    {
        error_log("MT DEBUG processSlugToProvinceId - POST: " . json_encode($_POST));
        error_log("MT DEBUG processSlugToProvinceId - Slug: " . $this->dispatcher->getParam('slug'));

        // Ưu tiên POST data nếu form được submit
        if (!empty($_POST['province_id'])) {
            $rawProvince = trim((string)$_POST['province_id']);
            $isRegionMode = (stripos($rawProvince, 'REGION:') === 0) || $rawProvince === '' || $rawProvince === '0';
            $provinceId = $isRegionMode ? null : (int)$rawProvince;

            // Xác định region từ province_id
            $region = $defaultRegion;
            if (!$isRegionMode && $provinceId) {
                // Xác định region từ province_id bằng cách query database
                try {
                    $db = $this->di->get('db');
                    $province = $db->query("SELECT region FROM provinces WHERE id = ?", [$provinceId])->fetch();
                    if ($province) {
                        $region = $province['region'];
                    }
                } catch (Exception $e) {
                    // Nếu có lỗi database, sử dụng defaultRegion
                    error_log("Database error in processSlugToProvinceId: " . $e->getMessage());
                }
            }

            error_log("MT DEBUG processSlugToProvinceId - POST mode - ProvinceId: " . $provinceId . ", Region: " . $region);

            // Nếu có provinceId từ POST, lấy slug
            $slug = null;
            if ($provinceId) {
                $slug = ThongkeStatisticsHelper::getSlugFromProvinceId($provinceId);
            }

            return [
                'provinceId' => $provinceId,
                'isRegionMode' => $isRegionMode,
                'rawProvince' => $rawProvince,
                'region' => $region,
                'slug' => $slug
            ];
        }

        // Fallback về slug từ URL
        $slug = $this->dispatcher->getParam('slug');
        if ($slug) {
            // Nếu có slug từ URL, chuyển đổi thành province_id
            $provinceId = ThongkeStatisticsHelper::getProvinceIdFromSlug($slug);
            $region = ThongkeStatisticsHelper::getRegionFromSlug($slug);

            // Nếu không tìm thấy region từ slug, sử dụng defaultRegion
            if (!$region) {
                $region = $defaultRegion;
            }

            $isRegionMode = ($provinceId === null);
            $rawProvince = $isRegionMode ? "REGION:{$region}" : (string)$provinceId;

            error_log("MT DEBUG processSlugToProvinceId - SLUG mode - Slug: " . $slug . ", ProvinceId: " . $provinceId . ", Region: " . $region);

            return [
                'provinceId' => $provinceId,
                'isRegionMode' => $isRegionMode,
                'rawProvince' => $rawProvince,
                'region' => $region,
                'slug' => $slug  // Trả về slug từ URL
            ];
        } else {
            // Fallback về cách cũ từ POST
            $rawProvince = isset($_POST['province_id']) ? trim((string)$_POST['province_id']) : "REGION:{$defaultRegion}";
            $isRegionMode = (stripos($rawProvince, 'REGION:') === 0) || $rawProvince === '' || $rawProvince === '0';
            $provinceId   = $isRegionMode ? null : (int)$rawProvince;

            return [
                'provinceId' => $provinceId,
                'isRegionMode' => $isRegionMode,
                'rawProvince' => $rawProvince,
                'region' => $region ?? $defaultRegion,
                'slug' => null
            ];
        }
    }
}
