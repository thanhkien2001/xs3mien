<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\VietlottResults;
use App\Models\LotteryResults;
use App\Services\VietlottScraper;
use App\Library\PerformanceHelper;
use App\Library\SchemaHelper;
use App\Library\SoiCauScheduleHelper;
use App\Library\KqxsHelper;

class IndexController extends ControllerBase
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
        
        // Thêm .html nếu chưa có và không phải root
        if ($path !== '/' && substr($path, -1) !== '/' && substr($path, -5) !== '.html') {
            $path .= '.html';
        }
        
        return $baseUrl . $path;
    }

    /**
     * Check if current time is within live window for XSMB
     * XSMB: 18:15 - 18:30
     */
    private function isWithinLiveWindowXSMB(): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        $current = $hour * 60 + $minute;
        
        $start = 18 * 60 + 15; // 18:15
        $end = 18 * 60 + 30;   // 18:30
        
        return $current >= $start && $current < $end;
    }

    /**
     * Check if current time is within live window for XSMT
     * XSMT: 17:15 - 18:00
     */
    private function isWithinLiveWindowXSMT(): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        $current = $hour * 60 + $minute;
        
        $start = 17 * 60 + 15; // 17:15
        $end = 18 * 60;        // 18:00
        
        return $current >= $start && $current < $end;
    }

    /**
     * Check if current time is within live window for XSMN
     * XSMN: 16:15 - 17:00
     */
    private function isWithinLiveWindowXSMN(): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        $current = $hour * 60 + $minute;
        
        $start = 16 * 60 + 15; // 16:15
        $end = 17 * 60;        // 17:00
        
        return $current >= $start && $current < $end;
    }

    public function indexAction()
    {

        $megaResult = VietlottResults::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind'       => ['draw_type' => 'Mega645'],
            'order'      => 'draw_date DESC'
        ]);

        $powerResult = VietlottResults::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind'       => ['draw_type' => 'Power655'],
            'order'      => 'draw_date DESC'
        ]);

        $this->view->setVar('megaResult',  $megaResult);
        $this->view->setVar('powerResult', $powerResult);

        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now     = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $today   = $now->format('Y-m-d');
        $currentHour = (int)$now->format('H');
        $currentMinute = (int)$now->format('i');
        $regions = ['XSMB', 'XSMT', 'XSMN'];

        $kqxsByRegion = [];

        $getLatestDate = function (string $region) use ($today) {
            $phql = "
            SELECT MAX(lr.draw_date) AS draw_date
            FROM App\\Models\\LotteryResults lr
            JOIN App\\Models\\Provinces p ON p.id = lr.province_id
            WHERE p.region = :region: AND lr.draw_date <= :d:
        ";
            $query = $this->modelsManager->createQuery($phql);
            $row = $query->execute(['region' => $region, 'd' => $today])->getFirst();
            return $row ? $row->draw_date : null;
        };

        $getRowsForRegionDate = function (string $region, string $date) {
            $phql = "
            SELECT
                lr.id, lr.province_id, lr.draw_date,
                lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
                lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize,
                lr.lv,
                p.name AS province_name, p.code AS province_code, p.region
            FROM App\\Models\\LotteryResults lr
            JOIN App\\Models\\Provinces p ON p.id = lr.province_id
            WHERE p.region = :region: AND lr.draw_date = :d:
            ORDER BY p.name
        ";
            $query = $this->modelsManager->createQuery($phql);
            $rs = $query->execute(['region' => $region, 'd' => $date]);
            return $rs->toArray();
        };

        foreach ($regions as $region) {
            // Check if in live window for this region
            $isLive = false;
            
            if ($region === 'XSMB') {
                $isLive = $this->isWithinLiveWindowXSMB();
            } elseif ($region === 'XSMT') {
                $isLive = $this->isWithinLiveWindowXSMT();
            } elseif ($region === 'XSMN') {
                $isLive = $this->isWithinLiveWindowXSMN();
            }
            
            $useDate = $getLatestDate($region);
            
            // If in live window, return empty structure for live update
            if ($isLive) {
                error_log("[IndexController] {$region} is LIVE - setting empty rows");
                $kqxsByRegion[$region] = [
                    'date' => $useDate ?? $today,
                    'rows' => [], // Empty rows - will be filled by WebSocket
                    'dauDuoi' => [],
                    'isLive' => true, // Flag to indicate live mode
                ];
            } else {
                // Normal mode: fetch from database
                $rows = $useDate ? $getRowsForRegionDate($region, $useDate) : [];
                
                $dauDuoiByProvince = [];
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $provinceId = $row['province_id'];

                        $twoDigits = \App\Library\KqxsHelper::collectAllTwoDigits($row);
                        $dauDuoi = \App\Library\KqxsHelper::buildDauDuoi($twoDigits);

                        $dauDuoiByProvince[$provinceId] = $dauDuoi;
                    }
                }
                
                $kqxsByRegion[$region] = [
                    'date' => $useDate,
                    'rows' => $rows,
                    'dauDuoi' => $dauDuoiByProvince,
                    'isLive' => false,
                ];
            }
        }
        
        $this->view->setVar('kqxsByRegion', $kqxsByRegion);

        // --- NEW PREDICTION LOGIC ---
        // Logic xác định ngày dự đoán cho từng miền
        $mbPredictionDate = clone $now;
        $mnPredictionDate = clone $now;
        $mtPredictionDate = clone $now;

        // MB: qua 18h30 -> dự đoán ngày tiếp theo
        if ($currentHour > 18 || ($currentHour == 18 && $currentMinute >= 30)) {
            $mbPredictionDate->modify('+1 day');
        }

        // MN: qua 16h30 -> dự đoán ngày tiếp theo
        if ($currentHour > 16 || ($currentHour == 16 && $currentMinute >= 30)) {
            $mnPredictionDate->modify('+1 day');
        }

        // MT: qua 17h30 -> dự đoán ngày tiếp theo
        if ($currentHour > 17 || ($currentHour == 17 && $currentMinute >= 30)) {
            $mtPredictionDate->modify('+1 day');
        }

        // Tính toán soi cầu cho từng miền
        $mbData = $this->calculateSoiCauForRegion('XSMB', $mbPredictionDate);
        $mnData = $this->calculateSoiCauForRegion('XSMN', $mnPredictionDate);
        $mtData = $this->calculateSoiCauForRegion('XSMT', $mtPredictionDate);

        // Lấy danh sách tỉnh quay trong ngày prediction cho từng miền
        $mnProvincesData = $this->getProvincesForPredictionDate('XSMN', $mnPredictionDate);
        $mtProvincesData = $this->getProvincesForPredictionDate('XSMT', $mtPredictionDate);

        $this->view->setVars([
            'currentDate' => $now->format('d/m/Y'),
            'currentTime' => $now->format('H:i'),
            'mbPredictionDate' => $mbPredictionDate->format('d/m/Y'),
            'mnPredictionDate' => $mnPredictionDate->format('d/m/Y'),
            'mtPredictionDate' => $mtPredictionDate->format('d/m/Y'),
            'mbData' => $mbData,
            'mnData' => $mnData,
            'mtData' => $mtData,
            'mnProvinces' => $mnProvincesData,
            'mtProvinces' => $mtProvincesData,
            'base_url' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/'
        ]);
        // --- END PREDICTION LOGIC ---

        $seoData = PerformanceHelper::generateCachedSeoData('homepage', null, null, [
            'kqxsByRegion' => $kqxsByRegion,
            'megaResult' => $megaResult,
            'powerResult' => $powerResult
        ], null);
        
        $this->view->setVars($seoData);
        $this->view->setVar('title', 'Soi cầu xổ số - Dự đoán xổ số 3 miền tỷ lệ trúng cao');
        $this->view->setVar('heading_title', 'Soi cầu xổ số - Dự đoán xổ số 3 miền tỷ lệ trúng cao');

        $this->view->setVar('page_schema_type', 'homepage');
        // $this->view->setVar('page_schema_data', [
        //     'title' => $seoData['seo_title'] ?? 'Xổ Số Kiến Thiết - Kết Quả Xổ Số 3 Miền',
        //     'description' => $seoData['seo_description'] ?? 'Kết quả xổ số kiến thiết 3 miền XSMB, XSMN, XSMT',
        //     'url' => $this->getAbsoluteUrl('/'),
        //     'breadcrumbs' => [
        //         ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]
        //     ]
        // ]);
        
        $this->view->start();
        $this->view->render('index', 'index');
        $this->view->finish();

        $content = $this->view->getContent();

        $this->response->setContent($content);

        return $this->response;
    }
    /**
     * Tính toán soi cầu cho một vùng cụ thể
     */
    private function calculateSoiCauForRegion(string $region, \DateTime $predictionDate): array
    {
        // Lấy kết quả gần nhất cho vùng này (10 ngày)
        $allResults = $this->getRecentResultsByRegion($region, 10);

        // Tính toán các loại cầu
        $results3Days = array_slice($allResults, 0, 3);
        $bachThu = $this->calculateBachThu($results3Days);
        $loKep = $this->calculateLoKepForRegion($results3Days);
        $loXien2 = $this->calculateLoXien2ForRegion($results3Days);
        $loXien3 = $this->calculateLoXien3ForRegion($results3Days);
        $lo3Cang = $this->calculateLo3CangForRegion($results3Days);
        $cauLo4So = $this->calculateCauLo4SoForRegion($results3Days);

        return [
            'bachThu' => array_keys(array_slice($bachThu, 0, 1, true))[0] ?? '00',
            'loKep' => $loKep,
            'loXien2' => $loXien2,
            'loXien3' => $loXien3,
            'lo3Cang' => $lo3Cang,
            'cauLo4So' => $cauLo4So,
            'predictionDate' => $predictionDate->format('d/m/Y')
        ];
    }

    /**
     * Lấy kết quả gần nhất theo vùng
     */
    private function getRecentResultsByRegion(string $region, int $days): array
    {
        $results = [];
        $currentDate = new \DateTime();
        $attempts = 0;
        $maxAttempts = $days * 3;

        while (count($results) < $days && $attempts < $maxAttempts) {
            $dateString = $currentDate->format('Y-m-d');

            // Query kết quả theo vùng
            $result = LotteryResults::findFirst([
                'conditions' => 'draw_date = :date: AND draw_type = :type:',
                'bind' => [
                    'date' => $dateString,
                    'type' => $region
                ]
            ]);

            if ($result) {
                $results[] = [
                    'date' => $currentDate->format('d/m/Y'),
                    'dateObj' => clone $currentDate,
                    'lv' => KqxsHelper::toArray($result->lv),
                    'special_prize' => KqxsHelper::toArray($result->special_prize),
                    'first_prize' => KqxsHelper::toArray($result->first_prize),
                    'second_prize' => KqxsHelper::toArray($result->second_prize),
                    'third_prize' => KqxsHelper::toArray($result->third_prize),
                    'fourth_prize' => KqxsHelper::toArray($result->fourth_prize),
                    'fifth_prize' => KqxsHelper::toArray($result->fifth_prize),
                    'sixth_prize' => KqxsHelper::toArray($result->sixth_prize),
                    'seventh_prize' => KqxsHelper::toArray($result->seventh_prize),
                    'eighth_prize' => KqxsHelper::toArray($result->eighth_prize),
                ];
            }

            $currentDate->modify('-1 day');
            $attempts++;
        }

        return $results;
    }

    /**
     * Tính toán cầu bạch thủ
     */
    private function calculateBachThu(array $results): array
    {
        $cauData = [];

        // Khởi tạo tất cả các số từ 00-99
        for ($i = 0; $i <= 99; $i++) {
            $num = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $cauData[$num] = [
                'count' => 0,
                'positions' => []
            ];
        }

        $globalPosition = 1;

        foreach ($results as $result) {
            $allPrizes = [
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

            foreach ($allPrizes as $prizeKey) {
                if (!empty($result[$prizeKey])) {
                    foreach ($result[$prizeKey] as $prizeNumber) {
                        $lastTwo = substr($prizeNumber, -2);

                        if (isset($cauData[$lastTwo])) {
                            $cauData[$lastTwo]['count']++;
                            $cauData[$lastTwo]['positions'][] = $globalPosition;
                        }

                        $globalPosition++;
                    }
                }
            }
        }

        return $cauData;
    }

    /**
     * Tính toán lô kép cho vùng
     */
    private function calculateLoKepForRegion(array $results): string
    {
        $cauData = $this->calculateBachThu($results);
        $loKep = [];

        for ($i = 0; $i <= 9; $i++) {
            $kep = str_pad((string)($i * 11), 2, '0', STR_PAD_LEFT);
            if (isset($cauData[$kep]) && $cauData[$kep]['count'] > 0) {
                $loKep[] = $kep;
            }
        }

        return !empty($loKep) ? $loKep[0] : '88';
    }

    /**
     * Tính toán lô xiên 2 cho vùng
     */
    private function calculateLoXien2ForRegion(array $results): string
    {
        $cauData = $this->calculateBachThu($results);
        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        $topNumbers = array_keys(array_slice($cauData, 0, 2, true));
        return implode(' - ', $topNumbers);
    }

    /**
     * Tính toán lô xiên 3 cho vùng
     */
    private function calculateLoXien3ForRegion(array $results): string
    {
        $cauData = $this->calculateBachThu($results);
        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        $topNumbers = array_keys(array_slice($cauData, 0, 3, true));
        return implode(' - ', $topNumbers);
    }

    /**
     * Tính toán lô 3 càng cho vùng
     */
    private function calculateLo3CangForRegion(array $results): string
    {
        // Tạo số 3 chữ số ngẫu nhiên nhưng có logic
        $cauData = $this->calculateBachThu($results);
        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        $topNumbers = array_keys(array_slice($cauData, 0, 3, true));
        return implode('', $topNumbers);
    }

    /**
     * Tính toán cầu lô 4 số cho vùng
     */
    private function calculateCauLo4SoForRegion(array $results): string
    {
        $cauData = $this->calculateBachThu($results);
        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        $topNumbers = array_keys(array_slice($cauData, 0, 4, true));
        return implode(' - ', $topNumbers);
    }

    /**
     * Lấy danh sách tỉnh quay trong ngày prediction
     */
    private function getProvincesForPredictionDate(string $region, \DateTime $predictionDate): array
    {
        $predictionDow = (int)$predictionDate->format('N'); // 1-7 (Monday = 1, Sunday = 7)

        $provinces = SoiCauScheduleHelper::getProvincesByDay($region, $predictionDow);

        // Format danh sách tỉnh với URL và title
        $result = [];
        foreach ($provinces as $province) {
            $result[] = [
                'name' => $province,
                'url' => SoiCauScheduleHelper::getSoiCauUrl($province),
                'title' => 'Soi cầu ' . $province
            ];
        }

        return $result;
    }
}
