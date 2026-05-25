<?php

namespace App\Library;

use Phalcon\Db\Adapter\Pdo\Mysql;

class ThongkeStatisticsHelper
{
    /**
     * Lấy 2 số cuối từ một chuỗi số
     */
    public static function last2(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '00';
        return substr(str_pad($s, 2, '0', STR_PAD_LEFT), -2);
    }

    /**
     * Format ngày sang định dạng VN (dd/mm/yyyy)
     */
    public static function vnDate(string $ymd): string
    {
        $ts = strtotime($ymd);
        if ($ts === false || $ts <= 0) return '';
        return date('d/m/Y', $ts);
    }

    /**
     * Lấy chữ số đầu tiên của một số
     */
    public static function headDigit(string $num): string
    {
        $s = ltrim(preg_replace('/\D+/', '', (string)$num), '0');
        if ($s === '') $s = '0';
        return substr($s, 0, 1) ?: '0';
    }

    /**
     * Lấy chữ số cuối cùng của một số
     */
    public static function tailDigit(string $num): string
    {
        $s = preg_replace('/\D+/', '', (string)$num);
        if ($s === '') return '0';
        return substr($s, -1);
    }

    /**
     * Thứ trong tuần tiếng Việt
     */
    public static function weekdayVN(string $ymd): string
    {
        $w = (int)date('N', strtotime($ymd)); // 1..7
        return [
            1 => 'Thứ 2', 
            2 => 'Thứ 3', 
            3 => 'Thứ 4', 
            4 => 'Thứ 5', 
            5 => 'Thứ 6', 
            6 => 'Thứ 7', 
            7 => 'Chủ Nhật'
        ][$w] ?? '';
    }

    /**
     * Parse một field giải thưởng (có thể là JSON array hoặc string đơn)
     */
    public static function parsePrizeField($val): array
    {
        if ($val === null) return [];
        $s = trim((string)$val);
        if ($s === '') return [];
        
        // Thử parse JSON array
        if ($s[0] === '[') {
            $arr = json_decode($s, true);
            if (is_array($arr)) {
                return array_values(array_filter(array_map('strval', $arr), fn($x) => $x !== ''));
            }
        }
        
        // Tách tất cả cụm số trong chuỗi
        preg_match_all('/\d+/', $s, $m);
        return $m[0] ?? [];
    }

    /**
     * Lấy tất cả 2 số cuối từ một row kết quả (tất cả giải)
     */
    public static function extractAllLast2FromRow(array $r): array
    {
        $cols = [
            'special_prize', 'first_prize', 'second_prize', 'third_prize',
            'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'
        ];
        
        $out = [];
        foreach ($cols as $c) {
            if (!isset($r[$c]) || $r[$c] === null || $r[$c] === '') continue;
            foreach (self::parsePrizeField($r[$c]) as $raw) {
                $out[] = self::last2((string)$raw);
            }
        }
        return $out;
    }

