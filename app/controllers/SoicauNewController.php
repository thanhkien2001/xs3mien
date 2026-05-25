<?php

namespace App\Controllers;

use App\Models\LotteryResults;
use App\Models\Provinces;
use App\Library\ThongkeStatisticsHelper;
use App\Library\KqxsHelper;
use Phalcon\Mvc\Controller;

class SoicauNewController extends Controller
{
    public function soiCauXsmtAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Soi cầu XSMT - Soi cầu xổ số miền Trung chính xác 100%';
        $description = 'Soi cầu XSMT hôm nay - Dự đoán xổ số miền Trung chính xác nhất, cập nhật liên tục các cặp số đẹp, bạch thủ lô miền Trung.';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        // Xác định ngày dự đoán
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $currentHour = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');

        // Nếu sau 17:30 thì dự đoán cho ngày mai
        if ($currentHour > 17 || ($currentHour == 17 && $currentMinute >= 30)) {
            $targetDate = clone $now;
            $targetDate->modify('+1 day');
        } else {
            $targetDate = clone $now;
        }

        $targetDateStr = $targetDate->format('Y-m-d');
        $targetDateDisplay = $targetDate->format('d/m/Y');

        // Tính ngày tuần trước (7 ngày trước)
        $lastWeekDate = clone $targetDate;
        $lastWeekDate->modify('-7 days');
        $lastWeekDateStr = $lastWeekDate->format('Y-m-d');

        $currentDateDisplay = $now->format('d/m/Y');
        $this->view->setVar('current_date_full', ThongkeStatisticsHelper::weekdayVN($now->format('Y-m-d')) . ', ' . $currentDateDisplay . ' - 01:00');
        $this->view->setVar('current_date', $targetDateDisplay);

        // Lấy kết quả XSMT tuần trước
        $lastWeekResults = LotteryResults::find([
            'conditions' => 'draw_date = :date: AND draw_type = "XSMT"',
            'bind' => ['date' => $lastWeekDateStr],
            'order' => 'province_id ASC'
        ]);

        // Lấy tên tỉnh từ bảng Provinces
        $provinceIds = [];
        foreach ($lastWeekResults as $result) {
            $provinceIds[] = $result->province_id;
        }

        $provinceMap = [];
        if (!empty($provinceIds)) {
            $provinces = Provinces::find([
                'conditions' => 'id IN ({ids:array})',
                'bind' => ['ids' => $provinceIds]
            ]);

            foreach ($provinces as $province) {
                $provinceMap[$province->id] = [
                    'name' => $province->name,
                    'code' => $province->code,
                    'keyid' => $province->keyid
                ];
            }
        }

        // Tạo dự đoán cho từng tỉnh
        $provinceData = [];
        foreach ($lastWeekResults as $result) {
            $provinceInfo = $provinceMap[$result->province_id] ?? null;
            if (!$provinceInfo)
                continue;

            $predictions = $this->generatePredictions($result);

            $provinceData[] = [
                'name' => $provinceInfo['name'],
                'code' => $provinceInfo['code'],
                'keyid' => $provinceInfo['keyid'],
                'url' => '/xo-so-' . $provinceInfo['keyid'] . '.html',
                'predictions' => $predictions
            ];
        }

        $this->view->setVar('province_data', $provinceData);

        // Chuẩn bị dữ liệu kết quả để hiển thị
        $resultRows = [];
        foreach ($lastWeekResults as $result) {
            $provinceInfo = $provinceMap[$result->province_id] ?? null;
            if (!$provinceInfo)
                continue;

            $resultRows[] = [
                'province_id' => $result->province_id,
                'province_name' => $provinceInfo['name'],
                'province_code' => $provinceInfo['code'],
                'keyid' => $provinceInfo['keyid'],
                'eighth_prize' => $result->eighth_prize,
                'seventh_prize' => $result->seventh_prize,
                'sixth_prize' => $result->sixth_prize,
                'fifth_prize' => $result->fifth_prize,
                'fourth_prize' => $result->fourth_prize,
                'third_prize' => $result->third_prize,
                'second_prize' => $result->second_prize,
                'first_prize' => $result->first_prize,
                'special_prize' => $result->special_prize
            ];
        }

