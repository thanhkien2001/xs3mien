<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Db\Adapter\Pdo\Mysql;
use App\Library\ThongkeStatisticsHelper;
use App\Library\PerformanceHelper;
use Exception;

class ThongkemnController extends ControllerBase
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

    public function thongKeXsmnAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // ====== Input ======
        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMN');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region']; // Region được detect từ slug
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // ====== Cache check ======
        $cache    = $this->di->get('modelsCache');
        $cacheKey = 'thongke_xsmn_' . md5($rawProvince . '_' . $totalDay);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            // Regenerate SEO data mới với provinceCode
            $cachedProvinceId = $cachedResult['selectedProvinceId'] ?? null;
            $cachedProvinceName = $cachedResult['selectedProvinceName'] ?? 'Miền Nam';
            $cachedIsRegionMode = empty($cachedProvinceId);
            $cachedProvinceCode = $cachedProvinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($cachedProvinceId) : null;

            $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'thongke', !$cachedIsRegionMode ? $cachedProvinceName : null, $cachedProvinceCode);
            $cachedResult = array_merge($cachedResult, $seoData);
            $cachedResult['selectedProvinceCode'] = $cachedProvinceCode; // FIX: Update provinceCode in cache

            $this->view->setVars($cachedResult);

            // Schema data for cache hit
            $cachedIsRegionMode = $cachedResult['isRegionMode'] ?? true;
            $cachedProvinceName = $cachedResult['selectedProvinceName'] ?? 'Miền Nam';

            // Breadcrumb động: 2 levels cho region, 3 levels cho tỉnh
            $breadcrumbs = [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]
            ];

            if ($cachedIsRegionMode) {
                // Region mode: Trang chủ -> Thống kê XSMN
                $breadcrumbs[] = ['name' => 'Thống kê XSMN', 'url' => $this->getAbsoluteUrl()];
            } else {
                // Province mode: Trang chủ -> Thống kê XSMN -> Thống kê [Tỉnh]
                $breadcrumbs[] = ['name' => 'Thống kê XSMN', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmn')];
                $breadcrumbs[] = ['name' => 'Thống kê ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Thống kê XSMN',
                'description' => $cachedResult['seo_description'] ?? 'Thống kê xổ số Miền Nam',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            $this->view->pick('thongkemn/thongkexsmn');
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        // ====== Provinces ======
        $provinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        // ====== Data ======
        $specialGrid = [];
        $topMost     = [];
        $loGan       = [];
        $asOfDate    = null;

        if ($isRegionMode) {
            $dates = ThongkeStatisticsHelper::getRecentDatesByRegion($region, $db, $totalDay);
            if ($dates && isset($dates[0])) {
                $asOfDate = $dates[0];
                $rows     = ThongkeStatisticsHelper::getResultsByDates($region, $db, $dates, true);

                if ($rows) {
                    $specialGrid = ThongkeStatisticsHelper::groupByDateForGrid($rows);
                    $dailySets   = ThongkeStatisticsHelper::buildDailyLast2SetsAllPrizes($rows);
                    $loGan       = ThongkeStatisticsHelper::computeGanByDays($dailySets, 10);
                    $topMost     = ThongkeStatisticsHelper::computeTopMost($rows, 10);
                }
            }
        } else {
            $rows = ThongkeStatisticsHelper::getRecentByProvince($provinceId, $db, $totalDay, true);
            if ($rows && isset($rows[0]['draw_date'])) {
                $asOfDate    = $rows[0]['draw_date'];
                $specialGrid = ThongkeStatisticsHelper::groupByDateForGridSingleProvince($rows, $provinceId, $db);
                $dailySets   = ThongkeStatisticsHelper::buildDailyLast2SetsAllPrizes($rows);
                $loGan       = ThongkeStatisticsHelper::computeGanByDays($dailySets, 10);
                $topMost     = ThongkeStatisticsHelper::computeTopMost($rows, 10);
            }
        }

        // ====== Province display name ======
        $selectedProvinceName = match ($region) {
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung',
            'XSMB' => 'Miền Bắc',
            default => 'Miền Nam'
        };
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($provinces as $p) {
                if ((int)$p['id'] === (int)$provinceId) {
                    $selectedProvinceName = $p['name'];
                    break;
                }
            }
        }

        // ====== Prepare final result ======
        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $provinces,
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

        // ====== SEO Data ======
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'thongke', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        // ====== Save cache ======
        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($specialGrid, 'date') ?: 300;
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        // ====== Render view ======
        $this->view->setVars($finalResult);

        // Schema data for cache miss
        // Breadcrumb động: 2 levels cho region, 3 levels cho tỉnh
        $breadcrumbs = [
            ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]
        ];

        if ($isRegionMode) {
            // Region mode: Trang chủ -> Thống kê XSMN
            $breadcrumbs[] = ['name' => 'Thống kê XSMN', 'url' => $this->getAbsoluteUrl()];
        } else {
            // Province mode: Trang chủ -> Thống kê XSMN -> Thống kê [Tỉnh]
            $breadcrumbs[] = ['name' => 'Thống kê XSMN', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmn')];
            $breadcrumbs[] = ['name' => 'Thống kê ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Thống kê XSMN',
            'description' => $finalResult['seo_description'] ?? 'Thống kê xổ số Miền Nam',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick('thongkemn/thongkexsmn');
    }

    public function loGanXsmnAction()
    {
        // CSS
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // ===== Input =====
        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMN');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region']; // Region được detect từ slug
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        // DEBUG LOG
        error_log("=== DEBUG loGanXsmnAction ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("provinceId: " . $provinceId);
        error_log("isRegionMode: " . ($isRegionMode ? 'true' : 'false'));
        error_log("rawProvince: " . $rawProvince);
        error_log("region: " . $region);

        $windowDays = isset($_POST['total_day']) ? max(5, min(100, (int)$_POST['total_day'])) : 30;

        // ===== Cache =====
        $cache       = $this->di->get('modelsCache');
        $viewToPick  = 'thongkemn/loganxsmn';
        $cacheKey    = 'logan_xsmn_' . md5($rawProvince . '_' . $windowDays . '_v4'); // Force clear cache

        // DEBUG LOG
        error_log("cacheKey: " . $cacheKey);
        error_log("windowDays: " . $windowDays);

        // Force clear cache for testing (Remove in prod if stable)
        // $cache->delete($cacheKey);

        if (($cached = $cache->get($cacheKey)) !== null) {
            // Ensure CSS is set even on cache hit
            $this->view->customindex    = '/css/indexheader.css';
            $this->view->customthongke1 = '/css/thongke/thongke1.css';

            // Regenerate SEO data mới với provinceCode
            // Regenerate SEO data mới với provinceCode
            $cachedProvinceId = $cached['selectedProvinceId'] ?? null;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Nam';
            $cachedIsRegionMode = empty($cachedProvinceId);
            $cachedProvinceCode = $cachedProvinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($cachedProvinceId) : null;

            $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'logan', !$cachedIsRegionMode ? $cachedProvinceName : null, $cachedProvinceCode);
            $cached = array_merge($cached, $seoData);
            $cached['selectedProvinceCode'] = $cachedProvinceCode; // FIX: Update provinceCode in cache

            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Nam';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Lô gan XSMN', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Lô gan XSMN', 'url' => $this->getAbsoluteUrl('/lo-gan-xsmn')];
                $breadcrumbs[] = ['name' => 'Lô gan ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Lô gan XSMN',
                'description' => $cached['seo_description'] ?? 'Lô gan xổ số Miền Nam',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            // QUAN TRỌNG: luôn pick view trước khi return
            $this->view->pick($viewToPick);
            return;
        }

        /** @var Mysql $db */
        $db = $this->di->get('db');

        // ===== Provinces =====
        $provinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);
        $selectedProvinceName = match ($region) {
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung',
            'XSMB' => 'Miền Bắc',
            default => 'Miền Nam'
        };
        if (!$isRegionMode && $provinceId) {
            foreach ($provinces as $p) {
                if ((int)$p['id'] === $provinceId) {
                    $selectedProvinceName = $p['name'];
                    break;
                }
            }
        }

        // ===== Data =====
        $asOfDate        = null;
        $topGanSingles   = [];
        $topGanPairs     = [];
        $headGanSpecial  = [];
        $tailGanSpecial  = [];
        $maxGanAll       = [];

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($region, $db, $windowDays);
            if ($dates) {
                $asOfDate = $dates[0];
                $rows         = $this->getResultsByDates($region, $db, $dates);
                $dailyAll     = $this->buildDailyLast2SetsAllPrizes($rows);
                $dailySpecial = $this->buildDailySpecialLast2Sets($rows);

                $topGanSingles  = $this->computeGanByDays($dailyAll, 10);
                $topGanPairs    = $this->computeGanPairsByDays($dailyAll, 10);
                $headGanSpecial = $this->computeHeadTailGanFromSpecial($dailySpecial, 'head');
                $tailGanSpecial = $this->computeHeadTailGanFromSpecial($dailySpecial, 'tail');
                $maxGanAll      = $this->computeMaxGanAllHistoryRegion($region, $db);
            }
        } else {
            $dates = $this->getRecentDatesByProvince($provinceId, $db, $windowDays);
            if ($dates) {
                $asOfDate = $dates[0];
                $rows         = $this->getResultsByDatesProvince($provinceId, $db, $dates);
                $dailyAll     = $this->buildDailyLast2SetsAllPrizes($rows);
                $dailySpecial = $this->buildDailySpecialLast2Sets($rows);

                $topGanSingles  = $this->computeGanByDays($dailyAll, 10);
                $topGanPairs    = $this->computeGanPairsByDays($dailyAll, 10);
                $headGanSpecial = $this->computeHeadTailGanFromSpecial($dailySpecial, 'head');
                $tailGanSpecial = $this->computeHeadTailGanFromSpecial($dailySpecial, 'tail');
                $maxGanAll      = $this->computeMaxGanAllHistoryProvince($provinceId, $db);
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);

        // Debug log
        error_log("DEBUG logan - provinceId: " . ($provinceId ?? 'null'));
        error_log("DEBUG logan - provinceSlug: " . ($provinceSlug ?? 'null'));
        error_log("DEBUG logan - provinceCode: " . ($provinceCode ?? 'null'));
        error_log("DEBUG logan - selectedProvinceName: " . $selectedProvinceName);

        $final = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $provinces,
            'selectedProvinceRaw'  => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'         => $isRegionMode, // Thêm biến isRegionMode
            'asOfDate'             => $asOfDate,
            'windowDays'           => $windowDays,
            'topGanSingles'        => $topGanSingles,
            'topGanPairs'          => $topGanPairs,
            'headGanSpecial'       => $headGanSpecial,
            'tailGanSpecial'       => $tailGanSpecial,
            'maxGanAll'            => $maxGanAll,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'logan'),
            'currentPage'          => 'logan',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'logan', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        error_log("DEBUG logan - seo_title: " . ($seoData['seo_title'] ?? 'null'));
        $final = array_merge($final, $seoData);

        $cacheLife = ThongkeStatisticsHelper::getSmartCacheLifetime($topGanSingles, 'last_seen');
        $cache->set($cacheKey, $final, $cacheLife);

        $this->view->setVars($final);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Lô gan XSMN', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Lô gan XSMN', 'url' => $this->getAbsoluteUrl('/lo-gan-xsmn')];
            $breadcrumbs[] = ['name' => 'Lô gan ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $final['seo_title'] ?? 'Lô gan XSMN',
            'description' => $final['seo_description'] ?? 'Lô gan xổ số Miền Nam',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewToPick);
    }


    public function dacBietXsmnAction()
    {
        // CSS
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // ===== Input =====
        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMN');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region']; // Region được detect từ slug
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        if ($totalDay < 5)   $totalDay = 5;
        if ($totalDay > 100) $totalDay = 100;

        // ===== Cache =====
        $cache       = $this->di->get('modelsCache');
        $viewToPick  = 'thongkemn/dacbietxsmn';
        $cacheKey    = 'dacbiet_xsmn_' . md5($rawProvince . '_' . $totalDay);

        if (($cached = $cache->get($cacheKey)) !== null) {
            // Regenerate SEO data mới với provinceCode
            $cachedProvinceId = $cached['selectedProvinceId'] ?? null;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Nam';
            $cachedIsRegionMode = empty($cachedProvinceId);
            $cachedProvinceCode = $cachedProvinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($cachedProvinceId) : null;

            $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'dacbiet', !$cachedIsRegionMode ? $cachedProvinceName : null, $cachedProvinceCode);
            $cached = array_merge($cached, $seoData);
            $cached['selectedProvinceCode'] = $cachedProvinceCode; // FIX: Update provinceCode in cache

            $this->view->setVars($cached);

            // Schema data for cache hit
            $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
            $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Nam';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Đặc biệt XSMN', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Đặc biệt XSMN', 'url' => $this->getAbsoluteUrl('/dac-biet-xsmn')];
                $breadcrumbs[] = ['name' => 'Đặc biệt ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Đặc biệt XSMN',
                'description' => $cached['seo_description'] ?? 'Thống kê giải đặc biệt xổ số Miền Nam',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            // QUAN TRỌNG: pick trước khi return
            $this->view->pick($viewToPick);
            return;
        }

        /** @var Mysql $db */
        $db = $this->di->get('db');

        // ===== Provinces =====
        $provinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        // ===== Data =====
        $specialGrid = [];
        $last2Stats  = [];
        $headGaps    = [];
        $tailGaps    = [];
        $asOfDate    = null;

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($region, $db, $totalDay);
            if ($dates) {
                $asOfDate = $dates[0];
                $rows = $this->getResultsByDates($region, $db, $dates);

                $specialGrid = $this->groupByDateForGrid($rows);
                $last2Stats  = $this->computeTopMost($rows, 100);

                $dailyHeadSets = $this->buildDailyHeadSetsRegion($rows);
                $dailyTailSets = $this->buildDailyTailSetsRegion($rows);
                $headGaps      = $this->computeDigitGapsByDays($dailyHeadSets);
                $tailGaps      = $this->computeDigitGapsByDays($dailyTailSets);
            }
        } else {
            $rows = $this->getRecentByProvince($provinceId, $db, $totalDay);
            if ($rows) {
                $asOfDate = $rows[0]['draw_date'];

                $specialGrid   = $this->groupByDateForGridSingleProvince($rows, $provinceId, $db);
                $last2Stats    = $this->computeTopMostSingleProvince($rows, 100);
                $dailyHeadSets = $this->buildDailyHeadSetsSingleProvince($rows);
                $dailyTailSets = $this->buildDailyTailSetsSingleProvince($rows);
                $headGaps      = $this->computeDigitGapsByDays($dailyHeadSets);
                $tailGaps      = $this->computeDigitGapsByDays($dailyTailSets);
            }
        }

        // Tên hiển thị
        $selectedProvinceName = match ($region) {
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung',
            'XSMB' => 'Miền Bắc',
            default => 'Miền Nam'
        };
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($provinces as $p) {
                if ((int)$p['id'] === $provinceId) {
                    $selectedProvinceName = $p['name'];
                    break;
                }
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $final = [
            'selectRegionToken'     => "REGION:{$region}",
            'provinceOptions'       => $provinces,
            'selectedProvinceRaw'   => $rawProvince,
            'selectedProvinceName'  => $selectedProvinceName,
            'selectedProvinceId'    => $provinceId,
            'selectedProvinceCode'  => $provinceCode,
            'isRegionMode'          => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'              => $totalDay,
            'asOfDate'              => $asOfDate,
            'specialGrid'           => $specialGrid,
            'last2Stats'            => $last2Stats,
            'headGaps'              => $headGaps,
            'tailGaps'              => $tailGaps,
            'statisticsMenu'        => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'dacbiet'),
            'currentPage'           => 'dacbiet',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'dacbiet', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $final = array_merge($final, $seoData);

        $cacheLife = ThongkeStatisticsHelper::getSmartCacheLifetime($specialGrid, 'date');
        $cache->set($cacheKey, $final, $cacheLife);

        $this->view->setVars($final);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Đặc biệt XSMN', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Đặc biệt XSMN', 'url' => $this->getAbsoluteUrl('/dac-biet-xsmn')];
            $breadcrumbs[] = ['name' => 'Đặc biệt ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $final['seo_title'] ?? 'Đặc biệt XSMN',
            'description' => $final['seo_description'] ?? 'Thống kê giải đặc biệt xổ số Miền Nam',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewToPick);
    }



    public function dauDuoiXsmnAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMN');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region']; // Region được detect từ slug
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        $totalDay = max(5, min(100, $totalDay));

        // Cache key ổn định
        $cacheKey = 'dauduoi_xsmn_' . md5($rawProvince . '_' . $totalDay);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemn/dauduoixsmn';

        if ($cachedResult = $cache->get($cacheKey)) {
            // Regenerate SEO data mới với provinceCode
            $cachedProvinceId = $cachedResult['selectedProvinceId'] ?? null;
            $cachedProvinceName = $cachedResult['selectedProvinceName'] ?? 'Miền Nam';
            $cachedIsRegionMode = empty($cachedProvinceId);
            $cachedProvinceCode = $cachedProvinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($cachedProvinceId) : null;

            $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'dauduoi', !$cachedIsRegionMode ? $cachedProvinceName : null, $cachedProvinceCode);
            $cachedResult = array_merge($cachedResult, $seoData);
            $cachedResult['selectedProvinceCode'] = $cachedProvinceCode; // FIX: Update provinceCode in cache

            $this->view->setVars($cachedResult);

            // Schema data for cache hit
            $cachedIsRegionMode = $cachedResult['isRegionMode'] ?? true;
            $cachedProvinceName = $cachedResult['selectedProvinceName'] ?? 'Miền Nam';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Đầu đuôi XSMN', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Đầu đuôi XSMN', 'url' => $this->getAbsoluteUrl('/dau-duoi-xsmn')];
                $breadcrumbs[] = ['name' => 'Đầu đuôi ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Đầu đuôi XSMN',
                'description' => $cachedResult['seo_description'] ?? 'Thống kê đầu đuôi xổ số Miền Nam',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            // QUAN TRỌNG: pick view trước khi return để tránh 404
            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');
        $provinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $headGrid = [];
        $tailGrid = [];
        $sumGrid  = [];
        $headDbStats = [];
        $tailDbStats = [];
        $asOfDate = null;

        if ($isRegionMode) {
            $dates = $this->getRecentDatesByRegion($region, $db, $totalDay);
            if ($dates) {
                $asOfDate    = $dates[0];
                $rows        = $this->getFullResultsByDates($region, $db, $dates);
                $dailyStats  = $this->buildDailyLotoStats($rows);
                foreach (array_reverse($dates) as $d) {
                    $stat      = $dailyStats[$d] ?? ['head' => array_fill(0, 10, 0), 'tail' => array_fill(0, 10, 0), 'sum' => array_fill(0, 10, 0)];
                    $headGrid[] = ['date' => $d, 'date_vn' => $this->vnDate($d), 'counts' => $stat['head']];
                    $tailGrid[] = ['date' => $d, 'date_vn' => $this->vnDate($d), 'counts' => $stat['tail']];
                    $sumGrid[]  = ['date' => $d, 'date_vn' => $this->vnDate($d), 'counts' => $stat['sum']];
                }
                $headDbStats = $this->computeDbHeadOrTailStats($rows, 'head');
                $tailDbStats = $this->computeDbHeadOrTailStats($rows, 'tail');
            }
        } else {
            $rows = $this->getFullRecentByProvince($provinceId, $db, $totalDay);
            if ($rows) {
                $asOfDate   = $rows[0]['draw_date'];
                $dailyStats = $this->buildDailyLotoStats($rows);
                $dates      = array_values(array_unique(array_column($rows, 'draw_date')));
                foreach (array_reverse($dates) as $d) {
                    $stat      = $dailyStats[$d] ?? ['head' => array_fill(0, 10, 0), 'tail' => array_fill(0, 10, 0), 'sum' => array_fill(0, 10, 0)];
                    $headGrid[] = ['date' => $d, 'date_vn' => $this->vnDate($d), 'counts' => $stat['head']];
                    $tailGrid[] = ['date' => $d, 'date_vn' => $this->vnDate($d), 'counts' => $stat['tail']];
                    $sumGrid[]  = ['date' => $d, 'date_vn' => $this->vnDate($d), 'counts' => $stat['sum']];
                }
                $headDbStats = $this->computeDbHeadOrTailStats($rows, 'head');
                $tailDbStats = $this->computeDbHeadOrTailStats($rows, 'tail');
            }
        }

        $selectedProvinceName = match ($region) {
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung',
            'XSMB' => 'Miền Bắc',
            default => 'Miền Nam'
        };
        if (!$isRegionMode && $provinceId) {
            foreach ($provinces as $p) {
                if ((int)$p['id'] === (int)$provinceId) {
                    $selectedProvinceName = $p['name'];
                    break;
                }
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'    => "REGION:{$region}",
            'provinceOptions'      => $provinces,
            'selectedProvinceRaw'  => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'         => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'             => $totalDay,
            'asOfDate'             => $asOfDate,
            'headGrid'             => $headGrid,
            'tailGrid'             => $tailGrid,
            'sumGrid'              => $sumGrid,
            'headDbStats'          => $headDbStats,
            'tailDbStats'          => $tailDbStats,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'dauduoi'),
            'currentPage'          => 'dauduoi',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'dauduoi', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($headGrid, 'date_vn');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Đầu đuôi XSMN', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Đầu đuôi XSMN', 'url' => $this->getAbsoluteUrl('/dau-duoi-xsmn')];
            $breadcrumbs[] = ['name' => 'Đầu đuôi ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Đầu đuôi XSMN',
            'description' => $finalResult['seo_description'] ?? 'Thống kê đầu đuôi xổ số Miền Nam',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }


    public function tanSuatXsmnAction()
    {
        $this->view->customindex    = '/css/indexheader.css';
        $this->view->customthongke1 = '/css/thongke/thongke1.css';

        // Xử lý slug từ URL hoặc POST data
        $slugData = $this->processSlugToProvinceId('XSMN');
        $provinceId = $slugData['provinceId'];
        $isRegionMode = $slugData['isRegionMode'];
        $rawProvince = $slugData['rawProvince'];
        $region = $slugData['region']; // Region được detect từ slug
        $provinceSlug = $slugData['slug'] ?? null; // Lưu slug từ URL

        $totalDay = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 30;
        $totalDay = max(5, min(100, $totalDay));

        // Cache key ổn định
        $cacheKey = 'tansuat_xsmn_' . md5($rawProvince . '_' . $totalDay);

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'thongkemn/tansuatxsmn';

        if ($cachedResult = $cache->get($cacheKey)) {
            // Regenerate SEO data mới với provinceCode
            $cachedProvinceId = $cachedResult['selectedProvinceId'] ?? null;
            $cachedProvinceName = $cachedResult['selectedProvinceName'] ?? 'Miền Nam';
            $cachedIsRegionMode = empty($cachedProvinceId);
            $cachedProvinceCode = $cachedProvinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($cachedProvinceId) : null;

            $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'tansuat', !$cachedIsRegionMode ? $cachedProvinceName : null, $cachedProvinceCode);
            $cachedResult = array_merge($cachedResult, $seoData);
            $cachedResult['selectedProvinceCode'] = $cachedProvinceCode; // FIX: Update provinceCode in cache

            $this->view->setVars($cachedResult);

            // Schema data for cache hit
            $cachedIsRegionMode = $cachedResult['isRegionMode'] ?? true;
            $cachedProvinceName = $cachedResult['selectedProvinceName'] ?? 'Miền Nam';

            $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
            if ($cachedIsRegionMode) {
                $breadcrumbs[] = ['name' => 'Tần suất XSMN', 'url' => $this->getAbsoluteUrl()];
            } else {
                $breadcrumbs[] = ['name' => 'Tần suất XSMN', 'url' => $this->getAbsoluteUrl('/tan-suat-xsmn')];
                $breadcrumbs[] = ['name' => 'Tần suất ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
            }

            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Tần suất XSMN',
                'description' => $cachedResult['seo_description'] ?? 'Thống kê tần suất xổ số Miền Nam',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);

            // QUAN TRỌNG: pick view trước khi return
            $this->view->pick($viewPick);
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');
        $provinces = ThongkeStatisticsHelper::getProvincesByRegion($region, $db);

        $tsDateYmd = [];
        $tsDateHeaders = [];
        $tsMatrix = [];
        $rowTotals = [];
        $asOfDate = null;

        $nums = array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99));

        if ($isRegionMode) {
            $tsDateYmd = $this->getRecentDatesByRegion($region, $db, $totalDay);
            if ($tsDateYmd) {
                $asOfDate      = $tsDateYmd[0];
                $tsDateHeaders = array_map(fn($d) => date('d/m/Y', strtotime($d)), $tsDateYmd);
                $rows          = $this->getFullResultsByDates($region, $db, $tsDateYmd);

                $dateIndex = [];
                foreach ($tsDateYmd as $i => $d) $dateIndex[$d] = $i;

                foreach ($nums as $num) {
                    $tsMatrix[$num]  = array_fill(0, count($tsDateYmd), 0);
                    $rowTotals[$num] = 0;
                }

                foreach ($rows as $r) {
                    $d = $r['draw_date'];
                    if (!isset($dateIndex[$d])) continue;
                    $idx = $dateIndex[$d];
                    foreach ($this->extractAllLast2FromRow($r) as $l2) {
                        $tsMatrix[$l2][$idx] += 1;
                        $rowTotals[$l2]      += 1;
                    }
                }
            }
        } else {
            $rows = $this->getFullRecentByProvince($provinceId, $db, $totalDay);
            if ($rows) {
                $datesSet = [];
                foreach ($rows as $r) $datesSet[$r['draw_date']] = true;
                $tsDateYmd = array_keys($datesSet);
                rsort($tsDateYmd, SORT_STRING);

                $asOfDate      = $tsDateYmd[0];
                $tsDateHeaders = array_map(fn($d) => date('d/m/Y', strtotime($d)), $tsDateYmd);

                $dateIndex = [];
                foreach ($tsDateYmd as $i => $d) $dateIndex[$d] = $i;

                foreach ($nums as $num) {
                    $tsMatrix[$num]  = array_fill(0, count($tsDateYmd), 0);
                    $rowTotals[$num] = 0;
                }

                foreach ($rows as $r) {
                    $d = $r['draw_date'];
                    if (!isset($dateIndex[$d])) continue;
                    $idx = $dateIndex[$d];
                    foreach ($this->extractAllLast2FromRow($r) as $l2) {
                        $tsMatrix[$l2][$idx] += 1;
                        $rowTotals[$l2]      += 1;
                    }
                }
            }
        }

        $selectedProvinceName = match ($region) {
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung',
            'XSMB' => 'Miền Bắc',
            default => 'Miền Nam'
        };
        if (!$isRegionMode && $provinceId !== null) {
            foreach ($provinces as $p) {
                if ((int)$p['id'] === (int)$provinceId) {
                    $selectedProvinceName = $p['name'];
                    break;
                }
            }
        }

        $provinceCode = $provinceSlug ? ThongkeStatisticsHelper::getProvinceCodeFromSlug($provinceSlug) : ($provinceId ? ThongkeStatisticsHelper::getProvinceCodeFromId($provinceId) : null);
        $finalResult = [
            'selectRegionToken'   => "REGION:{$region}",
            'provinceOptions'     => $provinces,
            'selectedProvinceRaw' => $rawProvince,
            'selectedProvinceName' => $selectedProvinceName,
            'selectedProvinceId'   => $provinceId,
            'selectedProvinceCode' => $provinceCode,
            'isRegionMode'        => $isRegionMode, // Thêm biến isRegionMode
            'totalDay'            => $totalDay,
            'asOfDate'            => $asOfDate,
            'tsDateHeaders'       => $tsDateHeaders,
            'tsDateYmd'           => $tsDateYmd,
            'tsMatrix'            => $tsMatrix,
            'rowTotals'           => $rowTotals,
            'statisticsMenu'       => ThongkeStatisticsHelper::generateStatisticsMenu($provinceId, $selectedProvinceName, 'tansuat'),
            'currentPage'          => 'tansuat',
        ];

        // SEO data
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta($region, 'tansuat', !$isRegionMode ? $selectedProvinceName : null, $provinceCode);
        $finalResult = array_merge($finalResult, $seoData);

        $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime([['date' => $asOfDate]]);
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);

        // Schema data for cache miss
        $breadcrumbs = [['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]];
        if ($isRegionMode) {
            $breadcrumbs[] = ['name' => 'Tần suất XSMN', 'url' => $this->getAbsoluteUrl()];
        } else {
            $breadcrumbs[] = ['name' => 'Tần suất XSMN', 'url' => $this->getAbsoluteUrl('/tan-suat-xsmn')];
            $breadcrumbs[] = ['name' => 'Tần suất ' . $selectedProvinceName, 'url' => $this->getAbsoluteUrl()];
        }

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Tần suất XSMN',
            'description' => $finalResult['seo_description'] ?? 'Thống kê tần suất xổ số Miền Nam',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);

        $this->view->pick($viewPick);
    }




    private function getFullResultsByDates(string $region, Mysql $db, array $dates): array
    {
        if (!$dates) return [];
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


    private function getFullRecentByProvince(int $provinceId, Mysql $db, int $limit): array
    {
        $sql = "SELECT lr.draw_date, lr.province_id, p.name AS province_name,
                       lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                       lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize
                FROM lottery_results lr
                JOIN provinces p ON p.id = lr.province_id
                WHERE lr.province_id = :pid
                ORDER BY lr.draw_date DESC
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    private function valueToList($val): array
    {
        if ($val === null) return [];
        $s = trim((string)$val);
        if ($s === '' || $s === 'null' || $s === '[]') return [];

        // thử parse JSON array
        $decoded = json_decode($s, true);
        if (is_array($decoded)) {
            $arr = [];
            foreach ($decoded as $it) {
                $str = trim((string)$it);
                if ($str !== '') $arr[] = $str;
            }
            return $arr;
        }

        // scalar
        return [$s];
    }


    private function headDigitFull(string $num): string
    {
        $s = ltrim(preg_replace('/\D+/', '', (string)$num), '0');
        if ($s === '') $s = '0';
        return substr($s, 0, 1) ?: '0';
    }
    private function tailDigitFull(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '0';
        return substr($s, -1);
    }
    private function buildDailyLotoStats(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($byDate[$d])) {
                $byDate[$d] = [
                    'head' => array_fill(0, 10, 0),
                    'tail' => array_fill(0, 10, 0),
                    'sum'  => array_fill(0, 10, 0),
                ];
            }
            $allLast2 = $this->extractAllLast2FromRow($r);
            foreach ($allLast2 as $l2) {
                // l2 = 'xy'
                $x = (int)$l2[0];
                $y = (int)$l2[1];
                $byDate[$d]['head'][$x]++;
                $byDate[$d]['tail'][$y]++;
                $byDate[$d]['sum'][($x + $y) % 10]++;
            }
        }
        ksort($byDate);
        return $byDate;
    }

    private function computeDbHeadOrTailStats(array $rows, string $mode = 'head'): array
    {
        $cnt = array_fill_keys(range(0, 9), 0);
        foreach ($rows as $r) {
            $sp = (string)($r['special_prize'] ?? '');
            $digit = ($mode === 'head') ? $this->headDigitFull($sp) : $this->tailDigitFull($sp);
            $cnt[(int)$digit]++;
        }
        arsort($cnt);
        $out = [];
        foreach ($cnt as $d => $times) {
            if ($times <= 0) continue;
            $out[] = ['digit' => (string)$d, 'times' => (int)$times];
        }
        return $out;
    }

    private function headDigit(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '0';
        return substr($s, 0, 1);
    }

    private function tailDigit(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '0';
        return substr($s, -1);
    }

    private function buildDailyHeadSetsRegion(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            $h = $this->headDigit($r['special_prize']);
            if (!isset($byDate[$d])) $byDate[$d] = [];
            $byDate[$d][$h] = true;
        }
        ksort($byDate);
        $out = [];
        foreach ($byDate as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }

    private function buildDailyHeadSetsSingleProvince(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['draw_date']] = [$this->headDigit($r['special_prize'])];
        }
        ksort($byDate);
        return $byDate;
    }

    private function buildDailyTailSetsRegion(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            $t = $this->tailDigit($r['special_prize']);
            if (!isset($byDate[$d])) $byDate[$d] = [];
            $byDate[$d][$t] = true;
        }
        ksort($byDate);
        $out = [];
        foreach ($byDate as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }

    private function buildDailyTailSetsSingleProvince(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['draw_date']] = [$this->tailDigit($r['special_prize'])];
        }
        ksort($byDate);
        return $byDate;
    }

    private function computeDigitGapsByDays(array $dailySets, int $limit = 10): array
    {
        // Domain 0..9
        $domain = array_map(fn($d) => (string)$d, range(0, 9));

        // Khởi tạo
        $gap  = array_fill_keys($domain, 0);
        $last = array_fill_keys($domain, null);

        foreach ($dailySets as $date => $digits) {
            $present = array_fill_keys($digits, true);
            foreach ($domain as $d) {
                if (isset($present[$d])) {
                    $last[$d] = $date;
                    $gap[$d]  = 0;
                } else {
                    $gap[$d]++;
                }
            }
        }

        $rows = [];
        foreach ($domain as $d) {
            $rows[] = [
                'digit'     => $d,
                'gap_days'  => $gap[$d],
                'last_seen' => $last[$d] ?: null,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['gap_days'] === $b['gap_days']) return strcmp($a['digit'], $b['digit']);
            return $b['gap_days'] <=> $a['gap_days'];
        });

        return array_slice($rows, 0, $limit);
    }

    private function getRecentDatesByProvince(int $provinceId, Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date
                FROM lottery_results
                WHERE province_id = :pid
                GROUP BY draw_date
                ORDER BY draw_date DESC
                LIMIT :lim";
        $st = $db->prepare($sql);
        $st->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return array_column($st->fetchAll(\PDO::FETCH_ASSOC), 'draw_date');
    }


    private function getResultsByDatesProvince(int $provinceId, Mysql $db, array $dates): array
    {
        if (!$dates) return [];
        $ph = implode(',', array_fill(0, count($dates), '?'));
        $sql = "SELECT draw_date, province_id,
                       special_prize, first_prize, second_prize, third_prize,
                       fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
                FROM lottery_results
                WHERE province_id = ? AND draw_date IN ($ph)
                ORDER BY draw_date DESC";
        $st = $db->prepare($sql);
        $st->execute(array_merge([$provinceId], $dates));
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getAllRowsRegion(string $region, Mysql $db): array
    {
        $sql = "SELECT draw_date,
                       special_prize, first_prize, second_prize, third_prize,
                       fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
                FROM lottery_results
                WHERE draw_type = :r
                ORDER BY draw_date ASC";
        $st = $db->prepare($sql);
        $st->bindValue(':r', $region);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getAllRowsProvince(int $provinceId, Mysql $db): array
    {
        $sql = "SELECT draw_date,
                       special_prize, first_prize, second_prize, third_prize,
                       fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
                FROM lottery_results
                WHERE province_id = :pid
                ORDER BY draw_date ASC";
        $st = $db->prepare($sql);
        $st->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function last2(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '00';
        return substr(str_pad($s, 2, '0', STR_PAD_LEFT), -2);
    }



    private function extractSpecialLast2FromRow(array $r): array
    {
        $out = [];
        foreach ($this->parsePrizeField($r['special_prize'] ?? null) as $raw) $out[] = $this->last2((string)$raw);
        return $out;
    }


    private function buildDailySpecialLast2Sets(array $rows): array
    {
        $by = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($by[$d])) $by[$d] = [];
            foreach ($this->extractSpecialLast2FromRow($r) as $l2) $by[$d][$l2] = true;
        }
        ksort($by);
        $out = [];
        foreach ($by as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }

    private function computeGanByDays(array $dailySets, int $limit = 10): array
    {
        $cur = $max = $last = [];
        foreach (range(0, 99) as $n) {
            $k = str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            $cur[$k] = 0;
            $max[$k] = 0;
            $last[$k] = null;
        }

        foreach ($dailySets as $ymd => $nums) {
            $present = array_fill_keys($nums, true);
            foreach ($cur as $num => $streak) {
                if (isset($present[$num])) {
                    $last[$num] = $ymd;
                    $cur[$num]  = 0;
                } else {
                    $cur[$num]  = $streak + 1;
                    if ($cur[$num] > $max[$num]) $max[$num] = $cur[$num];
                }
            }
        }

        $rows = [];
        foreach ($cur as $num => $st) {
            $rows[] = [
                'num'        => $num,
                'streak'     => $st,
                'last_seen'  => $last[$num] ?: null,
                'max_streak' => $max[$num],
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['num'], $b['num']);
            return $b['streak'] <=> $a['streak'];
        });

        return array_slice($rows, 0, $limit);
    }

    private function computeGanPairsByDays(array $dailySets, int $limit = 10): array
    {
        $pairs = [];
        foreach (range(0, 9) as $a) foreach (range(0, 9) as $b) {
            if ($a === $b) continue;
            $ab = (string)$a . (string)$b;
            $ba = (string)$b . (string)$a;
            $key = strcmp($ab, $ba) < 0 ? "$ab-$ba" : "$ba-$ab";
            $pairs[$key] = ['a' => $ab, 'b' => $ba];
        }

        $cur = $max = $last = [];
        foreach ($pairs as $k => $_) {
            $cur[$k] = 0;
            $max[$k] = 0;
            $last[$k] = null;
        }

        foreach ($dailySets as $ymd => $nums) {
            $present = array_fill_keys($nums, true);
            foreach ($pairs as $key => $p) {
                $seen = isset($present[$p['a']]) || isset($present[$p['b']]);
                if ($seen) {
                    $last[$key] = $ymd;
                    $cur[$key] = 0;
                } else {
                    $cur[$key] += 1;
                    if ($cur[$key] > $max[$key]) $max[$key] = $cur[$key];
                }
            }
        }

        $rows = [];
        foreach ($pairs as $key => $p) {
            $rows[] = [
                'label'      => $key, // "ab-ba"
                'streak'     => $cur[$key],
                'last_seen'  => $last[$key] ?: null,
                'max_streak' => $max[$key],
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['label'], $b['label']);
            return $b['streak'] <=> $a['streak'];
        });

        return array_slice($rows, 0, $limit);
    }

    private function computeHeadTailGanFromSpecial(array $dailySpecialSets, string $mode /* head|tail */): array
    {
        $cur = [];
        $last = [];
        foreach (range(0, 9) as $d) {
            $cur[$d] = 0;
            $last[$d] = null;
        }

        foreach ($dailySpecialSets as $ymd => $nums) {
            $present = [];
            foreach ($nums as $n) {
                if (is_string($n) && strlen($n) >= 2) {
                    $d1 = (int)$n[0];
                    $d2 = (int)$n[1];
                    if ($mode === 'head') $present[$d1] = true;
                    else $present[$d2] = true;
                }
            }
            foreach ($cur as $digit => $st) {
                if (isset($present[$digit])) {
                    $last[$digit] = $ymd;
                    $cur[$digit] = 0;
                } else                          $cur[$digit] = $st + 1;
            }
        }

        $out = [];
        foreach (range(0, 9) as $d) {
            $out[] = [
                'digit'     => (string)$d,
                'streak'    => $cur[$d],
                'last_seen' => $last[$d] ?: null,
            ];
        }
        usort($out, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['digit'], $b['digit']);
            return $b['streak'] <=> $a['streak'];
        });
        return $out;
    }
    private function computeMaxGanAllHistoryRegion(string $region, Mysql $db): array
    {
        $rows = $this->getAllRowsRegion($region, $db);
        return $this->computeMaxGanOverRows($rows);
    }

    private function computeMaxGanAllHistoryProvince(int $provinceId, Mysql $db): array
    {
        $rows = $this->getAllRowsProvince($provinceId, $db);
        return $this->computeMaxGanOverRows($rows);
    }

    private function computeMaxGanOverRows(array $rows): array
    {
        $cur = $max = $last = [];
        foreach (range(0, 99) as $n) {
            $k = str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            $cur[$k] = 0;
            $max[$k] = 0;
            $last[$k] = null;
        }

        $by = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($by[$d])) $by[$d] = [];
            foreach ($this->extractAllLast2FromRow($r) as $l2) $by[$d][$l2] = true;
        }
        ksort($by);

        foreach ($by as $ymd => $set) {
            foreach ($cur as $num => $st) {
                if (isset($set[$num])) {
                    $last[$num] = $ymd;
                    $cur[$num] = 0;
                } else {
                    $cur[$num] = $st + 1;
                    if ($cur[$num] > $max[$num]) $max[$num] = $cur[$num];
                }
            }
        }

        $out = [];
        foreach ($max as $num => $mx) {
            $out[] = [
                'num'        => $num,
                'max_streak' => $mx,
                'last_seen'  => $last[$num] ?: null,
            ];
        }
        usort($out, function ($a, $b) {
            if ($a['max_streak'] === $b['max_streak']) return strcmp($a['num'], $b['num']);
            return $b['max_streak'] <=> $a['max_streak'];
        });
        return $out;
    }

    private function getProvincesByRegion(string $region, \Phalcon\Db\Adapter\Pdo\Mysql $db): array
    {
        $st = $db->prepare("SELECT id, name FROM provinces WHERE region = :r ORDER BY name ASC");
        $st->bindValue(':r', $region);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getRecentDatesByRegion(string $region, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date
            FROM lottery_results
            WHERE draw_type = :r
            GROUP BY draw_date
            ORDER BY draw_date DESC
            LIMIT :lim";
        $st = $db->prepare($sql);
        $st->bindValue(':r', $region);
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return array_column($st->fetchAll(\PDO::FETCH_ASSOC), 'draw_date');
    }

    private function getResultsByDates(string $region, \Phalcon\Db\Adapter\Pdo\Mysql $db, array $dates): array
    {
        if (empty($dates)) return [];
        $ph = implode(',', array_fill(0, count($dates), '?'));
        $sql = "SELECT lr.draw_date, lr.province_id, p.name AS province_name,
                   lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                   lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize
            FROM lottery_results lr
            JOIN provinces p ON p.id = lr.province_id
            WHERE lr.draw_type = ? AND lr.draw_date IN ($ph)
            ORDER BY lr.draw_date DESC, p.name ASC";
        $st = $db->prepare($sql);
        $bind = array_merge([$region], $dates);
        $st->execute($bind);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getRecentByProvinceAllPrizes(int $provinceId, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date, province_id,
                   special_prize, first_prize, second_prize, third_prize,
                   fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE province_id = :pid
            ORDER BY draw_date DESC
            LIMIT :lim";
        $st = $db->prepare($sql);
        $st->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }


    private function vnDate(string $ymd): string
    {
        return date('d/m/Y', strtotime($ymd));
    }

    private function toLast2(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '00';
        return substr(str_pad($s, 2, '0', STR_PAD_LEFT), -2);
    }

    private function parsePrizeField($val): array
    {
        if ($val === null) return [];
        $s = trim((string)$val);
        if ($s === '') return [];
        if ($s[0] === '[') { // JSON dạng ["12345","67890"]
            $arr = json_decode($s, true);
            if (is_array($arr)) return array_map('strval', $arr);
        }
        return [$s];
    }

    private function extractAllLast2FromRow(array $r): array
    {
        $fields = [
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
        $out = [];
        foreach ($fields as $f) {
            if (!array_key_exists($f, $r)) continue;
            foreach ($this->parsePrizeField($r[$f]) as $raw) {
                $out[] = $this->toLast2((string)$raw);
            }
        }
        return $out;
    }

    private function groupByDateForGrid(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($byDate[$d])) $byDate[$d] = ['date' => $d, 'date_vn' => $this->vnDate($d), 'items' => []];
            $byDate[$d]['items'][] = [
                'province_id'   => (int)$r['province_id'],
                'province_name' => (string)$r['province_name'],
                'num'           => (string)$r['special_prize'],
                'last2'         => $this->toLast2((string)$r['special_prize']),
            ];
        }
        uksort($byDate, fn($a, $b) => strcmp($b, $a));
        return array_values($byDate);
    }

    private function groupByDateForGridSingleProvince(array $rows, int $provinceId, \Phalcon\Db\Adapter\Pdo\Mysql $db): array
    {
        static $nameCache = [];
        if (!isset($nameCache[$provinceId])) {
            $st = $db->prepare("SELECT name FROM provinces WHERE id = :id");
            $st->bindValue(':id', $provinceId, \PDO::PARAM_INT);
            $st->execute();
            $nameCache[$provinceId] = (string)($st->fetchColumn() ?: '---');
        }
        $pname = $nameCache[$provinceId];

        $out = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            $out[] = [
                'date'    => $d,
                'date_vn' => $this->vnDate($d),
                'items'   => [[
                    'province_id'   => $provinceId,
                    'province_name' => $pname,
                    'num'           => (string)$r['special_prize'],
                    'last2'         => $this->toLast2((string)$r['special_prize']),
                ]],
            ];
        }
        return $out;
    }

    private function buildDailyLast2SetsAllPrizes(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($byDate[$d])) $byDate[$d] = [];
            foreach ($this->extractAllLast2FromRow($r) as $l2) {
                $byDate[$d][$l2] = true;
            }
        }
        ksort($byDate);
        $out = [];
        foreach ($byDate as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }


    private function computeTopMostAllPrizes(array $rows, int $limit = 10): array
    {
        $cnt = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
        foreach ($rows as $r) {
            foreach ($this->extractAllLast2FromRow($r) as $l2) {
                $cnt[$l2]++;
            }
        }
        arsort($cnt);
        $max = max($cnt) ?: 1;

        $out = [];
        foreach ($cnt as $num => $times) {
            if ($times <= 0) continue;
            $out[] = [
                'num'     => $num,
                'times'   => $times,
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
                'times'   => $times,
                'percent' => round($times * 100 / $max, 2),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
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
                'times'   => $times,
                'percent' => round($times * 100 / $max, 2),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    /**
     * Helper method để xử lý slug từ URL thành province_id
     */
    private function processSlugToProvinceId($defaultRegion = 'XSMN')
    {
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

            return [
                'provinceId' => $provinceId,
                'isRegionMode' => $isRegionMode,
                'rawProvince' => $rawProvince,
                'region' => $region
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

            // Nếu có provinceId từ POST, lấy slug
            $slug = null;
            if ($provinceId) {
                $slug = ThongkeStatisticsHelper::getSlugFromProvinceId($provinceId);
            }

            return [
                'provinceId' => $provinceId,
                'isRegionMode' => $isRegionMode,
                'rawProvince' => $rawProvince,
                'region' => $region ?? $defaultRegion,
                'slug' => $slug
            ];
        }
    }
}
