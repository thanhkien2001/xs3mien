<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Db\Adapter\Pdo\Mysql;
use App\Library\ThongkeStatisticsHelper;
use App\Library\KqxsHelper;

class SokequaController extends ControllerBase
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

    /**
     * Sổ kết quả XSMB 30 ngày
     */
    /**
     * Generic method for XSMB results stats
     */
    private function renderSoKetQuaXsmb(int $limit)
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMB';
        $regionName = 'Miền Bắc';
        $perPage = 10;
        $page = (int) $this->request->get('page', 'int', 1);
        if ($page < 1)
            $page = 1;

        // Cache key
        $cacheKey = "so_ket_qua_xsmb_{$limit}_ngay_" . $page;
        $cache = $this->di->get('modelsCache');

        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);
            $this->view->pick('sokequa/so_ket_qua_mb');
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        // Lấy $limit ngày gần nhất
        $dates = ThongkeStatisticsHelper::getRecentDatesByRegion($region, $db, $limit);

        if (empty($dates)) {
            $empty = [
                'region' => $region,
                'regionName' => $regionName,
                'results' => [],
                'statistics' => $this->getEmptyStatistics(),
                'currentPage' => $page,
                'totalPages' => 0,
                'hasMore' => false,
                'limitDays' => $limit
            ];
            $this->view->setVars($empty);
            $this->view->pick('sokequa/so_ket_qua_mb');
            return;
        }

        // Lấy kết quả chi tiết
        $allResults = ThongkeStatisticsHelper::getResultsByDates($region, $db, $dates, true);

        // Nhóm kết quả theo ngày
        $resultsByDate = $this->groupResultsByDate($allResults, $region);

        // Pagination
        $totalResults = count($resultsByDate);
        $totalPages = (int) ceil($totalResults / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = array_slice($resultsByDate, $offset, $perPage);

        // Tính toán thống kê từ tất cả $limit ngày
        $statistics = $this->calculateStatistics($allResults, $region);

        $finalResult = [
            'region' => $region,
            'regionName' => $regionName,
            'results' => $paginatedResults,
            'statistics' => $statistics,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasMore' => $page < $totalPages,
            'totalResults' => $totalResults,
            'baseUrl' => $this->getAbsoluteUrl(),
            'limitDays' => $limit
        ];

        // Generate dynamic SEO data
        $seoData = \App\Library\PerformanceHelper::generateCachedSeoData('custom', 'sokequa', null, [
            'regionName' => $regionName,
            'regionCode' => $region,
            'regionNameLower' => mb_strtolower($regionName, 'UTF-8'),
            'regionCodeLower' => strtolower($region),
            'limit' => $limit,
            'extra_desc' => ''
        ], $cache);

        $finalResult = array_merge($finalResult, $seoData);

        // Cache 5 phút
        $cache->set($cacheKey, $finalResult, 300);

        $this->view->setVars($finalResult);

        // Schema data
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'],
            'description' => $finalResult['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'XSMB', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html')],
                ['name' => "Sổ kết quả XSMB {$limit} ngày", 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->pick('sokequa/so_ket_qua_mb');
    }

    /**
     * Sổ kết quả XSMB 10 ngày
     */
    public function soKetQuaXsmb10NgayAction()
    {
        $this->renderSoKetQuaXsmb(10);
    }

    /**
     * Sổ kết quả XSMB 30 ngày
     */
    public function soKetQuaXsmb30NgayAction()
    {
        $this->renderSoKetQuaXsmb(30);
    }

    /**
     * Sổ kết quả XSMB 60 ngày
     */
    public function soKetQuaXsmb60NgayAction()
    {
        $this->renderSoKetQuaXsmb(60);
    }

    /**
     * Sổ kết quả XSMB 90 ngày
     */
    public function soKetQuaXsmb90NgayAction()
    {
        $this->renderSoKetQuaXsmb(90);
    }

    /**
     * Sổ kết quả XSMB 100 ngày
     */
    public function soKetQuaXsmb100NgayAction()
    {
        $this->renderSoKetQuaXsmb(100);
    }

    /**
     * Sổ kết quả XSMN 30 ngày
     */
    public function soKetQuaXsmn30NgayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMN';
        $regionName = 'Miền Nam';
        $limit = 30;
        $perPage = 10;
        $page = (int) $this->request->get('page', 'int', 1);
        if ($page < 1)
            $page = 1;

        // Cache key
        $cacheKey = 'so_ket_qua_xsmn_30_ngay_' . $page;
        $cache = $this->di->get('modelsCache');

        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);
            $this->view->pick('sokequa/so_ket_qua_mtmn');
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        // Lấy 30 ngày gần nhất
        $dates = ThongkeStatisticsHelper::getRecentDatesByRegion($region, $db, $limit);

        if (empty($dates)) {
            $empty = [
                'region' => $region,
                'regionName' => $regionName,
                'results' => [],
                'statistics' => $this->getEmptyStatistics(),
                'currentPage' => $page,
                'totalPages' => 0,
                'hasMore' => false
            ];
            $this->view->setVars($empty);
            $this->view->pick('sokequa/so_ket_qua_mtmn');
            return;
        }

        // Lấy kết quả chi tiết
        $allResults = ThongkeStatisticsHelper::getResultsByDates($region, $db, $dates, true);

        // Nhóm kết quả theo ngày
        $resultsByDate = $this->groupResultsByDate($allResults, $region);

        // Pagination
        $totalResults = count($resultsByDate);
        $totalPages = (int) ceil($totalResults / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = array_slice($resultsByDate, $offset, $perPage);

        // Tính toán thống kê từ tất cả 30 ngày
        $statistics = $this->calculateStatistics($allResults, $region);

        $finalResult = [
            'region' => $region,
            'regionName' => $regionName,
            'results' => $paginatedResults,
            'statistics' => $statistics,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasMore' => $page < $totalPages,
            'totalResults' => $totalResults,
            'baseUrl' => $this->getAbsoluteUrl()
        ];

        // SEO data
        $seoData = \App\Library\PerformanceHelper::generateCachedSeoData('custom', 'sokequa', null, [
            'regionName' => $regionName,
            'regionCode' => $region,
            'regionNameLower' => mb_strtolower($regionName, 'UTF-8'),
            'regionCodeLower' => strtolower($region),
            'limit' => $limit,
            'extra_desc' => 'chính xác nhất của 21 tỉnh quay thưởng XSMN.'
        ], $cache);
        $finalResult = array_merge($finalResult, $seoData);

        // Cache 5 phút
        $cache->set($cacheKey, $finalResult, 300);

        $this->view->setVars($finalResult);

        // Schema data
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Sổ kết quả XSMN 30 ngày',
            'description' => $finalResult['seo_description'] ?? 'Sổ kết quả xổ số Miền Nam 30 ngày',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'XSMN', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html')],
                ['name' => 'Sổ kết quả XSMN', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->pick('sokequa/so_ket_qua_mtmn');
    }

    /**
     * Sổ kết quả XSMT 30 ngày
     */
    public function soKetQuaXsmt30NgayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $region = 'XSMT';
        $regionName = 'Miền Trung';
        $limit = 30;
        $perPage = 10;
        $page = (int) $this->request->get('page', 'int', 1);
        if ($page < 1)
            $page = 1;

        // Cache key
        $cacheKey = 'so_ket_qua_xsmt_30_ngay_' . $page;
        $cache = $this->di->get('modelsCache');

        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);
            $this->view->pick('sokequa/so_ket_qua_mtmn');
            return;
        }

        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->di->get('db');

        // Lấy 30 ngày gần nhất
        $dates = ThongkeStatisticsHelper::getRecentDatesByRegion($region, $db, $limit);

        if (empty($dates)) {
            $empty = [
                'region' => $region,
                'regionName' => $regionName,
                'results' => [],
                'statistics' => $this->getEmptyStatistics(),
                'currentPage' => $page,
                'totalPages' => 0,
                'hasMore' => false
            ];
            $this->view->setVars($empty);
            $this->view->pick('sokequa/so_ket_qua_mtmn');
            return;
        }

        // Lấy kết quả chi tiết
        $allResults = ThongkeStatisticsHelper::getResultsByDates($region, $db, $dates, true);

        // Nhóm kết quả theo ngày
        $resultsByDate = $this->groupResultsByDate($allResults, $region);

        // Pagination
        $totalResults = count($resultsByDate);
        $totalPages = (int) ceil($totalResults / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = array_slice($resultsByDate, $offset, $perPage);

        // Tính toán thống kê từ tất cả 30 ngày
        $statistics = $this->calculateStatistics($allResults, $region);

        $finalResult = [
            'region' => $region,
            'regionName' => $regionName,
            'results' => $paginatedResults,
            'statistics' => $statistics,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasMore' => $page < $totalPages,
            'totalResults' => $totalResults,
            'baseUrl' => $this->getAbsoluteUrl()
        ];

        // SEO data
        $seoData = \App\Library\PerformanceHelper::generateCachedSeoData('custom', 'sokequa', null, [
            'regionName' => $regionName,
            'regionCode' => $region,
            'regionNameLower' => mb_strtolower($regionName, 'UTF-8'),
            'regionCodeLower' => strtolower($region),
            'limit' => $limit,
            'extra_desc' => 'chính xác nhất của 14 tỉnh quay thưởng XSMT.'
        ], $cache);
        $finalResult = array_merge($finalResult, $seoData);

        // Cache 5 phút
        $cache->set($cacheKey, $finalResult, 300);

        $this->view->setVars($finalResult);

        // Schema data
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Sổ kết quả XSMT 30 ngày',
            'description' => $finalResult['seo_description'] ?? 'Sổ kết quả xổ số Miền Trung 30 ngày',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'XSMT', 'url' => $this->getAbsoluteUrl('/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html')],
                ['name' => 'Sổ kết quả XSMT', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->pick('sokequa/so_ket_qua_mtmn');
    }

    /**
     * Nhóm kết quả theo ngày
     */
    private function groupResultsByDate(array $results, string $region): array
    {
        $grouped = [];

        foreach ($results as $row) {
            $date = $row['draw_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'dateFormatted' => ThongkeStatisticsHelper::vnDate($date),
                    'weekday' => ThongkeStatisticsHelper::weekdayVN($date),
                    'provinces' => []
                ];
            }

            $provinceData = [
                'province_id' => (int) $row['province_id'],
                'province_name' => $row['province_name'] ?? '',
                'special_prize' => $row['special_prize'] ?? '',
                'first_prize' => $row['first_prize'] ?? '',
                'second_prize' => $this->parsePrizeField($row['second_prize'] ?? ''),
                'third_prize' => $this->parsePrizeField($row['third_prize'] ?? ''),
                'fourth_prize' => $this->parsePrizeField($row['fourth_prize'] ?? ''),
                'fifth_prize' => $this->parsePrizeField($row['fifth_prize'] ?? ''),
                'sixth_prize' => $this->parsePrizeField($row['sixth_prize'] ?? ''),
                'seventh_prize' => $this->parsePrizeField($row['seventh_prize'] ?? ''),
                'eighth_prize' => $this->parsePrizeField($row['eighth_prize'] ?? ''),
            ];

            // Tính lô tô đầu đuôi cho từng tỉnh
            $allLast2 = ThongkeStatisticsHelper::extractAllLast2FromRow($row);
            $provinceData['loto_dau'] = $this->buildDauDuoi($allLast2);

            $grouped[$date]['provinces'][] = $provinceData;
        }

        // Sắp xếp theo ngày giảm dần
        krsort($grouped);

        return array_values($grouped);
    }

    /**
     * Parse prize field (có thể là JSON array hoặc string)
     */
    private function parsePrizeField($val): array
    {
        return ThongkeStatisticsHelper::parsePrizeField($val);
    }

    /**
     * Tính toán thống kê
     */
    private function calculateStatistics(array $allResults, string $region): array
    {
        // Thống kê giải đặc biệt về nhiều nhất (2 số cuối)
        $dbLast2Count = [];
        $dbHeadCount = array_fill(0, 10, 0);
        $dbTailCount = array_fill(0, 10, 0);
        $dbSumCount = array_fill(0, 10, 0);

        // Thống kê lô tô về nhiều nhất
        $lotoCount = array_fill_keys(array_map(fn($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
        $lotoHeadCount = array_fill(0, 10, 0);
        $lotoTailCount = array_fill(0, 10, 0);
        $lotoSumCount = array_fill(0, 10, 0);

        foreach ($allResults as $row) {
            // Giải đặc biệt
            if (!empty($row['special_prize'])) {
                $sp = preg_replace('/\D+/', '', (string) $row['special_prize']);
                if (strlen($sp) >= 2) {
                    $last2 = substr($sp, -2);
                    $dbLast2Count[$last2] = ($dbLast2Count[$last2] ?? 0) + 1;

                    $head = (int) substr($last2, 0, 1);
                    $tail = (int) substr($last2, 1, 1);
                    $sum = ($head + $tail) % 10;

                    $dbHeadCount[$head]++;
                    $dbTailCount[$tail]++;
                    $dbSumCount[$sum]++;
                }
            }

            // Lô tô từ tất cả giải
            $allLast2 = ThongkeStatisticsHelper::extractAllLast2FromRow($row);
            foreach ($allLast2 as $l2) {
                $lotoCount[$l2]++;

                $head = (int) $l2[0];
                $tail = (int) $l2[1];
                $sum = ($head + $tail) % 10;

                $lotoHeadCount[$head]++;
                $lotoTailCount[$tail]++;
                $lotoSumCount[$sum]++;
            }
        }

        // Sắp xếp giải đặc biệt về nhiều nhất
        arsort($dbLast2Count);
        $dbTopFrequent = [];
        foreach ($dbLast2Count as $num => $count) {
            $dbTopFrequent[] = [
                'number' => $num,
                'count' => $count
            ];
            if (count($dbTopFrequent) >= 10)
                break;
        }

        // Sắp xếp lô tô về nhiều nhất
        arsort($lotoCount);
        $lotoTopFrequent = [];
        foreach ($lotoCount as $num => $count) {
            if ($count > 0) {
                $lotoTopFrequent[] = [
                    'number' => $num,
                    'count' => $count
                ];
                if (count($lotoTopFrequent) >= 10)
                    break;
            }
        }

        return [
            'db_top_frequent' => $dbTopFrequent,
            'db_head_stats' => $this->formatHeadTailSumStats($dbHeadCount),
            'db_tail_stats' => $this->formatHeadTailSumStats($dbTailCount),
            'db_sum_stats' => $this->formatHeadTailSumStats($dbSumCount),
            'loto_top_frequent' => $lotoTopFrequent,
            'loto_head_stats' => $this->formatHeadTailSumStats($lotoHeadCount),
            'loto_tail_stats' => $this->formatHeadTailSumStats($lotoTailCount),
            'loto_sum_stats' => $this->formatHeadTailSumStats($lotoSumCount),
        ];
    }

    /**
     * Format thống kê đầu/đuôi/tổng
     */
    private function formatHeadTailSumStats(array $counts): array
    {
        $stats = [];
        for ($i = 0; $i <= 9; $i++) {
            $stats[] = [
                'digit' => $i,
                'count' => $counts[$i] ?? 0
            ];
        }
        return $stats;
    }

    /**
     * Build đầu đuôi từ danh sách 2 số cuối
     */
    private function buildDauDuoi(array $last2List): array
    {
        $dau = array_fill(0, 10, []);
        $duoi = array_fill(0, 10, []);

        foreach ($last2List as $l2) {
            if (strlen($l2) === 2) {
                $d = (int) $l2[0];
                $u = (int) $l2[1];
                $dau[$d][] = $l2;
                $duoi[$u][] = $l2;
            }
        }

        // Loại bỏ trùng lặp và sắp xếp
        foreach ($dau as &$list) {
            $list = array_values(array_unique($list));
            sort($list);
        }
        foreach ($duoi as &$list) {
            $list = array_values(array_unique($list));
            sort($list);
        }

        return ['dau' => $dau, 'duoi' => $duoi];
    }

    /**
     * Trả về thống kê rỗng
     */
    private function getEmptyStatistics(): array
    {
        return [
            'db_top_frequent' => [],
            'db_head_stats' => array_fill(0, 10, ['digit' => 0, 'count' => 0]),
            'db_tail_stats' => array_fill(0, 10, ['digit' => 0, 'count' => 0]),
            'db_sum_stats' => array_fill(0, 10, ['digit' => 0, 'count' => 0]),
            'loto_top_frequent' => [],
            'loto_head_stats' => array_fill(0, 10, ['digit' => 0, 'count' => 0]),
            'loto_tail_stats' => array_fill(0, 10, ['digit' => 0, 'count' => 0]),
            'loto_sum_stats' => array_fill(0, 10, ['digit' => 0, 'count' => 0]),
        ];
    }
}
