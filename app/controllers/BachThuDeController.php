<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Library\PerformanceHelper;

class BachThuDeController extends ControllerBase
{
    public function bachThuDeXsmbHomNayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Bạch thủ đề hôm nay - Độc thủ đề XSMB tỷ lệ về cực cao';
        $description = 'Bạch thủ đề (còn gọi là Độc thủ đề) là một trong những phương pháp chơi lô đề phổ biến tại Việt Nam, được nhiều người chơi áp dụng vì tính đơn giản nhưng cần sự chính xác cao trong phân tích và lựa chọn con số';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        $db = $this->di->get('db');

        // 1. Get latest XSMB date
        $latestDraw = \App\Models\LotteryResults::findFirst([
            'conditions' => 'draw_type = "XSMB"',
            'order' => 'draw_date DESC'
        ]);

        $currentDateTs = $latestDraw ? strtotime($latestDraw->draw_date) : time();
        $todayStr = date('Y-m-d', $currentDateTs);
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN($todayStr) . ', ' . date('d/m/Y', $currentDateTs) . ' - 01:00');

        // Fetch last 200 days to have enough context
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 200);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to get rows BEFORE a certain date
        $getRowsBefore = function ($date, $limit = 100) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] < $date) {
                    $filtered[] = $r;
                    if (count($filtered) >= $limit)
                        break;
                }
            }
            return $filtered;
        };

        // Algorithm to predict Độc thủ đề
        $predictDocThuDe = function ($date) use ($getRowsBefore) {
            $rows = $getRowsBefore($date, 50);
            if (empty($rows))
                return '00';

            $freq = array_fill(0, 100, 0);
            $lastSeen = array_fill(0, 100, 100);

            foreach ($rows as $index => $row) {
                $last2 = \App\Library\ThongkeStatisticsHelper::last2($row['special_prize']);
                $num = (int) $last2;
                $freq[$num]++;
                if ($lastSeen[$num] === 100) {
                    $lastSeen[$num] = $index;
                }
            }

            // Find number with longest gan (not appeared)
            arsort($lastSeen);
            $ganNum = key($lastSeen);

            // Also consider frequency
            arsort($freq);
            $freqNum = key($freq);

            // Combine logic: prefer gan if it's been more than 10 days
            if ($lastSeen[$ganNum] > 10) {
                return str_pad((string) $ganNum, 2, '0', STR_PAD_LEFT);
            }

            return str_pad((string) $freqNum, 2, '0', STR_PAD_LEFT);
        };

        // Generate Độc thủ đề predictions for next 10 days
        $docThuDeHistory = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i >= count($allDates))
                break;

            $date = $allDates[$i];
            $dateTs = strtotime($date);

            // Predict for this date
            $prediction = $predictDocThuDe($date);

            // Check actual result
            $actualLast2 = '';
            $status = 'Chờ kết quả';

            foreach ($allRows as $r) {
                if ($r['draw_date'] === $date) {
                    $actualLast2 = \App\Library\ThongkeStatisticsHelper::last2($r['special_prize']);
                    if ($actualLast2 === $prediction) {
                        $status = 'Trúng <span class="text-red font-weight-bold">' . $actualLast2 . '</span>';
                    } else {
                        $status = 'Trượt';
                    }
                    break;
                }
            }

            // If date is in the future
            if ($dateTs > time()) {
                $status = 'Chờ kết quả';
            }

            $docThuDeHistory[] = [
                'date' => date('d/m/Y', $dateTs),
                'date_link' => date('d-m-Y', $dateTs),
                'prediction' => $prediction,
                'status' => $status
            ];
        }

        $this->view->setVar('doc_thu_de_history', $docThuDeHistory);

        // 2. Thống kê đặc biệt - Find all occurrences of a specific last 2 digits
        // Get the last special prize last 2 digits
        $latestLast2 = '';
        if (!empty($allRows)) {
            $latestLast2 = \App\Library\ThongkeStatisticsHelper::last2($allRows[0]['special_prize']);
        }

        // Find when this number appeared before and what came next
        $specialStats = [];
        $targetNum = $latestLast2;

        foreach ($allRows as $idx => $row) {
            $last2 = \App\Library\ThongkeStatisticsHelper::last2($row['special_prize']);
            if ($last2 === $targetNum && $idx < count($allRows) - 1) {
                // Get next day's result
                $nextRow = $allRows[$idx + 1];
                $nextLast2 = \App\Library\ThongkeStatisticsHelper::last2($nextRow['special_prize']);

                $specialStats[] = [
                    'date' => date('d/m/Y', strtotime($row['draw_date'])),
                    'date_link' => date('d-m-Y', strtotime($row['draw_date'])),
                    'special' => $row['special_prize'],
                    'target' => $targetNum,
                    'next_date' => date('d/m/Y', strtotime($nextRow['draw_date'])),
                    'next_date_link' => date('d-m-Y', strtotime($nextRow['draw_date'])),
                    'next_special' => $nextRow['special_prize'],
                    'next_last2' => $nextLast2
                ];
            }
        }

        $this->view->setVar('special_stats', array_slice($specialStats, 0, 60));
        $this->view->setVar('current_target', $targetNum);
        $this->view->setVar('current_special', $allRows[0]['special_prize'] ?? '');
        $this->view->setVar('current_date_stats', date('d/m/Y', strtotime($allRows[0]['draw_date'] ?? 'now')));

        // 3. Thống kê giải đặc biệt theo ngày trong năm (e.g., 13/01 hàng năm)
        $currentDay = date('d', $currentDateTs);
        $currentMonth = date('m', $currentDateTs);

        $yearlyStats = [];
        foreach ($allRows as $row) {
            $rowDate = $row['draw_date'];
            if (date('d', strtotime($rowDate)) === $currentDay && date('m', strtotime($rowDate)) === $currentMonth) {
                $yearlyStats[] = [
                    'year' => date('Y', strtotime($rowDate)),
                    'date' => date('d/m/Y', strtotime($rowDate)),
                    'date_link' => date('d-m-Y', strtotime($rowDate)),
                    'special' => $row['special_prize'],
                    'last2' => \App\Library\ThongkeStatisticsHelper::last2($row['special_prize'])
                ];
            }
        }

        $this->view->setVar('yearly_stats', array_slice($yearlyStats, 0, 20));
        $this->view->setVar('stats_day', $currentDay);
        $this->view->setVar('stats_month', $currentMonth);

        $this->view->setVar('rating_value', 3.6);
        $this->view->setVar('rating_count', 6424);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/bach-thu-de-xsmb-hom-nay.html']
            ]
        ]);

        $this->view->pick('bachthu/bachThuDeXsmbHomNay');
    }

    public function soiCauSongThuDeAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Song thủ đề: Soi cầu song thủ đề XSMB từ các chuyên gia xổ số';
        $description = 'Soi cầu song thủ đề miền Bắc miễn phí với những con số "Đẹp nhất và VIP nhất", anh em hoàn toàn không cần phải lo lắng về chất lượng vì khi đến với Soi cầu 247 đã "Chơi là phải trúng"';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        $db = $this->di->get('db');

        // 1. Get latest XSMB date
        $latestDraw = \App\Models\LotteryResults::findFirst([
            'conditions' => 'draw_type = "XSMB"',
            'order' => 'draw_date DESC'
        ]);

        $currentDateTs = $latestDraw ? strtotime($latestDraw->draw_date) : time();
        $todayStr = date('Y-m-d', $currentDateTs);
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN($todayStr) . ', ' . date('d/m/Y', $currentDateTs) . ' - 01:00');

        // Fetch last 200 days
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 200);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to extract all lottos from a result
        $extractAllLotto = function ($row) {
            $prizes = ['special_prize', 'first_prize', 'second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize'];
            $allLotto = [];
            foreach ($prizes as $p) {
                $val = $row[$p] ?? '';
                if (empty($val))
                    continue;
                $clean = trim($val, "[]\"");
                $parts = preg_split('/[",]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $num) {
                    $allLotto[] = substr($num, -2);
                }
            }
            return $allLotto;
        };

        // Helper to get rows BEFORE a certain date
        $getRowsBefore = function ($date, $limit = 100) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] < $date) {
                    $filtered[] = $r;
                    if (count($filtered) >= $limit)
                        break;
                }
            }
            return $filtered;
        };

        // Algorithm to predict Song Thủ Đề (pair of reversed numbers)
        $predictSongThuDe = function ($date) use ($getRowsBefore, $extractAllLotto) {
            $rows = $getRowsBefore($date, 50);
            if (empty($rows))
                return ['00', '00'];

            // Count frequency of all numbers
            $freq = array_fill(0, 100, 0);
            $lastSeen = array_fill(0, 100, 100);

            foreach ($rows as $index => $row) {
                $lottos = $extractAllLotto($row);
                foreach ($lottos as $lotto) {
                    $num = (int) $lotto;
                    $freq[$num]++;
                    if ($lastSeen[$num] === 100) {
                        $lastSeen[$num] = $index;
                    }
                }
            }

            // Find best candidate based on gan and frequency
            arsort($lastSeen);
            $ganCandidates = array_slice(array_keys($lastSeen), 0, 20, true);

            // Among gan candidates, pick one with decent frequency
            $bestNum = 0;
            $bestScore = -1;
            foreach ($ganCandidates as $num) {
                $score = $lastSeen[$num] * 2 + $freq[$num];
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestNum = $num;
                }
            }

            // Create reversed pair
            $num1 = str_pad((string) $bestNum, 2, '0', STR_PAD_LEFT);
            $reversed = $num1[1] . $num1[0];

            return [$num1, $reversed];
        };

        // Generate Song Thủ Đề predictions for last 10 days
        $songThuDeHistory = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i >= count($allDates))
                break;

            $date = $allDates[$i];
            $dateTs = strtotime($date);

            // Predict for this date
            $pair = $predictSongThuDe($date);

            // Check actual result
            $status = 'Chờ kết quả';
            $actualLottos = [];

            foreach ($allRows as $r) {
                if ($r['draw_date'] === $date) {
                    $actualLottos = $extractAllLotto($r);
                    break;
                }
            }

            if (!empty($actualLottos)) {
                $hit = false;
                $hitNum = '';
                foreach ($pair as $p) {
                    if (in_array($p, $actualLottos)) {
                        $hit = true;
                        $hitNum = $p;
                        break;
                    }
                }

                if ($hit) {
                    $status = 'Trúng <span class="text-red font-weight-bold">' . $hitNum . '</span>';
                } else {
                    $status = 'Xịt';
                }
            } elseif ($dateTs > time()) {
                $status = 'Chờ kết quả';
            } else {
                $status = 'Xịt';
            }

            $songThuDeHistory[] = [
                'date' => date('d/m/Y', $dateTs),
                'date_link' => date('d-m-Y', $dateTs),
                'pair' => implode(' - ', $pair),
                'status' => $status
            ];
        }

        $this->view->setVar('song_thu_de_history', $songThuDeHistory);

        // 2. Calculate Lô Gan Pairs (cặp lô gan)
        // Find pairs that haven't appeared together recently
        $calculateLoGanPairs = function () use ($allRows, $extractAllLotto) {
            $pairLastSeen = [];
            $pairMaxGan = [];

            // Generate all possible reversed pairs
            for ($i = 0; $i < 100; $i++) {
                $num1 = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $num2 = $num1[1] . $num1[0];

                // Skip if same (like 00, 11, 22, etc.)
                if ($num1 === $num2)
                    continue;

                // Use smaller number as key to avoid duplicates
                $key = min($num1, $num2) . '-' . max($num1, $num2);
                $pairLastSeen[$key] = count($allRows);
                $pairMaxGan[$key] = 0;
            }

            // Track when each pair appeared
            foreach ($allRows as $index => $row) {
                $lottos = $extractAllLotto($row);

                foreach ($pairLastSeen as $key => $lastIdx) {
                    list($n1, $n2) = explode('-', $key);

                    // Check if either number in pair appeared
                    if (in_array($n1, $lottos) || in_array($n2, $lottos)) {
                        $ganDays = $index - $lastIdx;
                        if ($ganDays > $pairMaxGan[$key]) {
                            $pairMaxGan[$key] = $ganDays;
                        }
                        $pairLastSeen[$key] = $index;
                    }
                }
            }

            // Calculate current gan
            $pairGanList = [];
            foreach ($pairLastSeen as $key => $lastIdx) {
                $currentGan = count($allRows) - $lastIdx - 1;
                if ($currentGan > 30) { // Only show pairs with significant gan
                    $pairGanList[] = [
                        'pair' => str_replace('-', ' - ', $key),
                        'gan' => $currentGan,
                        'last_date' => isset($allRows[$lastIdx]) ? date('d/m/Y', strtotime($allRows[$lastIdx]['draw_date'])) : 'N/A',
                        'last_date_link' => isset($allRows[$lastIdx]) ? date('d-m-Y', strtotime($allRows[$lastIdx]['draw_date'])) : '',
                        'max_gan' => $pairMaxGan[$key]
                    ];
                }
            }

            // Sort by current gan descending
            usort($pairGanList, function ($a, $b) {
                return $b['gan'] - $a['gan'];
            });

            return array_slice($pairGanList, 0, 8);
        };

        $loGanPairs = $calculateLoGanPairs();
        $this->view->setVar('lo_gan_pairs', $loGanPairs);

        // Calculate next day date for stats title
        $nextDayTs = $currentDateTs + 86400;
        $this->view->setVar('next_day', date('d/m/Y', $nextDayTs));

        $this->view->setVar('rating_value', 3.6);
        $this->view->setVar('rating_count', 4366);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/soi-cau-song-thu-de.html']
            ]
        ]);

        $this->view->pick('bachthu/soiCauSongThuDe');
    }
}
