<?php

namespace App\Library;

/**
 * PredictionHelper - Tính toán cầu lô và cầu đặc biệt dựa trên tần suất thực tế
 */
class PredictionHelper
{
    /**
     * Sinh cầu lô đẹp dựa trên tần suất xuất hiện thực tế
     */
    public static function generateLoDep(array $recentResults, int $count = 10): array
    {
        $allNumbers = self::collectAllTwoDigitNumbers($recentResults);
        
        if (empty($allNumbers)) {
            return self::getDefaultLoDep($count);
        }

        // Đếm tần suất
        $frequency = array_count_values($allNumbers);
        arsort($frequency);

        // Lấy các số xuất hiện nhiều nhất
        $topNumbers = array_keys($frequency);
        
        // Sinh cặp số (số và số đảo)
        $pairs = [];
        $used = [];
        
        foreach ($topNumbers as $num) {
            $numStr = str_pad((string)$num, 2, '0', STR_PAD_LEFT);
            $reversed = strrev($numStr);
            
            // Bỏ qua số đối xứng (00, 11, 22,...) và số đã dùng
            if ($numStr === $reversed) continue;
            if (isset($used[$numStr]) || isset($used[$reversed])) continue;
            
            $pairs[] = "{$numStr},{$reversed}";
            $used[$numStr] = true;
            $used[$reversed] = true;
            
            if (count($pairs) >= $count) {
                break;
            }
        }

        // Nếu không đủ, thêm random
        while (count($pairs) < $count) {
            $rand = mt_rand(0, 99);
            $randStr = str_pad((string)$rand, 2, '0', STR_PAD_LEFT);
            $reversed = strrev($randStr);
            
            if ($randStr !== $reversed && !isset($used[$randStr]) && !isset($used[$reversed])) {
                $pairs[] = "{$randStr},{$reversed}";
                $used[$randStr] = true;
                $used[$reversed] = true;
            }
        }

        return $pairs;
    }

    /**
     * Sinh cầu đặc biệt - Tập trung vào giải ĐẶC BIỆT và G1
     * Logic khác với cầu lô: Chỉ phân tích các giải lớn (ĐB, G1, G2)
     */
    public static function generateDacBietDep(array $recentResults, int $count = 10): array
    {
        $specialNumbers = [];
        
        // Chỉ lấy số từ các giải lớn (prizes[1], prizes[2], prizes[3])
        foreach ($recentResults as $result) {
            if (!isset($result['prizes']) || !is_array($result['prizes'])) {
                continue;
            }
            
            // prizes[1] = ĐB, prizes[2] = G1, prizes[3] = G2
            for ($i = 1; $i <= 3; $i++) {
                if (!isset($result['prizes'][$i])) continue;
                
                $prizeGroup = $result['prizes'][$i];
                if (!is_array($prizeGroup)) {
                    $prizeGroup = [$prizeGroup];
                }
                
                foreach ($prizeGroup as $prize) {
                    if (is_string($prize) && strlen($prize) >= 2) {
                        $twoDigit = substr($prize, -2);
                        $specialNumbers[] = (int)$twoDigit;
                    }
                }
            }
        }
        
        if (empty($specialNumbers)) {
            return self::getDefaultDacBiet($count);
        }

        // Đếm tần suất
        $frequency = array_count_values($specialNumbers);
        arsort($frequency);

        // Lấy TOP số từ giải đặc biệt
        $topNumbers = array_keys($frequency);
        
        // Sinh cặp số (khác với lô đẹp - dùng +1/-1 hoặc doubling pattern)
        $pairs = [];
        $used = [];
        
        foreach ($topNumbers as $num) {
            $numStr = str_pad((string)$num, 2, '0', STR_PAD_LEFT);
            
            // Strategy 1: Đảo ngược
            $reversed = strrev($numStr);
            
            if ($numStr !== $reversed && !isset($used[$numStr]) && !isset($used[$reversed])) {
                $pairs[] = "{$numStr},{$reversed}";
                $used[$numStr] = true;
                $used[$reversed] = true;
            }
            
            if (count($pairs) >= $count) {
                break;
            }
        }

        // Strategy 2: Nếu không đủ, dùng số đẹp (kép, đối xứng với ±1)
        if (count($pairs) < $count) {
            $beautifulPairs = [
                '06,60', '07,70', '08,80', '09,90',
                '16,61', '17,71', '18,81', '19,91',
                '26,62', '27,72', '28,82', '29,92',
                '36,63', '37,73', '38,83', '39,93',
                '46,64', '47,74', '48,84', '49,94'
            ];
            
            foreach ($beautifulPairs as $pair) {
                list($n1, $n2) = explode(',', $pair);
                if (!isset($used[$n1]) && !isset($used[$n2])) {
                    $pairs[] = $pair;
                    $used[$n1] = true;
                    $used[$n2] = true;
                    
                    if (count($pairs) >= $count) {
                        break;
                    }
                }
            }
        }

        return $pairs;
    }

    /**
     * Thu thập TẤT CẢ các số 2 chữ số từ kết quả
     */
    private static function collectAllTwoDigitNumbers(array $recentResults): array
    {
        $allNumbers = [];
        
        foreach ($recentResults as $result) {
            if (!isset($result['prizes']) || !is_array($result['prizes'])) {
                continue;
            }
            
            foreach ($result['prizes'] as $prizeGroup) {
                if (!is_array($prizeGroup)) {
                    // Nếu là single value (string)
                    $prizeGroup = [$prizeGroup];
                }
                
                foreach ($prizeGroup as $prize) {
                    if (is_string($prize) && strlen($prize) >= 2) {
                        $twoDigit = substr($prize, -2);
                        $allNumbers[] = (int)$twoDigit;
                    }
                }
            }
        }
        
        return $allNumbers;
    }

    /**
     * Trả về ngày mai
     */
    public static function getTomorrowDate(): string
    {
        return date('d/m/Y', strtotime('+1 day'));
    }

    /**
     * Trả về hôm nay
     */
    public static function getTodayDate(): string
    {
        return date('d/m/Y');
    }

    /**
     * Default lô đẹp (fallback)
     */
    private static function getDefaultLoDep(int $count): array
    {
        $defaults = [
            '07,70', '18,81', '29,92', '36,63', '45,54',
            '17,71', '28,82', '39,93', '46,64', '58,85'
        ];
        return array_slice($defaults, 0, $count);
    }

    /**
     * Default đặc biệt (fallback)
     */
    private static function getDefaultDacBiet(int $count): array
    {
        $defaults = [
            '05,50', '16,61', '27,72', '38,83', '49,94',
            '06,60', '17,71', '28,82', '39,93', '48,84'
        ];
        return array_slice($defaults, 0, $count);
    }
}