        $this->view->setVar('xsmt_result_data', [
            'rows' => $resultRows,
            'date' => $lastWeekDateStr,
            'region' => 'XSMT'
        ]);

        // Rating
        $this->view->setVar('rating_value', 4.1);
        $this->view->setVar('rating_count', 1245);

        // Schema
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get('/soi-cau-xsmt.html'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/soi-cau-xsmt.html']
            ]
        ]);

        $this->view->pick('soicaunew/soiCauXsmt');
    }

    private function generatePredictions($result)
    {
        $allNumbers = $this->extractNumbers($result);

        if (empty($allNumbers)) {
            return [
                'dac_biet_dau' => rand(0, 9),
                'dac_biet_duoi' => rand(0, 9),
                'bach_thu' => str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                'lo_xien_2' => [
                    str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                    str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT)
                ],
                'lo_dep' => [
                    str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                    str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                    str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT)
                ],
                'lo_kep' => ['00', '11']
            ];
        }

        // Phân tích tần suất
        $frequency = array_count_values($allNumbers);
        arsort($frequency);
        $topNumbers = array_keys(array_slice($frequency, 0, 10, true));

        $bachThu = $topNumbers[0] ?? str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        $loDep = array_slice($topNumbers, 0, 3);
        $loXien = array_slice($topNumbers, 1, 2);

        // Tìm lô kép
        $loKep = [];
        foreach ($allNumbers as $num) {
            if (strlen($num) == 2 && $num[0] == $num[1]) {
                $loKep[] = $num;
            }
        }
        $loKep = array_unique($loKep);
        $loKep = array_slice($loKep, 0, 2);

        // Dự đoán đầu đuôi giải ĐB
        $specialPrize = $result->special_prize ?? '';
        $dauDb = rand(0, 9);
        $duoiDb = rand(0, 9);

        if (strlen($specialPrize) >= 2) {
            $lastTwo = substr($specialPrize, -2);
            if (strlen($lastTwo) == 2) {
                $dauDb = ($lastTwo[0] + 1) % 10;
                $duoiDb = ($lastTwo[1] + 1) % 10;
            }
        }

        return [
            'dac_biet_dau' => $dauDb,
            'dac_biet_duoi' => $duoiDb,
            'bach_thu' => $bachThu,
            'lo_xien_2' => $loXien,
            'lo_dep' => $loDep,
            'lo_kep' => $loKep
        ];
    }

    private function extractNumbers($result)
    {
        $prizes = [
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
        $allNumbers = [];

        foreach ($prizes as $prize) {
            $val = $result->$prize ?? '';
            if (empty($val))
                continue;

            if (is_string($val)) {
                $clean = trim($val, "[]\"");
                $parts = preg_split('/[",]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $num) {
                    $num = trim($num);
                    if (strlen($num) >= 2) {
                        $allNumbers[] = substr($num, -2);
                    }
                }
            } elseif (is_array($val)) {
                foreach ($val as $num) {
                    if (strlen($num) >= 2) {
                        $allNumbers[] = substr($num, -2);
                    }
                }
            }
        }

        return $allNumbers;
    }

    public function soiCauXsmnAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Soi cầu XSMN - Soi cầu miền Nam chính xác nhất';
        $description = 'Soi cầu MN - Soi cầu XSMN siêu chuẩn xác hôm nay xác suất nổ 90% trong ngày. Tham khảo những phân tích, chốt số soi cầu miền Nam Vip miễn phí từ những cao thủ, chuyên gia lô đề.';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        // Xác định ngày dự đoán
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $currentHour = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');

        // Nếu sau 16:30 thì dự đoán cho ngày mai
        if ($currentHour > 16 || ($currentHour == 16 && $currentMinute >= 30)) {
            $targetDate = clone $now;
            $targetDate->modify('+1 day');
        } else {
            $targetDate = clone $now;
        }

        $targetDateStr = $targetDate->format('Y-m-d');
        $targetDateDisplay = $targetDate->format('d/m/Y');

        // Tính ngày tuần trước (7 ngày trước)
        $lastWeekDate = clone $targetDate;
        $lastWeekDate->modify('-7 days');
        $lastWeekDateStr = $lastWeekDate->format('Y-m-d');

        $currentDateDisplay = $now->format('d/m/Y');
        $this->view->setVar('current_date_full', ThongkeStatisticsHelper::weekdayVN($now->format('Y-m-d')) . ', ' . $currentDateDisplay . ' - 01:00');
        $this->view->setVar('current_date', $targetDateDisplay);

        // Lấy kết quả XSMN tuần trước
        $lastWeekResults = LotteryResults::find([
            'conditions' => 'draw_date = :date: AND draw_type = "XSMN"',
            'bind' => ['date' => $lastWeekDateStr],
            'order' => 'province_id ASC'
        ]);

        // Lấy tên tỉnh từ bảng Provinces
        $provinceIds = [];
        foreach ($lastWeekResults as $result) {
            $provinceIds[] = $result->province_id;
        }

        $provinceMap = [];
        if (!empty($provinceIds)) {
            $provinces = Provinces::find([
                'conditions' => 'id IN ({ids:array})',
                'bind' => ['ids' => $provinceIds]
            ]);

            foreach ($provinces as $province) {
                $provinceMap[$province->id] = [
                    'name' => $province->name,
                    'code' => $province->code,
                    'keyid' => $province->keyid
                ];
            }
        }

        // Tạo dự đoán cho từng tỉnh
        $provinceData = [];
        foreach ($lastWeekResults as $result) {
            $provinceInfo = $provinceMap[$result->province_id] ?? null;
            if (!$provinceInfo)
                continue;

            $predictions = $this->generatePredictions($result);

            $provinceData[] = [
                'name' => $provinceInfo['name'],
                'code' => $provinceInfo['code'],
                'keyid' => $provinceInfo['keyid'],
                'url' => '/xo-so-' . $provinceInfo['keyid'] . '.html',
                'predictions' => $predictions
            ];
        }

        $this->view->setVar('province_data', $provinceData);

        // Chuẩn bị dữ liệu kết quả để hiển thị
        $resultRows = [];
        foreach ($lastWeekResults as $result) {
            $provinceInfo = $provinceMap[$result->province_id] ?? null;
            if (!$provinceInfo)
                continue;

            $resultRows[] = [
                'province_id' => $result->province_id,
                'province_name' => $provinceInfo['name'],
                'province_code' => $provinceInfo['code'],
                'keyid' => $provinceInfo['keyid'],
                'eighth_prize' => $result->eighth_prize,
                'seventh_prize' => $result->seventh_prize,
                'sixth_prize' => $result->sixth_prize,
                'fifth_prize' => $result->fifth_prize,
                'fourth_prize' => $result->fourth_prize,
                'third_prize' => $result->third_prize,
                'second_prize' => $result->second_prize,
                'first_prize' => $result->first_prize,
                'special_prize' => $result->special_prize
            ];
        }

        $this->view->setVar('xsmn_result_data', [
            'rows' => $resultRows,
            'date' => $lastWeekDateStr,
            'region' => 'XSMN'
        ]);

        // Rating
        $this->view->setVar('rating_value', 3.6);
        $this->view->setVar('rating_count', 447);

        // Schema
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get('/soi-cau-xsmn.html'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/soi-cau-xsmn.html']
            ]
        ]);

        $this->view->pick('soicaunew/soiCauXsmn');
    }
}
