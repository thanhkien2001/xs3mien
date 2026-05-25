<?php
namespace App\Library;
class KqxsHelper
{
    public static function toArray($value): array
    {
        if (is_array($value)) return array_values(array_map('strval', $value));
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_map('strval', $decoded));
            }
            if (strpos($value, ',') !== false) {
                return array_values(array_map('trim', explode(',', $value)));
            }
            return [$value];
        }
        return [];
    }

    public static function collectAllTwoDigits(array $r): array
    {
        $out = [];
        $push = function ($num) use (&$out) {
            $num = trim((string)$num);
            if ($num === '') return;
            $out[] = substr(str_pad($num, 2, '0', STR_PAD_LEFT), -2);
        };

        if (!empty($r['special_prize'])) $push($r['special_prize']);
        if (!empty($r['first_prize']))   $push($r['first_prize']);

        foreach (['second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize', 'eighth_prize'] as $k) {
            foreach (self::toArray($r[$k] ?? []) as $n) $push($n);
        }
        return $out;
    }

    public static function buildDauDuoi(array $twoDigits): array
    {
        $dau  = array_fill(0, 10, []);
        $duoi = array_fill(0, 10, []);
        foreach ($twoDigits as $v) {
            if (strlen($v) !== 2) continue;
            $dau[(int)$v[0]][]  = $v;
            $duoi[(int)$v[1]][] = $v;
        }
        $norm = function (&$a) {
            foreach ($a as &$lst) {
                $lst = array_values(array_unique($lst));
                sort($lst, SORT_STRING);
            }
        };
        $norm($dau);
        $norm($duoi);
        return ['dau' => $dau, 'duoi' => $duoi];
    }

    public static function weekdayInfo(\DateTime $date): array
    {
        $map = [
            1 => ['Thứ 2', 'thu-2'],
            2 => ['Thứ 3', 'thu-3'],
            3 => ['Thứ 4', 'thu-4'],
            4 => ['Thứ 5', 'thu-5'],
            5 => ['Thứ 6', 'thu-6'],
            6 => ['Thứ 7', 'thu-7'],
            7 => ['Chủ Nhật', 'chu-nhat'],
        ];
        return $map[(int)$date->format('N')] ?? ['Chủ Nhật', 'chu-nhat'];
    }

    public static function otherDaysLinksXSMB(\DateTime $date, int $count = 12): array
    {
        $links = [];
        
        // Thêm ngày mai (next day) vào đầu danh sách
        $tomorrow = (clone $date)->modify("+1 day");
        $dl = (int)$tomorrow->format('j');
        $ml = (int)$tomorrow->format('n');
        $y = $tomorrow->format('Y');
        $titleDate = $tomorrow->format('d/m/Y');
        // $links[] = [
        //     // 'title' => "XSMB {$dl}/{$ml}/{$y}",
        //     'title' => "XSMB {$titleDate}",
        //     'href'  => sprintf("/xsmb-%d-%d-ket-qua-xo-so-mien-bac-ngay-%d-%d-%d", $dl, $ml, $dl, $ml, $y),
        //     'is_tomorrow' => true,
        // ];
        
        // Thêm các ngày còn lại (bao gồm cả ngày hiện tại)
        for ($i = 0; $i < $count; $i++) {
            $d = (clone $date)->modify("-{$i} day");
            $dl = (int)$d->format('j');
            $ml = (int)$d->format('n');
            $y = $d->format('Y');
            $titleDate = $d->format('d/m/Y');
            $links[] = [
                // 'title' => "XSMB {$dl}/{$ml}/{$y}",
                'title' => "XSMB {$titleDate}",
                'href'  => sprintf("/xsmb-%d-%d-ket-qua-xo-so-mien-bac-ngay-%d-%d-%d", $dl, $ml, $dl, $ml, $y),
                'is_today' => $i === 0,
            ];
        }
        return $links;
    }
    public static function otherDaysLinksXSMN(\DateTime $date, int $count = 12): array
    {
        $links = [];
        
        // Thêm ngày mai (next day) vào đầu danh sách
        $tomorrow = (clone $date)->modify("+1 day");
        $dl = (int)$tomorrow->format('j');
        $ml = (int)$tomorrow->format('n');
        $y = $tomorrow->format('Y');
        $titleDate = $tomorrow->format('d/m/Y');
        // $links[] = [
        //     // 'title' => "XSMN {$dl}/{$ml}/{$y}",
        //     'title' => "XSMN {$titleDate}",
        //     'href'  => sprintf("/xsmn-%d-%d-ket-qua-xo-so-mien-nam-ngay-%d-%d-%d", $dl, $ml, $dl, $ml, $y),
        //     'is_tomorrow' => true,
        // ];
        
        // Thêm các ngày còn lại (bao gồm cả ngày hiện tại)
        for ($i = 0; $i < $count; $i++) {
            $d = (clone $date)->modify("-{$i} day");
            $dl = (int)$d->format('j');
            $ml = (int)$d->format('n');
            $y = $d->format('Y');
            $titleDate = $d->format('d/m/Y');
            $links[] = [
                // 'title' => "XSMN {$dl}/{$ml}/{$y}",
                'title' => "XSMN {$titleDate}",
                'href'  => sprintf("/xsmn-%d-%d-ket-qua-xo-so-mien-nam-ngay-%d-%d-%d", $dl, $ml, $dl, $ml, $y),
                'is_today' => $i === 0,
            ];
        }
        return $links;
    }
    public static function otherDaysLinksXSMT(\DateTime $date, int $count = 12): array
    {
        $links = [];
        
        // Thêm ngày mai (next day) vào đầu danh sách
        $tomorrow = (clone $date)->modify("+1 day");
        $dl = (int)$tomorrow->format('j');
        $ml = (int)$tomorrow->format('n');
        $y = $tomorrow->format('Y');
        $titleDate = $tomorrow->format('d/m/Y');
        // $links[] = [
        //     // 'title' => "XSMT {$dl}/{$ml}/{$y}",
        //     'title' => "XSMT {$titleDate}",
        //     'href'  => sprintf("/xsmt-%d-%d-ket-qua-xo-so-mien-trung-ngay-%d-%d-%d", $dl, $ml, $dl, $ml, $y),
        //     'is_tomorrow' => true,
        // ];
        
        // Thêm các ngày còn lại (bao gồm cả ngày hiện tại)
        for ($i = 0; $i < $count; $i++) {
            $d = (clone $date)->modify("-{$i} day");
            $dl = (int)$d->format('j');
            $ml = (int)$d->format('n');
            $y = $d->format('Y');
            $titleDate = $d->format('d/m/Y');
            $links[] = [
                // 'title' => "XSMT {$dl}/{$ml}/{$y}",
                'title' => "XSMT {$titleDate}",
                'href'  => sprintf("/xsmt-%d-%d-ket-qua-xo-so-mien-trung-ngay-%d-%d-%d", $dl, $ml, $dl, $ml, $y),
                'is_today' => $i === 0,
            ];
        }
        return $links;
    }
    public static function slugify(string $text): string
    {
        $text = trim($text);
        if ($text === '') return 'n-a';

        $text = mb_strtolower($text, 'UTF-8');

        // Chuẩn hoá tiếng Việt
        $replacements = [
            // a
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            // e
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            // i
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            // o
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            // u
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            // y
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            // d
            'đ' => 'd',
            // khoảng trắng/chuỗi đặc biệt thường gặp
            ' & ' => ' va ',
        ];
        $text = strtr($text, $replacements);

        // Loại ký tự không phải chữ/số -> '-'
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');

        return $text !== '' ? $text : 'n-a';
    }
}
