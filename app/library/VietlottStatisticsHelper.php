<?php

namespace App\Library;

use Phalcon\Db\Adapter\Pdo\Mysql;

/**
 * Helper class cho thống kê Vietlott
 * Tối ưu truy vấn và caching cho các controller Vietlott
 */
class VietlottStatisticsHelper
{
    /**
     * Lấy kết quả mới nhất của một loại Vietlott
     */
    public static function getLatestResult(string $drawType, Mysql $db): ?array
    {
        $sql = "SELECT * FROM vietlott_results 
                WHERE draw_type = ? 
                ORDER BY draw_date DESC, draw_number DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Lấy kết quả gần đây của một loại Vietlott
     */
    public static function getRecentResults(string $drawType, Mysql $db, int $limit = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM vietlott_results 
                WHERE draw_type = ? 
                ORDER BY STR_TO_DATE(draw_date, '%Y-%m-%d') DESC, draw_number DESC 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Chuyển đổi định dạng ngày từ dd-mm-yyyy sang yyyy-mm-dd
     */
    private static function formatDateForMySQL(string $date): string
    {
        // Nếu đã là định dạng yyyy-mm-dd thì giữ nguyên
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Chuyển đổi từ dd-mm-yyyy sang yyyy-mm-dd
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $date, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }
        
        // Nếu không nhận dạng được, trả về ngày hiện tại
        return date('Y-m-d');
    }

    /**
     * Lấy kết quả theo ngày cụ thể
     */
    public static function getResultByDate(string $drawType, string $date, Mysql $db): ?array
    {
        $formattedDate = self::formatDateForMySQL($date);
        
        $sql = "SELECT * FROM vietlott_results 
                WHERE draw_type = ? AND draw_date = ? 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType, $formattedDate]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Lấy kết quả MAX3D/MAX3DPRO gần đây
     */
    public static function getMaxRecentDraws(string $drawType, Mysql $db, int $limit = 10, ?string $date = null): array
    {
        $whereClause = (!empty($date)) ? "WHERE draw_type = ? AND draw_date = ?" : "WHERE draw_type = ?";
        $bind = (!empty($date)) ? [$drawType, self::formatDateForMySQL($date)] : [$drawType];
        
        $sql = "SELECT * FROM max_results 
                {$whereClause}
                ORDER BY draw_date DESC, draw_number DESC 
                LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($bind);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Format dữ liệu để view có thể sử dụng
        return array_map([self::class, 'formatMaxResultForView'], $results);
    }

    /**
     * Format dữ liệu MAX3D/MAX3DPRO cho view
     */
    private static function formatMaxResultForView(array $result): array
    {
        // Tách các số từ chuỗi và tạo array
        $jackpotNumbers = !empty($result['jackpot_numbers']) ? explode(',', $result['jackpot_numbers']) : [];
        $firstNumbers = !empty($result['first_numbers']) ? explode(',', $result['first_numbers']) : [];
        $secondNumbers = !empty($result['second_numbers']) ? explode(',', $result['second_numbers']) : [];
        $thirdNumbers = !empty($result['third_numbers']) ? explode(',', $result['third_numbers']) : [];
        
        // Tách số người trúng từ chuỗi
        $jackpotWinners = !empty($result['jackpot_winners']) ? explode(',', $result['jackpot_winners']) : [0, 0];
        $firstWinners = !empty($result['first_winners']) ? explode(',', $result['first_winners']) : [0, 0];
        $secondWinners = !empty($result['second_winners']) ? explode(',', $result['second_winners']) : [0, 0];
        $thirdWinners = !empty($result['third_winners']) ? explode(',', $result['third_winners']) : [0, 0];
        
        // Tách số người trúng cho các giải phụ
        $fourthWinners = !empty($result['fourth_winners']) ? explode(',', $result['fourth_winners']) : [0];
        $fifthWinners = !empty($result['fifth_winners']) ? explode(',', $result['fifth_winners']) : [0];
        $sixthWinners = !empty($result['sixth_winners']) ? explode(',', $result['sixth_winners']) : [0];
        $secondaryPrizeWinners = !empty($result['secondary_prize_winners']) ? explode(',', $result['secondary_prize_winners']) : [0];
        
        return [
            'id' => $result['id'] ?? 0,
            'draw_date' => $result['draw_date'] ?? '',
            'draw_number' => $result['draw_number'] ?? '',
            'jackpot_numbers' => $jackpotNumbers,
            'first_numbers' => $firstNumbers,
            'second_numbers' => $secondNumbers,
            'third_numbers' => $thirdNumbers,
            'jackpot_winners' => $jackpotWinners,
            'first_winners' => $firstWinners,
            'second_winners' => $secondWinners,
            'third_winners' => $thirdWinners,
            'fourth_winners' => $fourthWinners,
            'fifth_winners' => $fifthWinners,
            'sixth_winners' => $sixthWinners,
            'secondary_prize_winners' => $secondaryPrizeWinners,
        ];
    }

    /**
     * Lấy các ngày khác có kết quả MAX3D/MAX3DPRO
     */
    public static function getMaxOtherDates(string $drawType, Mysql $db, int $limit = 12): array
    {
        $sql = "SELECT DISTINCT draw_date 
                FROM max_results 
                WHERE draw_type = ? 
                ORDER BY draw_date DESC 
                LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType]);
        
