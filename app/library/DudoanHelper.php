<?php

namespace App\Library;

use Phalcon\Db\Adapter\Pdo\Mysql;
use App\Models\LotteryResults;
use App\Models\Provinces;
use App\Models\PredictionArticles;
use App\Models\VietlottResults;

class DudoanHelper
{
    /**
     * Tạo cache key chuẩn cho dự đoán
     */
    public static function buildCacheKey(string $region, string $action, array $params = []): string
    {
        $keyParts = [$region, $action];
        
        ksort($params);
        foreach ($params as $key => $value) {
            $cleanKey = preg_replace('/[^a-zA-Z0-9]/', '', $key);
            $cleanValue = preg_replace('/[^a-zA-Z0-9]/', '', (string)$value);
            $keyParts[] = $cleanKey . $cleanValue;
        }
        
        $finalKey = implode('_', $keyParts);
        return preg_replace('/[^a-zA-Z0-9_]/', '', $finalKey);
    }

    /**
     * Lấy thời gian cache thông minh
     */
    public static function getSmartCacheLifetime(array $results, string $dateField = 'draw_date'): int
    {
        if (empty($results)) return 86000; // 1 giờ nếu không có dữ liệu
        
        $firstResult = $results[0];
        if (is_object($firstResult)) {
            $latestDate = $firstResult->$dateField ?? date('Y-m-d');
        } else { // Assume it's an array
            $latestDate = $firstResult[$dateField] ?? date('Y-m-d');
        }

        $hoursSinceLatest = (time() - strtotime($latestDate)) / 3600;
        
        if ($hoursSinceLatest < 24) {
            return 86000; // 30 phút nếu dữ liệu mới
        } elseif ($hoursSinceLatest < 168) { // 1 tuần
            return 86000; // 2 giờ
        } else {
            return 86000; // 6 giờ cho dữ liệu cũ
        }
    }

    /**
     * Lấy tên miền tiếng Việt
     */
    public static function getRegionName(string $region): string
    {
        $map = [
            'XSMN' => 'Miền Nam',
            'XSMT' => 'Miền Trung',
            'XSMB' => 'Miền Bắc'
        ];
        return $map[$region] ?? 'Miền Bắc';
    }

    /**
     * Lấy thứ trong tuần (1-7)
     */
    public static function getDayOfWeek(string $ymd): int
    {
        return (int)date('N', strtotime($ymd));
    }

    /**
     * Lấy danh sách tỉnh theo miền & thứ
     */
    public static function getProvincesByRegionAndDow(string $region, int $dow)
    {
        return Provinces::find([
            'conditions' => 'region = :r: AND FIND_IN_SET(:d:, draw_days)',
            'bind'       => ['r' => $region, 'd' => $dow],
            'order'      => 'id ASC'
        ]);
    }

    /**
     * Tìm kết quả tuần trước cho một tỉnh
     */
    public static function findPrevWeekResult(string $region, int $provinceId, string $predictionYmd, int $maxHops = 6): array
    {
        $usedDate = date('Y-m-d', strtotime($predictionYmd . ' -7 days'));
        
        for ($hop = 0; $hop <= $maxHops; $hop++) {
            $rec = LotteryResults::findFirst([
                'conditions' => 'draw_type = :r: AND province_id = :pid: AND draw_date = :d:',
                'bind'       => ['r' => $region, 'pid' => $provinceId, 'd' => $usedDate],
                'order'      => 'draw_date DESC'
            ]);
            if ($rec) return [$usedDate, $rec];

            $usedDate = date('Y-m-d', strtotime($usedDate . ' -7 days'));
        }

        $rec = LotteryResults::findFirst([
            'conditions' => 'draw_type = :r: AND province_id = :pid: AND draw_date < :d:',
            'bind'       => ['r' => $region, 'pid' => $provinceId, 'd' => $predictionYmd],
            'order'      => 'draw_date DESC'
        ]);
        return [$rec ? $rec->draw_date : null, $rec];
    }

