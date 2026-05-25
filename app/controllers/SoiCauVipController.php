<?php

namespace App\Controllers;

use App\Models\LotteryResults;
use App\Models\PredictionArticles;
use App\Library\KqxsHelper;
use App\Library\ArchiveHelper;
use App\Library\SoiCauScheduleHelper;
use Phalcon\Mvc\Controller;

/**
 * SoiCauVipController - Soi cầu VIP XSMB
 *
 * Trang soi cầu VIP cho xổ số miền Bắc với các dự đoán chuyên nghiệp
 */
class SoiCauVipController extends Controller
{
    /**
     * Index action - Hiển thị trang soi cầu VIP XSMB
     */
    public function indexAction()
    {
        // Đảm bảo timezone đúng
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $currentHour = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');

        // Nếu đã qua 18h30 thì dự đoán cho ngày tiếp theo
        if ($currentHour > 18 || ($currentHour == 18 && $currentMinute >= 30)) {
            $now->modify('+1 day');
        }

        // Lấy ngày hiện tại (đã điều chỉnh)
        $today = $now;
        $yesterday = clone $today;
        $yesterday->modify('-1 day');

        // Lấy kết quả XSMB trong 10 ngày gần nhất để tính toán
        $allResults = $this->getRecentXSMBResults($today, 10);

        // Lấy kết quả ngày hôm qua (kỳ quay trước)
        $yesterdayResult = null;
        if (!empty($allResults) && count($allResults) > 0) {
            $yesterdayResult = $allResults[0]; // Kết quả mới nhất
        }

        // 1. Tính toán cầu bạch thủ VIP (biên độ 3 ngày)
        $results3Days = array_slice($allResults, 0, 3);
        $bachThuVipData = $this->calculateBachThu($results3Days);
        $bachThuVipFiltered = $this->filterNumbersByBienDo($bachThuVipData, 3);

        // 2. Tính toán 3 càng VIP (biên độ 3 ngày)
        $baCangVipData = $this->calculateBaCang($results3Days);

        // 3. Tính toán dàn đề VIP (biên độ 3 ngày)
        $danDeVipData = $this->calculateDanDe($results3Days);

        // 4. Tính toán đặc biệt VIP (logic phức tạp hơn)
        $dacBietVipData = $this->calculateDacBietVip($allResults);

        // 5. Tính toán lô 2 nháy VIP
        $loHaiNhayVipData = $this->calculateLoHaiNhayVip($allResults);

        // 6. Tính toán lô kép VIP
        $loKepVipData = $this->calculateLoKepVip($allResults);

        // 7. Tính toán dàn lô xiên 3 VIP
        $danLoXienVipData = $this->calculateDanLoXienVip($allResults);

        // 8. Tính toán thống kê lô gan XSMB
        $loGanStats = $this->calculateLoGanStats($allResults);

        // Generate dynamic SEO data
        $cache = $this->di->get('modelsCache');
        $seoData = \App\Library\PerformanceHelper::generateCachedSeoData('custom', 'soicauvip', null, [
            'regionName' => 'Miền Bắc',
            'regionCode' => 'XSMB'
        ], $cache);

        $this->view->setVars($seoData);

        // Prepare view data
        $this->view->setVars([
            'today' => $today->format('d/m/Y'),
            'todayObj' => $today,
            'yesterday' => $yesterday->format('d/m/Y'),
            'yesterdayResult' => $yesterdayResult,
            'allResults' => $allResults,

            // Dữ liệu VIP
            'bachThuVip' => $bachThuVipFiltered,
            'baCangVip' => $baCangVipData,
            'danDeVip' => $danDeVipData,
            'dacBietVip' => $dacBietVipData,
            'loHaiNhayVip' => $loHaiNhayVipData,
            'loKepVip' => $loKepVipData,
            'danLoXienVip' => $danLoXienVipData,
            'loGanStats' => $loGanStats,

            'base_url' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/'
        ]);

        // Set view
        $this->view->pick('soicauvip/soi_cau_vip');
    }

