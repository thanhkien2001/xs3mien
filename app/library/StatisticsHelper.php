<?php

namespace App\Library;

use App\Models\VietlottResults;

class StatisticsHelper
{
    /**
     * Tạo cache key chuẩn cho thống kê Vietlott
     */
    public static function buildCacheKey(string $drawType, string $action, array $params = []): string
    {
        $keyParts = [$drawType, $action];
        
        // Thêm các tham số vào key và sanitize
        ksort($params); // Đảm bảo thứ tự nhất quán
        foreach ($params as $key => $value) {
            // Sanitize key and value to remove invalid characters
            $cleanKey = preg_replace('/[^a-zA-Z0-9]/', '', $key);
            $cleanValue = preg_replace('/[^a-zA-Z0-9]/', '', (string)$value);
            $keyParts[] = $cleanKey . $cleanValue;
        }
        
        // Create final key and ensure it's valid for Phalcon cache
        $finalKey = implode('_', $keyParts);
        return preg_replace('/[^a-zA-Z0-9_]/', '', $finalKey);
    }

    /**
     * Tính cache lifetime thông minh dựa trên ngày
     */
    public static function getSmartCacheLifetime(array $dataWithDate, string $dateField = 'draw_date'): int
    {
        if (empty($dataWithDate)) return 86000; // 5 phút nếu không có dữ liệu

        // Lấy ngày mới nhất từ dữ liệu
        $latestDate = null;
        foreach ($dataWithDate as $item) {
            $date = is_array($item) ? ($item[$dateField] ?? null) : ($item->$dateField ?? null);
            if ($date) {
                if (!$latestDate || $date > $latestDate) {
                    $latestDate = $date;
                }
            }
        }

        if (!$latestDate) return 86000;

        $today = date('Y-m-d');
        $isToday = (strpos($latestDate, $today) === 0);

        if ($isToday) {
            // Dữ liệu của hôm nay: cache ngắn hơn (30 phút)
            return 86000;
        } else {
            // Dữ liệu cũ: cache lâu hơn (6 tiếng)
            return 86000;
        }
    }