    /**
     * Tính tần suất & lô gan trong 30 kỳ
     */
    public static function computeFreqAndGan(string $region, int $provinceId, string $untilYmd): array
    {
        $lastDraws = LotteryResults::find([
            'conditions' => 'draw_type = :r: AND province_id = :pid: AND draw_date <= :d:',
            'bind'       => ['r' => $region, 'pid' => $provinceId, 'd' => $untilYmd],
            'order'      => 'draw_date DESC',
            'limit'      => 40
        ]);

        $freq = [];
        $appearLastDate = [];
        
        foreach ($lastDraws as $row) {
            $nums = self::extractTwoDigitsFromRow($row);
            foreach ($nums as $nn) {
                $freq[$nn] = ($freq[$nn] ?? 0) + 1;
                if (!isset($appearLastDate[$nn])) {
                    $appearLastDate[$nn] = $row->draw_date;
                }
            }
        }

        arsort($freq);
        $frequentNumbers = [];
        foreach ($freq as $nn => $cnt) {
            $frequentNumbers[] = (object)[
                'number' => $nn, 
                'count' => $cnt,
                'last_appeared' => $appearLastDate[$nn] ?? null
            ];
            if (count($frequentNumbers) >= 10) break;
        }

        // Tính max_days (gan cực đại) cho mỗi số
        $maxGanDays = [];
        foreach ($lastDraws as $row) {
            $nums = self::extractTwoDigitsFromRow($row);
            $numsSet = array_flip($nums);
            
            for ($i = 0; $i <= 99; $i++) {
                $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                if (!isset($numsSet[$nn])) {
                    // Số này không xuất hiện trong kỳ này
                    $maxGanDays[$nn] = ($maxGanDays[$nn] ?? 0) + 1;
                } else {
                    // Số này xuất hiện, reset về 0
                    if (isset($maxGanDays[$nn]) && $maxGanDays[$nn] > 0) {
                        // Lưu lại max nếu cần
                        if (!isset($maxGanDays[$nn . '_max'])) {
                            $maxGanDays[$nn . '_max'] = $maxGanDays[$nn];
                        } else {
                            $maxGanDays[$nn . '_max'] = max($maxGanDays[$nn . '_max'], $maxGanDays[$nn]);
                        }
                    }
                    $maxGanDays[$nn] = 0;
                }
            }
        }

        $ganList = [];
        $predDate = new \DateTime($untilYmd);
        
        for ($i = 0; $i <= 99; $i++) {
            $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            if (isset($appearLastDate[$nn])) {
                $last = new \DateTime($appearLastDate[$nn]);
                $days = (int)$last->diff($predDate)->format('%a');
                $ganList[] = (object)[
                    'number'        => $nn,
                    'days'          => $days,
                    'last_appeared' => $appearLastDate[$nn],
                    'max_days'      => max($maxGanDays[$nn . '_max'] ?? 0, $maxGanDays[$nn] ?? 0),
                ];
            }
        }
        
        usort($ganList, function ($a, $b) {
            if ($a->days === $b->days) return strcmp($a->number, $b->number);
            return $b->days <=> $a->days;
        });
        
        $ganNumbers = array_slice($ganList, 0, 10);
        return [$frequentNumbers, $ganNumbers];
    }

    /**
     * Lấy 2 số cuối từ một row kết quả
     */
    public static function extractTwoDigitsFromRow($row): array
    {
        $all = [];
        $push = function ($val) use (&$all) {
            if (!$val) return;
            if (!preg_match('/^\d+$/', $val)) return;
            $all[] = str_pad(substr($val, -2), 2, '0', STR_PAD_LEFT);
        };

        $push($row->special_prize);
        $push($row->first_prize);
        foreach (self::parsePrize($row->second_prize) as $v) $push($v);
        foreach (self::parsePrize($row->third_prize) as $v) $push($v);
        foreach (self::parsePrize($row->fourth_prize) as $v) $push($v);
        foreach (self::parsePrize($row->fifth_prize) as $v) $push($v);
        foreach (self::parsePrize($row->sixth_prize) as $v) $push($v);
        foreach (self::parsePrize($row->seventh_prize) as $v) $push($v);
        foreach (self::parsePrize($row->eighth_prize) as $v) $push($v);

        return $all;
    }

