<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Db\Adapter\Pdo\Mysql;
use App\Models\LotteryResults;
use App\Library\ThongkeStatisticsHelper;
use App\Library\PerformanceHelper;
use App\Library\KqxsHelper;

class ThongkembController extends ControllerBase
{
  /**
   * Helper method to get absolute URL for breadcrumb
   */
  private function getAbsoluteUrl($path = '')
  {
    $config = $this->getDI()->get('config');
    $baseUrl = $config->application->baseUrl;

    if (empty($path)) {
      $path = $this->request->getURI();
    }

    if ($path !== '/' && substr($path, -1) !== '/' && substr($path, -5) !== '.html') {
      $path .= '.html';
    }

    return $baseUrl . $path;
  }

  public function thongkexsmbAction()
  {
    // CSS
    $this->view->customindex    = '/css/indexheader.css';
    $this->view->customthongke1 = '/css/thongke/thongke1.css';

    $region     = 'XSMB';
    $regionName = 'Miền Bắc';

    // ==== FIX CACHE KEY ====
    $params   = ['uri' => $_SERVER['REQUEST_URI'] ?? '', 'total_day' => 100];
    $cacheKey = 'thongke_xsmb_' . md5(json_encode($params));

    $cache    = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/thongkexsmb';
    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);

      // Schema data for cache hit
      $cachedIsRegionMode = $cached['isRegionMode'] ?? true;
      $cachedProvinceName = $cached['selectedProvinceName'] ?? 'Miền Bắc';