    /**
     * Lấy dữ liệu thống kê
     */
    public static function getStatisticsData(string $drawType, int $maxNumber, int $limit = 100): array
    {
        // Lấy dữ liệu từ database
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => $drawType],
            'order' => 'draw_date DESC',
            'limit' => $limit,
        ]);

        if (count($results) == 0) {
            return [
                'results' => [],
                'error' => "Không tìm thấy dữ liệu thống kê {$drawType}. Vui lòng kiểm tra cơ sở dữ liệu."
            ];
        }

        // Xử lý dữ liệu
        $processedData = self::processStatisticsData($results, $maxNumber);
        
        return $processedData;
    }

    /**
     * Xử lý dữ liệu thống kê
     */
    private static function processStatisticsData($results, int $maxNumber): array
    {
        $numberFrequency = [];
        $specialNumberFrequency = [];
        $triplets = [];
        $pairs = [];
        $consecutivePairs = [];

        // Khởi tạo mảng tần suất
        for ($i = 1; $i <= $maxNumber; $i++) {
            $numberFrequency[str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
            $specialNumberFrequency[str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
        }

        // Tính tần suất
        foreach ($results as $result) {
            if (empty($result->numbers)) {
                continue;
            }

            $numbers = explode(',', trim($result->numbers));
            $mainNumbers = array_slice($numbers, 0, 6);
            $specialNumber = isset($numbers[6]) ? trim($numbers[6]) : null;

            // Tần suất số chính
            foreach ($mainNumbers as $num) {
                $num = trim($num);
                if ($num && is_numeric($num) && $num >= 1 && $num <= $maxNumber) {
                    $numberFrequency[str_pad((int)$num, 2, '0', STR_PAD_LEFT)]++;
                }
            }

            // Tần suất số đặc biệt
            if ($specialNumber && is_numeric($specialNumber) && $specialNumber >= 1 && $specialNumber <= $maxNumber) {
                $specialNumberFrequency[str_pad((int)$specialNumber, 2, '0', STR_PAD_LEFT)]++;
            }

            // Thống kê bộ 3 số
            $combinations = self::getCombinations($mainNumbers, 3);
            foreach ($combinations as $combo) {
                $key = implode(',', $combo);
                $triplets[$key] = ($triplets[$key] ?? 0) + 1;
            }

            // Thống kê cặp số
            $pairCombinations = self::getCombinations($mainNumbers, 2);
            foreach ($pairCombinations as $pair) {
                $key = implode(',', $pair);
                $pairs[$key] = ($pairs[$key] ?? 0) + 1;

                // Kiểm tra cặp số liên tiếp
                $pairNums = array_map('intval', $pair);
                sort($pairNums);
                if (count($pairNums) == 2 && $pairNums[1] - $pairNums[0] === 1) {
                    $consecutiveKey = implode(',', $pairNums);
                    $consecutivePairs[$consecutiveKey] = ($consecutivePairs[$consecutiveKey] ?? 0) + 1;
                }
            }
        }

        // Sắp xếp và lấy top
        arsort($numberFrequency);
        $top20Numbers = array_slice($numberFrequency, 0, 20, true);
        $bottom20Numbers = array_slice(array_reverse($numberFrequency), 0, 20, true);

        arsort($specialNumberFrequency);
        $top20SpecialNumbers = array_slice($specialNumberFrequency, 0, 20, true);

        arsort($triplets);
        $topTriplets = array_slice($triplets, 0, 10, true);

        arsort($pairs);
        $top20Pairs = array_slice($pairs, 0, 20, true);

        arsort($consecutivePairs);
        $top20ConsecutivePairs = array_slice($consecutivePairs, 0, 20, true);

        return [
            'results' => $results,
            'numberFrequency' => $numberFrequency,
            'top20Numbers' => $top20Numbers,
            'bottom20Numbers' => $bottom20Numbers,
            'top20SpecialNumbers' => $top20SpecialNumbers,
            'topTriplets' => $topTriplets,
            'top20Pairs' => $top20Pairs,
            'top20ConsecutivePairs' => $top20ConsecutivePairs,
            'consecutivePairs' => $consecutivePairs
        ];
    }

    /**
     * Lấy top jackpots
     */
    public static function getTopJackpots(string $drawType, int $limit = 20): array
    {
        // Lấy từ database
        $topJackpots = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type: AND jackpot_amount > 0',
            'bind' => ['draw_type' => $drawType],
            'order' => 'jackpot_amount DESC',
            'limit' => $limit,
            'columns' => 'draw_date, jackpot_amount',
        ]);

        return $topJackpots->toArray();
    }

    /**
     * Lấy số hiếm
     */
    public static function getRareNumbers(string $drawType, int $limit = 20, string $order = 'ASC'): array
    {
        // Lấy dữ liệu từ database
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => $drawType],
            'order' => 'draw_date DESC',
            'limit' => 100, // Lấy 100 kỳ gần nhất để tính toán
        ]);

        if (count($results) == 0) {
            return [];
        }

        // Tính tần suất số
        $numberFrequency = [];
        $maxNumber = ($drawType === 'Mega645') ? 45 : 55;
        
        // Khởi tạo mảng tần suất
        for ($i = 1; $i <= $maxNumber; $i++) {
            $numberFrequency[str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
        }

        // Tính tần suất
        foreach ($results as $result) {
            if (empty($result->numbers)) {
                continue;
            }

            $numbers = explode(',', trim($result->numbers));
            $mainNumbers = array_slice($numbers, 0, 6);

            foreach ($mainNumbers as $num) {
                $num = trim($num);
                if ($num && is_numeric($num) && $num >= 1 && $num <= $maxNumber) {
                    $numberFrequency[str_pad((int)$num, 2, '0', STR_PAD_LEFT)]++;
                }
            }
        }

        // Sắp xếp theo tần suất
        if ($order === 'ASC') {
            asort($numberFrequency); // Tăng dần (số hiếm lên đầu)
        } else {
            arsort($numberFrequency); // Giảm dần (số thường xuyên lên đầu)
        }

        // Lấy top theo limit và format lại để view sử dụng
        $topNumbers = array_slice($numberFrequency, 0, $limit, true);
        
        // Format lại thành array với cấu trúc ['number' => ..., 'frequency' => ...]
        $formattedNumbers = [];
        foreach ($topNumbers as $number => $frequency) {
            $formattedNumbers[] = [
                'number' => $number,
                'frequency' => $frequency
            ];
        }
        
        return $formattedNumbers;
    }

    /**
     * Tạo tổ hợp từ mảng
     */
    public static function getCombinations($array, $length)
    {
        $result = [];
        $n = count($array);
        if ($length > $n) {
            return $result;
        }
        $indices = range(0, $length - 1);
        $result[] = array_map(function ($i) use ($array) {
            return $array[$i];
        }, $indices);

        while (true) {
            $i = $length - 1;
            while ($i >= 0 && $indices[$i] == $i + $n - $length) {
                $i--;
            }
            if ($i < 0) {
                break;
            }
            $indices[$i]++;
            for ($j = $i + 1; $j < $length; $j++) {
                $indices[$j] = $indices[$j - 1] + 1;
            }
            $result[] = array_map(function ($i) use ($array) {
                return $array[$i];
            }, $indices);
        }
        return $result;
    }

    public static function getLatestDraw645() {
        return VietlottResults::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC'
        ]);
    }

    public static function getLatestDraw655() {
        return VietlottResults::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC'
        ]);
    }

    public static function analyzePatterns645() {
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC',
            'limit' => 50
        ]);

        $patterns = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            $oddCount = 0;
            $evenCount = 0;
            
            foreach ($numbers as $number) {
                if ((int)$number % 2 == 0) {
                    $evenCount++;
                } else {
                    $oddCount++;
                }
            }
            
            $patterns[] = [
                'date' => $result->draw_date,
                'odd_count' => $oddCount,
                'even_count' => $evenCount
            ];
        }
        
        return $patterns;
    }

    public static function analyzePatterns655() {
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC',
            'limit' => 50
        ]);

        $patterns = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            $oddCount = 0;
            $evenCount = 0;
            
            foreach ($numbers as $number) {
                if ((int)$number % 2 == 0) {
                    $evenCount++;
                } else {
                    $oddCount++;
                }
            }
            
            $patterns[] = [
                'date' => $result->draw_date,
                'odd_count' => $oddCount,
                'even_count' => $evenCount
            ];
        }
        
        return $patterns;
    }

    public static function predictNumbers645() {
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC',
            'limit' => 100
        ]);

        $numberFrequency = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            foreach ($numbers as $number) {
                $number = trim($number);
                if (!isset($numberFrequency[$number])) {
                    $numberFrequency[$number] = 0;
                }
                $numberFrequency[$number]++;
            }
        }

        arsort($numberFrequency);
        return array_slice($numberFrequency, 0, 10, true);
    }

    public static function predictNumbers655() {
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC',
            'limit' => 100
        ]);

        $numberFrequency = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            foreach ($numbers as $number) {
                $number = trim($number);
                if (!isset($numberFrequency[$number])) {
                    $numberFrequency[$number] = 0;
                }
                $numberFrequency[$number]++;
            }
        }

        arsort($numberFrequency);
        return array_slice($numberFrequency, 0, 10, true);
    }

    public static function formatDataForView($data, $defaultValue = [])
    {
        if (empty($data) || !is_array($data)) {
            return $defaultValue;
        }
        return $data;
    }

    public static function prepareStatisticsForView($statisticsData)
    {
        return [
            'numberFrequency' => self::formatDataForView($statisticsData['numberFrequency'] ?? []),
            'top20Numbers' => self::formatDataForView($statisticsData['top20Numbers'] ?? []),
            'bottom20Numbers' => self::formatDataForView($statisticsData['bottom20Numbers'] ?? []),
            'top20SpecialNumbers' => self::formatDataForView($statisticsData['top20SpecialNumbers'] ?? []),
            'topTriplets' => self::formatDataForView($statisticsData['topTriplets'] ?? []),
            'top20Pairs' => self::formatDataForView($statisticsData['top20Pairs'] ?? []),
            'top20ConsecutivePairs' => self::formatDataForView($statisticsData['top20ConsecutivePairs'] ?? []),
            'consecutivePairs' => self::formatDataForView($statisticsData['consecutivePairs'] ?? [])
        ];
    }
}