    /**
     * Parse chuỗi giải -> mảng số
     */
    public static function parsePrize(?string $src): array
    {
        if ($src === null) return [];
        $s = trim($src);

        $j = json_decode($s, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $arr = is_array($j) ? $j : [$j];
            $out = [];
            foreach ($arr as $v) {
                if ($v === null) continue;
                $v = trim((string)$v);
                if ($v !== '') $out[] = $v;
            }
            return $out;
        }

        $s = str_replace(['[', ']'], ' ', $s);
        $s = preg_replace('/"{2,}/', '" "', $s);
        $s = str_replace(['"', ',', ';', '|'], ' ', $s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        if ($s === '') return [];
        
        $out = [];
        foreach (explode(' ', $s) as $p) {
            $p = trim($p);
            if ($p !== '') $out[] = $p;
        }
        return $out;
    }

    /**
     * Tạo chốt số từ kết quả hôm trước + top tần suất
     */
    public static function buildChotSo($previousResult, array $frequentNumbers): array
    {
        $out = ['giai_dac_biet' => '??', 'lo_2_so' => '??', 'lo_3_so' => '??', 'lo_4_so' => '??'];
        
        if ($previousResult) {
            $gdb = (string)($previousResult->special_prize ?? '');
            if ($gdb !== '') {
                $out['giai_dac_biet'] = substr($gdb, 0, 2) . ' - ' . substr($gdb, -2);
                $out['lo_3_so'] = substr($gdb, -3);
                $out['lo_4_so'] = substr($gdb, 0, 2) . substr($gdb, -2);
            }
        }
        
        if (!empty($frequentNumbers)) {
            $top3 = array_slice($frequentNumbers, 0, 3);
            $out['lo_2_so'] = implode(' - ', array_map(fn($o) => $o->number, $top3));
        }
        
        return $out;
    }

    /**
     * Tạo soi cầu từ tập số liệu
     */
    public static function buildSoiCau($previousResult, array $frequentNumbers, array $ganNumbers): array
    {
        $res = [
            'bach_thu'   => $ganNumbers[0]->number ?? '??',
            'lat_lien'   => $frequentNumbers[0]->number ?? '??',
            'cau_2_nhay' => '',
            'pascal'     => '??',
            'lo_kep'     => '??',
            'lo_to_ve'   => ''
        ];

        $arr = [];
        foreach ($frequentNumbers as $o) if (($o->count ?? 0) >= 2) $arr[] = $o->number;
        $res['cau_2_nhay'] = implode(' - ', $arr);

        if ($previousResult && $previousResult->special_prize) {
            $res['pascal'] = substr($previousResult->special_prize, 0, 2) . ' - ' . substr($previousResult->special_prize, -2);
        }

        if (count($frequentNumbers) >= 2) {
            $n1 = (int)$frequentNumbers[0]->number;
            $n2 = (int)$frequentNumbers[1]->number;
            $res['lo_kep'] = str_pad((int)(($n1 + $n2) / 2), 2, '0', STR_PAD_LEFT);
        }

        $res['lo_to_ve'] = implode(' - ', array_map(fn($o) => $o->number, array_slice($frequentNumbers, 0, 5)));

        return $res;
    }

    /**
     * Lấy 10/30 kỳ giải đặc biệt cho một tỉnh
     */
    public static function fetchSpecialPrizeSeries(string $region, int $provinceId, string $untilYmd): array
    {
        $sp10 = LotteryResults::find([
            'conditions' => 'draw_type = :r: AND province_id = :pid: AND draw_date <= :d:',
            'bind'       => ['r' => $region, 'pid' => $provinceId, 'd' => $untilYmd],
            'order'      => 'draw_date DESC',
            'limit'      => 10
        ]);
        
        $sp30 = LotteryResults::find([
            'conditions' => 'draw_type = :r: AND province_id = :pid: AND draw_date <= :d:',
            'bind'       => ['r' => $region, 'pid' => $provinceId, 'd' => $untilYmd],
            'order'      => 'draw_date DESC',
            'limit'      => 30
        ]);
        
        return [$sp10, $sp30];
    }

    /**
     * Phát hiện trang tổng hợp
     */
    public static function isAggregateArticle($prediction): bool
    {
        return empty($prediction->province_id);
    }

    /**
     * Tìm province từ slug
     */
    public static function findProvinceFromSlug(string $region, string $slug)
    {
        $slugLower = strtolower($slug);
        $provList = Provinces::find([
            'conditions' => 'region = :r:',
            'bind'       => ['r' => $region]
        ]);
        
        $slugify = function ($str) {
            $map = ['à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'đ' => 'd', 'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Đ' => 'D'];
            $str = strtr($str, $map);
            $str = preg_replace('/[^a-zA-Z0-9\s-]/', '', $str);
            $str = strtolower(trim($str));
            $str = preg_replace('/[\s-]+/', '-', $str);
            return $str;
        };

        foreach ($provList as $p) {
            if (strpos($slugLower, $slugify($p->name)) !== false) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Lấy bài dự đoán liên quan
     */
    public static function getRelatedPredictions(int $excludeId, string $region, int $limit = 3)
    {
        return PredictionArticles::find([
            'conditions' => 'id != :id: AND region = :region:',
            'bind'       => ['id' => $excludeId, 'region' => $region],
            'order'      => 'prediction_date DESC',
            'limit'      => $limit
        ]);
    }

    /**
     * Lấy kết quả xổ số gần nhất trước ngày dự đoán
     */
    public static function getPreviousResult(string $region, string $predictionDate, ?int $provinceId = null)
    {
        $conditions = 'draw_date < :date: AND draw_type = :region:';
        $bind = ['date' => $predictionDate, 'region' => $region];
        
        if ($provinceId) {
            $conditions .= ' AND province_id = :pid:';
            $bind['pid'] = $provinceId;
        }
        
        return LotteryResults::findFirst([
            'conditions' => $conditions,
            'bind'       => $bind,
            'order'      => 'draw_date DESC'
        ]);
    }

    // ===========================
    //    VIETLOTT DỰ ĐOÁN
    // ===========================

    /**
     * Lấy kết quả mới nhất của Mega 6/45 với cache
     */
    public static function getLatestDraw645()
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');

        $cacheKey = self::buildCacheKey('MEGA', 'latest');
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $result = VietlottResults::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC'
        ]);

        // Cache với thời gian ngắn vì dữ liệu mới
        $cache->set($cacheKey, $result, 1800); // 30 phút
        
        return $result;
    }

    /**
     * Lấy kết quả mới nhất của Power 6/55 với cache
     */
    public static function getLatestDraw655()
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('POWER', 'latest');
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $result = VietlottResults::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC'
        ]);

        // Cache với thời gian ngắn vì dữ liệu mới
        $cache->set($cacheKey, $result, 1800); // 30 phút
        
        return $result;
    }

    /**
     * Phân tích mẫu số Mega 6/45 với cache và tối ưu truy vấn
     */
    public static function analyzePatterns645()
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('MEGA', 'patterns');
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Tối ưu: Chỉ lấy các trường cần thiết
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'columns' => 'draw_date, numbers',
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

        // Cache với thời gian thông minh
        $cacheLifetime = self::getSmartCacheLifetime($patterns, 'date');
        $cache->set($cacheKey, $patterns, $cacheLifetime);
        
        return $patterns;
    }

    /**
     * Phân tích mẫu số Power 6/55 với cache và tối ưu truy vấn
     */
    public static function analyzePatterns655()
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('POWER', 'patterns');
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Tối ưu: Chỉ lấy các trường cần thiết
        $results = VietlottResults::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'columns' => 'draw_date, numbers',
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

        // Cache với thời gian thông minh
        $cacheLifetime = self::getSmartCacheLifetime($patterns, 'date');
        $cache->set($cacheKey, $patterns, $cacheLifetime);
        
        return $patterns;
    }

    /**
     * Lấy tần suất số Mega 6/45 với cache và tối ưu truy vấn
     */
    public static function getNumberFrequency645($limit = 20, $order = 'DESC')
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('MEGA', 'frequency', ['limit' => $limit, 'order' => $order]);
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Tối ưu: Chỉ lấy trường numbers và sử dụng raw SQL để tính toán nhanh hơn
        $db = \Phalcon\Di\Di::getDefault()->get('db');
        $sql = "SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) AS number,
                    COUNT(*) as frequency
                FROM (
                    SELECT numbers
                    FROM vietlott_results
                    WHERE draw_type = 'Mega645'
                    ORDER BY draw_date DESC
                    LIMIT 100
                ) v
                CROSS JOIN (
                    SELECT 1 + units.i + tens.i * 10 AS n
                    FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) units,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) tens
                    WHERE 1 + units.i + tens.i * 10 <= 6
                ) n
                WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) BETWEEN '01' AND '45'
                GROUP BY number
                ORDER BY frequency " . ($order === 'ASC' ? 'ASC' : 'DESC') . "
                LIMIT " . (int)$limit;

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $formattedNumbers = [];
        foreach ($results as $row) {
            $formattedNumbers[] = [
                'number' => $row['number'],
                'frequency' => (int)$row['frequency']
            ];
        }

        // Cache với thời gian thông minh
        $cacheLifetime = self::getSmartCacheLifetime($results, 'frequency');
        $cache->set($cacheKey, $formattedNumbers, $cacheLifetime);
        
        return $formattedNumbers;
    }

    /**
     * Lấy tần suất số Power 6/55 với cache và tối ưu truy vấn
     */
    public static function getNumberFrequency655($limit = 20, $order = 'DESC')
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('POWER', 'frequency', ['limit' => $limit, 'order' => $order]);
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Tối ưu: Chỉ lấy trường numbers và sử dụng raw SQL để tính toán nhanh hơn
        $db = \Phalcon\Di\Di::getDefault()->get('db');
        $sql = "SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) AS number,
                    COUNT(*) as frequency
                FROM (
                    SELECT numbers
                    FROM vietlott_results
                    WHERE draw_type = 'Power655'
                    ORDER BY draw_date DESC
                    LIMIT 100
                ) v
                CROSS JOIN (
                    SELECT 1 + units.i + tens.i * 10 AS n
                    FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) units,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) tens
                    WHERE 1 + units.i + tens.i * 10 <= 6
                ) n
                WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) BETWEEN '01' AND '55'
                GROUP BY number
                ORDER BY frequency " . ($order === 'ASC' ? 'ASC' : 'DESC') . "
                LIMIT " . (int)$limit;

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $formattedNumbers = [];
        foreach ($results as $row) {
            $formattedNumbers[] = [
                'number' => $row['number'],
                'frequency' => (int)$row['frequency']
            ];
        }

        // Cache với thời gian thông minh
        $cacheLifetime = self::getSmartCacheLifetime($results, 'frequency');
        $cache->set($cacheKey, $formattedNumbers, $cacheLifetime);
        
        return $formattedNumbers;
    }

    /**
     * Dự đoán số Mega 6/45 với cache và tối ưu truy vấn
     */
    public static function predictNumbers645()
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('MEGA', 'prediction');
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Tối ưu: Sử dụng raw SQL để tính toán nhanh hơn
        $db = \Phalcon\Di\Di::getDefault()->get('db');
        $sql = "SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) AS number,
                    COUNT(*) as frequency
                FROM (
                    SELECT numbers
                    FROM vietlott_results
                    WHERE draw_type = 'Mega645'
                    ORDER BY draw_date DESC
                    LIMIT 100
                ) v
                CROSS JOIN (
                    SELECT 1 + units.i + tens.i * 10 AS n
                    FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) units,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) tens
                    WHERE 1 + units.i + tens.i * 10 <= 6
                ) n
                WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) BETWEEN '01' AND '45'
                GROUP BY number
                ORDER BY frequency DESC
                LIMIT 30";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $prediction = [];
        foreach ($results as $row) {
            $prediction[$row['number']] = (int)$row['frequency'];
        }

        // Tạo 6 số dự đoán duy nhất từ các số có tần suất cao nhất (Mega 6/45)
        $uniqueNumbers = array_keys($prediction);
        $finalPrediction = array_slice($uniqueNumbers, 0, 6);

        // Cache với thời gian thông minh
        $cacheLifetime = self::getSmartCacheLifetime($results, 'frequency');
        $cache->set($cacheKey, $finalPrediction, $cacheLifetime);
        
        return $finalPrediction;
    }

    /**
     * Dự đoán số Power 6/55 với cache và tối ưu truy vấn
     */
    public static function predictNumbers655()
    {
        $cache = \Phalcon\Di\Di::getDefault()->get('modelsCache');
        $cacheKey = self::buildCacheKey('POWER', 'prediction');
    
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Tối ưu: Sử dụng raw SQL để tính toán nhanh hơn
        $db = \Phalcon\Di\Di::getDefault()->get('db');
        $sql = "SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) AS number,
                    COUNT(*) as frequency
                FROM (
                    SELECT numbers
                    FROM vietlott_results
                    WHERE draw_type = 'Power655'
                    ORDER BY draw_date DESC
                    LIMIT 100
                ) v
                CROSS JOIN (
                    SELECT 1 + units.i + tens.i * 10 AS n
                    FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) units,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) tens
                    WHERE 1 + units.i + tens.i * 10 <= 6
                ) n
                WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(numbers, ',', n.n), ',', -1) BETWEEN '01' AND '55'
                GROUP BY number
                ORDER BY frequency DESC
                LIMIT 30";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $prediction = [];
        foreach ($results as $row) {
            $prediction[$row['number']] = (int)$row['frequency'];
        }

        // Tạo 7 số dự đoán duy nhất từ các số có tần suất cao nhất (Power 6/55)
        $uniqueNumbers = array_keys($prediction);
        $finalPrediction = array_slice($uniqueNumbers, 0, 7);

        // Cache với thời gian thông minh
        $cacheLifetime = self::getSmartCacheLifetime($results, 'frequency');
        $cache->set($cacheKey, $finalPrediction, $cacheLifetime);
        
        return $finalPrediction;
    }
    
}