        $dates = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Format dữ liệu cho view
        $formattedDates = [];
        foreach ($dates as $date) {
            if ($drawType === 'MAX3DPRO') {
                $url = '/ket-qua-xoso-max-3d-pro-vietlott-' . date('d-m-Y', strtotime($date));
            } else {
                $url = '/ket-qua-xoso-max-3d-vietlott-' . date('d-m-Y', strtotime($date));
            }
            
            $formattedDates[] = [
                'date' => $date,
                'url' => $url
            ];
        }
        
        return $formattedDates;
    }

    /**
     * Lấy số xuất hiện nhiều nhất MAX3D/MAX3DPRO
     */
    public static function getMaxFrequentNumbers(string $drawType, Mysql $db, int $limit = 60): array
    {
        $sql = "SELECT 
                    number,
                    COUNT(*) as frequency,
                    MAX(draw_date) as last_seen
                FROM (
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(jackpot_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                    UNION ALL
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(first_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                    UNION ALL
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(second_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                    UNION ALL
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(third_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                ) numbers
                WHERE number REGEXP '^[0-9]+$'
                GROUP BY number
                ORDER BY frequency DESC, number ASC
                LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType, $drawType, $drawType, $drawType]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Thêm key 'times' để tương thích với view cũ
        foreach ($results as &$result) {
            $result['times'] = $result['frequency'];
            // Đảm bảo có key frequency
            if (!isset($result['frequency'])) {
                $result['frequency'] = 0;
            }
        }
        
        return $results;
    }

    /**
     * Lấy số lâu chưa xuất hiện MAX3D/MAX3DPRO
     */
    public static function getMaxRareNumbers(string $drawType, Mysql $db, int $limit = 60): array
    {
        $sql = "SELECT 
                    number,
                    DATEDIFF(CURDATE(), MAX(draw_date)) as days_since_last,
                    MAX(draw_date) as last_seen
                FROM (
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(jackpot_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                    UNION ALL
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(first_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                    UNION ALL
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(second_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                    UNION ALL
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(third_numbers, ',', 1), ',', -1) as number,
                        draw_date
                    FROM max_results 
                    WHERE draw_type = ?
                ) numbers
                WHERE number REGEXP '^[0-9]+$'
                GROUP BY number
                ORDER BY days_since_last DESC, number ASC
                LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType, $drawType, $drawType, $drawType]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Thêm key 'times' và 'frequency' để tương thích với view cũ
        foreach ($results as &$result) {
            $result['times'] = $result['days_since_last'] ?? 0;
            $result['frequency'] = $result['days_since_last'] ?? 0; // Đảm bảo có key frequency
        }
        
        return $results;
    }

    /**
     * Tính toán sự tăng/giảm của Jackpot
     */
    public static function calculateJackpotChange(string $drawType, Mysql $db): array
    {
        $sql = "SELECT 
                    jackpot_amount,
                    draw_date,
                    draw_number
                FROM vietlott_results 
                WHERE draw_type = ? 
                ORDER BY draw_date DESC, draw_number DESC 
                LIMIT 2";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$drawType]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (count($results) < 2) {
            return [
                'current' => $results[0] ?? null,
                'previous' => null,
                'change' => 0
            ];
        }
        
        $current = $results[0];
        $previous = $results[1];
        $change = $current['jackpot_amount'] - $previous['jackpot_amount'];
        
        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $change
        ];
    }

    /**
     * Tạo cache key chuẩn cho Vietlott
     */
    public static function buildCacheKey(string $drawType, string $action, array $params = []): string
    {
        $keyParts = [$drawType, $action];
        
        // Thêm các tham số vào key và sanitize
        ksort($params);
        foreach ($params as $key => $value) {
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9]/', '', $key);
            $sanitizedValue = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$value);
            $keyParts[] = $sanitizedKey . '_' . $sanitizedValue;
        }
        
        $finalKey = implode('_', $keyParts);
        return preg_replace('/[^a-zA-Z0-9_]/', '', $finalKey);
    }

    /**
     * Tính toán cache lifetime thông minh cho Vietlott
     */
    public static function getSmartCacheLifetime(string $drawType): int
    {
        $now = time();
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');
        
        // Kiểm tra xem có phải ngày quay số không
        $drawDays = self::getDrawDays($drawType);
        $isDrawDay = in_array(date('w', $now), $drawDays);
        
        if ($isDrawDay) {
            // Nếu là ngày quay số, cache ngắn hơn
            $currentHour = (int)date('H', $now);
            if ($currentHour >= 18) {
                // Sau 18h, cache 1 giờ (có thể đã có kết quả)
                return 87000;
            } else {
                // Trước 18h, cache 30 phút
                return 87000;
            }
        } else {
            // Không phải ngày quay số, cache dài hơn
            return 87000; // 2 giờ
        }
    }

    /**
     * Lấy các ngày quay số trong tuần
     */
    private static function getDrawDays(string $drawType): array
    {
        switch ($drawType) {
            case 'Mega645':
            case 'Power655':
                return [2, 4, 6]; // Thứ 2, 4, 6
            case 'MAX3D':
            case 'MAX3DPRO':
                return [1, 3, 5, 7]; // Thứ 2, 4, 6, Chủ nhật
            default:
                return [1, 2, 3, 4, 5, 6, 7]; // Mọi ngày
        }
    }
}
