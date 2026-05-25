<?php
declare(strict_types=1);

namespace App\Library;

/**
 * Lottery Statistics Helper - Tính toán thống kê xổ số
 */
class LotteryStatisticsHelper
{
    /**
     * Phân tích thống kê kết quả XSMB
     * 
     * @param array $result Kết quả xổ số (special_prize, first_prize, ..., eighth_prize)
     * @return array
     */
    public static function analyzeXSMB(array $result): array
    {
        $allNumbers = self::collectAllNumbers($result);
        
        return [
            'bach_thu_de' => self::getBachThuDe($result),
            'lo_lon_cap' => self::getLoLonCap($allNumbers),
            'lo_kep' => self::getLoKep($allNumbers),
            'lo_2_nhay' => self::getLo2Nhay($allNumbers),
            'lo_3_nhay' => self::getLo3Nhay($allNumbers),
            'dau_cam' => self::getDauCam($allNumbers),
            'duoi_cam' => self::getDuoiCam($allNumbers),
            'dau_ve_nhieu' => self::getDauVeNhieu($allNumbers),
            'duoi_ve_nhieu' => self::getDuoiVeNhieu($allNumbers),
        ];
    }

    /**
     * Lấy bạch thủ đề (số đặc biệt)
     */
    private static function getBachThuDe(array $result): array
    {
        $specialPrize = $result['special_prize'] ?? '';
        if (empty($specialPrize)) {
            return ['dau' => '', 'duoi' => '', 'tong' => ''];
        }

        $last2Digits = substr($specialPrize, -2);
        $dau = (int)substr($last2Digits, 0, 1);
        $duoi = (int)substr($last2Digits, 1, 1);
        $tong = $dau + $duoi;

        return [
            'dau' => (string)$dau,
            'duoi' => (string)$duoi,
            'tong' => (string)$tong,
            'number' => $last2Digits,
        ];
    }

    /**
     * Thu thập tất cả các số 2 chữ số cuối
     */
    private static function collectAllNumbers(array $result): array
    {
        $numbers = [];
        $prizeFields = [
            'eighth_prize', 'seventh_prize', 'sixth_prize', 
            'fifth_prize', 'fourth_prize', 'third_prize', 
            'second_prize', 'first_prize', 'special_prize'
        ];

        foreach ($prizeFields as $field) {
            if (empty($result[$field])) continue;

            // Handle both string and array
            if (is_array($result[$field])) {
                $prizes = $result[$field];
            } else {
                $prizes = LotteryHelper::cleanPrizeData($result[$field]);
            }
            
            foreach ($prizes as $prize) {
                $prize = trim((string)$prize);
                if (strlen($prize) >= 2) {
                    $numbers[] = substr($prize, -2);
                }
            }
        }

        return $numbers;
    }

    /**
     * Lấy lô lộn về cả cặp (VD: 17 - 71)
     */
    private static function getLoLonCap(array $numbers): array
    {
        $pairs = [];
        $checked = [];

        foreach ($numbers as $num) {
            if (in_array($num, $checked)) continue;

            $reversed = strrev($num);
            if (in_array($reversed, $numbers) && $num !== $reversed) {
                $pair = [$num, $reversed];
                sort($pair);
                $pairKey = implode('-', $pair);
                
                if (!isset($pairs[$pairKey])) {
                    $pairs[$pairKey] = $pair;
                    $checked[] = $num;
                    $checked[] = $reversed;
                }
            }
        }

        return array_values($pairs);
    }

    /**
     * Lấy lô kép (00, 11, 22, ...)
     */
    private static function getLoKep(array $numbers): array
    {
        $kepNumbers = [];
        foreach ($numbers as $num) {
            if ($num[0] === $num[1]) {
                $kepNumbers[] = $num;
            }
        }
        return array_unique($kepNumbers);
    }

    /**
     * Lấy lô 2 nháy (số xuất hiện 2 lần)
     */
    private static function getLo2Nhay(array $numbers): array
    {
        $counts = array_count_values($numbers);
        $result = [];
        
        foreach ($counts as $num => $count) {
            if ($count === 2) {
                $result[] = $num;
            }
        }
        
        return $result;
    }

    /**
     * Lấy lô 3 nháy (số xuất hiện 3 lần)
     */
    private static function getLo3Nhay(array $numbers): array
    {
        $counts = array_count_values($numbers);
        $result = [];
        
        foreach ($counts as $num => $count) {
            if ($count >= 3) {
                $result[] = $num;
            }
        }
        
        return $result;
    }

    /**
     * Lấy đầu câm (đầu không về số nào)
     */
    private static function getDauCam(array $numbers): array
    {
        $dauCounts = [];
        for ($i = 0; $i <= 9; $i++) {
            $dauCounts[$i] = 0;
        }

        foreach ($numbers as $num) {
            $dau = (int)substr($num, -2, 1);
            $dauCounts[$dau]++;
        }

        $result = [];
        foreach ($dauCounts as $dau => $count) {
            if ($count === 0) {
                $result[] = (string)$dau;
            }
        }

        return $result;
    }