    /**
     * Lấy danh sách tỉnh theo region
     */
    public static function getProvincesByRegion(string $region, Mysql $db): array
    {
        $sql = "SELECT id, name FROM provinces WHERE region = :r ORDER BY name";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':r', $region);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy N ngày gần nhất có quay trong một region
     */
    public static function getRecentDatesByRegion(string $region, Mysql $db, int $limit): array
    {
        $sql = "SELECT draw_date
                FROM lottery_results
                WHERE draw_type = :r
                GROUP BY draw_date
                ORDER BY draw_date DESC
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':r', $region);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'draw_date');
    }

    /**
     * Lấy kết quả theo ngày với lựa chọn có lấy tất cả giải hay không
     */
    public static function getResultsByDates(string $region, Mysql $db, array $dates, bool $selectAllPrizes = false): array
    {
        if (empty($dates)) return [];

        $ph = implode(',', array_fill(0, count($dates), '?'));

        $cols = $selectAllPrizes
            ? "lr.draw_date, lr.province_id, p.name AS province_name,
               lr.special_prize, lr.first_prize, lr.second_prize, lr.third_prize,
               lr.fourth_prize, lr.fifth_prize, lr.sixth_prize, lr.seventh_prize, lr.eighth_prize"
            : "lr.draw_date, lr.province_id, p.name AS province_name, lr.special_prize";

        $sql = "SELECT $cols
                FROM lottery_results lr
                JOIN provinces p ON p.id = lr.province_id
                WHERE lr.draw_type = ? AND lr.draw_date IN ($ph)
                ORDER BY lr.draw_date DESC, p.name ASC";

        $stmt = $db->prepare($sql);
        $bind = array_merge([$region], $dates);
        $stmt->execute($bind);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy kết quả gần đây của một tỉnh
     */
    public static function getRecentByProvince(int $provinceId, Mysql $db, int $limit, bool $fullPrizes = false): array
    {
        $cols = $fullPrizes 
            ? "draw_date, special_prize, first_prize, second_prize, third_prize,
               fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize"
            : "draw_date, special_prize";

        $sql = "SELECT $cols
                FROM lottery_results
                WHERE province_id = :pid
                ORDER BY draw_date DESC
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Build daily sets của 2 số cuối từ tất cả giải
     */
    public static function buildDailyLast2SetsAllPrizes(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($byDate[$d])) $byDate[$d] = [];
            foreach (self::extractAllLast2FromRow($r) as $l2) {
                $byDate[$d][$l2] = true;
            }
        }
        ksort($byDate); // tăng dần để tính gan
        $out = [];
        foreach ($byDate as $d => $set) $out[$d] = array_keys($set);
        return $out;
    }

    /**
     * Tính lô gan theo ngày
     */
    public static function computeGanByDays(array $dailySets, int $limit = 10, bool $excludeNeverSeen = false): array
    {
        $curStreak = [];
        $maxStreak = [];
        $lastSeen  = [];

        foreach (range(0, 99) as $n) {
            $key = str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            $curStreak[$key] = 0;
            $maxStreak[$key] = 0;
            $lastSeen[$key]  = null;
        }

        foreach ($dailySets as $date => $nums) {
            $present = array_fill_keys($nums, true);
            foreach ($curStreak as $num => $streak) {
                if (isset($present[$num])) {
                    $lastSeen[$num]  = $date;
                    $curStreak[$num] = 0;
                } else {
                    $curStreak[$num]++;
                    if ($curStreak[$num] > $maxStreak[$num]) $maxStreak[$num] = $curStreak[$num];
                }
            }
        }

        $rows = [];
        foreach ($curStreak as $num => $streak) {
            if ($excludeNeverSeen && $lastSeen[$num] === null) continue;
            $rows[] = [
                'num'        => $num,
                'streak'     => (int)$streak,
                'last_seen'  => $lastSeen[$num] ? self::vnDate($lastSeen[$num]) : null,
                'max_streak' => (int)$maxStreak[$num],
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['num'], $b['num']);
            return $b['streak'] <=> $a['streak'];
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * Tính top most xuất hiện nhiều nhất (từ giải đặc biệt)
     */
    public static function computeTopMost(array $rows, int $limit = 10): array
    {
        $cnt = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
        foreach ($rows as $r) {
            $cnt[self::last2($r['special_prize'])]++;
        }
        arsort($cnt);
        $max = max($cnt) ?: 1;

        $out = [];
        foreach ($cnt as $num => $times) {
            if ($times <= 0) continue;
            $out[] = [
                'num'     => $num,
                'times'   => (int)$times,
                'percent' => round($times * 100 / $max, 2),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    /**
     * Group results theo ngày để hiển thị grid
     */
    public static function groupByDateForGrid(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            if (!isset($out[$d])) $out[$d] = ['date' => $d, 'date_vn' => self::vnDate($d), 'items' => []];
            $out[$d]['items'][] = [
                'province_id'   => (int)$r['province_id'],
                'province_name' => $r['province_name'],
                'num'           => $r['special_prize'],
                'last2'         => self::last2($r['special_prize']),
            ];
        }
        krsort($out);
        return array_values($out);
    }

    /**
     * Group cho single province
     */
    public static function groupByDateForGridSingleProvince(array $rows, int $provinceId, Mysql $db): array
    {
        static $nameCache = [];
        if (!isset($nameCache[$provinceId])) {
            $stmt = $db->prepare("SELECT name FROM provinces WHERE id = :id");
            $stmt->bindValue(':id', $provinceId, \PDO::PARAM_INT);
            $stmt->execute();
            $nameCache[$provinceId] = (string)($stmt->fetchColumn() ?: '---');
        }
        $pname = $nameCache[$provinceId];

        $out = [];
        foreach ($rows as $r) {
            $d = $r['draw_date'];
            $out[$d] = [
                'date'     => $d,
                'date_vn'  => self::vnDate($d),
                'items'    => [[
                    'province_id'   => $provinceId,
                    'province_name' => $pname,
                    'num'           => $r['special_prize'],
                    'last2'         => self::last2($r['special_prize']),
                ]],
            ];
        }
        krsort($out);
        return array_values($out);
    }

    /**
     * Build daily digit sets cho head/tail của giải đặc biệt
     */
    public static function buildDailyHeadTailSetsSpecialPrize(array $rows): array
    {
        $head = [];
        $tail = [];

        foreach ($rows as $r) {
            $d  = $r['draw_date'];
            $sp = isset($r['special_prize']) ? (string)$r['special_prize'] : '';
            $h  = self::headDigit($sp);
            $t  = self::tailDigit($sp);

            if (!isset($head[$d])) $head[$d] = [];
            if (!isset($tail[$d])) $tail[$d] = [];

            $head[$d][$h] = true;
            $tail[$d][$t] = true;
        }

        ksort($head);
        ksort($tail);

        $headSets = [];
        foreach ($head as $d => $set) $headSets[$d] = array_map('strval', array_keys($set));
        $tailSets = [];
        foreach ($tail as $d => $set) $tailSets[$d] = array_map('strval', array_keys($set));

        return [$headSets, $tailSets];
    }

    /**
     * Tính gan cho digits (0-9)
     */
    public static function computeGanDigitsByDays(array $dailySets, bool $excludeNeverSeen = false): array
    {
        $cur  = array_fill_keys(range(0, 9), 0);
        $seen = array_fill_keys(range(0, 9), null);
        $max  = array_fill_keys(range(0, 9), 0);

        foreach ($dailySets as $date => $digits) {
            $present = array_fill_keys($digits, true);
            foreach ($cur as $d => $streak) {
                $key = (string)$d;
                if (isset($present[$key])) {
                    $seen[$d] = $date;
                    $cur[$d]  = 0;
                } else {
                    $cur[$d]++;
                    if ($cur[$d] > $max[$d]) $max[$d] = $cur[$d];
                }
            }
        }

        $rows = [];
        foreach ($cur as $d => $streak) {
            if ($excludeNeverSeen && $seen[$d] === null) continue;
            $rows[] = [
                'digit'     => (string)$d,
                'streak'    => (int)$streak,
                'last_seen' => $seen[$d] ? self::vnDate($seen[$d]) : null,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['streak'] === $b['streak']) return strcmp($a['digit'], $b['digit']);
            return $b['streak'] <=> $a['streak'];
        });

        return $rows;
    }

    /**
     * Tính cache lifetime thông minh dựa trên ngày
     */
    public static function getSmartCacheLifetime(array $dataWithDate, string $dateField = 'date'): int
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
     * Tạo cache key chuẩn cho thống kê
     * Fixed: Sanitize invalid characters for Phalcon cache
     */
    public static function buildCacheKey(string $region, string $action, array $params = []): string
    {
        $keyParts = [$region, $action];
        
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
     * Optimized method: Get aggregated statistics in single query
     */
    public static function getAggregatedStatistics(string $region, Mysql $db, array $dates, ?int $provinceId = null): array
    {
        if (empty($dates)) return [];
        
        $ph = implode(',', array_fill(0, count($dates), '?'));
        $whereClause = $provinceId ? 'lr.province_id = ? AND' : '';
        $bind = $provinceId ? [$provinceId] : [];
        $bind = array_merge($bind, [$region], $dates);
        
        // Nếu không có province_id, lấy dữ liệu tổng hợp cho toàn bộ region
        if (!$provinceId) {
            $sql = "SELECT 
                        lr.draw_date,
                        lr.province_id,
                        p.name AS province_name,
                        lr.special_prize,
                        lr.first_prize,
                        lr.second_prize,
                        lr.third_prize,
                        lr.fourth_prize,
                        lr.fifth_prize,
                        lr.sixth_prize,
                        lr.seventh_prize,
                        lr.eighth_prize
                    FROM lottery_results lr
                    JOIN provinces p ON p.id = lr.province_id
                    WHERE lr.draw_type = ? AND lr.draw_date IN ($ph)
                    ORDER BY lr.draw_date DESC";
        } else {
            $sql = "SELECT 
                        lr.draw_date,
                        lr.province_id,
                        p.name AS province_name,
                        lr.special_prize,
                        COUNT(*) as daily_count,
                        GROUP_CONCAT(lr.special_prize ORDER BY lr.province_id) as all_special_prizes
                    FROM lottery_results lr
                    JOIN provinces p ON p.id = lr.province_id
                    WHERE {$whereClause} lr.draw_type = ? AND lr.draw_date IN ($ph)
                    GROUP BY lr.draw_date, lr.province_id, p.name, lr.special_prize
                    ORDER BY lr.draw_date DESC, lr.province_id ASC";
        }
                
        $stmt = $db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Optimized: Get frequency statistics with single query
     */
    public static function getFrequencyStatistics(string $region, Mysql $db, array $dates, ?int $provinceId = null, int $limit = 10): array
    {
        if (empty($dates)) return [];
        
        $ph = implode(',', array_fill(0, count($dates), '?'));
        $whereClause = $provinceId ? 'province_id = ? AND' : '';
        $bind = $provinceId ? [$provinceId] : [];
        $bind = array_merge($bind, [$region], $dates);
        
        $sql = "SELECT 
                    RIGHT(special_prize, 2) as last2,
                    COUNT(*) as frequency,
                    MAX(draw_date) as last_seen
                FROM lottery_results
                WHERE {$whereClause} draw_type = ? AND draw_date IN ($ph)
                GROUP BY RIGHT(special_prize, 2)
                ORDER BY frequency DESC, last2 ASC";
                
        $stmt = $db->prepare($sql);
        $stmt->execute($bind);
        $allResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = array_slice($allResults, 0, $limit);
        $maxFreq = !empty($results) ? (int)$results[0]['frequency'] : 1;
        
        return array_map(function($row) use ($maxFreq) {
            return [
                'number' => $row['last2'],
                'count' => (int)$row['frequency'],
                'percent' => round(((int)$row['frequency'] * 100) / $maxFreq, 2),
                'last_seen' => self::vnDate($row['last_seen'])
            ];
        }, $results);
    }

    /**
     * Optimized: Get head/tail statistics with single query  
     */
    public static function getHeadTailStatistics(string $region, Mysql $db, array $dates, ?int $provinceId = null): array
    {
        if (empty($dates)) return ['head' => [], 'tail' => []];
        
        $ph = implode(',', array_fill(0, count($dates), '?'));
        $whereClause = $provinceId ? 'province_id = ? AND' : '';
        $bind = $provinceId ? [$provinceId] : [];
        $bind = array_merge($bind, [$region], $dates);
        
        $sql = "SELECT 
                    draw_date,
                    LEFT(RIGHT(LPAD(special_prize, 5, '0'), 2), 1) as head_digit,
                    RIGHT(special_prize, 1) as tail_digit
                FROM lottery_results
                WHERE {$whereClause} draw_type = ? AND draw_date IN ($ph)
                ORDER BY draw_date ASC";
                
        $stmt = $db->prepare($sql);
        $stmt->execute($bind);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Build daily sets for head and tail
        $headSets = [];
        $tailSets = [];
        
        foreach ($results as $row) {
            $date = $row['draw_date'];
            if (!isset($headSets[$date])) $headSets[$date] = [];
            if (!isset($tailSets[$date])) $tailSets[$date] = [];
            
            $headSets[$date][$row['head_digit']] = true;
            $tailSets[$date][$row['tail_digit']] = true;
        }
        
        // Convert to arrays
        foreach ($headSets as $date => $set) {
            $headSets[$date] = array_keys($set);
        }
        foreach ($tailSets as $date => $set) {
            $tailSets[$date] = array_keys($set);
        }
        
        return [
            'head' => self::computeDigitGanStats($headSets),
            'tail' => self::computeDigitGanStats($tailSets)
        ];
    }

    /**
     * Compute digit gan statistics (helper for head/tail statistics)
     */
    private static function computeDigitGanStats(array $digitsByDays, int $limit = 10): array
    {
        $ganStats = [];
        $allDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        // Count consecutive days each digit appears
        foreach ($allDigits as $digit) {
            $maxGan = 0;
            $currentGan = 0;
            
            foreach ($digitsByDays as $date => $digits) {
                if (in_array($digit, $digits)) {
                    $currentGan++;
                    $maxGan = max($maxGan, $currentGan);
                } else {
                    $currentGan = 0;
                }
            }
            
            if ($maxGan > 0) {
                $ganStats[] = [
                    'digit' => $digit,
                    'gan_days' => $maxGan
                ];
            }
        }
        
        // Sort by gan_days desc, then by digit
        usort($ganStats, function($a, $b) {
            if ($a['gan_days'] == $b['gan_days']) {
                return $a['digit'] <=> $b['digit'];
            }
            return $b['gan_days'] <=> $a['gan_days'];
        });
        
        return array_slice($ganStats, 0, $limit);
    }

    /**
     * Mapping từ slug tỉnh thành sang province_id (dựa trên dữ liệu thực tế từ database)
     */
    public static function getProvinceIdFromSlug(string $slug): ?int
    {
        $slugToProvinceMap = [
            // Miền Nam (XSMN) - dựa trên dữ liệu thực tế
            'xsvt' => 11,   // Vũng Tàu
            'xsvl' => 19,   // Vĩnh Long
            'xsbd' => 20,   // Bình Dương
            'xstv' => 21,   // Trà Vinh
            'xshcm' => 7,   // TP. Hồ Chí Minh
            'xsla' => 22,   // Long An
            'xsdl' => 26,   // Đồng Nai
            'xsbp' => 23,   // Bình Phước
            'xshg' => 24,   // Hậu Giang
            'xskg' => 25,   // Kiên Giang
            'xstg' => 27,   // Tiền Giang
            'xstn' => 16,   // Tây Ninh
            'xsdt' => 8,    // Đồng Tháp
            'xsag' => 17,   // An Giang
            'xsbt' => 10,   // Bến Tre
            'xsbth' => 18,  // Bình Thuận
            'xsdn' => 13,   // Đồng Nai (duplicate với xsdl)
            'xsbl' => 12,   // Bạc Liêu
            'xsct' => 14,   // Cần Thơ
            'xsst' => 15,   // Sóc Trăng
            'xscm' => 9,    // Cà Mau
            
            // Miền Trung (XSMT) - dựa trên dữ liệu thực tế
            'xsdng' => 28,  // Đà Nẵng
            'xsbdinh' => 31, // Bình Định
            'xstth' => 35,  // Thừa Thiên Huế
            'xsdlk' => 38,  // Đắk Lắk
            'xsqng' => 30,  // Quảng Ngãi
            'xsqt' => 37,   // Quảng Trị
            'xsqtri' => 37, // Quảng Trị (alias, redirect to xsqt)
            'xsqb' => 36,   // Quảng Bình
            'xsqn' => 29,   // Quảng Nam
            'xsdno' => 41,  // Đắk Nông
            'xsgl' => 39,   // Gia Lai
            'xsnt' => 40,   // Ninh Thuận
            'xspy' => 32,   // Phú Yên
            'xskh' => 33,   // Khánh Hòa
            'xsktum' => 34, // Kon Tum
            
            // Region slugs
            'xsmb' => null,  // Miền Bắc (region mode)
            'xsmt' => null,  // Miền Trung (region mode)
        ];
        
        return $slugToProvinceMap[$slug] ?? null;
    }

    /**
     * Lấy region từ slug tỉnh thành
     */
    public static function getRegionFromSlug(string $slug): ?string
    {
        $mnSlugs = ['xsvt', 'xsvl', 'xsbd', 'xstv', 'xshcm', 'xsla', 'xsdl', 'xsbp', 'xshg', 'xskg', 'xstg', 'xstn', 'xsdt', 'xsag', 'xsbt', 'xsbth', 'xsdn', 'xsbl', 'xsct', 'xsst', 'xscm'];
        $mtSlugs = ['xsdng', 'xsbdinh', 'xstth', 'xsdlk', 'xsqng', 'xsqt', 'xsqtri', 'xsqb', 'xsqn', 'xsdno', 'xsgl', 'xsnt', 'xspy', 'xskh', 'xsktum'];
        
        if (in_array($slug, $mnSlugs)) {
            return 'XSMN';
        } elseif (in_array($slug, $mtSlugs)) {
            return 'XSMT';
        } elseif ($slug === 'xsmb') {
            return 'XSMB'; // Miền Bắc
        } elseif ($slug === 'xsmt') {
            return 'XSMT'; // Miền Trung
        }
        
        return null;
    }

    /**
     * Lấy slug từ province_id
     */
    public static function getSlugFromProvinceId(int $provinceId): ?string
    {
        $slugMap = [
            // Miền Nam
            11 => 'xsvt', 19 => 'xsvl', 20 => 'xsbd', 21 => 'xstv',
            7 => 'xshcm', 22 => 'xsla', 26 => 'xsdl', 23 => 'xsbp',
            24 => 'xshg', 25 => 'xskg', 27 => 'xstg', 16 => 'xstn',
            8 => 'xsdt', 17 => 'xsag', 10 => 'xsbt', 18 => 'xsbth',
            13 => 'xsdn', 12 => 'xsbl', 14 => 'xsct', 15 => 'xsst', 9 => 'xscm',
            // Miền Trung
            28 => 'xsdng', 31 => 'xsbdinh', 35 => 'xstth', 38 => 'xsdlk',
            30 => 'xsqng', 37 => 'xsqt', 36 => 'xsqb', 29 => 'xsqn',
            41 => 'xsdno', 39 => 'xsgl', 40 => 'xsnt', 32 => 'xspy',
            33 => 'xskh', 34 => 'xsktum',
        ];
        
        return $slugMap[$provinceId] ?? null;
    }

    /**
     * Lấy tên tỉnh từ slug
     */
    public static function getProvinceNameFromSlug(string $slug): ?string
    {
        $nameMap = [
            // Miền Nam
            'xsvt' => 'Vũng Tàu', 'xsvl' => 'Vĩnh Long', 'xsbd' => 'Bình Dương',
            'xstv' => 'Trà Vinh', 'xshcm' => 'TP. Hồ Chí Minh', 'xsla' => 'Long An',
            'xsdl' => 'Đồng Nai', 'xsbp' => 'Bình Phước', 'xshg' => 'Hậu Giang',
            'xskg' => 'Kiên Giang', 'xstg' => 'Tiền Giang', 'xstn' => 'Tây Ninh',
            'xsdt' => 'Đồng Tháp', 'xsag' => 'An Giang', 'xsbt' => 'Bến Tre',
            'xsbth' => 'Bình Thuận', 'xsdn' => 'Đồng Nai', 'xsbl' => 'Bạc Liêu',
            'xsct' => 'Cần Thơ', 'xsst' => 'Sóc Trăng', 'xscm' => 'Cà Mau',
            // Miền Trung
            'xsdng' => 'Đà Nẵng', 'xsbdinh' => 'Bình Định', 'xstth' => 'Thừa Thiên Huế',
            'xsdlk' => 'Đắk Lắk', 'xsqng' => 'Quảng Ngãi', 'xsqt' => 'Quảng Trị',
            'xsqb' => 'Quảng Bình', 'xsqn' => 'Quảng Nam', 'xsdno' => 'Đắk Nông',
            'xsgl' => 'Gia Lai', 'xsnt' => 'Ninh Thuận', 'xspy' => 'Phú Yên',
            'xskh' => 'Khánh Hòa', 'xsktum' => 'Kon Tum',
        ];
        
        return $nameMap[$slug] ?? null;
    }

    /**
     * Lấy code tỉnh từ slug (viết hoa)
     */
    public static function getProvinceCodeFromSlug(string $slug): ?string
    {
        $codeMap = [
            // Miền Nam
            'xsvt' => 'XSVT', 'xsvl' => 'XSVL', 'xsbd' => 'XSBD', 'xstv' => 'XSTV',
            'xshcm' => 'XSHCM', 'xsla' => 'XSLA', 'xsdl' => 'XSDL', 'xsbp' => 'XSBP',
            'xshg' => 'XSHG', 'xskg' => 'XSKG', 'xstg' => 'XSTG', 'xstn' => 'XSTN',
            'xsdt' => 'XSDT', 'xsag' => 'XSAG', 'xsbt' => 'XSBT', 'xsbth' => 'XSBTH',
            'xsdn' => 'XSDN', 'xsbl' => 'XSBL', 'xsct' => 'XSCT', 'xsst' => 'XSST', 'xscm' => 'XSCM',
            // Miền Trung
            'xsdng' => 'XSDNG', 'xsbdinh' => 'XSBDINH', 'xstth' => 'XSTTH', 'xsdlk' => 'XSDLK',
            'xsqng' => 'XSQNG', 'xsqt' => 'XSQT', 'xsqb' => 'XSQB', 'xsqn' => 'XSQN',
            'xsdno' => 'XSDNO', 'xsgl' => 'XSGL', 'xsnt' => 'XSNT', 'xspy' => 'XSPY',
            'xskh' => 'XSKH', 'xsktum' => 'XSKTUM',
        ];
        
        return $codeMap[$slug] ?? strtoupper($slug);
    }

    /**
     * Lấy code tỉnh từ province_id
     */
    public static function getProvinceCodeFromId(int $provinceId): ?string
    {
        $slug = self::getSlugFromProvinceId($provinceId);
        return $slug ? self::getProvinceCodeFromSlug($slug) : null;
    }

    /**
     * Tạo menu thống kê động cho tỉnh
     */
    public static function generateStatisticsMenu(?int $provinceId, string $provinceName, string $currentPage = 'thongke'): string
    {
        if (!$provinceId) {
            return ''; // Không hiển thị menu cho region mode
        }
        
        $slug = self::getSlugFromProvinceId($provinceId);
        if (!$slug) {
            return '';
        }
        
        $pages = [
            'thongke' => ['url' => "/thong-ke-{$slug}.html", 'label' => "Thống kê {$provinceName}"],
            'logan' => ['url' => "/thong-ke-lo-gan-{$slug}.html", 'label' => "Lô gan {$provinceName}"],
            'dacbiet' => ['url' => "/thong-ke-dac-biet-{$slug}.html", 'label' => "Giải đặc biệt {$provinceName}"],
            'dauduoi' => ['url' => "/thong-ke-dau-duoi-{$slug}.html", 'label' => "Đầu đuôi {$provinceName}"],
            'tansuat' => ['url' => "/thong-ke-tan-suat-{$slug}.html", 'label' => "Tần suất {$provinceName}"],
        ];
        
        $html = '<div class="bg-white">';
        // $html .= '<div class="bg-red text-white border-bottom fs-6 fw-medium p-2 px-3 mb-0 rounded-top">';
        // $html .= '<div class="bg-red text-white text-decoration-none">Thống kê xổ số</div>';
        // $html .= '</div>';
        $html .= '<ul class="row gx-0 -pt-3 pb-3">';
        
        foreach ($pages as $page => $data) {
            $activeClass = ($page === $currentPage) ? 'text-red' : '';
            $html .= sprintf(
                '<li class="col-6"><a class="d-block pt-1 pb-1 %s" title="%s" href="%s">%s</a></li>',
                $activeClass,
                htmlspecialchars($data['label'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($data['label'], ENT_QUOTES, 'UTF-8')
            );
        }
        
        $html .= '</ul></div>';
        
        return $html;
    }
}