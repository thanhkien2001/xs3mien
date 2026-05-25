<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Library\PerformanceHelper;

class DandeController extends ControllerBase
{
    public function taoDanDeAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Tạo dàn đề - Tạo dàn 2D, 3D và dàn đặc biệt chính xác';
        $description = 'Tạo dàn đề là một phương pháp đánh lô đề hiệu quả được nhiều anh em biết đến và áp dụng. Tuy nhiên nhiều anh em vẫn còn loay hoay trong việc tạo cho mình một dàn đề bất bại và dành được cơ hội chiến thắng cao. Cũng theo dõi ngay các cách tạo dàn đề hiệu quả nhất nhé.';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/tao-dan-de.html']
            ]
        ]);

        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');
        $this->view->pick('dande/tao-dan-de');
    }

    public function danDe10SoNuoiKhung5NgayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Dàn đề 10 số bất bại nuôi khung 2, 3 , 5 ngày siêu chuẩn';
        $description = 'Dàn đề 10 số bất bại nuôi khung 2, 3, 5 ngày hiện nay đang là cách chơi đang được nhiều người yêu thích, hôm nay các chuyên gia sẽ cung cấp cho anh em dàn đặc biệt dàn đề 10 số khung 3 ngày miễn phí với tỷ lệ chính xác cao.';

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
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN($todayStr) . ', ' . date('d/m/Y', $currentDateTs) . ' - 18:30');

        // Fetch last 200 days to have enough context for historical frames
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 200);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to get rows BEFORE a certain date
        $getRowsBefore = function ($date, $limit = 100) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] < $date) {
                    $filtered[] = $r;
                    if (count($filtered) >= $limit) break;
                }
            }
            return $filtered;
        };

        // Helper to get rows WITHIN a range
        $getRowsInRange = function ($start, $end) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] >= $start && $r['draw_date'] <= $end) {
                    $filtered[] = $r;
                }
            }
            return array_reverse($filtered); // Oldest to newest in range
        };

        // Algorithm to predict Head for a date
        $predictHead = function ($date, $type) use ($getRowsBefore) {
            $rows = $getRowsBefore($date, 100);
            if (empty($rows)) return 0;

            $headCounts = array_fill(0, 10, 0);
            $headLastSeen = array_fill(0, 10, 100); // Distance

            foreach ($rows as $index => $row) {
                $last2 = \App\Library\ThongkeStatisticsHelper::last2($row['special_prize']);
                $head = (int)substr($last2, 0, 1);
                $headCounts[$head]++;
                if ($headLastSeen[$head] === 100) {
                    $headLastSeen[$head] = $index;
                }
            }

            if ($type == 2) {
                arsort($headCounts);
                return key($headCounts);
            } elseif ($type == 3) {
                // Find head with mid-range gan
                foreach ($headLastSeen as $head => $dist) {
                    if ($dist >= 3 && $dist <= 10) return $head;
                }
                return array_search(max($headLastSeen), $headLastSeen);
            } else {
                // Longest gan
                arsort($headLastSeen);
                return key($headLastSeen);
            }
        };

        // Calculate Frames
        $calculateFrames = function ($length, $count = 5) use ($allDates, $predictHead, $getRowsInRange) {
            $frames = [];
            $dateIndex = 0;

            for ($i = 0; $i < $count; $i++) {
                if ($dateIndex >= count($allDates)) break;

                $startDate = $allDates[$dateIndex];
                // Frame dates are consecutive calendar days or draw days? 
                // Since XSMB is daily, we can use calendar days.
                $startTs = strtotime($startDate);
                $endTs = $startTs + (($length - 1) * 86400);
                $endDate = date('Y-m-d', $endTs);

                $head = $predictHead($startDate, $length);
                $numbers = array_map(fn($n) => str_pad((string)($head * 10 + $n), 2, '0', STR_PAD_LEFT), range(0, 9));

                // Check if hit
                $rangeRows = $getRowsInRange($startDate, $endDate);
                $status = "Chờ kết quả";
                $hitDay = 0;
                $hitVal = "";

                foreach ($rangeRows as $idx => $r) {
                    $last2 = \App\Library\ThongkeStatisticsHelper::last2($r['special_prize']);
                    if (in_array($last2, $numbers)) {
                        $hitDay = $idx + 1;
                        $hitVal = $last2;
                        break;
                    }
                }

                if ($hitDay > 0) {
                    $status = "Trúng đề <span class=\"text-red font-weight-bold\">$hitVal</span> ngày $hitDay";
                } elseif (strtotime($endDate) < time() - 86400) {
                    $status = "Trượt";
                }

                $frames[] = [
                    'range' => date('d/m', $startTs) . ' - ' . date('d/m', $endTs),
                    'year' => date('Y', $startTs),
                    'numbers' => implode(' - ', $numbers),
                    'status' => $status
                ];

                $dateIndex += $length; // Skip to next period
            }
            return $frames;
        };

        $this->view->setVar('frames2', $calculateFrames(2));
        $this->view->setVar('frames3', $calculateFrames(3));
        $this->view->setVar('frames5', $calculateFrames(5));

        // 4. Special Stats (Last 20 results instead of 10)
        $top20 = array_slice($allRows, 0, 20);
        $formattedTop20 = [];
        foreach ($top20 as $r) {
            $formattedTop20[] = [
                'last2' => \App\Library\ThongkeStatisticsHelper::last2($r['special_prize'])
            ];
        }
        $this->view->setVar('special_history', $formattedTop20);

        // 5. Chạm Stats (Last 30 periods)
        $last30 = array_slice($allRows, 0, 30);
        $headFreq = array_fill(0, 10, 0);
        $tailFreq = array_fill(0, 10, 0);
        $sumFreq = array_fill(0, 10, 0);

        foreach ($last30 as $r) {
            $l2 = \App\Library\ThongkeStatisticsHelper::last2($r['special_prize']);
            $headFreq[(int)$l2[0]]++;
            $tailFreq[(int)$l2[1]]++;
            $sumFreq[((int)$l2[0] + (int)$l2[1]) % 10]++;
        }

        $this->view->setVar('head_freq', $headFreq);
        $this->view->setVar('tail_freq', $tailFreq);
        $this->view->setVar('sum_freq', $sumFreq);

        $this->view->setVar('rating_value', 3.6);
        $this->view->setVar('rating_count', 1991);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/dan-de-10-so-nuoi-khung-5-ngay.html']
            ]
        ]);

        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');
        $this->view->pick('dande/danDe10SoNuoiKhung5Ngay');
    }
    public function nuoiDanDe20SoKhung3NgayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Dàn de 20 số nuôi khung 3 ngày chuẩn xác';
        $description = 'Dàn de 20 số nuôi khung 3 ngày chuẩn với lãi suất cao, rất tiết kiệm thời gian mà chi phí bỏ ra ban đầu cực thấp, nhiều anh em không cần nuôi hết khung 3 ngày đã trúng lớn chỉ trong ngày 1, ngày 2.';

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
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN($todayStr) . ', ' . date('d/m/Y', $currentDateTs) . ' - 18:30');

        // Fetch last 200 days
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 200);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to get rows BEFORE a certain date
        $getRowsBefore = function ($date, $limit = 100) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] < $date) {
                    $filtered[] = $r;
                    if (count($filtered) >= $limit) break;
                }
            }
            return $filtered;
        };

        // Helper to get rows WITHIN a range
        $getRowsInRange = function ($start, $end) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] >= $start && $r['draw_date'] <= $end) {
                    $filtered[] = $r;
                }
            }
            return array_reverse($filtered);
        };

        // Algorithm to predict 2 Heads for 20 numbers
        $predictHeads = function ($date) use ($getRowsBefore) {
            $rows = $getRowsBefore($date, 100);
            if (empty($rows)) return [0, 1];

            $headCounts = array_fill(0, 10, 0);
            foreach ($rows as $row) {
                $last2 = \App\Library\ThongkeStatisticsHelper::last2($row['special_prize']);
                $head = (int)substr($last2, 0, 1);
                $headCounts[$head]++;
            }
            arsort($headCounts);
            $keys = array_keys($headCounts);
            return array_slice($keys, 0, 2);
        };

        // Calculate Frames (3 days, 20 numbers)
        $frames = [];
        $dateIndex = 0;
        for ($i = 0; $i < 5; $i++) {
            if ($dateIndex >= count($allDates)) break;

            $startDate = $allDates[$dateIndex];
            $startTs = strtotime($startDate);
            $endTs = $startTs + (2 * 86400); // 3 days total
            $endDate = date('Y-m-d', $endTs);

            $heads = $predictHeads($startDate);
            $numbers = [];
            foreach ($heads as $h) {
                for ($n = 0; $n <= 9; $n++) {
                    $numbers[] = str_pad((string)($h * 10 + $n), 2, '0', STR_PAD_LEFT);
                }
            }

            $rangeRows = $getRowsInRange($startDate, $endDate);
            $status = "Chờ kết quả";
            $hitDay = 0;
            $hitVal = "";

            foreach ($rangeRows as $idx => $r) {
                $last2 = \App\Library\ThongkeStatisticsHelper::last2($r['special_prize']);
                if (in_array($last2, $numbers)) {
                    $hitDay = $idx + 1;
                    $hitVal = $last2;
                    break;
                }
            }

            if ($hitDay > 0) {
                $status = "Trúng đề <span class=\"text-red font-weight-bold\">$hitVal</span> ngày $hitDay";
            } elseif (strtotime($endDate) < time() - 86400) {
                $status = "Trượt";
            }

            $frames[] = [
                'range' => date('d/m', $startTs) . ' - ' . date('d/m', $endTs),
                'year' => date('Y', $startTs),
                'numbers' => implode(' ', $numbers),
                'status' => $status
            ];
            $dateIndex += 3;
        }

        $this->view->setVar('frames', $frames);

        $this->view->setVar('rating_value', 3.6);
        $this->view->setVar('rating_count', 918);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/nuoi-dan-de-20-so-khung-3-ngay.html']
            ]
        ]);

        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');
        $this->view->pick('dande/nuoiDanDe20SoKhung3Ngay');
    }

    public function danDe36SoKhung3NgayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Dàn đề 36 số - Nuôi dan 36 MB số bất bại ăn liên tục';
        $description = 'Dàn de 36 số bất bại số bất bại là cách nuôi dàn đề có độ chính xác cực cao giúp anh em ăn đề liên tục trong một thời gian dài và có lãi đều hàng tháng. Vậy nên có rất nhiều anh em ưa chuộng sử dụng hình thức dàn đề này. Hôm nay chúng tôi sẽ mang đến cho anh em dàn de 36 số bất bại hôm nay số bất bại đẹp nhất. Mời anh em theo dõi.';

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
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN($todayStr) . ', ' . date('d/m/Y', $currentDateTs) . ' - 18:30');

        // Fetch last 200 days
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 200);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to get rows BEFORE a certain date
        $getRowsBefore = function ($date, $limit = 100) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] < $date) {
                    $filtered[] = $r;
                    if (count($filtered) >= $limit) break;
                }
            }
            return $filtered;
        };

        // Helper to get rows WITHIN a range
        $getRowsInRange = function ($start, $end) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] >= $start && $r['draw_date'] <= $end) {
                    $filtered[] = $r;
                }
            }
            return array_reverse($filtered);
        };

        // Algorithm to predict 4 Heads for 36-40 numbers
        $predictHeads = function ($date) use ($getRowsBefore) {
            $rows = $getRowsBefore($date, 100);
            if (empty($rows)) return [0, 1, 2, 3];

            $headCounts = array_fill(0, 10, 0);
            foreach ($rows as $row) {
                $last2 = \App\Library\ThongkeStatisticsHelper::last2($row['special_prize']);
                $head = (int)substr($last2, 0, 1);
                $headCounts[$head]++;
            }
            arsort($headCounts);
            $keys = array_keys($headCounts);
            return array_slice($keys, 0, 4);
        };

        // Calculate Frames (3 days, 36 numbers)
        // We will pick 9 numbers from each of the top 4 heads to get exactly 36
        $frames = [];
        $dateIndex = 0;
        for ($i = 0; $i < 5; $i++) {
            if ($dateIndex >= count($allDates)) break;

            $startDate = $allDates[$dateIndex];
            $startTs = strtotime($startDate);
            $endTs = $startTs + (2 * 86400); // 3 days total
            $endDate = date('Y-m-d', $endTs);

            $heads = $predictHeads($startDate);
            $numbers = [];
            foreach ($heads as $h) {
                for ($n = 0; $n <= 8; $n++) { // 4 * 9 = 36
                    $numbers[] = str_pad((string)($h * 10 + $n), 2, '0', STR_PAD_LEFT);
                }
            }

            $rangeRows = $getRowsInRange($startDate, $endDate);
            $status = "Chờ kết quả";
            $hitDay = 0;
            $hitVal = "";

            foreach ($rangeRows as $idx => $r) {
                $last2 = \App\Library\ThongkeStatisticsHelper::last2($r['special_prize']);
                if (in_array($last2, $numbers)) {
                    $hitDay = $idx + 1;
                    $hitVal = $last2;
                    break;
                }
            }

            if ($hitDay > 0) {
                $status = "Trúng đề <span class=\"text-red font-weight-bold\">$hitVal</span> ngày $hitDay";
            } elseif (strtotime($endDate) < time() - 86400) {
                $status = "Trượt";
            }

            $frames[] = [
                'range' => date('d/m', $startTs) . ' - ' . date('d/m', $endTs),
                'year' => date('Y', $startTs),
                'numbers' => implode(' ', $numbers),
                'status' => $status
            ];
            $dateIndex += 3;
        }

        $this->view->setVar('frames', $frames);

        $this->view->setVar('rating_value', 3.5);
        $this->view->setVar('rating_count', 1844);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/dan-de-36-so-khung-3-ngay.html']
            ]
        ]);

        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');
        $this->view->pick('dande/danDe36SoKhung3Ngay');
    }

    public function nuoiLoKhungXsmbAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Nuôi lô khung 247 - Lô khung 1, 2, 3, 5 ngày siêu chuẩn xác';
        $description = 'Nuôi lô khung là phương pháp chọn ra những cặp số đẹp nhất theo cách thống kê để chọn lô khung 1 ngày, 2 ngày, 3 ngày, 5 ngày thậm chí là 7 ngày. Trong bài viết ngày hôm nay, chúng tôi sẽ giúp anh em dễ dàng lựa chọn dàn lô khung cho mình ngay sau đấy.';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        $db = $this->di->get('db');

        // 1. Get latest date
        $latestDraw = \App\Models\LotteryResults::findFirst([
            'conditions' => 'draw_type = "XSMB"',
            'order' => 'draw_date DESC'
        ]);

        $currentDateTs = $latestDraw ? strtotime($latestDraw->draw_date) : time();
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN(date('Y-m-d', $currentDateTs)) . ', ' . date('d/m/Y', $currentDateTs) . ' - 18:30');

        // Fetch last 200 days
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 200);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to extract last 2 digits of all 27 prizes for Lô
        $extractAllLotto = function ($row) {
            $prizes = ['special_prize', 'first_prize', 'second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize'];
            $allLotto = [];
            foreach ($prizes as $p) {
                $val = $row[$p] ?? '';
                if (empty($val)) continue;
                $clean = trim($val, "[]\"");
                $parts = preg_split('/[",]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $num) {
                    $allLotto[] = substr($num, -2);
                }
            }
            return $allLotto;
        };

        // Cache all lotto results for each row
        $indexedLotto = [];
        foreach ($allRows as $r) {
            $indexedLotto[$r['draw_date']] = $extractAllLotto($r);
        }

        $getRowsBefore = function ($date, $limit = 100) use ($allRows) {
            $filtered = [];
            foreach ($allRows as $r) {
                if ($r['draw_date'] < $date) {
                    $filtered[] = $r;
                    if (count($filtered) >= $limit) break;
                }
            }
            return $filtered;
        };

        $getLottoInRange = function ($start, $end) use ($allDates, $indexedLotto) {
            $res = [];
            foreach ($allDates as $d) {
                if ($d >= $start && $d <= $end) {
                    if (isset($indexedLotto[$d])) {
                        $res[] = $indexedLotto[$d];
                    }
                }
            }
            return array_reverse($res); // Oldest to newest
        };

        // Generic prediction logic
        $predictNumbers = function ($date, $count = 1) use ($getRowsBefore, $indexedLotto) {
            $rows = $getRowsBefore($date, 30);
            $freq = array_fill(0, 100, 0);
            foreach ($rows as $r) {
                if (!isset($indexedLotto[$r['draw_date']])) continue;
                foreach ($indexedLotto[$r['draw_date']] as $lotto) {
                    $freq[(int)$lotto]++;
                }
            }
            arsort($freq);
            $keys = array_keys($freq);
            $res = array_slice($keys, 0, $count);
            return array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), $res);
        };

        // Function to calculate frames for any type
        $calculateGenericFrames = function ($length, $numCount, $offset = 0) use ($allDates, $getLottoInRange, $predictNumbers) {
            $frames = [];
            $dateIndex = $offset;
            for ($i = 0; $i < 10; $i++) {
                if ($dateIndex >= count($allDates)) break;
                $startDate = $allDates[$dateIndex];
                $startTs = strtotime($startDate);
                $endTs = $startTs + (($length - 1) * 86400);
                $endDate = date('Y-m-d', $endTs);

                $numbers = $predictNumbers($startDate, $numCount);
                $rangeLotto = $getLottoInRange($startDate, $endDate);

                $status = "Chờ kết quả";
                $hitDay = 0;
                $hitVal = "";

                foreach ($rangeLotto as $idx => $dayLotto) {
                    foreach ($numbers as $num) {
                        if (in_array($num, $dayLotto)) {
                            $hitDay = $idx + 1;
                            $hitVal = $num;
                            break 2;
                        }
                    }
                }

                if ($hitDay > 0) {
                    $status = "Ăn lô <span class=\"text-red font-weight-bold\">$hitVal</span> ngày $hitDay";
                } elseif (strtotime($endDate) < time() - 86400) {
                    $status = "Trượt";
                }

                $frames[] = [
                    'date' => ($length == 1) ? date('j/n/Y', $startTs) : (date('d/m', $startTs) . ' - ' . date('d/m/Y', $endTs)),
                    'numbers' => implode(' - ', $numbers),
                    'status' => $status
                ];
                $dateIndex += $length;
            }
            return $frames;
        };

        // Generate all tables
        $this->view->setVar('lkep_1d', $calculateGenericFrames(1, 2));
        $this->view->setVar('btl_1d', $calculateGenericFrames(1, 1));
        $this->view->setVar('btl_2d', $calculateGenericFrames(2, 1));
        $this->view->setVar('lkep_2d', $calculateGenericFrames(2, 2));
        $this->view->setVar('stl_2d', $calculateGenericFrames(2, 2, 1));
        $this->view->setVar('btl_3d', $calculateGenericFrames(3, 1));
        $this->view->setVar('stl_3d', $calculateGenericFrames(3, 2));
        $this->view->setVar('lkep_3d', $calculateGenericFrames(3, 2, 2));
        $this->view->setVar('lkep_5d', $calculateGenericFrames(5, 2));
        $this->view->setVar('btl_5d', $calculateGenericFrames(5, 1));

        $this->view->setVar('rating_value', 3.5);
        $this->view->setVar('rating_count', 2056);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/nuoi-lo-khung-xsmb.html']
            ]
        ]);

        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');
        $this->view->pick('dande/nuoiLoKhungXsmb');
    }

    public function loTopAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Lô Top - Lô Top miền Bắc đẹp nhất ngày hôm nay';
        $description = 'Lô Top - Bảng lô top hôm nay là địa điểm thống kê cầu loto đẹp nhất trong ngày cho anh em. Lô top Rồng Bạch Kim 666 sử dụng hình tổng hợp thống kê từ đó đưa ra top những cặp số có xác suất về cao nhất.';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        $db = $this->di->get('db');

        // 1. Get latest XSMB date
        $latestDraw = \App\Models\LotteryResults::findFirst([
            'conditions' => 'draw_type = "XSMB"',
            'order' => 'draw_date DESC'
        ]);

        if (!$latestDraw) {
            // Fallback or empty handle
            $this->view->pick('dande/loTop');
            return;
        }

        $currentDateTs = strtotime($latestDraw->draw_date);
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN(date('Y-m-d', $currentDateTs)) . ', ' . date('d/m/Y', $currentDateTs) . ' - 18:30');
        $this->view->setVar('latest_draw', $latestDraw);

        // Fetch last 30 days
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 30);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to extract all 2-digit lottos from a row
        $extractAllLotto = function ($row) {
            $prizes = ['special_prize', 'first_prize', 'second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize'];
            $allLotto = [];
            foreach ($prizes as $p) {
                $val = $row[$p] ?? '';
                if (empty($val)) continue;
                $clean = trim($val, "[]\"");
                $parts = preg_split('/[",]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $num) {
                    $allLotto[] = substr($num, -2);
                }
            }
            return $allLotto;
        };

        // Calculate frequencies
        $freq = array_fill(0, 100, 0);
        $headFreq = array_fill(0, 10, 0);
        $tailFreq = array_fill(0, 10, 0);

        foreach ($allRows as $r) {
            $lottos = $extractAllLotto($r);
            foreach ($lottos as $l) {
                $num = (int)$l;
                $freq[$num]++;
                $headFreq[(int)substr($l, 0, 1)]++;
                $tailFreq[(int)substr($l, 1, 1)]++;
            }
        }

        arsort($freq);
        $topNumbers = array_keys(array_slice($freq, 0, 20, true));

        // Dynamic list with font sizes
        $loTopList = [];
        $baseSize = 28;
        foreach ($topNumbers as $idx => $num) {
            $loTopList[] = [
                'val' => str_pad((string)$num, 2, '0', STR_PAD_LEFT),
                'size' => $baseSize - ($idx * 0.6)
            ];
        }
        $this->view->setVar('loTopList', $loTopList);

        // Top 10 Table
        $top10 = [];
        foreach (array_slice($topNumbers, 0, 10) as $idx => $num) {
            $top10[] = [
                'rank' => 'Top ' . ($idx + 1),
                'val' => str_pad((string)$num, 2, '0', STR_PAD_LEFT),
                'prob' => (90 - ($idx * 2)) . '%'
            ];
        }
        $this->view->setVar('top10', $top10);

        // Head/Tail Table
        arsort($headFreq);
        arsort($tailFreq);

        $headSorted = [];
        foreach ($headFreq as $h => $f) {
            $headSorted[] = ['val' => $h, 'prob' => round(($f / (30 * 2.7)) * 10, 0) . '%']; // simple scale
        }
        $tailSorted = [];
        foreach ($tailFreq as $t => $f) {
            $tailSorted[] = ['val' => $t, 'prob' => round(($f / (30 * 2.7)) * 10, 0) . '%'];
        }
        $this->view->setVar('headSorted', $headSorted);
        $this->view->setVar('tailSorted', $tailSorted);

        // Statistics for latest draw
        $latestLottos = $extractAllLotto($latestDraw->toArray());
        $lottoCounts = array_count_values($latestLottos);
        $multiHits = [];
        foreach ($lottoCounts as $num => $c) {
            if ($c > 1) $multiHits[] = $num . ' (x' . $c . ')';
        }

        $latestHeads = array_count_values(array_map(fn($l) => substr($l, 0, 1), $latestLottos));
        arsort($latestHeads);
        $maxHead = key($latestHeads);
        $maxHeadCount = current($latestHeads);

        $latestTails = array_count_values(array_map(fn($l) => substr($l, 1, 1), $latestLottos));
        arsort($latestTails);
        $maxTail = key($latestTails);
        $maxTailCount = current($latestTails);

        $this->view->setVar('latestLottos', implode(' - ', $latestLottos));
        $this->view->setVar('multiHits', empty($multiHits) ? 'Không có' : implode(', ', $multiHits));
        $this->view->setVar('maxHead', "$maxHead ($maxHeadCount lô)");
        $this->view->setVar('maxTail', "$maxTail ($maxTailCount lô)");

        $this->view->setVar('rating_value', 3.7);
        $this->view->setVar('rating_count', 489);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/lo-top.html']
            ]
        ]);

        $this->view->setVar('base_url', $this->request->getScheme() . '://' . $this->request->getHttpHost() . '/');
        $this->view->pick('dande/loTop');
    }

    public function soiCauXsmbMienPhiNgayHomNayAction()
    {
        $this->view->customindex = '/css/indexheader.css';

        $title = 'Soi cầu XSMB miễn phí ngày hôm nay siêu chuẩn';
        $description = 'Soi cầu miễn phí ngày hôm nay hoàn toàn miễn phí có độ chính xác lên tới 99%. Nhiều anh em đã trúng giải thưởng lớn và dành cơ hội đổi đời cho mình sau khi tham khảo kết quả soi cầu XSMB miễn phí ngày hôm nay của chúng tôi.';

        $this->view->setVar('seo_title', $title);
        $this->view->setVar('seo_description', $description);

        $db = $this->di->get('db');

        $latestDraw = \App\Models\LotteryResults::findFirst([
            'conditions' => 'draw_type = "XSMB"',
            'order' => 'draw_date DESC'
        ]);

        if (!$latestDraw) {
            $this->view->pick('dande/soiCauXsmbMienPhiNgayHomNay');
            return;
        }

        $currentDateTs = strtotime($latestDraw->draw_date);
        $this->view->setVar('current_date', \App\Library\ThongkeStatisticsHelper::weekdayVN(date('Y-m-d', $currentDateTs)) . ', ' . date('d/m/Y', $currentDateTs) . ' - 18:30');
        $this->view->setVar('latest_draw', $latestDraw);

        // Fetch last 100 days
        $allDates = \App\Library\ThongkeStatisticsHelper::getRecentDatesByRegion('XSMB', $db, 100);
        $allRows = \App\Library\ThongkeStatisticsHelper::getResultsByDates('XSMB', $db, $allDates);

        // Helper to extract lottos
        $extractLottos = function ($row) {
            $prizes = ['special_prize', 'first_prize', 'second_prize', 'third_prize', 'fourth_prize', 'fifth_prize', 'sixth_prize', 'seventh_prize'];
            $lottos = [];
            foreach ($prizes as $p) {
                $val = $row[$p] ?? '';
                if (empty($val)) continue;
                $clean = trim($val, "[]\"");
                $parts = preg_split('/[",]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $num) {
                    $lottos[] = substr($num, -2);
                }
            }
            return $lottos;
        };

        // Statistics
        $numFreq = array_fill(0, 100, 0);
        $specFreq = array_fill(0, 100, 0);

        foreach ($allRows as $r) {
            $ls = $extractLottos($r);
            foreach ($ls as $l) $numFreq[(int)$l]++;
            $specFreq[(int)substr($r['special_prize'], -2)]++;
        }

        // Predictions
        arsort($numFreq);
        $topList = array_keys($numFreq);
        $this->view->setVar('bach_thu', str_pad((string)$topList[0], 2, '0', STR_PAD_LEFT));
        $this->view->setVar('song_thu', str_pad((string)$topList[1], 2, '0', STR_PAD_LEFT) . ' - ' . str_pad((string)$topList[2], 2, '0', STR_PAD_LEFT));

        $lastSpecial = substr($latestDraw->special_prize, -2);
        $this->view->setVar('dau_duoi_db', [
            'dau' => $lastSpecial[0],
            'duoi' => $lastSpecial[1],
            'tong' => ((int)$lastSpecial[0] + (int)$lastSpecial[1]) % 10
        ]);

        $this->view->setVar('lo_xien', str_pad((string)$topList[3], 2, '0', STR_PAD_LEFT) . ' - ' . str_pad((string)$topList[4], 2, '0', STR_PAD_LEFT) . ' - ' . str_pad((string)$topList[5], 2, '0', STR_PAD_LEFT));

        $loToDep = array_slice($topList, 6, 4);
        $this->view->setVar('lo_to_dep', array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), $loToDep));

        // Cầu đẹp
        $this->view->setVar('cau_bach_thu', array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), array_slice($topList, 10, 6)));
        $this->view->setVar('cau_2_nhay', [
            ['v1' => $topList[16], 'v2' => str_pad((string)$topList[16], 2, '0', STR_PAD_LEFT) == strrev(str_pad((string)$topList[16], 2, '0', STR_PAD_LEFT)) ? '' : strrev(str_pad((string)$topList[16], 2, '0', STR_PAD_LEFT))],
            ['v1' => $topList[17], 'v2' => strrev(str_pad((string)$topList[17], 2, '0', STR_PAD_LEFT))]
        ]);
        $this->view->setVar('cau_kep', ['11', '66']);
        $this->view->setVar('cau_db', [
            ['v1' => '45', 'v2' => '54'],
            ['v1' => '12', 'v2' => '21']
        ]);

        // Thống kê Lô
        arsort($numFreq);
        $loVeNhieu = [];
        foreach (array_slice($numFreq, 0, 5, true) as $num => $f) {
            $loVeNhieu[] = ['val' => str_pad((string)$num, 2, '0', STR_PAD_LEFT), 'count' => $f];
        }
        asort($numFreq);
        $loVeIt = [];
        foreach (array_slice($numFreq, 0, 5, true) as $num => $f) {
            $loVeIt[] = ['val' => str_pad((string)$num, 2, '0', STR_PAD_LEFT), 'count' => $f];
        }
        $this->view->setVar('lo_ve_nhieu', $loVeNhieu);
        $this->view->setVar('lo_ve_it', $loVeIt);

        // Thống kê ĐB
        arsort($specFreq);
        $dbVeNhieu = [];
        foreach (array_slice($specFreq, 0, 5, true) as $num => $f) {
            $dbVeNhieu[] = ['val' => str_pad((string)$num, 2, '0', STR_PAD_LEFT), 'count' => $f];
        }
        asort($specFreq);
        $dbVeIt = [];
        foreach (array_slice($specFreq, 0, 5, true) as $num => $f) {
            $dbVeIt[] = ['val' => str_pad((string)$num, 2, '0', STR_PAD_LEFT), 'count' => $f];
        }
        $this->view->setVar('db_ve_nhieu', $dbVeNhieu);
        $this->view->setVar('db_ve_it', $dbVeIt);

        // Đặc biệt xác suất cao (next 8 in top list)
        $specHigh = array_slice($topList, 20, 8);
        $this->view->setVar('spec_high', array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), $specHigh));

        // Detail for latest draw
        $latestLottos = $extractLottos($latestDraw->toArray());
        $this->view->setVar('latestLottos', $latestLottos);

        $this->view->setVar('rating_value', 3.9);
        $this->view->setVar('rating_count', 647);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $title,
            'description' => $description,
            'url' => $this->url->get(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => '/'],
                ['name' => $title, 'url' => '/soi-cau-xsmb-mien-phi-ngay-hom-nay.html']
            ]
        ]);

        $this->view->pick('dande/soiCauXsmbMienPhiNgayHomNay');
    }
}
