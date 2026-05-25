<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Models\VietlottResults;
use App\Library\StatisticsHelper;
use App\Library\PerformanceHelper;

class Statistics645Controller extends ControllerBase
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
    
    public function statisticspower645Action()
    {
        $this->view->customindex = '/css/indexheader.css';

        // ==== Cache key ổn định theo ngữ cảnh ====
        $params   = ['uri' => $_SERVER['REQUEST_URI'] ?? '', 'limit' => 100];
        $cacheKey = 'statistics_mega645_' . md5(json_encode($params));

        $cache    = $this->di->get('modelsCache');
        $viewPick = 'statistics645/statisticspower645';

        if ($cached = $cache->get($cacheKey)) {
            $this->view->setVars($cached);
            
            // Schema data for cache hit
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cached['seo_title'] ?? 'Thống kê Mega 6/45',
                'description' => $cached['seo_description'] ?? 'Thống kê xổ số Mega 6/45 Vietlott',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Thống kê Mega 6/45', 'url' => $this->getAbsoluteUrl()]
                ]
            ]);
            
            $this->view->pick($viewPick);
            return;
        }

        // Lấy thống kê
        $statisticsData = StatisticsHelper::getStatisticsData('Mega645', 45, 100);

        if (isset($statisticsData['error'])) {
            // Đẩy lỗi ra view + pick để tránh 404
            $this->view->setVars([
                'numberFrequency'        => [],
                'top20Numbers'           => [],
                'bottom20Numbers'        => [],
                'top20SpecialNumbers'    => [],
                'topTriplets'            => [],
                'top20Pairs'             => [],
                'top20ConsecutivePairs'  => [],
                'topJackpots'            => [],
                'rareNumbers'            => [],
                'error'                  => $statisticsData['error'],
            ]);
            $this->view->pick($viewPick);
            return;
        }

        $topJackpots = StatisticsHelper::getTopJackpots('Mega645', 20) ?? [];
        $rareNumbers = StatisticsHelper::getRareNumbers('Mega645', 20, 'ASC') ?? [];

        $finalResult = [
            'numberFrequency'        => $statisticsData['numberFrequency']        ?? [],
            'top20Numbers'           => $statisticsData['top20Numbers']           ?? [],
            'bottom20Numbers'        => $statisticsData['bottom20Numbers']        ?? [],
            'top20SpecialNumbers'    => $statisticsData['top20SpecialNumbers']    ?? [],
            'topTriplets'            => $statisticsData['topTriplets']            ?? [],
            'top20Pairs'             => $statisticsData['top20Pairs']             ?? [],
            'top20ConsecutivePairs'  => $statisticsData['top20ConsecutivePairs']  ?? [],
            'topJackpots'            => $topJackpots,
            'rareNumbers'            => $rareNumbers,
            // Nếu API không có cặp liên tiếp, trả thông báo nhẹ nhàng cho view (không 404)
            'error'                  => empty($statisticsData['top20ConsecutivePairs']) ? 'Không tìm thấy cặp số liên tiếp nào.' : null,
        ];

        // SEO với SeoHelper
        $seoData = \App\Library\SeoHelper::generateStatisticsMeta('Mega645', 'statistics');
        $finalResult = array_merge($finalResult, $seoData);

        // Cache + render
        $cacheLifetime = StatisticsHelper::getSmartCacheLifetime($topJackpots, 'draw_date');
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $this->view->setVars($finalResult);
        
        // Schema data for cache miss
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Thống kê Mega 6/45',
            'description' => $finalResult['seo_description'] ?? 'Thống kê xổ số Mega 6/45 Vietlott',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Thống kê Mega 6/45', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        
        $this->view->pick($viewPick);
    }
}
