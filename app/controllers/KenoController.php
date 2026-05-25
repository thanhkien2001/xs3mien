<?php

declare(strict_types=1);

namespace App\Controllers;
use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use App\Models\KenoResults;
use App\Library\PerformanceHelper;
use App\Library\SchemaHelper;

class KenoController extends ControllerBase
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
    
    public function kenoindexAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // Lấy date từ URL parameter
        $dateParam = $this->dispatcher->getParam('date');
        $selectedDate = null;
        
        if ($dateParam) {
            // Xử lý format date từ URL - có thể là dd-mm-yyyy hoặc yyyy-mm-dd
            $dateParts = explode('-', $dateParam);
            if (count($dateParts) === 3) {
                // Kiểm tra format: nếu phần đầu là 4 số thì là yyyy-mm-dd, ngược lại là dd-mm-yyyy
                if (strlen($dateParts[0]) === 4) {
                    // Format yyyy-mm-dd
                    $selectedDate = $dateParam;
                } else {
                    // Format dd-mm-yyyy, convert thành yyyy-mm-dd
                    $day = $dateParts[0];
                    $month = $dateParts[1];
                    $year = $dateParts[2];
                    $selectedDate = "$year-$month-$day";
                }
                
                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
                    $selectedDate = null;
                }
            }
        }
        
        // Kiểm tra view cache trước
        // $viewCache = $this->di->get('viewCache');
        $viewCacheKey = "keno_index" . ($selectedDate ? '_' . $selectedDate : '');
        // $cachedHtml = $viewCache->get($viewCacheKey);
        
        // if ($cachedHtml !== null) {
        //     // Cache hit - trả về nội dung đã cache ngay lập tức
        //     $this->response->setContent($cachedHtml);
        //     $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
        //     $this->response->setHeader('X-Cache', 'HIT');
        //     $this->response->setHeader('X-Cache-Key', $viewCacheKey);
        //     return $this->response;
        // }
        
        try {
            $recentCacheKey = 'models_KENO_recent_' . date('Y-m-d-H-i');
            $recentResults = $this->cache->get($recentCacheKey);
            if ($recentResults === null) {
                $recentResults = KenoResults::getResults(1, 10);
                // $this->cache->set($recentCacheKey, $recentResults, 720);
            }
            
            $statsCacheKey = 'models_KENO_stats_' . date('Y-m-d-H');
            $statistics = $this->cache->get($statsCacheKey);
            if ($statistics === null) {
                $statistics = KenoResults::getStatistics();
                // $this->cache->set($statsCacheKey, $statistics, 43000);
            }
            
            $latestResult = KenoResults::getLatest();
            
            $nextDrawTime = $this->calculateNextDrawTime();
            
            $finalResult = [
                'latestResult' => $latestResult,
                'recentResults' => $recentResults,
                'statistics' => $statistics,
                'nextDrawTime' => $nextDrawTime,
                'currentPage' => 1,
                'selectedDate' => $selectedDate
            ];
            
            $seoData = PerformanceHelper::generateCachedSeoData('keno', 'Keno', $selectedDate, [
                'latestResult' => $latestResult,
                'statistics' => $statistics,
                'nextDrawTime' => $nextDrawTime
            ], $this->cache);
            $finalResult = array_merge($finalResult, $seoData);
            
            $this->view->setVars($finalResult);
            
            // Schema data for Keno page
            $breadcrumbs = [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Keno', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-keno-vietlott.html')]
            ];
            
            // Nếu có selectedDate thì thêm breadcrumb cho ngày
            if ($selectedDate) {
                $breadcrumbs[] = [
                    'name' => date('d/m/Y', strtotime($selectedDate)), 
                    'url' => $this->getAbsoluteUrl()
                ];
            }
            
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $finalResult['seo_title'] ?? 'Keno - Kết quả xổ số Vietlott',
                'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Keno Vietlott mới nhất',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);
            
            // Cache miss - render view và lưu cache
            $this->view->start();
            $this->view->render('keno', 'kenoindex');
            $this->view->finish();
            
            $content = $this->view->getContent();
            
            // Lưu vào cache (5 phút - Keno quay mỗi 8 phút)
            // $viewCache->set($viewCacheKey, $content, 500);
            
            $this->response->setContent($content);
            // $this->response->setHeader('X-Cache', 'MISS');
            // $this->response->setHeader('X-Cache-Key', $viewCacheKey);
            
            return $this->response;
        
        } catch (\Exception $e) {
            $this->response->setStatusCode(500, 'Internal Server Error');
            $this->view->pick('errors/500');
            $this->view->setVar('error_message', 'An error occurred. Please try again later.');
        }
    }

    public function resultsAction()
    {
        $this->view->disable();
        
        $page = (int) $this->request->getQuery('page', 'int', 1);
        $limit = (int) $this->request->getQuery('limit', 'int', 10);
        
        $results = KenoResults::getResults($page, $limit);
        
        $response = new Response();
        $response->setJsonContent([
            'status' => 'success',
            'data' => $results->toArray(),
            'page' => $page,
            'limit' => $limit
        ]);
        
        return $response;
    }

    private function calculateNextDrawTime()
    {
        $currentTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $minutes = (int) $currentTime->format('i');
        $nextDrawMinutes = ceil($minutes / 8) * 8;
        
        if ($nextDrawMinutes >= 60) {
            $currentTime->modify('+1 hour');
            $nextDrawMinutes -= 60;
        }
        
        $currentTime->setTime((int) $currentTime->format('H'), (int) $nextDrawMinutes);
        return $currentTime->format('H:i');
    }
}
