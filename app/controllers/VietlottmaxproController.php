<?php
namespace App\Controllers;
use Phalcon\Mvc\Controller;
use App\Models\MaxResults;
use App\Library\VietlottStatisticsHelper;
use App\Library\PerformanceHelper;

class VietlottmaxproController extends ControllerBase
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
    
    public function index3dmaxproAction(?string $date = null)
    {
        $this->view->customindex = '/css/indexheader.css';
        
        $drawType = 'MAX3DPRO';
        
        // Check cache
        $cache = $this->di->get('modelsCache');
        $cacheKey = VietlottStatisticsHelper::buildCacheKey($drawType, 'index', ['date' => $date, 'limit' => 1]);
        $cacheLifetime = VietlottStatisticsHelper::getSmartCacheLifetime($drawType);
        
        $cachedResult = $cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->view->setVars($cachedResult);
            
            // Schema data for Max 3D Pro page (cache hit)
            $breadcrumbs = [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Max 3D Pro', 'url' => $this->getAbsoluteUrl()]
            ];
            
            // Nếu có selectedDate thì thêm breadcrumb cho ngày
            if (!empty($cachedResult['selectedDate'])) {
                $breadcrumbs = [
                    ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                    ['name' => 'Max 3D Pro', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-max3d-pro-vietlott')],
                    ['name' => date('d/m/Y', strtotime($cachedResult['selectedDate'])), 'url' => $this->getAbsoluteUrl()]
                ];
            }
            
            $this->view->setVar('page_schema_type', 'webpage');
            $this->view->setVar('page_schema_data', [
                'title' => $cachedResult['seo_title'] ?? 'Max 3D Pro - Kết quả xổ số Vietlott',
                'description' => $cachedResult['seo_description'] ?? 'Kết quả xổ số Max 3D Pro Vietlott mới nhất',
                'url' => $this->getAbsoluteUrl(),
                'breadcrumbs' => $breadcrumbs
            ]);
            return;
        }
        
        // Lấy dữ liệu từ database
        $db = $this->di->get('db');
        
        // Lấy dữ liệu từ helper tối ưu - chỉ lấy 1 kết quả mới nhất
        if ($date) {
            // Nếu có ngày cụ thể, lấy kết quả theo ngày đó
            $results = VietlottStatisticsHelper::getMaxRecentDraws($drawType, $db, 1, $date);
            if (!$results) {
                // Nếu không có kết quả cho ngày đó, lấy kết quả mới nhất
                $results = VietlottStatisticsHelper::getMaxRecentDraws($drawType, $db, 1, null);
            }
        } else {
            // Nếu không có ngày, lấy kết quả mới nhất
            $results = VietlottStatisticsHelper::getMaxRecentDraws($drawType, $db, 1, null);
        }
        
        $otherDates = VietlottStatisticsHelper::getMaxOtherDates($drawType, $db, 12);
        $frequentNumbers = VietlottStatisticsHelper::getMaxFrequentNumbers($drawType, $db, 60);
        $rareNumbers = VietlottStatisticsHelper::getMaxRareNumbers($drawType, $db, 60);

        // Chuẩn bị kết quả cuối cùng
        $finalResult = [
            'results' => $results,
            'otherDates' => $otherDates,
            'frequentNumbers' => $frequentNumbers,
            'rareNumbers' => $rareNumbers,
            'selectedDate' => $date
        ];

        // Generate SEO data using existing data
        $seoData = PerformanceHelper::generateCachedSeoData('vietlott', $drawType, $date, [
            'results' => $results,
            'frequentNumbers' => $frequentNumbers,
            'rareNumbers' => $rareNumbers
        ], $this->cache);
        
        // Merge SEO data with final result
        $finalResult = array_merge($finalResult, $seoData);
        
        // Set cache
        $cache->set($cacheKey, $finalResult, $cacheLifetime);

        // Truyền dữ liệu sang view
        $this->view->setVars($finalResult);
        
        // Schema data for Max 3D Pro page (cache miss)
        $breadcrumbs = [
            ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
            ['name' => 'Max 3D Pro', 'url' => $this->getAbsoluteUrl()]
        ];
        
        // Nếu có selectedDate thì thêm breadcrumb cho ngày
        if (!empty($finalResult['selectedDate'])) {
            $breadcrumbs = [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Max 3D Pro', 'url' => $this->getAbsoluteUrl('/ket-qua-xoso-max3d-pro-vietlott')],
                ['name' => date('d/m/Y', strtotime($finalResult['selectedDate'])), 'url' => $this->getAbsoluteUrl()]
            ];
        }
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $finalResult['seo_title'] ?? 'Max 3D Pro - Kết quả xổ số Vietlott',
            'description' => $finalResult['seo_description'] ?? 'Kết quả xổ số Max 3D Pro Vietlott mới nhất',
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => $breadcrumbs
        ]);
    }
}