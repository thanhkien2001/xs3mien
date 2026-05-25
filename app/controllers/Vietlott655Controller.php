<?php
namespace App\Controllers;
use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use App\Models\VietlottResults;
use App\Library\VietlottStatisticsHelper;
use App\Library\PerformanceHelper;
use App\Helpers\SeoHelper;

class Vietlott655Controller extends ControllerBase
{
    /**
     * Helper method to get absolute URL for breadcrumb
     */
    private function getAbsoluteUrl($path = '')
    {
        $config = $this->getDI()->get('config');
        $baseUrl = $config->application->baseUrl;
        
        // Nếu path rỗng, lấy URI hiện tại
        if (empty($path)) {
            $path = $this->request->getURI();
        }
        
        // Thêm .html nếu chưa có và không phải root
        if ($path !== '/' && substr($path, -1) !== '/' && substr($path, -5) !== '.html') {
            $path .= '.html';
        }
        
        return $baseUrl . $path;
    }
    
    public function index655Action()
    {
        $this->view->customindex = '/css/indexheader.css';

        $drawType = 'Power655';
        
        // Lấy date từ URL parameter
        $date = $this->dispatcher->getParam('date');
        if ($date) {
            // Xử lý format date từ URL (dd-mm-yyyy)
            $dateParts = explode('-', $date);
            if (count($dateParts) === 3) {
                $day = $dateParts[0];
                $month = $dateParts[1];
                $year = $dateParts[2];
                $apiDate = "$year-$month-$day";
                
                // Validate date format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $apiDate)) {
                    $this->handleSpecificDate($apiDate, $drawType);
                    return;
                }
            }
        }
        