      // Breadcrumb động: 2 levels cho region, 3 levels cho tỉnh
      $breadcrumbs = [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]
      ];

      if ($cachedIsRegionMode) {
        // Region mode: Trang chủ -> Thống kê XSMB
        $breadcrumbs[] = ['name' => 'Thống kê XSMB', 'url' => $this->getAbsoluteUrl()];
      } else {
        // Province mode: Trang chủ -> Thống kê XSMB -> Thống kê [Tỉnh]
        $breadcrumbs[] = ['name' => 'Thống kê XSMB', 'url' => $this->getAbsoluteUrl('/thong-ke-xsmb')];
        $breadcrumbs[] = ['name' => 'Thống kê ' . $cachedProvinceName, 'url' => $this->getAbsoluteUrl()];
      }

      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'] ?? 'Thống kê XSMB',
        'description' => $cached['seo_description'] ?? 'Thống kê xổ số Miền Bắc',
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => $breadcrumbs
      ]);

      $this->view->pick($viewPick);
      return;
    }

    // 1) 30/100 kỳ gần nhất (DESC)
    $specialPrize30 = LotteryResults::find([
      'conditions' => 'draw_type = :r:',
      'bind'       => ['r' => $region],
      'order'      => 'draw_date DESC',
      'limit'      => 100
    ]);

    // 2) Tần suất top 10 trong 30/100 kỳ
    $freqTop10 = $this->buildFrequencyTop($specialPrize30, 10);
    $freqMaxCount = 0;
    foreach ($freqTop10 as $row) {
      if ($row['count'] > $freqMaxCount) $freqMaxCount = $row['count'];
    }

    // 3) Lịch sử dài hơn để tính lô gan
    $historyRows = LotteryResults::find([
      'conditions' => 'draw_type = :r:',
      'bind'       => ['r' => $region],
      'order'      => 'draw_date ASC',
      'limit'      => 100
    ]);
    $loGanTop = $this->buildLoGanFromHistory($historyRows, 10);

    // 4) Kỳ mới nhất
    $latest = LotteryResults::findFirst([
      'conditions' => 'draw_type = :r:',
      'bind'       => ['r' => $region],
      'order'      => 'draw_date DESC'
    ]);

    // Result
    $finalResult = [
      'region'         => $region,
      'regionName'     => $regionName,
      'specialPrize30' => $specialPrize30,
      'freqTop10'      => $freqTop10,
      'freqMaxCount'   => $freqMaxCount,
      'loGanTop'       => $loGanTop,
      'latest'         => $latest,
    ];

    // SEO giữ nguyên
    $seoData = PerformanceHelper::generateCachedSeoData('statistics', $region, null, [
      'type' => 'thongke',
      'data' => $finalResult
    ], $this->cache);
    $finalResult = array_merge($finalResult, $seoData);

    // Cache & render
    $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime([$latest], 'draw_date');
    $cache->set($cacheKey, $finalResult, $cacheLifetime);

    $this->view->setVars($finalResult);

    // Schema data for cache miss
    // Breadcrumb động: 2 levels cho region, 3 levels cho tỉnh
    $breadcrumbs = [
      ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')]
    ];

    // ThongkembController không có isRegionMode vì chỉ có XSMB (1 region)
    // Nhưng để nhất quán, ta giữ 2 levels
    $breadcrumbs[] = ['name' => 'Thống kê XSMB', 'url' => $this->getAbsoluteUrl()];

    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $finalResult['seo_title'] ?? 'Thống kê XSMB',
      'description' => $finalResult['seo_description'] ?? 'Thống kê xổ số Miền Bắc',
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => $breadcrumbs
    ]);

    $this->view->pick($viewPick);
  }

  public function loganXsmbAction()
  {
    $this->view->customindex    = '/css/indexheader.css';
    $this->view->customthongke1 = '/css/thongke/thongke1.css';

    // ==== FIX CACHE KEY ====
    $params   = ['uri' => $_SERVER['REQUEST_URI'] ?? ''];
    $cacheKey = 'logan_xsmb_mb_' . md5(json_encode($params)); // Thêm _mb để tránh conflict

    $cache    = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/loganxsmb';
    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);

      // Schema data for cache hit
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'] ?? 'Lô gan XSMB',
        'description' => $cached['seo_description'] ?? 'Lô gan xổ số Miền Bắc',
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Lô gan XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);

      $this->view->pick($viewPick);
      return;
    }

    // Tải toàn bộ lịch sử XSMB (ASC)
    $rows = LotteryResults::find([
      'conditions' => 'draw_type = "XSMB"',
      'order'      => 'draw_date ASC',
    ]);

    if (!$rows || count($rows) === 0) {
      $empty = [
        'asOfDate'      => null,
        'topGanSingles' => [],
        'topGanPairs'   => [],
        'gdbHeadGan'    => [],
        'gdbTailGan'    => [],
        'allGan100'     => [],
      ];
      $cache->set($cacheKey, $empty, 1800);
      $this->view->setVars($empty);
      $this->view->pick($viewPick);
      return;
    }

    // Biến thống kê
    $streak = $maxStreak = $lastSeen = [];
    $pairStreak = $pairMaxStreak = $pairLastSeen = [];
    $headStreak = $headLastSeen = $tailStreak = $tailLastSeen = [];

    foreach ($rows as $row) {
      $ymd = date('Y-m-d', strtotime($row->draw_date));
      $nums = $this->twoDigitsFromRow($row);
      $todaySet = [];
      foreach ($nums as $nn) $todaySet[$nn] = true;

      $this->updateStreaks($streak, $maxStreak, $lastSeen, $todaySet, $ymd);
      $this->updatePairStreaks($pairStreak, $pairMaxStreak, $pairLastSeen, $todaySet, $ymd);
      $this->updateGdbHeadTail($headStreak, $headLastSeen, $tailStreak, $tailLastSeen, $row->special_prize, $ymd);
    }

    $asOfDate = date('Y-m-d', strtotime($rows[count($rows) - 1]->draw_date));

    // Top 10 lô gan đơn
    $singleList = [];
    for ($i = 0; $i <= 99; $i++) {
      $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
      $singleList[] = (object)[
        'number'     => $nn,
        'streak'     => (int)($streak[$nn] ?? 0),
        'last_date'  => $lastSeen[$nn] ?? null,
        'max_streak' => (int)($maxStreak[$nn] ?? 0),
      ];
    }
    usort($singleList, function ($a, $b) {
      if ($a->streak === $b->streak) return strcmp($a->number, $b->number);
      return $b->streak <=> $a->streak;
    });
    $topGanSingles = array_slice($singleList, 0, 10);

    // Top 10 cặp lộn
    $pairList = [];
    foreach ($pairStreak as $key => $st) {
      $pairList[] = (object)[
        'pair'       => $key,
        'streak'     => (int)$pairStreak[$key],
        'last_date'  => $pairLastSeen[$key] ?? null,
        'max_streak' => (int)($pairMaxStreak[$key] ?? 0),
      ];
    }
    usort($pairList, function ($a, $b) {
      if ($a->streak === $b->streak) return strcmp($a->pair, $b->pair);
      return $b->streak <=> $a->streak;
    });
    $topGanPairs = array_slice($pairList, 0, 10);

    // Gan đầu/đuôi GĐB
    $gdbHeadGan = [];
    $gdbTailGan = [];
    for ($d = 0; $d <= 9; $d++) {
      $gdbHeadGan[] = (object)[
        'digit'     => $d,
        'streak'    => (int)($headStreak[$d] ?? 0),
        'last_date' => $headLastSeen[$d] ?? null,
      ];
      $gdbTailGan[] = (object)[
        'digit'     => $d,
        'streak'    => (int)($tailStreak[$d] ?? 0),
        'last_date' => $tailLastSeen[$d] ?? null,
      ];
    }
    usort($gdbHeadGan, fn($a, $b) => $b->streak <=> $a->streak ?: $a->digit <=> $b->digit);
    usort($gdbTailGan, fn($a, $b) => $b->streak <=> $a->streak ?: $a->digit <=> $b->digit);

    // Bảng 00–99 theo số tăng dần
    $allGan100 = $singleList;
    usort($allGan100, fn($a, $b) => strcmp($a->number, $b->number));

    $finalResult = [
      'asOfDate'      => $asOfDate,
      'topGanSingles' => $topGanSingles,
      'topGanPairs'   => $topGanPairs,
      'gdbHeadGan'    => $gdbHeadGan,
      'gdbTailGan'    => $gdbTailGan,
      'allGan100'     => $allGan100,
    ];

    // Tính toán thêm cho phần Text mô tả (Max Gan lớn nhất / nhỏ nhất)
    $maxGanDesc = $singleList; // $singleList là list chưa sort lại theo number (lúc nãy sort theo streak).
    // Tuy nhiên $singleList ở dòng 234 đã bị usort theo streak.
    // Nên ta copy lại từ $allGan100 rồi sort theo max_streak
    $maxGanDesc = $allGan100;
    usort($maxGanDesc, fn($a, $b) => $b->max_streak <=> $a->max_streak ?: strcmp($a->number, $b->number));

    $maxGanAsc = $allGan100;
    usort($maxGanAsc, fn($a, $b) => $a->max_streak <=> $b->max_streak ?: strcmp($a->number, $b->number));

    $finalResult['maxGanDesc'] = array_slice($maxGanDesc, 0, 5); // 5 số max gan lớn nhất
    $finalResult['maxGanAsc']  = array_slice($maxGanAsc, 0, 5);  // 5 số max gan nhỏ nhất

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'lo_gan_xsmb', null, [], $cache);
    $finalResult = array_merge($finalResult, $seoData);

    $cache->set($cacheKey, $finalResult, 7200);

    $this->view->setVars($finalResult);

    // Schema data for cache miss
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $finalResult['seo_title'] ?? 'Lô gan XSMB',
      'description' => $finalResult['seo_description'] ?? 'Lô gan xổ số Miền Bắc',
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Lô gan XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function dacBietXsmbAction()
  {
    $this->view->customindex    = '/css/indexheader.css';
    $this->view->customthongke1 = '/css/thongke/thongke1.css';

    $totalDayRaw = isset($_POST['total_day']) ? (int)$_POST['total_day'] : 100;
    $totalDay    = max(1, min($totalDayRaw, 100));
    $drawType    = 'XSMB';

    // ==== FIX CACHE KEY ====
    $params   = ['uri' => $_SERVER['REQUEST_URI'] ?? '', 'total_day' => $totalDay];
    $cacheKey = 'dacbiet_xsmb_' . md5(json_encode($params));

    $cache    = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/dacbietxsmb';
    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);

      // Schema data for cache hit
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'] ?? 'Đặc biệt XSMB',
        'description' => $cached['seo_description'] ?? 'Thống kê giải đặc biệt xổ số Miền Bắc',
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Đặc biệt XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);

      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db   = $this->getDI()->getShared('db');
    $rows = $this->getRecentByDrawType($drawType, $db, $totalDay);

    $gridRows = $last2Stats = $headStats = $tailStats = $sameDayPast = [];
    $followBlock = ['yesterday_last2' => null, 'rows' => []];
    $asOfDate = null;
    $norm = [];

    if (!empty($rows)) {
      $norm       = $this->normalizeList($rows);
      $asOfDate   = $norm[0]['date'];
      $gridRows   = $this->chunk($norm, 7);
      $last2Stats = $this->statsLast2($norm);
      $headStats  = $this->statsHead($norm);
      $tailStats  = $this->statsTail($norm);

      $sameDayPast = $this->sameDayPastByDrawType($drawType, $asOfDate, $db);
      $followBlock = $this->followYesterdayByDrawType($drawType, $asOfDate, $db, 10);
    }

    // Tính tần suất 00-99 trong khoảng $totalDay
    $countMap = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
    if (!empty($norm)) {
      foreach ($norm as $dayInfo) {
        $l2 = substr(preg_replace('/\D+/', '', $dayInfo['num'] ?? ''), -2);
        if (isset($countMap[$l2])) $countMap[$l2]++;
      }
    }

    // Top 10 về nhiều
    $arrFreq = [];
    foreach ($countMap as $num => $freq) {
      $arrFreq[] = (object)['number' => $num, 'count' => $freq];
    }
    usort($arrFreq, fn($a, $b) => $b->count <=> $a->count ?: strcmp($a->number, $b->number));
    $top10Freq = array_slice($arrFreq, 0, 10);

    // Top 10 về ít
    $arrLeast = $arrFreq;
    usort($arrLeast, fn($a, $b) => $a->count <=> $b->count ?: strcmp($a->number, $b->number));
    $top10Least = array_slice($arrLeast, 0, 10);

    // Xử lý Logic Follow: Dựa trên KQ mới nhất để soi lịch sử
    // Lấy 1 lượng bản ghi lớn hơn để tìm mẫu số
    $historyLimit = 5000;
    $bigRows = $this->getRecentByDrawType($drawType, $db, $historyLimit);

    $forecastPairs = [];
    $followHistory = [];
    $followNextStats = [];
    $latestSeed = null;

    if (!empty($bigRows)) {
      // bigRows được sort DESC (date giảm dần). $bigRows[0] là mới nhất.
      $latest = $bigRows[0];
      $latestSeed = ThongkeStatisticsHelper::last2($latest['special_prize']);

      // Duyệt tìm các lần xuất hiện của seed trong quá khứ
      // Bắt đầu từ 1 vì 0 là hiện tại (chưa có tương lai)
      // Nếu bigRows[i] có 2 số cuối == seed => bigRows[i-1] là kết quả hôm sau
      $nextCounts = [];

      for ($i = 1; $i < count($bigRows); $i++) {
        $currSp = (string)$bigRows[$i]['special_prize'];
        if (trim($currSp) === '') continue;

        $currL2 = ThongkeStatisticsHelper::last2($currSp);
        if ($currL2 === $latestSeed) {
          // Tìm thấy 1 lần trong quá khứ
          $prevIndex = $i - 1; // Vì DESC nên i-1 là ngày sau đó (tương lai của i)
          if (isset($bigRows[$prevIndex])) {
            $nextRow = $bigRows[$prevIndex];
            // Check next row valid
            if (trim((string)$nextRow['special_prize']) === '') continue;

            $nextL2 = ThongkeStatisticsHelper::last2($nextRow['special_prize']);

            // Add to stats
            if (!isset($nextCounts[$nextL2])) $nextCounts[$nextL2] = 0;
            $nextCounts[$nextL2]++;

            // Add to history list (chỉ lấy top 20 hiển thị)
            if (count($followHistory) < 20) {
              $followHistory[] = [
                'date' => $bigRows[$i]['draw_date'],
                'special' => $bigRows[$i]['special_prize'],
                'next_date' => $nextRow['draw_date'],
                'next_special' => $nextRow['special_prize']
              ];
            }
          }
        }
      }

      // Sort tần suất ngày sau
      arsort($nextCounts);
      foreach ($nextCounts as $num => $cnt) {
        $followNextStats[] = (object)['number' => $num, 'count' => $cnt];
      }

      // Dự đoán: Lấy top 4 số về nhiều nhất ngày hôm sau
      $forecastPairs = array_slice(array_keys($nextCounts), 0, 4);
    }

    // Nếu không đủ dữ liệu dự đoán (ví dụ số mới chưa từng ra), lấy top frequence chung
    if (count($forecastPairs) < 4) {
      $remain = 4 - count($forecastPairs);
      for ($k = 0; $k < $remain; $k++) {
        if (isset($top10Freq[$k])) {
          $forecastPairs[] = $top10Freq[$k]->number;
        }
      }
      $forecastPairs = array_unique($forecastPairs);
    }

    $finalResult = [
      'gridRows'    => $gridRows,
      'last2Stats'  => $last2Stats,
      'headStats'   => $headStats,
      'tailStats'   => $tailStats,
      'sameDayPast' => $sameDayPast,
      'followBlock' => $followBlock, // Vẫn giữ cái cũ nếu cần

      // New Data
      'latestSeed'      => $latestSeed,
      'top10Freq'       => $top10Freq,
      'top10Least'      => $top10Least,
      'forecastPairs'   => $forecastPairs,
      'followHistory'   => $followHistory,
      'followNextStats' => array_slice($followNextStats, 0, 10), // Top 10 next stats

      'asOfDate'    => $asOfDate,
      'totalDay'    => $totalDay,
    ];

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'dac_biet_xsmb', null, [], $cache);
    $finalResult = array_merge($finalResult, $seoData);

    $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($gridRows);
    $cache->set($cacheKey, $finalResult, $cacheLifetime);

    $this->view->setVars($finalResult);

    // Schema data for cache miss
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $finalResult['seo_title'] ?? 'Thống kê đặc biệt XSMB',
      'description' => $finalResult['seo_description'] ?? 'Thống kê giải đặc biệt xổ số Miền Bắc',
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Đặc biệt XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function dacBietXsmbTuanAction()
  {
    // Filter params
    $dateEndStr = $this->request->get('date_end', 'string', date('d/m/Y'));
    $dateBeginStr = $this->request->get('date_begin', 'string', date('01/m/Y'));

    // Convert to Y-m-d
    $dEnd = \DateTime::createFromFormat('d/m/Y', $dateEndStr);
    $dBegin = \DateTime::createFromFormat('d/m/Y', $dateBeginStr);

    if (!$dEnd) $dEnd = new \DateTime();
    if (!$dBegin) $dBegin = new \DateTime(date('Y-m-01')); // Default first day of current month

    // Ensure Begin <= End
    if ($dBegin > $dEnd) {
      $temp = $dBegin;
      $dBegin = $dEnd;
      $dEnd = $temp;
    }

    $ymdEnd = $dEnd->format('Y-m-d');
    $ymdBegin = $dBegin->format('Y-m-d');

    // Fetch Data
    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->di->getShared('db');
    $sql = "SELECT draw_date, special_prize 
            FROM lottery_results 
            WHERE draw_type = 'XSMB' 
              AND draw_date >= :dBegin 
              AND draw_date <= :dEnd
            ORDER BY draw_date ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':dBegin', $ymdBegin);
    $stmt->bindValue(':dEnd', $ymdEnd);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Process Weeks
    $weeks = [];
    $touchHead = array_fill(0, 10, 0);
    $touchTail = array_fill(0, 10, 0);
    $touchSum  = array_fill(0, 10, 0);

    // Helper to calc stats
    $calcStats = function ($num) use (&$touchHead, &$touchTail, &$touchSum) {
      if (!preg_match('/^\d+$/', $num)) return null;
      // Normalization: Ensure 2 digits minimal
      $l2 = substr($num, -2);
      if (strlen($l2) < 2) $l2 = str_pad($l2, 2, '0', STR_PAD_LEFT);

      $h = (int)$l2[0];
      $t = (int)$l2[1];
      $s = ($h + $t) % 10;

      $touchHead[$h]++;
      $touchTail[$t]++;
      $touchSum[$s]++;

      return ['h' => $h, 't' => $t, 'loto' => $l2, 'sum' => $s];
    };

    // Grouping
    // Strategy: Strict Mon-Sun weeks grouping
    $grouped = [];
    foreach ($rows as $row) {
      $date = $row['draw_date'];
      $dt = new \DateTime($date);
      $w = $dt->format('o-W'); // ISO week year - week number

      $dayIndex = (int)$dt->format('N') - 1; // 1(Mon)-7(Sun) -> 0-6

      if (!isset($grouped[$w])) {
        $grouped[$w] = array_fill(0, 7, null);
      }

      $stats = $calcStats($row['special_prize']);
      $grouped[$w][$dayIndex] = [
        'full' => $row['special_prize'],
        'date' => $row['draw_date'],
        'stats' => $stats
      ];
    }

    // Re-index to plain array for view
    $weeksOut = [];
    $idx = 1;
    foreach ($grouped as $weekKey => $days) {
      $weeksOut[] = [
        'label' => "Tuần " . $idx++,
        'days' => $days
      ];
    }

    // Reverse weeks if we want newest last? No, standard calendar is typically sequential. 
    // Usually "Week 1", "Week 2" implies chronological order.

    // View vars
    $this->view->setVars([
      'weeks'      => $weeksOut,
      'touchHead'  => $touchHead,
      'touchTail'  => $touchTail,
      'touchSum'   => $touchSum,
      'dateBegin'  => $dBegin->format('d/m/Y'),
      'dateEnd'    => $dEnd->format('d/m/Y'),
      'pageTitle'  => 'Thống kê đặc biệt XSMB theo tuần',
      // For reusing sidebar or layout vars if needed
      'asOfDate'   => time(),
    ]);

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'dac_biet_tuan_xsmb', null, [], $this->di->get('modelsCache'));
    $this->view->setVars($seoData);

    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $seoData['seo_title'] ?? 'Thống kê đặc biệt tuần XSMB',
      'description' => $seoData['seo_description'] ?? 'Thống kê giải đặc biệt theo tuần xổ số Miền Bắc',
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Thống kê tuần', 'url' => $this->getAbsoluteUrl('/dac-biet-tuan-xsmb.html')]
      ]
    ]);

    $this->view->pick('thongkemb/dacbietxsmbtuan');
  }

  public function dacBietXsmbThangAction()
  {
    $month = $this->request->get('month', 'int', (int)date('m'));
    if ($month < 1 || $month > 12) $month = (int)date('m');

    $chon_tinh = $this->request->get('years'); // name in form might be 'years' or 'chon_tinh'? The HTML had <div for="chon_tinh">Select Year... input name=? 
    // Reference HTML: <input type="checkbox" value="2025" checked=""> ... Wait, inputs in mutliSelect need a name to be submitted.
    // The reference HTML form inputs for year checkboxes DID NOT HAVE A "name" attribute visible in the snippet 
    // "<li><input type="checkbox" value="2025" checked=""><span>2025</span></li>"
    // This implies the reference HTML relies on JS to gather values or the snippet was incomplete.
    // We must ensure our View gives them a name, e.g., name="years[]".

    // Default years: last 10 years
    $currentYear = (int)date('Y');
    $defaultYears = range($currentYear, $currentYear - 9);

    $selectedYears = $this->request->get('years', null, $defaultYears);

    if (!is_array($selectedYears)) {
      if (is_string($selectedYears) && strpos($selectedYears, ',') !== false) {
        $selectedYears = explode(',', $selectedYears);
      } elseif (is_numeric($selectedYears)) {
        $selectedYears = [$selectedYears];
      } else {
        // If completely missing or invalid, stick to default
        $selectedYears = $defaultYears;
      }
    }
    // Clean and sort
    $selectedYears = array_map('intval', $selectedYears);
    $selectedYears = array_filter($selectedYears, fn($y) => $y > 1900 && $y <= $currentYear + 1);
    if (empty($selectedYears)) $selectedYears = $defaultYears;
    rsort($selectedYears); // Newest first

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->di->getShared('db');

    // QUERY 1: For the "Month View" (Table 1) - multiple years, specific month
    $inSql = implode(',', $selectedYears);

    $sqlMonth = "SELECT draw_date, special_prize 
                 FROM lottery_results 
                 WHERE draw_type = 'XSMB' 
                   AND MONTH(draw_date) = :m 
                   AND YEAR(draw_date) IN ($inSql)
                 ORDER BY draw_date ASC";

    $stmt = $db->prepare($sqlMonth);
    $stmt->bindValue(':m', $month, \PDO::PARAM_INT);
    $stmt->execute();
    $monthRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Organize Month Data: [Year][Day] => Result
    $monthViewData = [];
    foreach ($selectedYears as $y) {
      $monthViewData[$y] = array_fill(1, 31, null);
    }

    $touchHead = array_fill(0, 10, 0);
    $touchTail = array_fill(0, 10, 0);
    $touchSum  = array_fill(0, 10, 0);

    $calcStats = function ($num) use (&$touchHead, &$touchTail, &$touchSum) {
      if (!preg_match('/^\d+$/', $num)) return null;
      $l2 = substr($num, -2);
      if (strlen($l2) < 2) $l2 = str_pad($l2, 2, '0', STR_PAD_LEFT);

      $h = (int)$l2[0];
      $t = (int)$l2[1];
      $s = ($h + $t) % 10;

      $touchHead[$h]++;
      $touchTail[$t]++;
      $touchSum[$s]++;

      return ['h' => $h, 't' => $t, 'loto' => $l2, 'sum' => $s];
    };

    foreach ($monthRows as $row) {
      $time = strtotime($row['draw_date']);
      $y = (int)date('Y', $time);
      $d = (int)date('d', $time);

      $stats = $calcStats($row['special_prize']);

      if (isset($monthViewData[$y])) {
        $monthViewData[$y][$d] = [
          'full' => $row['special_prize'],
          'stats' => $stats
        ];
      }
    }

    // QUERY 2: For "Year View" (Table 3) - specific year (latest selected), all months
    $latestYear = $selectedYears[0] ?? $currentYear;
    $sqlYear = "SELECT draw_date, special_prize 
                FROM lottery_results 
                WHERE draw_type = 'XSMB' 
                  AND YEAR(draw_date) = :y
                ORDER BY draw_date ASC";
    $stmt2 = $db->prepare($sqlYear);
    $stmt2->bindValue(':y', $latestYear, \PDO::PARAM_INT);
    $stmt2->execute();
    $yearRows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

    // Org Year Data: [Day][Month] => Result
    $yearViewData = [];
    for ($d = 1; $d <= 31; $d++) {
      $yearViewData[$d] = array_fill(1, 12, null);
    }

    foreach ($yearRows as $row) {
      $time = strtotime($row['draw_date']);
      $m = (int)date('m', $time);
      $d = (int)date('d', $time);

      $l2 = substr($row['special_prize'], -2);
      // Pre-pad
      $pre = substr($row['special_prize'], 0, -2);

      $yearViewData[$d][$m] = [
        'pre' => $pre,
        'loto' => $l2
      ];
    }

    $this->view->setVars([
      'month'         => $month,
      'selectedYears' => $selectedYears,
      'monthViewData' => $monthViewData,
      'stats' => [
        'head' => $touchHead,
        'tail' => $touchTail,
        'sum'  => $touchSum
      ],
      'latestYear'    => $latestYear,
      'yearViewData'  => $yearViewData,
      'pageTitle'     => "Thống kê đặc biệt XSMB theo tháng $month",
      'asOfDate'      => time()
    ]);

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'dac_biet_thang_xsmb', null, [], $this->di->get('modelsCache'));
    $this->view->setVars($seoData);

    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $seoData['seo_title'] ?? "Thống kê đặc biệt tháng $month XSMB",
      'description' => $seoData['seo_description'] ?? "Thống kê giải đặc biệt xổ số Miền Bắc tháng $month",
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Thống kê tháng', 'url' => $this->getAbsoluteUrl('/dac-biet-thang-xsmb.html')]
      ]
    ]);

    $this->view->pick('thongkemb/dacbietxsmbthang');
  }




  public function dauDuoiXsmbAction()
  {
    $this->view->customindex    = '/css/indexheader.css';
    $this->view->customthongke1 = '/css/thongke/thongke1.css';

    $dateEndStr = $this->request->get('date_end', 'string', date('d/m/Y'));
    $limit      = $this->request->get('around', 'int', 10);
    $limit      = max(1, min($limit, 100)); // Safety bounds

    // Convert date
    $dEnd = \DateTime::createFromFormat('d/m/Y', $dateEndStr);
    if (!$dEnd) $dEnd = new \DateTime();
    $ymdEnd = $dEnd->format('Y-m-d');

    $region   = 'XSMB';

    // ==== FIX CACHE KEY ====
    $params   = [
      'uri' => $_SERVER['REQUEST_URI'] ?? '',
      'date_end' => $dateEndStr,
      'around' => $limit
    ];
    $cacheKey = 'dauduoi_xsmb_' . md5(json_encode($params));

    $cache    = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/dauduoixsmb';
    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);
      // ... schema ...
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'] ?? 'Đầu đuôi XSMB',
        'description' => $cached['seo_description'] ?? 'Thống kê đầu đuôi xổ số Miền Bắc',
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Đầu đuôi XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);
      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->getDI()->getShared('db');

    // Custom query to support date_end
    $sql = "SELECT draw_date, special_prize, first_prize, second_prize, third_prize,
                 fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE draw_type = :region
              AND draw_date <= :dEnd
            ORDER BY draw_date DESC
            LIMIT :lim";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':dEnd', $ymdEnd);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);


    if (!$rows) {
      $empty = [
        'perDayHead' => [],
        'perDayTail' => [],
        'perDaySum'  => [],
        'specHeadStats' => array_fill(0, 10, 0),
        'specTailStats' => array_fill(0, 10, 0),
        'asOfDate' => null,
        'totalDay' => 0,
      ];
      $cache->set($cacheKey, $empty, 900);
      $this->view->setVars($empty);
      $this->view->pick($viewPick);
      return;
    }

    $perDayHead = [];
    $perDayTail = [];
    $perDaySum  = [];
    $specHeadStats = array_fill(0, 10, 0);
    $specTailStats = array_fill(0, 10, 0);

    foreach ($rows as $r) {
      $allNumbers = $this->flattenAllPrizes($r);
      $lotos      = array_map([$this, 'last2'], $allNumbers);

      $headCnt = array_fill(0, 10, 0);
      $tailCnt = array_fill(0, 10, 0);
      $sumCnt  = array_fill(0, 10, 0);

      foreach ($lotos as $l2) {
        $a = (int)$l2[0];
        $b = (int)$l2[1];
        $headCnt[$a]++;
        $tailCnt[$b]++;
        $sumCnt[($a + $b) % 10]++;
      }

      $perDayHead[] = ['date' => $r['draw_date'], 'counts' => $headCnt];
      $perDayTail[] = ['date' => $r['draw_date'], 'counts' => $tailCnt];
      $perDaySum[]  = ['date' => $r['draw_date'], 'counts' => $sumCnt];

      $sp = $this->onlyDigits((string)$r['special_prize']);
      $sp = str_pad($sp, 5, '0', STR_PAD_LEFT);
      $specHeadStats[(int)$sp[0]]++;
      $specTailStats[(int)substr($sp, -1, 1)]++;
    }

    $asOfDate = $rows[0]['draw_date'];

    $finalResult = [
      'perDayHead' => $perDayHead,
      'perDayTail' => $perDayTail,
      'perDaySum'  => $perDaySum,
      'specHeadStats' => $specHeadStats,
      'specTailStats' => $specTailStats,
      'asOfDate' => $asOfDate,
      'totalDay' => count($rows),
      'limit'    => $limit,
      'dateEnd'  => $dateEndStr,
    ];

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'dau_duoi_xsmb', null, [], $cache);
    $finalResult = array_merge($finalResult, $seoData);

    $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($perDayHead, 'date');
    $cache->set($cacheKey, $finalResult, $cacheLifetime);

    $this->view->setVars($finalResult);

    // Schema data for cache miss
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $finalResult['seo_title'] ?? 'Thống kê đầu đuôi XSMB',
      'description' => $finalResult['seo_description'] ?? 'Thống kê đầu đuôi xổ số Miền Bắc',
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Đầu đuôi XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function tanSuatXsmbAction()
  {
    $this->view->customindex    = '/css/indexheader.css';
    $this->view->customthongke1 = '/css/thongke/thongke1.css';

    // Input
    $provinceId = isset($_POST['province_id']) ? (int)$_POST['province_id'] : 11; // 11 = Miền Bắc
    $wantDays   = isset($_POST['total_day']) ? max(5, (int)$_POST['total_day']) : 100;
    $region     = 'XSMB';

    // ==== FIX CACHE KEY ====
    $params   = ['uri' => $_SERVER['REQUEST_URI'] ?? '', 'province_id' => $provinceId, 'total_day' => $wantDays];
    $cacheKey = 'tansuat_xsmb_' . md5(json_encode($params));

    $cache    = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/tansuatxsmb';
    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);

      // Schema data for cache hit
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'] ?? 'Tần suất XSMB',
        'description' => $cached['seo_description'] ?? 'Thống kê tần suất xổ số Miền Bắc',
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Tần suất XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);

      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db   = $this->getDI()->getShared('db');
    $rows = $this->getRecentFull($region, $provinceId, $db, $wantDays);

    if (!$rows) {
      $empty = [
        'dateList' => [],
        'matrix'   => [],
        'totalDay' => 0,
        'asOfDate' => null,
        'freqJSON' => json_encode(['dates' => [], 'matrix' => []], JSON_UNESCAPED_UNICODE),
      ];
      $cache->set($cacheKey, $empty, 900);
      $this->view->setVars($empty);
      $this->view->pick($viewPick);
      return;
    }

    $daily    = $this->normalizeDailyLoto($rows);  // DESC
    $dateList = array_column($daily, 'date');
    $matrix   = $this->buildFrequencyMatrix($daily);

    $finalResult = [
      'dateList' => $dateList,
      'matrix'   => $matrix,
      'totalDay' => count($dateList),
      'asOfDate' => $dateList[0] ?? null,
      'freqJSON' => json_encode(['dates' => $dateList, 'matrix' => $matrix], JSON_UNESCAPED_UNICODE),
    ];

    // SEO data
    $seoData = \App\Library\SeoHelper::generateStatisticsMeta('XSMB', 'tansuat');
    $finalResult = array_merge($finalResult, $seoData);

    $cacheLifetime = ThongkeStatisticsHelper::getSmartCacheLifetime($daily, 'date');
    $cache->set($cacheKey, $finalResult, $cacheLifetime);

    $this->view->setVars($finalResult);

    // Schema data for cache miss
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $finalResult['seo_title'] ?? 'Tần suất XSMB',
      'description' => $finalResult['seo_description'] ?? 'Thống kê tần suất xổ số Miền Bắc',
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Tần suất XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function tanSuatLoToXsmbAction()
  {
    $dateEndStr = $this->request->get('date_end', 'string', date('d/m/Y'));
    $limit      = $this->request->get('around', 'int',   30); // Default 30 as per template
    $limit      = max(1, min($limit, 100));

    // Convert date
    $dEnd = \DateTime::createFromFormat('d/m/Y', $dateEndStr);
    if (!$dEnd) $dEnd = new \DateTime();
    $ymdEnd = $dEnd->format('Y-m-d');

    $region = 'XSMB';

    // Cache key
    $params = [
      'uri' => $_SERVER['REQUEST_URI'] ?? '',
      'date_end' => $dateEndStr,
      'around' => $limit
    ];
    $cacheKey = 'tansuatloto_xsmb_' . md5(json_encode($params));

    $cache = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/tansuatsolo';

    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);
      // SEO
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'] ?? 'Thống kê tần suất loto XSMB',
        'description' => $cached['seo_description'] ?? 'Thống kê tần suất loto XSMB',
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Tần suất Loto XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);
      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->getDI()->getShared('db');

    // Query with date_end
    $sql = "SELECT draw_date, special_prize, first_prize,
                   second_prize, third_prize, fourth_prize,
                   fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE draw_type = :region
              AND draw_date <= :dEnd
            ORDER BY draw_date DESC
            LIMIT :lim";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':dEnd', $ymdEnd);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Process
    $daily    = $this->normalizeDailyLoto($rows);
    $dateList = array_map(function ($d) {
      $time = strtotime($d['date']);
      return [
        'full' => date('d-m-Y', $time),
        'd'    => date('d', $time),
        'm'    => date('m', $time),
        'ymd'  => $d['date']
      ];
    }, $daily);

    $matrix   = $this->buildFrequencyMatrix($daily);

    // Calculate Extended Stats (Top 10, Least 10, Even/Odd)
    $lotoStats = [];
    foreach ($matrix as $num => $dRow) {
      $lastDate = null;
      // Search by_day for last occurrence (index 0 is latest)
      foreach ($dRow['by_day'] as $idx => $cnt) {
        if ($cnt > 0) {
          $lastDate = $dateList[$idx];
          break;
        }
      }
      $lotoStats[] = [
        'number' => $num,
        'count' => $dRow['total'],
        'last_date_full' => $lastDate['full'] ?? '',
        // Link format: xsmb-dd-mm-YYYY.html
        'last_date_url' => $lastDate ? "xsmb-" . $lastDate['full'] . ".html" : "#",
        'type' => $dRow['type']
      ];
    }

    // Sort Descending (Most Frequent)
    $statDesc = $lotoStats;
    usort($statDesc, function ($a, $b) {
      if ($a['count'] === $b['count']) {
        return $a['number'] <=> $b['number'];
      }
      return $b['count'] <=> $a['count'];
    });
    $top10Freq = array_slice($statDesc, 0, 10);

    // Sort Ascending (Least Frequent)
    $statAsc = $lotoStats;
    usort($statAsc, function ($a, $b) {
      if ($a['count'] === $b['count']) {
        return $a['number'] <=> $b['number'];
      }
      return $a['count'] <=> $b['count'];
    });
    $top10Least = array_slice($statAsc, 0, 10);

    // Even / Odd
    $even = array_filter($lotoStats, function ($x) {
      return $x['type'] === 'chan';
    });
    $odd  = array_filter($lotoStats, function ($x) {
      return $x['type'] === 'le';
    });

    // Sort Even
    usort($even, function ($a, $b) {
      if ($a['count'] === $b['count']) return $a['number'] <=> $b['number'];
      return $b['count'] <=> $a['count'];
    });
    $topEven = array_slice($even, 0, 5);
    $leastEven = array_slice($even, -5); // Smallest are at end? No, sorted DESC. So smallest at end.
    $leastEven = array_reverse($leastEven); // Make them ASC order for display? Or just list them?

    // Sort Odd
    usort($odd, function ($a, $b) {
      if ($a['count'] === $b['count']) return $a['number'] <=> $b['number'];
      return $b['count'] <=> $a['count'];
    });
    $topOdd = array_slice($odd, 0, 5);
    $leastOdd = array_slice($odd, -5);
    $leastOdd = array_reverse($leastOdd);

    $data = [
      'matrix'   => $matrix,
      'dateList' => $dateList,
      'limit'    => $limit,
      'dateEnd'  => $dateEndStr,
      'asOfDate' => $rows[0]['draw_date'] ?? null,
      'top10Freq' => $top10Freq,
      'top10Least' => $top10Least,
      'topEven' => $topEven,
      'leastEven' => $leastEven,
      'topOdd' => $topOdd,
      'leastOdd' => $leastOdd
    ];

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'tan_suat_lo_to_xsmb', null, [], $cache);
    $data = array_merge($data, $seoData);

    $cache->set($cacheKey, $data, 900);

    $this->view->setVars($data);
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $data['seo_title'],
      'description' => $data['seo_description'],
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Tần suất loto XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function loXienXsmbAction()
  {
    // Retrieve params (User uses 'rangeday', support 'limit' as fallback)
    $limit = $this->request->get('rangeday', 'int');
    if (!$limit) $limit = $this->request->get('limit', 'int', 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 100) $limit = 100;

    $dow = $this->request->get('dow', 'int'); // 1=Mon ... 7=Sun

    $dateEndStr = $this->request->get('date_end', 'string');
    if (!$dateEndStr) {
      $dateEndStr = date('d/m/Y');
    }

    // Parse date
    $dEnd = \DateTime::createFromFormat('d/m/Y', $dateEndStr);
    if (!$dEnd) {
      $dEnd = new \DateTime();
      $dateEndStr = $dEnd->format('d/m/Y');
    }
    $ymdEnd = $dEnd->format('Y-m-d');
    $region = 'XSMB';

    // Cache key
    $cacheKey = 'loxien_xsmb_' . md5($dateEndStr . '-' . $limit . '-' . $dow);
    $cache = $this->di->get('modelsCache');

    // View
    $viewPick = 'thongkemb/thongkeloxien';

    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);
      // SEO
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'],
        'description' => $cached['seo_description'],
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Thống kê lô xiên XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);
      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->getDI()->getShared('db');

    // Fetch Data
    $sql = "SELECT draw_date, special_prize, first_prize,
                   second_prize, third_prize, fourth_prize,
                   fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE draw_type = :region
              AND draw_date <= :dEnd";

    // Add DOW filter if present
    // User dow: 1 (Mon) -> 7 (Sun)
    // MySQL WEEKDAY: 0 (Mon) -> 6 (Sun)
    if ($dow && $dow >= 1 && $dow <= 7) {
      $sql .= " AND WEEKDAY(draw_date) = :wday";
    }

    $sql .= " ORDER BY draw_date DESC LIMIT :lim";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':dEnd', $ymdEnd);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);

    if ($dow && $dow >= 1 && $dow <= 7) {
      $stmt->bindValue(':wday', $dow - 1, \PDO::PARAM_INT);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Normalize
    $daily = $this->normalizeDailyLoto($rows);
    $dateList = [];
    $xien2Acc = [];
    $xien3Acc = [];

    foreach ($daily as $dayData) {
      $dStr = $dayData['date']; // Y-m-d
      $time = strtotime($dStr);
      $dObj = [
        'full' => date('d-m-Y', $time),
        'd'    => date('d', $time),
        'm'    => date('m', $time),
        'ymd'  => $dStr
      ];
      $dateList[] = $dObj;

      $nums = $dayData['nums'];

      // Filter invalid numbers
      $nums = array_filter($nums, function ($n) {
        return is_string($n) && strlen(trim($n)) > 0;
      });

      // Filter invalid numbers
      $nums = array_filter($nums, function ($n) {
        return is_string($n) && strlen(trim($n)) > 0;
      });

      // Unique first to remove duplicates
      $nums = array_unique($nums);
      // Sort re-indexes the array keys to 0..N
      sort($nums);

      $count = count($nums);

      // Xiên 2
      for ($i = 0; $i < $count - 1; $i++) {
        for ($j = $i + 1; $j < $count; $j++) {
          $pair = $nums[$i] . ' - ' . $nums[$j];
          if (!isset($xien2Acc[$pair])) {
            $xien2Acc[$pair] = ['pair' => $pair, 'count' => 0, 'dates' => []];
          }
          $xien2Acc[$pair]['count']++;
          $xien2Acc[$pair]['dates'][] = $dObj;
        }
      }

      // Xiên 3
      for ($i = 0; $i < $count - 2; $i++) {
        for ($j = $i + 1; $j < $count - 1; $j++) {
          for ($k = $j + 1; $k < $count; $k++) {
            $triplet = $nums[$i] . ' - ' . $nums[$j] . ' - ' . $nums[$k];
            if (!isset($xien3Acc[$triplet])) {
              $xien3Acc[$triplet] = ['triplet' => $triplet, 'count' => 0, 'dates' => []];
            }
            $xien3Acc[$triplet]['count']++;
            $xien3Acc[$triplet]['dates'][] = $dObj;
          }
        }
      }
    }

    // Sort and Slice Xiên 2
    usort($xien2Acc, function ($a, $b) {
      return $b['count'] <=> $a['count'];
    });
    $xien2 = array_slice($xien2Acc, 0, 50);

    // Sort and Slice Xiên 3
    usort($xien3Acc, function ($a, $b) {
      return $b['count'] <=> $a['count'];
    });
    $xien3 = array_slice($xien3Acc, 0, 50);

    // Frequency Matrix
    $matrix = $this->buildFrequencyMatrix($daily);

    $data = [
      'xien2' => $xien2,
      'xien3' => $xien3,
      'matrix' => $matrix,
      'dateList' => $dateList,
      'limit' => $limit,
      'dow' => $dow,
      'dateEnd' => $dateEndStr,
    ];

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'lo_xien_xsmb', null, [], $cache);
    $data = array_merge($data, $seoData);

    $cache->set($cacheKey, $data, 900);
    $this->view->setVars($data);
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $data['seo_title'],
      'description' => $data['seo_description'],
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Thống kê lô xiên XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function getRecentFull(string $region, ?int $provinceId, \Phalcon\Db\Adapter\Pdo\Mysql $connection, int $limit = 100): array
  {
    // Temporarily remove province_id filter for debugging
    $sql = "SELECT draw_date, special_prize, first_prize,
                   second_prize, third_prize, fourth_prize,
                   fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE draw_type = :region
            ORDER BY draw_date DESC
            LIMIT :lim";

    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  public function extractAllPrizeNumbers(array $row): array
  {
    $cols = [
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

    $out = [];
    foreach ($cols as $c) {
      if (!isset($row[$c]) || $row[$c] === null || $row[$c] === '') continue;

      $v = $row[$c];

      if (is_array($v)) {
        $arr = $v;
      } else {
        // Thử decode JSON
        $arr = null;
        if (is_string($v) && strlen($v) > 0 && $v[0] === '[') {
          $decoded = json_decode($v, true);
          if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $arr = $decoded;
          }
        }
        if ($arr === null) {
          // fallback: tách theo ký tự không phải số
          $arr = preg_split('/[^0-9]+/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);
        }
      }

      if (!$arr) continue;
      foreach ($arr as $num) {
        $num = preg_replace('/\D+/', '', (string)$num);
        if ($num === '') continue;
        $out[] = $num;
      }
    }

    return $out;
  }

  public function normalizeDailyLoto(array $rows): array
  {
    $out = [];
    foreach ($rows as $r) {
      $all = $this->extractAllPrizeNumbers($r);
      $last2s = [];
      foreach ($all as $num) {
        $last2s[] = $this->last2($num);
      }
      $out[] = [
        'date' => $r['draw_date'],
        'nums' => $last2s,
      ];
    }
    return $out;
  }

  public function buildFrequencyMatrix(array $daily): array
  {
    $days = count($daily);
    // Khởi tạo
    $matrix = [];
    for ($n = 0; $n <= 99; $n++) {
      $val = str_pad((string)$n, 2, '0', STR_PAD_LEFT);
      $dau = (int)$val[0];
      $duoi = (int)$val[1];
      $matrix[$val] = [
        'by_day' => array_fill(0, $days, 0),
        'total'  => 0,
        'dau'    => $dau,
        'duoi'   => $duoi,
        'type'   => ((int)$val % 2 === 0 ? 'chan' : 'le'),
      ];
    }

    // Đổ dữ liệu (chú ý daily đã DESC: index 0 là ngày mới nhất)
    foreach ($daily as $i => $d) {
      if (empty($d['nums'])) continue;
      foreach ($d['nums'] as $l2) {
        if (!isset($matrix[$l2])) continue; // phòng hờ
        $matrix[$l2]['by_day'][$i] += 1;
        $matrix[$l2]['total']      += 1;
      }
    }
    return $matrix;
  }

  private function fetchXsmbForHeadTail(
    \Phalcon\Db\Adapter\Pdo\Mysql $db,
    string $region,
    int $limit
  ): array {
    $sql = "SELECT draw_date, special_prize, first_prize, second_prize, third_prize,
                   fourth_prize, fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE draw_type = :region
            ORDER BY draw_date DESC
            LIMIT :lim";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  private function flattenAllPrizes(array $row): array
  {
    $keys = [
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
    $out = [];
    foreach ($keys as $k) {
      if (!isset($row[$k]) || $row[$k] === null || $row[$k] === '') continue;
      $val = $row[$k];

      // second_prize .. eighth_prize có thể là JSON array (["...","..."]) hoặc CSV ("a,b,c") hoặc 1 số
      if ($this->looksLikeJsonArray($val)) {
        $arr = json_decode($val, true);
        if (is_array($arr)) {
          foreach ($arr as $v) {
            $d = $this->onlyDigits((string)$v);
            if ($d !== '') $out[] = $d;
          }
          continue;
        }
      }

      // Nếu không phải JSON hợp lệ -> tách CSV hoặc đơn trị
      $parts = array_map('trim', explode(',', (string)$val));
      foreach ($parts as $p) {
        if ($p === '') continue;
        $d = $this->onlyDigits($p);
        if ($d !== '') $out[] = $d;
      }
    }
    return $out;
  }

  private function looksLikeJsonArray($s): bool
  {
    if (!is_string($s)) return false;
    $s = ltrim($s);
    return strlen($s) > 1 && $s[0] === '[';
  }

  private function onlyDigits(string $s): string
  {
    return preg_replace('/\D+/', '', $s);
  }

  private function last2(string $num): string
  {
    return ThongkeStatisticsHelper::last2($num);
  }

  public function getRecent(string $region, ?int $provinceId, \Phalcon\Db\Adapter\Pdo\Mysql $connection, int $limit = 100): array
  {
    if ($provinceId) {
      return ThongkeStatisticsHelper::getRecentByProvince($provinceId, $connection, $limit, false);
    }

    $sql = "SELECT draw_date, special_prize
                FROM lottery_results
                WHERE draw_type = :region
                ORDER BY draw_date DESC
                LIMIT :lim";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /** Lấy kết quả đúng 1 ngày (nếu có) */
  public function getByDate(string $region, ?int $provinceId, string $ymd, \Phalcon\Db\Adapter\Pdo\Mysql $connection): ?array
  {
    $sql = "SELECT draw_date, special_prize
                FROM lottery_results
                WHERE draw_type = :region" // Changed 'region' to 'draw_type'
      . ($provinceId ? " AND province_id = :pid" : "")
      . " AND draw_date = :d
                LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':region', $region);
    if ($provinceId) $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
    $stmt->bindValue(':d', $ymd);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function getNextAfter(string $region, ?int $provinceId, string $ymd, \Phalcon\Db\Adapter\Pdo\Mysql $connection): ?array
  {
    $sql = "SELECT draw_date, special_prize
                FROM lottery_results
                WHERE draw_type = :region" // Changed 'region' to 'draw_type'
      . ($provinceId ? " AND province_id = :pid" : "")
      . " AND draw_date > :d
                ORDER BY draw_date ASC
                LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':region', $region);
    if ($provinceId) $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
    $stmt->bindValue(':d', $ymd);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function findByLast2(string $region, ?int $provinceId, string $last2, \Phalcon\Db\Adapter\Pdo\Mysql $connection, int $limit = 20): array
  {
    $sql = "SELECT draw_date, special_prize
                FROM lottery_results
                WHERE draw_type = :region" // Changed 'region' to 'draw_type'
      . ($provinceId ? " AND province_id = :pid" : "")
      . " AND RIGHT(special_prize, 2) = :l2
                ORDER BY draw_date DESC
                LIMIT :lim";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':region', $region);
    if ($provinceId) $stmt->bindValue(':pid', $provinceId, \PDO::PARAM_INT);
    $stmt->bindValue(':l2', $last2);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  public function head(string $num): string
  {
    return ThongkeStatisticsHelper::headDigit($num);
  }
  public function tail(string $num): string
  {
    return ThongkeStatisticsHelper::tailDigit($num);
  }

  public function normalizeList(array $rows): array
  {
    $out = [];
    foreach ($rows as $r) {
      $num = (string)$r['special_prize'];
      $out[] = [
        'date'  => $r['draw_date'],
        'num'   => $num,
        'last2' => self::last2($num),
        'head'  => self::head($num),
        'tail'  => self::tail($num),
      ];
    }
    return $out;
  }

  public function statsLast2(array $normList): array
  {
    $cnt = array_fill_keys(array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(0, 99)), 0);
    foreach ($normList as $row) $cnt[$row['last2']]++;
    // sort desc theo count rồi số
    uasort($cnt, function ($a, $b) {
      return $b <=> $a;
    });
    // trả về mảng [{val, times}]
    $out = [];
    foreach ($cnt as $val => $times) if ($times > 0) $out[] = ['val' => $val, 'times' => $times];
    return $out;
  }

  public function statsHead(array $normList): array
  {
    $cnt = array_fill_keys(range(0, 9), 0);
    foreach ($normList as $row) $cnt[(int)$row['head']]++;
    arsort($cnt);
    $out = [];
    foreach ($cnt as $digit => $times) $out[] = ['digit' => (string)$digit, 'times' => $times];
    return $out;
  }
  public function statsTail(array $normList): array
  {
    $cnt = array_fill_keys(range(0, 9), 0);
    foreach ($normList as $row) $cnt[(int)$row['tail']]++;
    arsort($cnt);
    $out = [];
    foreach ($cnt as $digit => $times) $out[] = ['digit' => (string)$digit, 'times' => $times];
    return $out;
  }

  public function sameDayPast(string $region, ?int $provinceId, string $anchorYmd, \Phalcon\Db\Adapter\Pdo\Mysql $connection): array
  {
    $dates = [
      ['label' => '1 tuần trước',  'date' => date('Y-m-d', strtotime($anchorYmd . ' -7 days'))],
      ['label' => '1 tháng trước', 'date' => date('Y-m-d', strtotime($anchorYmd . ' -1 month'))],
    ];
    for ($i = 1; $i <= 5; $i++) {
      $dates[] = ['label' => "$i năm trước", 'date' => date('Y-m-d', strtotime($anchorYmd . " -$i year"))];
    }
    $out = [];
    foreach ($dates as $d) {
      $row = $this->getByDate($region, $provinceId, $d['date'], $connection);
      if ($row) {
        $out[] = [
          'date_text' => self::weekdayVN($d['date']) . ', ' . date('d/m/Y', strtotime($d['date'])),
          'ago'       => $d['label'],
          'num'       => $row['special_prize'],
          'last2'     => self::last2($row['special_prize']),
        ];
      }
    }
    return $out;
  }

  private function sameDayPastByDrawType(string $drawType, string $anchorYmd, \Phalcon\Db\Adapter\Pdo\Mysql $db): array
  {
    $dates = [
      ['label' => '1 tuần trước',  'date' => date('Y-m-d', strtotime($anchorYmd . ' -7 days'))],
      ['label' => '1 tháng trước', 'date' => date('Y-m-d', strtotime($anchorYmd . ' -1 month'))],
    ];
    for ($i = 1; $i <= 5; $i++) {
      $dates[] = ['label' => "$i năm trước", 'date' => date('Y-m-d', strtotime("$anchorYmd -$i year"))];
    }

    $out = [];
    foreach ($dates as $d) {
      $row = $this->getByDateByDrawType($drawType, $d['date'], $db);
      if ($row) {
        $out[] = [
          'date_text' => $this->weekdayVN($d['date']) . ', ' . date('d/m/Y', strtotime($d['date'])),
          'ago'       => $d['label'],
          'num'       => $row['special_prize'],
          'last2'     => $this->last2($row['special_prize']),
        ];
      }
    }
    return $out;
  }
  private function findByLast2ByDrawType(string $drawType, string $last2, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit = 20): array
  {
    $limit = (int)$limit;
    $sql = "
        SELECT draw_date, special_prize
        FROM lottery_results
        WHERE draw_type = :dt
          AND RIGHT(CAST(special_prize AS CHAR(20)), 2) = :l2
        ORDER BY draw_date DESC
        LIMIT $limit
    ";
    $st = $db->prepare($sql);
    $st->bindValue(':dt', $drawType);
    $st->bindValue(':l2', $last2);
    $st->execute();
    return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
  }
  private function getNextAfterByDrawType(string $drawType, string $ymd, \Phalcon\Db\Adapter\Pdo\Mysql $db): ?array
  {
    $sql = "
        SELECT draw_date, special_prize
        FROM lottery_results
        WHERE draw_type = :dt AND draw_date > :d
        ORDER BY draw_date ASC
        LIMIT 1
    ";
    $st = $db->prepare($sql);
    $st->bindValue(':dt', $drawType);
    $st->bindValue(':d',  $ymd);
    $st->execute();
    $row = $st->fetch(\PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  private function getRecentByDrawType(string $drawType, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit = 100): array
  {
    $limit = (int)$limit; // tránh bind LIMIT
    $sql = "
        SELECT draw_date, special_prize
        FROM lottery_results
        WHERE draw_type = :dt
        ORDER BY draw_date DESC
        LIMIT $limit
    ";
    $st = $db->prepare($sql);
    $st->bindValue(':dt', $drawType);
    $st->execute();
    return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
  }

  /** Lấy đúng 1 ngày theo draw_type */
  private function getByDateByDrawType(string $drawType, string $ymd, \Phalcon\Db\Adapter\Pdo\Mysql $db): ?array
  {
    $sql = "
        SELECT draw_date, special_prize
        FROM lottery_results
        WHERE draw_type = :dt AND draw_date = :d
        LIMIT 1
    ";
    $st = $db->prepare($sql);
    $st->bindValue(':dt', $drawType);
    $st->bindValue(':d',  $ymd);
    $st->execute();
    $row = $st->fetch(\PDO::FETCH_ASSOC);
    return $row ?: null;
  }
  /** Bảng “Hôm qua về XX, xem các lần XX trước đó & ngày sau” */
  private function followYesterdayByDrawType(string $drawType, string $anchorYmd, \Phalcon\Db\Adapter\Pdo\Mysql $db, int $limit = 10): array
  {
    $yesterday = date('Y-m-d', strtotime($anchorYmd . ' -1 day'));
    $yRow = $this->getByDateByDrawType($drawType, $yesterday, $db);
    if (!$yRow) return ['yesterday_last2' => null, 'rows' => []];

    $yLast2 = $this->last2($yRow['special_prize']);
    $occurs = $this->findByLast2ByDrawType($drawType, $yLast2, $db, $limit + 1);

    $rows = [];
    foreach ($occurs as $oc) {
      if ($oc['draw_date'] === $yesterday) continue;
      $next = $this->getNextAfterByDrawType($drawType, $oc['draw_date'], $db);
      if ($next) {
        $rows[] = [
          'prev_date' => $oc['draw_date'],
          'prev_num'  => $oc['special_prize'],
          'next_date' => $next['draw_date'],
          'next_num'  => $next['special_prize'],
        ];
      }
      if (count($rows) >= $limit) break;
    }

    return ['yesterday_last2' => $yLast2, 'rows' => $rows];
  }

  /** Helper VN weekday */
  public function weekdayVN(string $ymd): string
  {
    return ThongkeStatisticsHelper::weekdayVN($ymd);
  }

  /** Chia mảng thành từng hàng $cols phần tử để view render lưới 7 cột */
  public function chunk(array $arr, int $cols = 7): array
  {
    return array_chunk($arr, $cols);
  }
  private function pairKey(string $nn): ?string
  {
    if (!preg_match('/^\d{2}$/', $nn)) return null;
    $rev = strrev($nn);
    if ($rev === $nn) return null; // loại số kép (00,11,...99) khỏi bảng cặp lộn
    // tạo key theo thứ tự từ điển để gom 1 lần
    return (strcmp($nn, $rev) < 0) ? ($nn . '-' . $rev) : ($rev . '-' . $nn);
  }

  /** Cập nhật streak/maxStreak/lastSeen cho *tập hợp số* trong 1 kỳ quay */
  private function updateStreaks(array &$streak, array &$maxStreak, array &$lastSeen, array $todaySet, string $dateYmd): void
  {
    for ($i = 0; $i <= 99; $i++) {
      $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
      if (isset($todaySet[$nn])) {
        // xuất hiện -> reset streak, cập nhật last seen
        $streak[$nn] = 0;
        $lastSeen[$nn] = $dateYmd;
      } else {
        // không xuất hiện -> tăng streak
        $streak[$nn] = ($streak[$nn] ?? 0) + 1;
        $maxStreak[$nn] = max($maxStreak[$nn] ?? 0, $streak[$nn]);
      }
    }
  }

  /** Cập nhật streak cho *cặp lộn* trong 1 kỳ quay */
  private function updatePairStreaks(array &$pStreak, array &$pMaxStreak, array &$pLastSeen, array $todaySet, string $dateYmd): void
  {
    // tạo danh sách cặp lộn chuẩn (45 cặp)
    static $allPairs = null;
    if ($allPairs === null) {
      $allPairs = [];
      for ($i = 0; $i <= 99; $i++) {
        $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        $key = $this->pairKey($nn);
        if ($key) $allPairs[$key] = true;
      }
      $allPairs = array_keys($allPairs);
    }

    foreach ($allPairs as $key) {
      [$a, $b] = explode('-', $key, 2);
      $present = isset($todaySet[$a]) || isset($todaySet[$b]);
      if ($present) {
        $pStreak[$key] = 0;
        $pLastSeen[$key] = $dateYmd;
      } else {
        $pStreak[$key] = ($pStreak[$key] ?? 0) + 1;
        $pMaxStreak[$key] = max($pMaxStreak[$key] ?? 0, $pStreak[$key]);
      }
    }
  }

  /** Cập nhật gan đầu/đuôi *giải đặc biệt* theo số kỳ */
  private function updateGdbHeadTail(array &$headStreak, array &$headLastSeen, array &$tailStreak, array &$tailLastSeen, ?string $gdb, string $dateYmd): void
  {
    // init
    for ($d = 0; $d <= 9; $d++) {
      if (!isset($headStreak[$d])) $headStreak[$d] = 0;
      if (!isset($tailStreak[$d])) $tailStreak[$d] = 0;
    }

    $head = null;
    $tail = null;
    if (is_string($gdb) && preg_match('/^\d+$/', $gdb)) {
      $head = (int)substr($gdb, 0, 1);
      $tail = (int)substr($gdb, -1);
    }

    // khi có kết quả hôm nay:
    for ($d = 0; $d <= 9; $d++) {
      // head
      if ($head !== null && $d === $head) {
        $headStreak[$d] = 0;
        $headLastSeen[$d] = $dateYmd;
      } else {
        $headStreak[$d] = ($headStreak[$d] ?? 0) + 1;
      }
      // tail
      if ($tail !== null && $d === $tail) {
        $tailStreak[$d] = 0;
        $tailLastSeen[$d] = $dateYmd;
      } else {
        $tailStreak[$d] = ($tailStreak[$d] ?? 0) + 1;
      }
    }
  }

  protected function safeDecode(?string $src): array
  {
    if ($src === null) return [];
    $s = trim((string)$src);

    // Thử JSON
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

    // Fallback: normalize phân tách
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

  protected function twoDigitsFromRow($row): array
  {
    $all = [];
    $push = static function (?string $val) use (&$all) {
      if (!$val) return;
      if (!preg_match('/^\d+$/', $val)) return;
      $all[] = str_pad(substr($val, -2), 2, '0', STR_PAD_LEFT);
    };

    $push($row->special_prize);
    $push($row->first_prize);

    foreach ($this->safeDecode($row->second_prize)  as $v) $push($v);
    foreach ($this->safeDecode($row->third_prize)   as $v) $push($v);
    foreach ($this->safeDecode($row->fourth_prize)  as $v) $push($v);
    foreach ($this->safeDecode($row->fifth_prize)   as $v) $push($v);
    foreach ($this->safeDecode($row->sixth_prize)   as $v) $push($v);
    foreach ($this->safeDecode($row->seventh_prize) as $v) $push($v);
    foreach ($this->safeDecode($row->eighth_prize)  as $v) $push($v);

    return $all;
  }

  protected function buildFrequencyTop($rows, int $limit = 10): array
  {
    // Use helper to extract data more efficiently
    $freq = [];
    foreach ($rows as $r) {
      foreach ($this->twoDigitsFromRow($r) as $nn) {
        $freq[$nn] = ($freq[$nn] ?? 0) + 1;
      }
    }
    arsort($freq);

    $out = [];
    foreach ($freq as $nn => $cnt) {
      $out[] = ['number' => $nn, 'count' => $cnt];
      if (count($out) >= $limit) break;
    }
    return $out;
  }

  /**
   * Tính lô gan top N từ lịch sử (ORDER BY draw_date ASC).
   * miss = số kỳ liên tiếp chưa ra đến kỳ mới nhất trong cửa sổ.
   * max_gap = khoảng cách lớn nhất giữa 2 lần về trong cửa sổ.
   */
  protected function buildLoGanFromHistory($historyRows, int $top = 10): array
  {
    $lastSeenIndex = array_fill_keys(range(0, 99), null);
    $lastSeenDate  = array_fill_keys(range(0, 99), null);
    $prevSeenIndex = array_fill_keys(range(0, 99), null);
    $maxGap        = array_fill_keys(range(0, 99), 0);

    $N = count($historyRows);
    foreach ($historyRows as $i => $row) {
      $seenToday = array_fill_keys(range(0, 99), false);

      foreach ($this->twoDigitsFromRow($row) as $nn) {
        $k = (int)$nn;
        if ($seenToday[$k]) continue;
        $seenToday[$k] = true;

        if ($prevSeenIndex[$k] !== null) {
          $gap = $i - $prevSeenIndex[$k] - 1;
          if ($gap > $maxGap[$k]) $maxGap[$k] = $gap;
        }
        $prevSeenIndex[$k] = $i;
        $lastSeenIndex[$k] = $i;
        $lastSeenDate[$k]  = $row->draw_date;
      }
    }

    $missNow = [];
    foreach (range(0, 99) as $k) {
      $missNow[$k] = ($lastSeenIndex[$k] === null) ? $N : (($N - 1) - $lastSeenIndex[$k]);
    }

    arsort($missNow);
    $out = [];
    foreach ($missNow as $k => $miss) {
      $out[] = [
        'number'        => str_pad((string)$k, 2, '0', STR_PAD_LEFT),
        'miss'          => $miss,
        'last_appeared' => $lastSeenDate[$k],
        'max_gap'       => $maxGap[$k],
      ];
      if (count($out) >= $top) break;
    }
    return $out;
  }

  /* ================== ACTION: Thống kê XSMB ================== */

  public function loKepXsmbAction()
  {
    // Retrieve params
    $limit = $this->request->get('rangeday', 'int');
    if (!$limit) $limit = $this->request->get('limit', 'int', 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 100) $limit = 100;

    $dow = $this->request->get('dow', 'int'); // 1=Mon ... 7=Sun

    $dateEndStr = $this->request->get('date_end', 'string');
    if (!$dateEndStr) {
      $dateEndStr = date('d/m/Y');
    }

    // Parse date
    $dEnd = \DateTime::createFromFormat('d/m/Y', $dateEndStr);
    if (!$dEnd) {
      $dEnd = new \DateTime();
      $dateEndStr = $dEnd->format('d/m/Y');
    }
    $ymdEnd = $dEnd->format('Y-m-d');
    $region = 'XSMB';

    // Cache key
    $cacheKey = 'lokep_xsmb_' . md5($dateEndStr . '-' . $limit . '-' . $dow);
    $cache = $this->di->get('modelsCache');

    // View: lokepmb.phtml
    $viewPick = 'thongkemb/lokepmb';

    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);
      // SEO
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'],
        'description' => $cached['seo_description'],
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Thống kê lô kép XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);
      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->getDI()->getShared('db');

    // Fetch Data
    $sql = "SELECT draw_date, special_prize, first_prize,
                   second_prize, third_prize, fourth_prize,
                   fifth_prize, sixth_prize, seventh_prize, eighth_prize
            FROM lottery_results
            WHERE draw_type = :region
              AND draw_date <= :dEnd";

    if ($dow && $dow >= 1 && $dow <= 7) {
      $sql .= " AND WEEKDAY(draw_date) = :wday";
    }

    $sql .= " ORDER BY draw_date DESC LIMIT :lim";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':dEnd', $ymdEnd);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);

    if ($dow && $dow >= 1 && $dow <= 7) {
      $stmt->bindValue(':wday', $dow - 1, \PDO::PARAM_INT);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Normalize
    $daily = $this->normalizeDailyLoto($rows);
    $dateList = [];

    // Accumulators
    $doubleNums = ['00', '11', '22', '33', '44', '55', '66', '77', '88', '99'];
    $lokepStats = []; // For single doubles
    foreach ($doubleNums as $dn) {
      $lokepStats[$dn] = ['num' => $dn, 'count' => 0, 'dates' => []];
    }

    $lokep2Stats = []; // For pairs of doubles

    foreach ($daily as $dayData) {
      $dStr = $dayData['date']; // Y-m-d
      $time = strtotime($dStr);
      $dObj = [
        'full' => date('d-m-Y', $time),
        'd'    => date('d', $time),
        'm'    => date('m', $time),
        'ymd'  => $dStr,
        'url'  => '/xsmb-' . date('d-m-Y', $time) . '.html'
      ];
      $dateList[] = $dObj;

      $nums = $dayData['nums'];

      // Filter invalid numbers
      $nums = array_filter($nums, function ($n) {
        return is_string($n) && strlen(trim($n)) > 0;
      });
      $nums = array_unique($nums);
      sort($nums);

      // Identify Doubles in this day
      $dayDoubles = [];
      foreach ($nums as $n) {
        if (in_array($n, $doubleNums)) {
          $dayDoubles[] = $n;

          // Update single stats
          $lokepStats[$n]['count']++;
          $lokepStats[$n]['dates'][] = $dObj;
        }
      }

      // Identify Pairs of Doubles (Lo Kep 2)
      $countD = count($dayDoubles);
      if ($countD >= 2) {
        sort($dayDoubles);
        for ($i = 0; $i < $countD - 1; $i++) {
          for ($j = $i + 1; $j < $countD; $j++) {
            $pair = $dayDoubles[$i] . ' - ' . $dayDoubles[$j];
            if (!isset($lokep2Stats[$pair])) {
              $lokep2Stats[$pair] = ['pair' => $pair, 'count' => 0, 'dates' => []];
            }
            $lokep2Stats[$pair]['count']++;
            $lokep2Stats[$pair]['dates'][] = $dObj;
          }
        }
      }
    }

    // Sort Single Stats
    usort($lokepStats, function ($a, $b) {
      return $b['count'] <=> $a['count'];
    });

    // Sort Pair Stats
    usort($lokep2Stats, function ($a, $b) {
      return $b['count'] <=> $a['count'];
    });

    $titleS = "Thống kê Lô kép XSMB";
    if ($dow) $titleS .= " thứ " . ($dow == 7 ? "CN" : $dow + 1);
    $titleS .= " - Soi cầu Lô kép miền Bắc";

    $data = [
      'lokep_stats'   => $lokepStats,
      'lokep_2_stats' => $lokep2Stats,
      'dateList'      => $dateList,
      'limit'         => $limit,
      'dow'           => $dow,
      'dateEnd'       => $dateEndStr,
    ];

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'lo_kep_xsmb', null, [], $cache);
    $data = array_merge($data, $seoData);

    // Set Cache
    $cache->set($cacheKey, $data, 900); // 15 mins

    $this->view->setVars($data);

    // Schema
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $data['seo_title'],
      'description' => $data['seo_description'],
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Lô kép XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  public function thongKeTheoTongXsmbAction()
  {
    // Retrieve params
    $dateBeginStr = $this->request->get('date_begin', 'string');
    $dateEndStr = $this->request->get('date_end', 'string');
    $chonTong = $this->request->get('chon_tong', 'int', 0); // 0-9
    $isSpecial = $this->request->get('is_special', 'int', 0); // 0 or 1

    if (!$dateBeginStr) {
      $dateBeginStr = date('d/m/Y', strtotime('-30 days'));
    }
    if (!$dateEndStr) {
      $dateEndStr = date('d/m/Y');
    }

    // Parse dates
    $dBegin = \DateTime::createFromFormat('d/m/Y', $dateBeginStr);
    $dEnd = \DateTime::createFromFormat('d/m/Y', $dateEndStr);
    if (!$dBegin) $dBegin = new \DateTime('-30 days');
    if (!$dEnd) $dEnd = new \DateTime();

    $ymdBegin = $dBegin->format('Y-m-d');
    $ymdEnd = $dEnd->format('Y-m-d');
    $region = 'XSMB';

    // Cache key
    $cacheKey = 'tk_tong_xsmb_' . md5($dateBeginStr . '-' . $dateEndStr . '-' . $chonTong . '-' . $isSpecial);
    $cache = $this->di->get('modelsCache');
    $viewPick = 'thongkemb/thongketheotong';

    if ($cached = $cache->get($cacheKey)) {
      $this->view->setVars($cached);
      $this->view->setVar('page_schema_type', 'webpage');
      $this->view->setVar('page_schema_data', [
        'title' => $cached['seo_title'],
        'description' => $cached['seo_description'],
        'url' => $this->getAbsoluteUrl(),
        'breadcrumbs' => [
          ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
          ['name' => 'Thống kê theo tổng XSMB', 'url' => $this->getAbsoluteUrl()]
        ]
      ]);
      $this->view->pick($viewPick);
      return;
    }

    /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
    $db = $this->getDI()->getShared('db');

    // Fetch Data
    $sql = "SELECT draw_date, special_prize, first_prize,
                     second_prize, third_prize, fourth_prize,
                     fifth_prize, sixth_prize, seventh_prize, eighth_prize
              FROM lottery_results
              WHERE draw_type = :region
                AND draw_date BETWEEN :dBegin AND :dEnd
              ORDER BY draw_date DESC";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':region', $region);
    $stmt->bindValue(':dBegin', $ymdBegin);
    $stmt->bindValue(':dEnd', $ymdEnd);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Normalize
    $daily = $this->normalizeDailyLoto($rows);

    $targetNumbers = [];
    for ($i = 0; $i <= 99; $i++) {
      $ns = str_pad($i, 2, '0', STR_PAD_LEFT);
      $sum = ((int)$ns[0] + (int)$ns[1]) % 10;
      if ($sum == $chonTong) {
        $targetNumbers[$ns] = [
          'num' => $ns,
          'count' => 0,
          'last_date' => null,
          'days_since' => null
        ];
      }
    }

    // Re-processing rows for specific logic
    // Note: $rows is DESC by date.
    $stats = $targetNumbers;

    // Map for quick lookup
    $targetKeys = array_keys($targetNumbers);

    // For "Recent Loto by Sum" (for the second table)
    $recentDraw = null;
    if (count($rows) > 0) {
      $recentDraw = $rows[0];
    }


    $recentLotoBySum = [];
    for ($k = 0; $k <= 9; $k++) $recentLotoBySum[$k] = [];

    if ($recentDraw && isset($daily[0])) {
      $recentNums = $daily[0]['nums'];
      foreach ($recentNums as $tn) {
        $s = ((int)$tn[0] + (int)$tn[1]) % 10;
        $recentLotoBySum[$s][] = $tn;
      }
    }

    // Process Stats for Selected Sum over Period
    foreach ($rows as $row) {
      $d = $row['draw_date'];

      $dayNums = [];
      // Extract numbers
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
      if ($isSpecial) {
        $prizes = ['special_prize'];
      }

      foreach ($prizes as $pz) {
        if (!empty($row[$pz])) {
          $parts = explode('-', $row[$pz]);
          foreach ($parts as $p) {
            $dayNums[] = substr(trim($p), -2);
          }
        }
      }
      $dayNums = array_unique($dayNums);

      foreach ($targetKeys as $tn) {
        if (in_array($tn, $dayNums)) {
          $stats[$tn]['count']++;
          if ($stats[$tn]['last_date'] === null) {
            $stats[$tn]['last_date'] = $d;
            // Calculate days since
            $diff = (strtotime($ymdEnd) - strtotime($d)) / (60 * 60 * 24);
            $stats[$tn]['days_since'] = floor($diff);
            $stats[$tn]['url'] = '/xsmb-' . date('d-m-Y', strtotime($d)) . '.html'; // simple url
          }
        }
      }
    }

    // Fill days_since for numbers that never appeared
    foreach ($targetKeys as $tn) {
      if ($stats[$tn]['last_date'] === null) {
        $stats[$tn]['days_since'] = 'Chưa về';
      }
    }

    ksort($stats);

    $seoTitle = "Thống kê theo tổng XSMB - Tổng $chonTong";
    if ($isSpecial) $seoTitle .= " (Giải Đặc Biệt)";

    $data = [
      'stats' => $stats,
      'recentLotoBySum' => $recentLotoBySum,
      'recentDate' => $recentDraw ? date('d/m/Y', strtotime($recentDraw['draw_date'])) : '',
      'dateBegin' => $dateBeginStr,
      'dateEnd'   => $dateEndStr,
      'chonTong'  => $chonTong,
      'isSpecial' => $isSpecial,
    ];

    // SEO data
    $seoData = PerformanceHelper::generateCachedSeoData('custom', 'thong_ke_theo_tong_xsmb', null, [], $cache);
    $data = array_merge($data, $seoData);

    $cache->set($cacheKey, $data, 900);
    $this->view->setVars($data);

    // Schema
    $this->view->setVar('page_schema_type', 'webpage');
    $this->view->setVar('page_schema_data', [
      'title' => $data['seo_title'],
      'description' => $data['seo_description'],
      'url' => $this->getAbsoluteUrl(),
      'breadcrumbs' => [
        ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
        ['name' => 'Thống kê theo tổng XSMB', 'url' => $this->getAbsoluteUrl()]
      ]
    ]);

    $this->view->pick($viewPick);
  }

  // Phương thức mới cho thống kê 10 ngày
  public function thongKe10NgayAction()
  {
    $this->view->customindex = '/css/indexheader.css';
    // Setup SEO
    $this->view->setVar('seo_title', 'Thống kê XSMB 10 ngày - Kết quả xổ số Miền Bắc 10 ngày gần nhất');
    $this->view->setVar('seo_description', 'Xem thống kê XSMB 10 ngày gần đây nhất. Tổng hợp kết quả xổ số Miền Bắc, thống kê lô tô, giải đặc biệt trong 10 ngày qua chính xác.');

    // 1. Get last 10 days results
    $results = LotteryResults::find([
      'conditions' => 'draw_type = :type:',
      'bind' => ['type' => 'XSMB'],
      'order' => 'draw_date DESC',
      'limit' => 10
    ]);

    // 2. Format results similar to frontend needs (KqxsHelper::toArray behavior)
    $formattedResults = [];
    foreach ($results as $res) {
      $item = [
        'date' => date('d/m/Y', strtotime($res->draw_date)),
        'day_name' => $this->getDayName($res->draw_date),
        'special_prize' => KqxsHelper::toArray($res->special_prize),
        'first_prize' => KqxsHelper::toArray($res->first_prize),
        'second_prize' => KqxsHelper::toArray($res->second_prize),
        'third_prize' => KqxsHelper::toArray($res->third_prize),
        'fourth_prize' => KqxsHelper::toArray($res->fourth_prize),
        'fifth_prize' => KqxsHelper::toArray($res->fifth_prize),
        'sixth_prize' => KqxsHelper::toArray($res->sixth_prize),
        'seventh_prize' => KqxsHelper::toArray($res->seventh_prize),
        'eighth_prize' => [], // XSMB has no 8th prize usually, but for consistency
      ];

      // Calculate loto (Dau/Duoi) for this day
      $lotoTable = $this->calculateLotoTable($item);
      $item['loto_table'] = $lotoTable;

      $formattedResults[] = $item;
    }

    // 3. Stats
    // 3.1 Special Prize Stats (2 digits)
    $specialStats = $this->calculateSpecialStats($formattedResults);

    // 3.2 Loto Stats (all prizes)
    $lotoStats = $this->calculateLotoStats($formattedResults);

    // Pass data to view
    $this->view->setVars([
      'results' => $formattedResults,
      'specialStats' => $specialStats,
      'lotoStats' => $lotoStats,
      'base_url' => $this->getAbsoluteUrl('/')
    ]);

    $this->view->pick('thongkemb/thongke10ngay');
  }

  private function getDayName($dateStr)
  {
    $days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    return $days[date('w', strtotime($dateStr))];
  }

  private function calculateLotoTable($resultItem)
  {
    $heads = array_fill(0, 10, []);
    $tails = array_fill(0, 10, []);

    $prizes = array_merge(
      $resultItem['special_prize'],
      $resultItem['first_prize'],
      $resultItem['second_prize'],
      $resultItem['third_prize'],
      $resultItem['fourth_prize'],
      $resultItem['fifth_prize'],
      $resultItem['sixth_prize'],
      $resultItem['seventh_prize']
    );

    foreach ($prizes as $prize) {
      if (trim($prize) === '') continue;
      $len = strlen($prize);
      if ($len >= 2) {
        $loto = substr($prize, -2);
        $h = intval($loto[0]);
        $t = intval($loto[1]);

        $heads[$h][] = $t;
        $tails[$t][] = $h;
      }
    }

    // Sort
    foreach ($heads as &$v) sort($v);
    foreach ($tails as &$v) sort($v);

    return ['heads' => $heads, 'tails' => $tails];
  }

  private function calculateSpecialStats($results)
  {
    $freq = [];
    $head = array_fill(0, 10, 0);
    $tail = array_fill(0, 10, 0);
    $sum = array_fill(0, 10, 0);

    foreach ($results as $res) {
      if (!empty($res['special_prize'][0])) {
        $sp = $res['special_prize'][0];
        $loto = substr($sp, -2);

        if (!isset($freq[$loto])) $freq[$loto] = 0;
        $freq[$loto]++;

        $h = intval($loto[0]);
        $t = intval($loto[1]);
        $s = ($h + $t) % 10;

        $head[$h]++;
        $tail[$t]++;
        $sum[$s]++;
      }
    }

    // Sort freq
    arsort($freq);
    $topFreq = array_slice($freq, 0, 10, true);

    return [
      'top_freq' => $topFreq,
      'head' => $head,
      'tail' => $tail,
      'sum' => $sum
    ];
  }

  private function calculateLotoStats($results)
  {
    $freq = [];
    $head = array_fill(0, 10, 0);
    $tail = array_fill(0, 10, 0);
    $sum = array_fill(0, 10, 0);

    foreach ($results as $res) {
      $prizes = array_merge(
        $res['special_prize'],
        $res['first_prize'],
        $res['second_prize'],
        $res['third_prize'],
        $res['fourth_prize'],
        $res['fifth_prize'],
        $res['sixth_prize'],
        $res['seventh_prize']
      );

      foreach ($prizes as $prize) {
        if (trim($prize) === '') continue;
        $l2 = substr($prize, -2);

        if (!isset($freq[$l2])) $freq[$l2] = 0;
        $freq[$l2]++;

        $h = intval($l2[0]);
        $t = intval($l2[1]);
        $s = ($h + $t) % 10;

        $head[$h]++;
        $tail[$t]++;
        $sum[$s]++;
      }
    }

    // Sort freq
    arsort($freq);
    $topFreq = array_slice($freq, 0, 10, true);

    return [
      'top_freq' => $topFreq,
      'head' => $head,
      'tail' => $tail,
      'sum' => $sum
    ];
  }
}
