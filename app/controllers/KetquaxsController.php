<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Library\KqxsHelper;
use App\Models\LotteryResults;
use App\Models\PredictionArticles;
use App\Models\Provinces;
use App\Library\PerformanceHelper;
use App\Library\SeoHelper;
use App\Library\LotteryStatisticsHelper;

class KetquaxsController extends ControllerBase
{
    private $tz;

    public function initialize()
    {
        $this->setViewStyles();
        $this->tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
    }

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
        
        return $baseUrl . $path;
    }

    /**
     * Detect region from URI pattern
     */
    private function detectRegionFromUri(string $uri): string
    {
        if (preg_match('/\/xsmb-/i', $uri)) {
            return 'XSMB';
        } elseif (preg_match('/\/xsmn-/i', $uri)) {
            return 'XSMN';
        } elseif (preg_match('/\/xsmt-/i', $uri)) {
            return 'XSMT';
        }
        
        return 'XSMB'; // Default
    }

    public function byDateShortAction($d, $m, $y)
    {
        $uri = $this->request->getURI();
        $region = $this->detectRegionFromUri($uri);

        $dateStr = sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);

        try {
            $date = new \DateTime($dateStr, $this->tz);
        } catch (\Exception $e) {
            $this->response->setStatusCode(404, 'Not Found');
            $this->view->pick('errors/404');
            return;
        }

        // Check if date is in the future
        $today = new \DateTime('today', $this->tz);
        if ($date > $today) {
            $this->response->setStatusCode(404, 'Not Found');
            $this->view->pick('errors/404');
            return;
        }

        return $this->forwardToDate($date, $region);
    }

    private function isWithinLiveWindow(string $region): bool
    {
        $now = new \DateTime('now', $this->tz);
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        $current = $hour * 60 + $minute;
        
        // Different live windows for each region
        $windows = [
            'XSMB' => ['start' => 18 * 60 + 15, 'end' => 19 * 60], // 18:15-19:00
            'XSMN' => ['start' => 16 * 60 + 15, 'end' => 17 * 60], // 16:15-17:00
            'XSMT' => ['start' => 17 * 60 + 15, 'end' => 18 * 60], // 17:15-18:00
        ];
        
        $window = $windows[$region] ?? $windows['XSMB'];
        return $current >= $window['start'] && $current < $window['end'];
    }

    private function forwardToDate(\DateTime $date, string $region)
    {
        $dateStr = $date->format('Y-m-d');
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        
        $basePagePatterns = [
            'XSMB' => '/\/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb\.html$/',
            'XSMN' => '/\/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn\.html$/',
            'XSMT' => '/\/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt\.html$/',
        ];
        
        $isBasePage = (bool)preg_match($basePagePatterns[$region] ?? $basePagePatterns['XSMB'], $currentUri);


        // Get data based on region
        $today = new \DateTime('today', $this->tz);
        $isLiveToday = $this->isWithinLiveWindow($region) && $date->format('Y-m-d') === $today->format('Y-m-d');
        
        if ($region === 'XSMB') {
            $result = $this->getXSMBData($date, $dateStr, $isLiveToday);
        } else {
            $result = $this->getMultiRegionData($date, $dateStr, $region, $isLiveToday);
        }

        $predictions = $this->getCachedPredictions($date, $region);
        [$thuText, $thuSlug] = KqxsHelper::weekdayInfo($date);
        $otherLinks = $this->getCachedOtherLinks($date, $region);
        $latestResults = $this->getCachedLatestResults($dateStr, $region);

        $thuLink = $this->getThuLink($thuSlug, $region);
        $active = $thuSlug;

        $finalResult = array_merge($result, [
            'pageDate' => $date,
            'thuText' => $thuText,
            'thuSlug' => $thuSlug,
            'thuLink' => $thuLink,
            'active' => $active,
            'otherLinks' => $otherLinks,
            'predictions' => $predictions,
            'currentVal' => $dateStr,
            'todayMax' => $today->format('Y-m-d'),
            'latestResults' => $latestResults,
            'isBasePage' => $isBasePage,
            'region' => $region,
        ]);


        // SEO data
        $seoData = $this->getSeoData($region, $dateStr, $isBasePage, $result);
        $finalResult = array_merge($finalResult, $seoData);

        // Set breadcrumb schema
        $this->setBreadcrumbSchema($date, $region, $isBasePage, $finalResult);

        $this->view->setVars($finalResult);
        $this->view->pick("ketquaxs/bydate_" . strtolower($region));
    }

    private function getXSMBData(\DateTime $date, string $dateStr, bool $isLiveToday): array
    {
        if ($isLiveToday) {
            return [
                'result' => null,
                'dau' => array_fill(0, 10, []),
                'duoi' => array_fill(0, 10, []),
                'provinceName' => 'Miền Bắc',
            ];
        }

        $result = $this->getCachedLotteryResult($dateStr);
        $statistics = null;
        if ($result['normalized']) {
            $statistics = LotteryStatisticsHelper::analyzeXSMB($result['normalized']);
        }

        // Tính toán logan data
        $loganData = $this->getLoganData();

        // Tính toán xác suất về cao nhất cho 2 số cuối giải đặc biệt
        $specialPrizeLast2Frequency = $this->getSpecialPrizeLast2Frequency($dateStr);

        return [
            'result' => $result['normalized'] ?? null,
            'dau' => $result['dauDuoi']['dau'] ?? array_fill(0, 10, []),
            'duoi' => $result['dauDuoi']['duoi'] ?? array_fill(0, 10, []),
            'provinceName' => $result['provinceName'] ?? 'Miền Bắc',
            'statistics' => $statistics,
            'loganData' => $loganData,
            'specialPrizeLast2Frequency' => $specialPrizeLast2Frequency,
        ];
    }

    private function getMultiRegionData(\DateTime $date, string $dateStr, string $region, bool $isLiveToday): array
    {
        if ($isLiveToday) {
            $dow = (int)$date->format('N');
            $provinces = Provinces::find([
                'conditions' => "region = :region: AND FIND_IN_SET(:dow:, draw_days)",
                'bind' => ['region' => $region, 'dow' => (string)$dow],
                'order' => 'id ASC',
            ]);

            $provincesForView = [];
            foreach ($provinces as $p) {
                $provincesForView[] = [
                    'id' => (int)$p->id,
                    'name' => (string)$p->name,
                    'code' => (string)$p->code,
                    'keyid' => (string)$p->keyid,
                ];
            }

            $resultsForView = [];
            foreach ($provincesForView as $pv) {
                $resultsForView[$pv['id']] = [
                    'prizes' => null,
                    'dau' => array_fill(0, 10, []),
                ];
            }

            return [
                'provincesForView' => $provincesForView,
                'resultsForView' => $resultsForView,
            ];
        }

        if ($region === 'XSMN') {
            $result = $this->getCachedXSMNResultsForDate($date);
            // Tính toán thống kê
            $result['statistics'] = $this->getMultiRegionStatistics($date, $region);
            return $result;
        } else {
            $result = $this->getCachedXSMTResultsForDate($date);
            // Tính toán thống kê
            $result['statistics'] = $this->getMultiRegionStatistics($date, $region);
            return $result;
        }
    }

    /**
     * Tính toán thống kê cho XSMN/XSMT: giải đặc biệt nhiều ngày, loto về nhiều/ít
     */
    private function getMultiRegionStatistics(\DateTime $date, string $region): array
    {
        // Lấy 30 ngày gần nhất
        $dates = [];
        for ($i = 0; $i < 30; $i++) {
            $checkDate = (clone $date)->modify("-{$i} days");
            $dates[] = $checkDate->format('Y-m-d');
        }

        // Thống kê giải đặc biệt từ nhiều ngày
        $specialPrizeStats = $this->getSpecialPrizeStatistics($region, $dates);

        // Thống kê loto về nhiều/ít cho top 3 tỉnh
        $top3Provinces = array_slice($this->getProvincesForDate($date, $region), 0, 3);
        $lotoStats = [];
        foreach ($top3Provinces as $province) {
            $lotoStats[$province['id']] = [
                'name' => $province['name'],
                'frequent' => $this->getLotoFrequency($region, $province['id'], $dates, 'DESC', 5),
                'rare' => $this->getLotoFrequency($region, $province['id'], $dates, 'ASC', 5),
            ];
        }

        return [
            'specialPrize' => $specialPrizeStats,
            'lotoStats' => $lotoStats,
        ];
    }

    /**
     * Lấy danh sách tỉnh cho một ngày
     */
    private function getProvincesForDate(\DateTime $date, string $region): array
    {
        $dow = (int)$date->format('N');
        $provinces = Provinces::find([
            'conditions' => "region = :region: AND FIND_IN_SET(:dow:, draw_days)",
            'bind' => ['region' => $region, 'dow' => (string)$dow],
            'order' => 'id ASC',
        ]);

        $provincesForView = [];
        foreach ($provinces as $p) {
            $provincesForView[] = [
                'id' => (int)$p->id,
                'name' => (string)$p->name,
                'code' => (string)$p->code,
                'keyid' => (string)$p->keyid,
            ];
        }

        return $provincesForView;
    }

    /**
     * Thống kê giải đặc biệt từ nhiều ngày
     */
    private function getSpecialPrizeStatistics(string $region, array $dates): array
    {
        $results = LotteryResults::find([
            'conditions' => 'draw_type = :type: AND draw_date IN ({dates:array})',
            'bind' => ['type' => $region, 'dates' => $dates],
            'order' => 'draw_date DESC, province_id ASC',
            'limit' => 20,
        ]);

        $stats = [];
        foreach ($results as $result) {
            if (empty($result->special_prize)) continue;

            $province = Provinces::findFirst($result->province_id);
            if (!$province) continue;

            $stats[] = [
                'date' => $result->draw_date,
                'province_code' => $province->code,
                'province_name' => $province->name,
                'special_prize' => $result->special_prize,
            ];
        }

        return $stats;
    }

    /**
     * Tính tần suất loto (2 số cuối) cho một tỉnh từ tất cả các giải
     */
    private function getLotoFrequency(string $region, int $provinceId, array $dates, string $order = 'DESC', int $limit = 5): array
    {
        $results = LotteryResults::find([
            'conditions' => 'draw_type = :type: AND province_id = :pid: AND draw_date IN ({dates:array})',
            'bind' => ['type' => $region, 'pid' => $provinceId, 'dates' => $dates],
            'order' => 'draw_date DESC',
        ]);

        $frequency = [];
        foreach ($results as $result) {
            // Lấy tất cả số 2 chữ số từ tất cả các giải
            $allTwoDigits = $this->extractAllTwoDigitsFromResult($result);
            
            // Đếm frequency (mỗi ngày chỉ đếm 1 lần cho mỗi số)
            $seenToday = [];
            foreach ($allTwoDigits as $loto) {
                if (!isset($seenToday[$loto])) {
                    if (!isset($frequency[$loto])) {
                        $frequency[$loto] = 0;
                    }
                    $frequency[$loto]++;
                    $seenToday[$loto] = true;
                }
            }
        }

        // Sắp xếp
        if ($order === 'DESC') {
            arsort($frequency);
        } else {
            asort($frequency);
        }

        // Format và lấy top
        $top = [];
        $count = 0;
        foreach ($frequency as $loto => $freq) {
            if ($count >= $limit) break;
            $top[] = [
                'loto' => $loto,
                'frequency' => $freq,
            ];
            $count++;
        }

        return $top;
    }

    /**
     * Trích xuất tất cả số 2 chữ số từ một kết quả XSMN/XSMT
     */
    private function extractAllTwoDigitsFromResult($result): array
    {
        $twoDigits = [];
        
        // Từ special_prize và first_prize (5 chữ số) -> lấy 2 số cuối
        if (!empty($result->special_prize) && strlen($result->special_prize) >= 2) {
            $twoDigits[] = substr($result->special_prize, -2);
        }
        if (!empty($result->first_prize) && strlen($result->first_prize) >= 2) {
            $twoDigits[] = substr($result->first_prize, -2);
        }

        // Từ các giải khác (mỗi giải có nhiều số)
        $prizes = [
            'second_prize', 'third_prize', 'fourth_prize', 
            'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'
        ];

        foreach ($prizes as $prize) {
            $prizeData = $result->$prize;
            if (empty($prizeData)) continue;

            $prizeArray = KqxsHelper::toArray($prizeData);
            foreach ($prizeArray as $num) {
                if (strlen($num) >= 2) {
                    $twoDigits[] = substr($num, -2);
                }
            }
        }

        return array_unique($twoDigits);
    }

    private function getCachedLotteryResult(string $dateStr): array
    {
        $result = LotteryResults::findFirst([
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
                'id' => $result->id,
                'special_prize' => (string)$result->special_prize,
                'first_prize' => (string)$result->first_prize,
                'second_prize' => KqxsHelper::toArray($result->second_prize),
                'third_prize' => KqxsHelper::toArray($result->third_prize),
                'fourth_prize' => KqxsHelper::toArray($result->fourth_prize),
                'fifth_prize' => KqxsHelper::toArray($result->fifth_prize),
                'sixth_prize' => KqxsHelper::toArray($result->sixth_prize),
                'seventh_prize' => KqxsHelper::toArray($result->seventh_prize),
                'eighth_prize' => KqxsHelper::toArray($result->eighth_prize),
                'lv' => KqxsHelper::toArray($result->lv),
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

        return $resultData;
    }

    private function getCachedXSMNResultsForDate(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $dow = (int)$date->format('N');

        $provinces = Provinces::find([
            'conditions' => "region = 'XSMN' AND FIND_IN_SET(:dow:, draw_days)",
            'bind' => ['dow' => (string)$dow],
            'order' => 'id ASC',
        ]);

        $provincesForView = [];
        foreach ($provinces as $p) {
            $provincesForView[] = [
                'id' => (int)$p->id,
                'name' => (string)$p->name,
                'code' => (string)$p->code,
                'keyid' => (string)$p->keyid,
            ];
        }

        $resultsForView = [];
        foreach ($provincesForView as $pv) {
            $res = LotteryResults::findFirst([
                'conditions' => 'draw_type=:t: AND draw_date=:d: AND province_id=:pid:',
                'bind' => ['t' => 'XSMN', 'd' => $dateStr, 'pid' => $pv['id']],
                'order' => 'id DESC',
            ]);

            if (!$res) {
                $resultsForView[$pv['id']] = [
                    'prizes' => null,
                    'dau' => array_fill(0, 10, []),
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
                'first_prize' => (string)$res->first_prize,
                'second_prize' => $prizes[3],
                'third_prize' => $prizes[4],
                'fourth_prize' => $prizes[5],
                'fifth_prize' => $prizes[6],
                'sixth_prize' => $prizes[7],
                'seventh_prize' => $prizes[8],
                'eighth_prize' => $prizes[9],
            ];
            $two = KqxsHelper::collectAllTwoDigits($flat);
            $dauDuoi = KqxsHelper::buildDauDuoi($two);

            $resultsForView[$pv['id']] = [
                'prizes' => $prizes,
                'dau' => $dauDuoi['dau'],
            ];
        }

        $resultData = [
            'provincesForView' => $provincesForView,
            'resultsForView' => $resultsForView,
        ];

        return $resultData;
    }

    private function getCachedXSMTResultsForDate(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $dow = (int)$date->format('N');

        $provinces = Provinces::find([
            'conditions' => "region = 'XSMT' AND FIND_IN_SET(:dow:, draw_days)",
            'bind' => ['dow' => (string)$dow],
            'order' => 'id ASC',
        ]);

        $provincesForView = [];
        foreach ($provinces as $p) {
            $provincesForView[] = [
                'id' => (int)$p->id,
                'name' => (string)$p->name,
                'code' => (string)$p->code,
                'keyid' => (string)$p->keyid,
            ];
        }

        $resultsForView = [];
        foreach ($provincesForView as $pv) {
            $res = LotteryResults::findFirst([
                'conditions' => 'draw_type=:t: AND draw_date=:d: AND province_id=:pid:',
                'bind' => ['t' => 'XSMT', 'd' => $dateStr, 'pid' => $pv['id']],
                'order' => 'id DESC',
            ]);

            if (!$res) {
                $resultsForView[$pv['id']] = [
                    'prizes' => null,
                    'dau' => array_fill(0, 10, []),
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
                'first_prize' => (string)$res->first_prize,
                'second_prize' => $prizes[3],
                'third_prize' => $prizes[4],
                'fourth_prize' => $prizes[5],
                'fifth_prize' => $prizes[6],
                'sixth_prize' => $prizes[7],
                'seventh_prize' => $prizes[8],
                'eighth_prize' => $prizes[9],
            ];
            $two = KqxsHelper::collectAllTwoDigits($flat);
            $dauDuoi = KqxsHelper::buildDauDuoi($two);

            $resultsForView[$pv['id']] = [
                'prizes' => $prizes,
                'dau' => $dauDuoi['dau'],
            ];
        }

        $resultData = [
            'provincesForView' => $provincesForView,
            'resultsForView' => $resultsForView,
        ];

        return $resultData;
    }

    private function getCachedPredictions(\DateTime $pageDate, string $region): array
    {
        $target = (clone $pageDate)->modify('+1 day')->format('Y-m-d');

        $allPredictions = PredictionArticles::find([
            'conditions' => 'region IN ({regions:array}) AND prediction_date >= :target:',
            'bind' => [
                'regions' => ['XSMN', 'XSMT', 'XSMB'],
                'target' => $target
            ],
            'order' => 'region ASC, prediction_date ASC, id DESC',
            'limit' => 9
        ]);

        if (count($allPredictions) === 0) {
            $allPredictions = PredictionArticles::find([
                'conditions' => 'region IN ({regions:array})',
                'bind' => ['regions' => ['XSMN', 'XSMT', 'XSMB']],
                'order' => 'prediction_date DESC, id DESC',
                'limit' => 9
            ]);
        }

        $groupedPredictions = [];
        foreach ($allPredictions as $pred) {
            $predRegion = $pred->region;
            if (!isset($groupedPredictions[$predRegion])) {
                $groupedPredictions[$predRegion] = [];
            }
            $groupedPredictions[$predRegion][] = $pred;
        }

        $items = [];
        $categoryConfig = $this->getPredictionCategoryConfig();

        foreach (['XSMN', 'XSMT', 'XSMB'] as $predRegion) {
            $art = $this->selectBestPrediction($groupedPredictions[$predRegion] ?? [], $target);

            if ($art) {
                $items[] = [
                    'region' => $predRegion,
                    'title' => $art->title,
                    'slug' => $art->slug,
                    'url' => $this->buildPredictionUrl($art->slug, $predRegion),
                    'image_url' => $art->image_url ?: '/images/no-image.jpg',
                    'category_href' => $categoryConfig[$predRegion]['href'],
                    'category_label' => $categoryConfig[$predRegion]['label'],
                ];
            }
        }

        return $items;
    }

    private function getCachedOtherLinks(\DateTime $date, string $region): array
    {
        switch ($region) {
            case 'XSMB':
                // Lấy 5 ngày mới nhất từ hôm nay trở về trước (không phải từ ngày được chọn)
                $today = new \DateTime('today', $this->tz);
                $otherLinks = KqxsHelper::otherDaysLinksXSMB($today, 5);
                break;
            case 'XSMN':
                $otherLinks = KqxsHelper::otherDaysLinksXSMN($date, 12);
                break;
            case 'XSMT':
                $otherLinks = KqxsHelper::otherDaysLinksXSMT($date, 12);
                break;
            default:
                $otherLinks = [];
                break;
        }

        return $otherLinks;
    }

    private function getCachedLatestResults(?string $excludeDate, string $region): array
    {
        try {
            $conditions = 'draw_type = :type:';
            $bind = ['type' => $region];
            
            if ($excludeDate) {
                $conditions .= ' AND draw_date != :exclude_date:';
                $bind['exclude_date'] = $excludeDate;
            }

            if ($region === 'XSMB') {
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
                        'id' => $latestResult->id,
                        'special_prize' => (string)$latestResult->special_prize,
                        'first_prize' => (string)$latestResult->first_prize,
                        'second_prize' => KqxsHelper::toArray($latestResult->second_prize),
                        'third_prize' => KqxsHelper::toArray($latestResult->third_prize),
                        'fourth_prize' => KqxsHelper::toArray($latestResult->fourth_prize),
                        'fifth_prize' => KqxsHelper::toArray($latestResult->fifth_prize),
                        'sixth_prize' => KqxsHelper::toArray($latestResult->sixth_prize),
                        'seventh_prize' => KqxsHelper::toArray($latestResult->seventh_prize),
                        'eighth_prize' => KqxsHelper::toArray($latestResult->eighth_prize),
                        'draw_date' => $latestResult->draw_date,
                        'lv' => KqxsHelper::toArray($latestResult->lv),
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

                return $results;
            } else {
                // XSMN/XSMT logic
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
                        'conditions' => "region = :region: AND FIND_IN_SET(:dow:, draw_days)",
                        'bind' => ['region' => $region, 'dow' => (string)$dow],
                        'order' => 'id ASC',
                    ]);

                    $provincesForView = [];
                    foreach ($provinces as $p) {
                        $provincesForView[] = [
                            'id' => (int)$p->id,
                            'name' => (string)$p->name,
                            'code' => (string)$p->code,
                            'keyid' => (string)$p->keyid,
                        ];
                    }

                    $resultsForView = [];
                    foreach ($provincesForView as $pv) {
                        $res = LotteryResults::findFirst([
                            'conditions' => 'draw_type=:t: AND draw_date=:d: AND province_id=:pid:',
                            'bind' => ['t' => $region, 'd' => $latestDate, 'pid' => $pv['id']],
                            'order' => 'id DESC',
                        ]);

                        if (!$res) {
                            $resultsForView[$pv['id']] = [
                                'prizes' => null,
                                'dau' => array_fill(0, 10, []),
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
                            'first_prize' => (string)$res->first_prize,
                            'second_prize' => $prizes[3],
                            'third_prize' => $prizes[4],
                            'fourth_prize' => $prizes[5],
                            'fifth_prize' => $prizes[6],
                            'sixth_prize' => $prizes[7],
                            'seventh_prize' => $prizes[8],
                            'eighth_prize' => $prizes[9],
                        ];
                        $two = KqxsHelper::collectAllTwoDigits($flat);
                        $dauDuoi = KqxsHelper::buildDauDuoi($two);

                        $resultsForView[$pv['id']] = [
                            'prizes' => $prizes,
                            'dau' => $dauDuoi['dau'],
                        ];
                    }

                    $allResults[] = [
                        'provincesForView' => $provincesForView,
                        'resultsForView' => $resultsForView,
                        'draw_date' => $latestDate,
                    ];
                }

                return $allResults;
            }
        } catch (\Exception $e) {
            error_log("Error in getCachedLatestResults {$region}: " . $e->getMessage());
            return [];
        }
    }

    private function getThuLink(string $thuSlug, string $region): string
    {
        $links = [
            'XSMB' => "/xsmb-{$thuSlug}-ket-qua-xo-so-mien-bac.html",
            'XSMN' => "/xsmn-{$thuSlug}-ket-qua-xo-so-mien-nam.html",
            'XSMT' => "/xsmt-{$thuSlug}-ket-qua-xo-so-mien-trung.html",
        ];
        
        return $links[$region] ?? $links['XSMB'];
    }

    private function getSeoData(string $region, string $dateStr, bool $isBasePage, array $result): array
    {
        $seoKeys = [
            'XSMB' => 'lottery_results_base_xsmb',
            'XSMN' => 'lottery_results_base_xsmn',
            'XSMT' => 'lottery_results_base_xsmt',
        ];

        if ($isBasePage) {
            return SeoHelper::getSeoData($seoKeys[$region] ?? $seoKeys['XSMB']);
        }

        // Only pass results for structured data if XSMB (single result format)
        // XSMN/XSMT have multi-province format which doesn't match structured data format
        $resultData = null;
        $provinceName = null;
        
        if ($region === 'XSMB') {
            $resultData = $result['result'] ?? null;
            $provinceName = $result['provinceName'] ?? 'Miền Bắc';
        }

        return PerformanceHelper::generateCachedSeoData('lottery_result', $region, $dateStr, [
            'results' => $resultData,
            'provinceName' => $provinceName
        ], null);
    }

    private function setBreadcrumbSchema(\DateTime $date, string $region, bool $isBasePage, array $finalResult): void
    {
        $this->view->setVar('page_schema_type', 'webpage');
        
        $baseUrls = [
            'XSMB' => '/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html',
            'XSMN' => '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
            'XSMT' => '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
        ];

        $regionNames = [
            'XSMB' => 'Kết quả XSMB',
            'XSMN' => 'Kết quả XSMN',
            'XSMT' => 'Kết quả XSMT',
        ];

        $dateUrlPatterns = [
            'XSMB' => 'xsmb-%d-%d-ket-qua-xo-so-mien-bac-ngay-%d-%d-%d.html',
            'XSMN' => 'xsmn-%d-%d-ket-qua-xo-so-mien-nam-ngay-%d-%d-%d.html',
            'XSMT' => 'xsmt-%d-%d-ket-qua-xo-so-mien-trung-ngay-%d-%d-%d.html',
        ];

        if ($isBasePage) {
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? $regionNames[$region],
                'description' => $finalResult['seo_description'] ?? "Kết quả xổ số {$region} hôm nay",
                'url' => $this->getAbsoluteUrl($baseUrls[$region]),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => $regionNames[$region], 'url' => $this->getAbsoluteUrl($baseUrls[$region])]
                ]
            ]);
        } else {
            $day = (int)$date->format('j');
            $month = (int)$date->format('n');
            $year = (int)$date->format('Y');
            $dateUrl = sprintf($dateUrlPatterns[$region], $day, $month, $day, $month, $year);
            
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? $regionNames[$region],
                'description' => $finalResult['seo_description'] ?? "Kết quả xổ số {$region} hôm nay",
                'url' => $this->getAbsoluteUrl('/' . $dateUrl),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => $regionNames[$region], 'url' => $this->getAbsoluteUrl($baseUrls[$region])],
                    ['name' => $region . ' ' . sprintf('%02d/%02d/%d', $day, $month, $year), 'url' => $this->getAbsoluteUrl('/' . $dateUrl)]
                ]
            ]);
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
        return [
            'XSMN' => ['href' => 'du-doan-xsmn', 'label' => 'Dự đoán XSMN'],
            'XSMT' => ['href' => 'du-doan-xsmt', 'label' => 'Dự đoán XSMT'],
            'XSMB' => ['href' => 'du-doan-xsmb', 'label' => 'Dự đoán XSMB'],
        ];
    }

    private function buildPredictionUrl(string $slug, string $region): string
    {
        $baseUrls = [
            'XSMN' => '/du-doan-',
            'XSMT' => '/du-doan-',
            'XSMB' => '/du-doan-',
        ];

        return ($baseUrls[$region] ?? $baseUrls['XSMB']) . $slug;
    }


    protected function setViewStyles()
    {
        $this->view->customindex = '/css/indexheader.css';
    }

    /**
     * Tính tần suất 2 số cuối giải đặc biệt XSMB (30 ngày gần nhất)
     */
    private function getSpecialPrizeLast2Frequency(string $currentDateStr): array
    {
        // Lấy 30 ngày gần nhất
        $currentDate = new \DateTime($currentDateStr, $this->tz);
        $dates = [];
        for ($i = 0; $i < 30; $i++) {
            $checkDate = (clone $currentDate)->modify("-{$i} days");
            $dates[] = $checkDate->format('Y-m-d');
        }

        $results = LotteryResults::find([
            'conditions' => 'draw_type = :type: AND draw_date IN ({dates:array})',
            'bind' => ['type' => 'XSMB', 'dates' => $dates],
            'order' => 'draw_date DESC',
        ]);

        $frequency = [];
        foreach ($results as $result) {
            if (empty($result->special_prize) || strlen($result->special_prize) < 2) continue;

            $last2 = substr($result->special_prize, -2);
            if (!isset($frequency[$last2])) {
                $frequency[$last2] = 0;
            }
            $frequency[$last2]++;
        }

        // Sắp xếp theo frequency giảm dần
        arsort($frequency);

        // Lấy top 8 (để hiển thị 2 hàng x 4 cột)
        $top = [];
        $count = 0;
        foreach ($frequency as $last2 => $freq) {
            if ($count >= 8) break;
            $top[] = [
                'number' => $last2,
                'frequency' => $freq,
            ];
            $count++;
        }

        return $top;
    }

    /**
     * Tính toán logan data cho XSMB - Top các số lâu chưa về nhất
     */
    private function getLoganData(): array
    {
        // Lấy toàn bộ lịch sử XSMB
        $rows = LotteryResults::find([
            'conditions' => 'draw_type = "XSMB"',
            'order' => 'draw_date ASC',
        ]);

        if (!$rows || count($rows) === 0) {
            return [];
        }

        // Tính streak (số lần chưa về) và last seen date cho mỗi số 00-99
        $streak = [];
        $lastSeen = [];
        $maxStreak = [];

        foreach ($rows as $row) {
            $ymd = $row->draw_date;
            $twoDigits = $this->extractTwoDigits($row);

            // Tạo set các số xuất hiện trong ngày này
            $todaySet = [];
            foreach ($twoDigits as $nn) {
                $todaySet[$nn] = true;
            }

            // Cập nhật streak cho tất cả số 00-99
            for ($i = 0; $i <= 99; $i++) {
                $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                
                if (isset($todaySet[$nn])) {
                    // Số này xuất hiện hôm nay
                    if (!isset($maxStreak[$nn]) || ($streak[$nn] ?? 0) > $maxStreak[$nn]) {
                        $maxStreak[$nn] = $streak[$nn] ?? 0;
                    }
                    $streak[$nn] = 0;
                    $lastSeen[$nn] = $ymd;
                } else {
                    // Số này không xuất hiện, tăng streak
                    $streak[$nn] = ($streak[$nn] ?? 0) + 1;
                }
            }
        }

        // Tạo danh sách và sắp xếp theo streak giảm dần
        $loganList = [];
        for ($i = 0; $i <= 99; $i++) {
            $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $loganList[] = [
                'number' => $nn,
                'streak' => (int)($streak[$nn] ?? 0),
                'last_date' => $lastSeen[$nn] ?? null,
                'max_streak' => (int)($maxStreak[$nn] ?? 0),
            ];
        }

        // Sắp xếp theo streak giảm dần
        usort($loganList, function ($a, $b) {
            if ($a['streak'] === $b['streak']) {
                return strcmp($a['number'], $b['number']);
            }
            return $b['streak'] <=> $a['streak'];
        });

        // Lấy top 10
        return array_slice($loganList, 0, 10);
    }

    /**
     * Trích xuất tất cả số 2 chữ số từ một kết quả XSMB
     */
    private function extractTwoDigits($row): array
    {
        $twoDigits = [];
        
        // Từ special_prize và first_prize (5 chữ số) -> lấy 2 số cuối
        if (!empty($row->special_prize)) {
            $twoDigits[] = substr($row->special_prize, -2);
        }
        if (!empty($row->first_prize)) {
            $twoDigits[] = substr($row->first_prize, -2);
        }

        // Từ các giải khác (mỗi giải có nhiều số)
        $prizes = [
            'second_prize', 'third_prize', 'fourth_prize', 
            'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize', 'lv'
        ];

        foreach ($prizes as $prize) {
            $prizeData = $row->$prize;
            if (empty($prizeData)) continue;

            $prizeArray = KqxsHelper::toArray($prizeData);
            foreach ($prizeArray as $num) {
                if (strlen($num) >= 2) {
                    $twoDigits[] = substr($num, -2);
                }
            }
        }

        return array_unique($twoDigits);
    }
}