        // Check cache for default page
        $cache = $this->di->get('modelsCache');
        $cacheKey = VietlottStatisticsHelper::buildCacheKey($drawType, 'index', ['limit' => 10]);
        $cacheLifetime = VietlottStatisticsHelper::getSmartCacheLifetime($drawType);
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);
            
            // Schema data for Vietlott 655 page (cache hit)
            $breadcrumbs = [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Power 6/55', 'url' => $this->getAbsoluteUrl()]
            ];
            
            // Nếu có selectedDate thì thêm breadcrumb cho ngày
            if (!empty($cachedResult['selectedDate'])) {
                $breadcrumbs = [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Power 6/55', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-power-6-55-vietlott')],
                    ['name' => date('d/m/Y', strtotime($cachedResult['selectedDate'])), 'url' => $this->getAbsoluteUrl()]
                ];
            }
            
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Power 6/55 - Kết quả xổ số Vietlott',
                'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Power 6/55 Vietlott mới nhất',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);
            return;
        }
        
        // Lấy dữ liệu từ database
        $db = $this->di->get('db');

        // Lấy kỳ quay mới nhất của Power 6/55
        $latestResult = VietlottStatisticsHelper::getLatestResult($drawType, $db);

        // Lấy 10 kết quả gần đây
        $recentResults = VietlottStatisticsHelper::getRecentResults($drawType, $db, 10);

        // Tính toán sự tăng/giảm của Jackpot
        $jackpotChange = VietlottStatisticsHelper::calculateJackpotChange($drawType, $db);
        $jackpotIncrease = $jackpotChange['change'];
        
        // Chuẩn bị kết quả cuối cùng
        $finalResult = [
            'latestResult' => $latestResult,
            'recentResults' => $recentResults,
            'jackpotIncrease' => $jackpotIncrease,
            'currentPage' => 1,
            'baseUrl' => '/ket-qua-xoso-power-6-55-vietlott.html'
        ];
        
        // Generate SEO data using existing data
        $seoData = PerformanceHelper::generateCachedSeoData('vietlott', $drawType, null, [
            'latestResult' => $latestResult
        ], $this->cache);
        
        // Merge SEO data with final result
        $finalResult = array_merge($finalResult, $seoData);

        // Set cache
        $cache->set($cacheKey, $finalResult, $cacheLifetime);
        
        // Truyền dữ liệu vào view
        $this->view->setVars($finalResult);
        
        // Schema data for Vietlott 655 page (cache miss)
        $breadcrumbs = [
            ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
            ['name' => 'Power 6/55', 'url' => $this->getAbsoluteUrl()]
        ];
        
        // Nếu có selectedDate thì thêm breadcrumb cho ngày
        if (!empty($finalResult['selectedDate'])) {
            $breadcrumbs = [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Power 6/55', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-power-6-55-vietlott')],
                ['name' => date('d/m/Y', strtotime($finalResult['selectedDate'])), 'url' => $this->getAbsoluteUrl()]
            ];
        }
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Power 6/55 - Kết quả xổ số Vietlott',
            'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Power 6/55 Vietlott mới nhất',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    public function resultsAction()
    {
        // Tắt view để trả về JSON
        $this->view->disable();

        $page = (int) $this->request->getQuery('page', 'int', 1);
        $limit = (int) $this->request->getQuery('limit', 'int', 10);
        $drawType = 'Power655';

        // Check cache
        $cache = $this->di->get('modelsCache');
        $cacheKey = VietlottStatisticsHelper::buildCacheKey($drawType, 'results', ['page' => $page, 'limit' => $limit]);
        $cacheLifetime = VietlottStatisticsHelper::getSmartCacheLifetime($drawType);
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $response = new Response();
            $response->setJsonContent($cachedResult);
            return $response;
        }

        // Lấy dữ liệu từ database
        $db = $this->di->get('db');
        $results = VietlottStatisticsHelper::getRecentResults($drawType, $db, $limit, $page);

        $finalResult = [
            'status' => 'success',
            'data' => $results,
            'page' => $page,
            'limit' => $limit
        ];
        
        // Set cache
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $response = new Response();
        $response->setJsonContent($finalResult);
        return $response;
    }
    public function byDateAction()
    {
        $this->view->disable();

        $date = $this->request->getQuery('date', 'string');
        
        // Xử lý trường hợp date có đuôi .html
        if ($date && strpos($date, '.html') !== false) {
            $date = str_replace('.html', '', $date);
        }
        
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $response = new Response();
            $response->setStatusCode(400);
            $response->setJsonContent(['status' => 'error', 'message' => 'Invalid date format']);
            return $response;
        }

        $drawType = 'Power655';

        // Check cache
        $cache = $this->di->get('modelsCache');
        $cacheKey = VietlottStatisticsHelper::buildCacheKey($drawType, 'byDate', ['date' => $date]);
        $cacheLifetime = VietlottStatisticsHelper::getSmartCacheLifetime($drawType);

        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $response = new Response();
            $response->setJsonContent($cachedResult);
            return $response;
        }

        // Lấy dữ liệu từ database
        $db = $this->di->get('db');
        $result = VietlottStatisticsHelper::getResultByDate($drawType, $date, $db);

        $finalResult = [];
        if ($result) {
            $finalResult = [
                'status' => 'success',
                'data' => $result
            ];
        } else {
            $finalResult = [
                'status' => 'error',
                'message' => 'No result found for the selected date'
            ];
        }

        // Set cache
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        $response = new Response();
        $response->setJsonContent($finalResult);
        return $response;
    }

    private function handleSpecificDate($date, $drawType)
    {
        // Check cache for specific date
        $cache = $this->di->get('modelsCache');
        $cacheKey = VietlottStatisticsHelper::buildCacheKey($drawType, 'date', ['date' => $date]);
        $cacheLifetime = VietlottStatisticsHelper::getSmartCacheLifetime($drawType);
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);
            
            // Schema data for specific date page (cache hit)
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Power 6/55 - Kết quả xổ số Vietlott',
                'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Power 6/55 Vietlott mới nhất',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Power 6/55', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-power-6-55-vietlott')],
                    ['name' => date('d/m/Y', strtotime($cachedResult['selectedDate'])), 'url' => $this->getAbsoluteUrl()]
                ]
            ]);
            
            $this->view->pick('vietlott655/index655');
            return;
        }
        
        // Lấy dữ liệu từ database
        $db = $this->di->get('db');
        
        // Lấy kết quả theo ngày cụ thể
        $specificResult = VietlottStatisticsHelper::getResultByDate($drawType, $date, $db);
        
        if (!$specificResult) {
            // Nếu không tìm thấy kết quả cho ngày đó, redirect về trang chủ
            return $this->response->redirect('/ket-qua-xoso-power-6-55-vietlott.html');
        }
        
        // Lấy 10 kết quả gần đây
        $recentResults = VietlottStatisticsHelper::getRecentResults($drawType, $db, 10);
        
        // Tính toán sự tăng/giảm của Jackpot so với kỳ trước
        $jackpotChange = VietlottStatisticsHelper::calculateJackpotChange($drawType, $db);
        $jackpotIncrease = $jackpotChange['change'];
        
        // Chuẩn bị kết quả cuối cùng
        $finalResult = [
            'latestResult' => $specificResult,
            'recentResults' => $recentResults,
            'jackpotIncrease' => $jackpotIncrease,
            'currentPage' => 1,
            'baseUrl' => '/ket-qua-xoso-power-6-55-vietlott',
            'selectedDate' => $date,
            'isSpecificDate' => true,
            'preloadDate' => $date // Để JavaScript biết ngày nào đang được hiển thị
        ];
        
        // Generate SEO data for specific date
        $seoData = \App\Library\SeoHelper::generateVietlottMeta($drawType, $specificResult['draw_date'] ?? null);
        $finalResult = array_merge($finalResult, $seoData);
        
        // Set cache
        $cache->set($cacheKey, $finalResult, $cacheLifetime);
        
        // Truyền dữ liệu vào view
        $this->view->setVars($finalResult);
        
        // Schema data for specific date page
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Power 6/55 - Kết quả xổ số Vietlott',
            'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Power 6/55 Vietlott mới nhất',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Power 6/55', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-power-6-55-vietlott')],
                ['name' => date('d/m/Y', strtotime($date)), 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        
        $this->view->pick('vietlott655/index655');
    }
}
