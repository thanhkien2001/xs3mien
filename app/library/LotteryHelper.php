<?php
declare(strict_types=1);

namespace App\Library;

/**
 * Lottery Helper - Các hàm tiện ích cho xổ số
 */
class LotteryHelper
{
    /**
     * Clean dữ liệu giải thưởng nếu bị lỗi JSON format
     * 
     * Xử lý trường hợp dữ liệu trong database bị JSON encode sai:
     * - Input: ["5678","0123","4567"] hoặc 5678,0123,4567
     * - Output: ['5678', '0123', '4567']
     * 
     * @param string|null $prizeData Dữ liệu giải thưởng từ database
     * @return array Mảng các số đã được clean
     */
    public static function cleanPrizeData(?string $prizeData): array
    {
        if (empty($prizeData)) {
            return [];
        }
        
        // Nếu có dấu [ hoặc " nghĩa là bị JSON encode
        if (strpos($prizeData, '[') !== false || strpos($prizeData, '"') !== false) {
            // Remove các ký tự không cần: [, ], "
            $cleaned = str_replace(['[', ']', '"'], '', $prizeData);
            return array_map('trim', explode(',', $cleaned));
        }
        
        // Dữ liệu bình thường - split by comma
        return array_map('trim', explode(',', $prizeData));
    }

    /**
     * Format số xổ số với leading zeros
     * 
     * @param string|int $number Số cần format
     * @param int $length Độ dài mong muốn (2, 3, 4, 5, 6 chữ số)
     * @return string Số đã được format
     */
    public static function formatNumber($number, int $length = 2): string
    {
        return str_pad((string)$number, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Parse dữ liệu giải thưởng có thể có nhiều format
     * 
     * Hỗ trợ:
     * - CSV: "12,34,56"
     * - JSON: ["12","34","56"]
     * - JSON array: [12,34,56]
     * 
     * @param string|null $prizeData
     * @return array
     */
    public static function parsePrizeData(?string $prizeData): array
    {
        if (empty($prizeData)) {
            return [];
        }

        // Thử decode JSON trước
        if (strpos($prizeData, '[') !== false) {
            $decoded = json_decode($prizeData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_map('trim', array_map('strval', $decoded));
            }
        }

        // Fallback: clean và split by comma
        return self::cleanPrizeData($prizeData);
    }

    /**
     * Kiểm tra số có hợp lệ không
     * 
     * @param string $number
     * @param int $expectedLength Độ dài mong đợi (2-6 chữ số)
     * @return bool
     */
    public static function isValidNumber(string $number, int $expectedLength = 2): bool
    {
        $number = trim($number);
        
        // Phải là số
        if (!ctype_digit($number)) {
            return false;
        }

        // Kiểm tra độ dài
        $length = strlen($number);
        return $length === $expectedLength;
    }

    /**
     * Lấy 2 số cuối của giải thưởng (đuôi)
     * 
     * @param string $number
     * @return string
     */
    public static function getTail(string $number): string
    {
        $number = trim($number);
        if (strlen($number) >= 2) {
            return substr($number, -2);
        }
        return str_pad($number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Lấy chữ số đầu tiên (đầu)
     * 
     * @param string $number
     * @return int
     */
    public static function getHead(string $number): int
    {
        $number = trim($number);
        if (strlen($number) >= 2) {
            return (int)substr($number, -2, 1);
        }
        return 0;
    }
}