    /**
     * Soi cầu xổ số 3 miền action - /soi-cau-xo-so.html
     */
    public function soiCauXoSoAction()
    {
        // Đảm bảo timezone đúng
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $currentHour = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');

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

        $cache = $this->di->get('modelsCache');
        $cache = $this->di->get('modelsCache');
        $seoData = \App\Library\PerformanceHelper::generateCachedSeoData('custom', 'soicauvip', null, [
            'regionName' => 'Miền Bắc',
            'regionCode' => 'XSMB'
        ], $cache);

        $this->view->setVars($seoData);

        // Prepare view data
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



            'pageTitle' => 'Soi cầu xổ số - Dự đoán xổ số 3 miền tỷ lệ trúng cao',
            'pageDescription' => 'Soi cầu xổ số 3 miền hôm nay cung cấp những con số chính xác nhất và hoàn toàn miễn phí. Những con số này sẽ giúp anh em tìm ra những con số may mắn.',
            'base_url' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/'
        ]);

        // Set view
        $this->view->pick('soicauvip/soi_cau_xo_so');
    }

    /**
     * Lấy kết quả XSMB trong N ngày gần nhất
     */
    private function getRecentXSMBResults(\DateTime $endDate, int $days): array
    {
        $results = [];
        $currentDate = clone $endDate;

        // Lùi lại để lấy đủ số ngày
        $attempts = 0;
        $maxAttempts = $days * 3;

        while (count($results) < $days && $attempts < $maxAttempts) {
            $dateString = $currentDate->format('Y-m-d');

            // Query kết quả XSMB
            $result = LotteryResults::findFirst([
                'conditions' => 'draw_date = :date: AND draw_type = :type:',
                'bind' => [
                    'date' => $dateString,
                    'type' => 'XSMB'
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

            // Lùi lại 1 ngày
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
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
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
     * Lọc số theo biên độ
     */
    private function filterNumbersByBienDo(array $cauData, int $bienDo): array
    {
        $activeNumbers = [];
        foreach ($cauData as $num => $info) {
            if ($info['count'] > 0) {
                $activeNumbers[$num] = $info;
            }
        }

        // Sắp xếp theo count giảm dần
        uasort($activeNumbers, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Lọc theo biên độ
        $threshold = match ($bienDo) {
            3 => 1, // Biên độ 3: lấy tất cả số xuất hiện >= 1 lần
            4 => 2, // Biên độ 4: lấy số xuất hiện >= 2 lần
            5 => 2, // Biên độ 5: lấy số xuất hiện >= 2 lần
            default => 1
        };

        $filtered = [];
        foreach ($activeNumbers as $num => $info) {
            if ($info['count'] >= $threshold) {
                $filtered[$num] = $info;
            }
        }

        // Sắp xếp lại theo số
        ksort($filtered);

        return $filtered;
    }

    /**
     * Tính toán 3 càng VIP
     */
    private function calculateBaCang(array $results): array
    {
        // Logic đơn giản: chọn các số xuất hiện nhiều nhất và tạo thành các bộ 3
        $cauData = $this->calculateBachThu($results);

        // Lấy top số xuất hiện nhiều nhất
        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        $topNumbers = array_keys(array_slice($cauData, 0, 6, true)); // Lấy 6 số top

        // Tạo thành các bộ 3 càng
        return [
            implode(' - ', array_slice($topNumbers, 0, 3)),
            implode(' - ', array_slice($topNumbers, 3, 3))
        ];
    }

    /**
     * Tính toán dàn đề VIP
     */
    private function calculateDanDe(array $results): array
    {
        // Logic đơn giản: chọn 4 số đẹp từ kết quả
        $cauData = $this->calculateBachThu($results);

        // Lấy top số xuất hiện nhiều nhất
        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        $topNumbers = array_keys(array_slice($cauData, 0, 4, true));

        return $topNumbers;
    }

    /**
     * Tính toán đặc biệt VIP
     */
    private function calculateDacBietVip(array $allResults): array
    {
        // Dự đoán đầu và đuôi đặc biệt VIP
        // Logic đơn giản dựa trên thống kê
        $yesterdayResult = $allResults[0] ?? null;

        if (!$yesterdayResult) {
            return ['dau' => '0', 'duoi' => '0'];
        }

        // Lấy đề về hôm qua
        $deVe = substr($yesterdayResult['special_prize'][0] ?? '00', -2);

        // Dự đoán đơn giản: đầu + 1, đuôi + 1
        $dauMoi = (intval($deVe[0]) + 1) % 10;
        $duoiMoi = (intval($deVe[1]) + 1) % 10;

        return [
            'dau' => $dauMoi,
            'duoi' => $duoiMoi
        ];
    }

    /**
     * Tính toán lô 2 nháy VIP
     */
    private function calculateLoHaiNhayVip(array $allResults): array
    {
        // Lấy top 2 số xuất hiện nhiều nhất trong 3 ngày gần nhất
        $results3Days = array_slice($allResults, 0, 3);
        $cauData = $this->calculateBachThu($results3Days);

        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_keys(array_slice($cauData, 0, 2, true));
    }

    /**
     * Tính toán lô kép VIP
     */
    private function calculateLoKepVip(array $allResults): array
    {
        // Chọn các số kép (00, 11, 22, etc.) xuất hiện nhiều nhất
        $results3Days = array_slice($allResults, 0, 3);
        $cauData = $this->calculateBachThu($results3Days);

        $loKep = [];
        for ($i = 0; $i <= 9; $i++) {
            $kep = str_pad($i * 11, 2, '0', STR_PAD_LEFT);
            if (isset($cauData[$kep]) && $cauData[$kep]['count'] > 0) {
                $loKep[$kep] = $cauData[$kep];
            }
        }

        // Sắp xếp theo count giảm dần
        uasort($loKep, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_keys(array_slice($loKep, 0, 2, true));
    }

    /**
     * Tính toán dàn lô xiên 3 VIP
     */
    private function calculateDanLoXienVip(array $allResults): array
    {
        // Chọn 3 số xuất hiện nhiều nhất
        $results3Days = array_slice($allResults, 0, 3);
        $cauData = $this->calculateBachThu($results3Days);

        uasort($cauData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_keys(array_slice($cauData, 0, 3, true));
    }

    /**
     * Tính toán thống kê lô gan XSMB
     */
    private function calculateLoGanStats(array $allResults): array
    {
        // Tính số ngày chưa về cho từng số (so với 60 ngày gần nhất)
        $recentResults = array_slice($allResults, 0, 60);
        $lastAppeared = [];

        foreach ($recentResults as $resultIndex => $result) {
            $allPrizes = [
                'special_prize', 'first_prize', 'second_prize', 'third_prize',
                'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'
            ];

            foreach ($allPrizes as $prizeKey) {
                if (!empty($result[$prizeKey])) {
                    foreach ($result[$prizeKey] as $prizeNumber) {
                        $lastTwo = substr($prizeNumber, -2);
                        if (!isset($lastAppeared[$lastTwo])) {
                            $lastAppeared[$lastTwo] = $resultIndex;
                        }
                    }
                }
            }
        }

        // Tạo danh sách top lô gan
        $ganStats = [];
        for ($i = 0; $i <= 99; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $missDays = isset($lastAppeared[$num]) ? $lastAppeared[$num] : count($recentResults);
            $ganStats[$num] = [
                'miss_days' => $missDays,
                'last_date' => isset($lastAppeared[$num]) ? $recentResults[$lastAppeared[$num]]['date'] : null,
                'max_gan' => rand(20, 35) // Mock data cho max gan
            ];
        }

        // Sắp xếp theo số ngày miss giảm dần
        uasort($ganStats, function ($a, $b) {
            return $b['miss_days'] - $a['miss_days'];
        });

        return array_slice($ganStats, 0, 7, true);
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
     * Tính toán lô kép cho vùng
     */
    private function calculateLoKepForRegion(array $results): string
    {
        $cauData = $this->calculateBachThu($results);
        $loKep = [];

        for ($i = 0; $i <= 9; $i++) {
            $kep = str_pad($i * 11, 2, '0', STR_PAD_LEFT);
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