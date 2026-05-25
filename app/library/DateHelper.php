<?php date_default_timezone_set('Asia/Ho_Chi_Minh'); ?>
<?php

class DateHelper
{
    /**
     * Calculates the next lottery draw date (Wednesday, Friday, or Sunday).
     *
     * @return string The next draw date in 'd-m-Y' format.
     */
    public static function getNextLotteryDate645(): string
    {
        // Các ngày quay: 3 (Thứ Tư), 5 (Thứ Sáu), 0 (Chủ Nhật)
        return self::findNextAvailableDate([3, 5, 0]);
    }

    /**
     * Lấy ngày quay số tiếp theo của Xổ số 6/55 (Thứ Ba, Thứ Năm, Thứ Bảy).
     * @return string
     */
    public static function getNextLotteryDate655(): string
    {
        // Các ngày quay: 2 (Thứ Ba), 4 (Thứ Năm), 6 (Thứ Bảy)
        return self::findNextAvailableDate([2, 4, 6]);
    }

    /**
     * Hàm chung để tìm ngày hợp lệ tiếp theo dựa trên danh sách các ngày trong tuần.
     *
     * @param array $allowedDaysOfWeek Mảng các ngày hợp lệ (0: CN, 1: T2, ..., 6: T7).
     * @return string Ngày hợp lệ tiếp theo theo định dạng 'd-m-Y'.
     */
    private static function findNextAvailableDate(array $allowedDaysOfWeek): string
    {
        // Vẫn bắt đầu kiểm tra từ ngày mai để đảm bảo luôn tìm được ngày sắp tới
        $currentTimestamp = strtotime('today');

        while (true) {
            $dayOfWeek = (int)date('w', $currentTimestamp); // Lấy ngày trong tuần dạng số

            // Sử dụng in_array để kiểm tra xem ngày hiện tại có trong danh sách được phép không
            if (in_array($dayOfWeek, $allowedDaysOfWeek)) {
                // Nếu có, trả về ngày này và kết thúc vòng lặp
                return date('d-m-Y', $currentTimestamp);
            }

            // Nếu không, kiểm tra ngày tiếp theo
            $currentTimestamp = strtotime('+1 day', $currentTimestamp);
        }
    }

    /**
     * Tạo URL kết quả Mega 6/45 cho ngày cụ thể
     *
     * @param string $date Ngày theo định dạng 'Y-m-d' hoặc 'd-m-Y'
     * @return string URL kết quả Mega 6/45
     */
    public static function generateMega645Url(string $date): string
    {
        // Chuyển đổi định dạng ngày nếu cần
        $formattedDate = self::formatDateForUrl($date);
        return "/ket-qua-xoso-mega-6-45-vietlott-{$formattedDate}";
    }

    /**
     * Tạo URL kết quả Power 6/55 cho ngày cụ thể
     *
     * @param string $date Ngày theo định dạng 'Y-m-d' hoặc 'd-m-Y'
     * @return string URL kết quả Power 6/55
     */
    public static function generatePower655Url(string $date): string
    {
        // Chuyển đổi định dạng ngày nếu cần
        $formattedDate = self::formatDateForUrl($date);
        return "/ket-qua-xoso-power-6-55-vietlott-{$formattedDate}";
    }

    /**
     * Tạo URL dự đoán Mega 6/45 cho ngày cụ thể
     *
     * @param string $date Ngày theo định dạng 'Y-m-d' hoặc 'd-m-Y'
     * @return string URL dự đoán Mega 6/45
     */
    public static function generateMega645PredictionUrl(string $date): string
    {
        // Chuyển đổi định dạng ngày nếu cần
        $formattedDate = self::formatDateForUrl($date);
        return "/du-doan-soi-cau-xo-so-mega-6-45-vietlott-ngay-{$formattedDate}-co-nen-xuong-tay";
    }

    /**
     * Tạo URL dự đoán Power 6/55 cho ngày cụ thể
     *
     * @param string $date Ngày theo định dạng 'Y-m-d' hoặc 'd-m-Y'
     * @return string URL dự đoán Power 6/55
     */
    public static function generatePower655PredictionUrl(string $date): string
    {
        // Chuyển đổi định dạng ngày nếu cần
        $formattedDate = self::formatDateForUrl($date);
        return "/du-doan-soi-cau-xo-so-power-6-55-vietlott-ngay-{$formattedDate}-co-nen-xuong-tay";
    }

    /**
     * Tạo danh sách URL để cập nhật cache cho Mega 6/45
     *
     * @param string $baseUrl URL gốc của website
     * @param array $dates Mảng các ngày cần cập nhật cache (mặc định là ngày hôm nay)
     * @return array Danh sách URL để cập nhật cache
     */
    public static function generateMega645CacheUrls(string $baseUrl, array $dates = null): array
    {
        if ($dates === null) {
            $dates = [date('Y-m-d')];
        }

        $urls = [];
        foreach ($dates as $date) {
            $urls[] = $baseUrl . self::generateMega645Url($date);
            $urls[] = $baseUrl . self::generateMega645PredictionUrl($date);
        }

        // Thêm các URL cố định
        $urls[] = $baseUrl . '/vietlott645/index645';
        $urls[] = $baseUrl . '/Statistics645/statisticspower645';

        return array_unique($urls);
    }

    /**
     * Tạo danh sách URL để cập nhật cache cho Power 6/55
     *
     * @param string $baseUrl URL gốc của website
     * @param array $dates Mảng các ngày cần cập nhật cache (mặc định là ngày hôm nay)
     * @return array Danh sách URL để cập nhật cache
     */
    public static function generatePower655CacheUrls(string $baseUrl, array $dates = null): array
    {
        if ($dates === null) {
            $dates = [date('Y-m-d')];
        }

        $urls = [];
        foreach ($dates as $date) {
            $urls[] = $baseUrl . self::generatePower655Url($date);
            $urls[] = $baseUrl . self::generatePower655PredictionUrl($date);
        }

        // Thêm các URL cố định
        $urls[] = $baseUrl . '/vietlott655/index655';
        $urls[] = $baseUrl . '/Statistics665/statisticspower655';

        return array_unique($urls);
    }

    /**
     * Chuyển đổi định dạng ngày cho URL (từ Y-m-d sang d-m-Y)
     *
     * @param string $date Ngày theo định dạng 'Y-m-d' hoặc 'd-m-Y'
     * @return string Ngày theo định dạng 'd-m-Y' cho URL
     */
    private static function formatDateForUrl(string $date): string
    {
        // Nếu đã là định dạng d-m-Y thì trả về luôn
        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $date)) {
            return $date;
        }

        // Nếu là định dạng Y-m-d thì chuyển đổi
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $date)) {
            return date('d-m-Y', strtotime($date));
        }

        // Nếu không nhận dạng được, thử chuyển đổi bằng strtotime
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('d-m-Y', $timestamp);
        }

        // Fallback: trả về ngày hiện tại
        return date('d-m-Y');
    }
}