    /**
     * Lấy đuôi câm (đuôi không về số nào)
     */
    private static function getDuoiCam(array $numbers): array
    {
        $duoiCounts = [];
        for ($i = 0; $i <= 9; $i++) {
            $duoiCounts[$i] = 0;
        }

        foreach ($numbers as $num) {
            $duoi = (int)substr($num, -1);
            $duoiCounts[$duoi]++;
        }

        $result = [];
        foreach ($duoiCounts as $duoi => $count) {
            if ($count === 0) {
                $result[] = (string)$duoi;
            }
        }

        return $result;
    }

    /**
     * Lấy đầu về nhiều nhất
     */
    private static function getDauVeNhieu(array $numbers): array
    {
        $dauCounts = [];
        for ($i = 0; $i <= 9; $i++) {
            $dauCounts[$i] = 0;
        }

        foreach ($numbers as $num) {
            $dau = (int)substr($num, -2, 1);
            $dauCounts[$dau]++;
        }

        $maxCount = max($dauCounts);
        $result = [];
        
        foreach ($dauCounts as $dau => $count) {
            if ($count === $maxCount && $count > 0) {
                $result[] = (string)$dau;
            }
        }

        return $result;
    }

    /**
     * Lấy đuôi về nhiều nhất
     */
    private static function getDuoiVeNhieu(array $numbers): array
    {
        $duoiCounts = [];
        for ($i = 0; $i <= 9; $i++) {
            $duoiCounts[$i] = 0;
        }

        foreach ($numbers as $num) {
            $duoi = (int)substr($num, -1);
            $duoiCounts[$duoi]++;
        }

        $maxCount = max($duoiCounts);
        $result = [];
        
        foreach ($duoiCounts as $duoi => $count) {
            if ($count === $maxCount && $count > 0) {
                $result[] = (string)$duoi;
            }
        }

        return $result;
    }

    /**
     * Format kết quả thống kê để hiển thị
     */
    public static function formatStatistics(array $stats): string
    {
        $html = '';
        
        // Bạch thủ đề
        if (!empty($stats['bach_thu_de']['number'])) {
            $html .= '<p>- Bạch thủ đề: Đầu <span style="color: #ff0000;">' . 
                     htmlspecialchars($stats['bach_thu_de']['dau']) . 
                     '</span> đuôi <span style="color: #ff0000;">' . 
                     htmlspecialchars($stats['bach_thu_de']['duoi']) . 
                     '</span>. Tổng <span style="color: #ff0000;">' . 
                     htmlspecialchars($stats['bach_thu_de']['tong']) . '</span></p>';
        }

        // Lô lộn cặp
        if (!empty($stats['lo_lon_cap'])) {
            $pairs = array_map(function($pair) {
                return implode(' - ', $pair);
            }, $stats['lo_lon_cap']);
            $html .= '<p>- Lô tô lộn về cả cặp: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(', ', $pairs)) . '</span></p>';
        } else {
            $html .= '<p>- Lô tô lộn về cả cặp: Không có</p>';
        }

        // Lô kép
        if (!empty($stats['lo_kep'])) {
            $html .= '<p>- Lô kép: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(' - ', $stats['lo_kep'])) . '</span></p>';
        } else {
            $html .= '<p>- Lô kép: Không có</p>';
        }

        // Lô 2 nháy
        if (!empty($stats['lo_2_nhay'])) {
            $html .= '<p>- Lô 2 nháy: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(' - ', $stats['lo_2_nhay'])) . '</span></p>';
        } else {
            $html .= '<p>- Lô 2 nháy: Không có</p>';
        }

        // Lô 3 nháy
        if (!empty($stats['lo_3_nhay'])) {
            $html .= '<p>- Lô 3 nháy: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(' - ', $stats['lo_3_nhay'])) . '</span></p>';
        } else {
            $html .= '<p>- Lô 3 nháy: Không có</p>';
        }

        // Đầu câm
        if (!empty($stats['dau_cam'])) {
            $html .= '<p>- Đầu câm: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(', ', $stats['dau_cam'])) . '</span></p>';
        } else {
            $html .= '<p>- Đầu câm: Không có</p>';
        }

        // Đuôi câm
        if (!empty($stats['duoi_cam'])) {
            $html .= '<p>- Đuôi câm: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(', ', $stats['duoi_cam'])) . '</span></p>';
        } else {
            $html .= '<p>- Đuôi câm: Không có</p>';
        }

        // Đầu về nhiều nhất
        if (!empty($stats['dau_ve_nhieu'])) {
            $html .= '<p>- Đầu về nhiều nhất: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(', ', $stats['dau_ve_nhieu'])) . '</span></p>';
        }

        // Đuôi về nhiều nhất
        if (!empty($stats['duoi_ve_nhieu'])) {
            $html .= '<p>- Đuôi về nhiều nhất: <span style="color: #ff0000;">' . 
                     htmlspecialchars(implode(', ', $stats['duoi_ve_nhieu'])) . '</span></p>';
        }

        return $html;
    }
}

