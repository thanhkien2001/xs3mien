<?php

declare(strict_types=1);

namespace App\Controllers;
use Phalcon\Mvc\Controller;
use App\Models\Provinces;
use App\Library\PerformanceHelper;
class QuaythuController extends ControllerBase
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
    
    public function quaythuxsAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_xs', null);
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử xổ số', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        
        $this->view->pick('quaythu/quaythuxs');
    }
    public function quaythumbAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_xsmb', null);
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử', 'url' => $this->getAbsoluteUrl('/quay-thu-xo-so')],
                ['name' => 'Quay thử XSMB', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    public function quaythumnAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_xsmn', null);
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử', 'url' => $this->getAbsoluteUrl('/quay-thu-xo-so')],
                ['name' => 'Quay thử XSMN', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    public function quaythumtAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_xsmt', null);
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử', 'url' => $this->getAbsoluteUrl('/quay-thu-xo-so')],
                ['name' => 'Quay thử XSMT', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    public function quaythuPower655Action()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_power655', null);
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử', 'url' => $this->getAbsoluteUrl('/quay-thu-xo-so')],
                ['name' => 'Quay thử Power 6/55', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    public function quaythuMega645Action() {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_mega645', null);
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử', 'url' => $this->getAbsoluteUrl('/quay-thu-xo-so')],
                ['name' => 'Quay thử Mega 6/45', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    
    // Quay thử theo tỉnh
    public function quaythuProvinceAction() {
        $this->view->customindex = '/css/indexheader.css';
        
        $provinceCode = $this->dispatcher->getParam('province');
        
        // Dùng ThongkeStatisticsHelper để lấy province ID từ slug
        $provinceId = \App\Library\ThongkeStatisticsHelper::getProvinceIdFromSlug($provinceCode);
        
        if (!$provinceId) {
            error_log("Province not found for slug: " . $provinceCode);
            return $this->response->redirect('/');
        }
        
        // Kiểm tra canonical slug và redirect nếu cần (TRƯỚC khi cache)
        $canonicalSlug = \App\Library\ThongkeStatisticsHelper::getSlugFromProvinceId($provinceId);
        if ($canonicalSlug && $canonicalSlug !== $provinceCode) {
            // Redirect về canonical URL (301 permanent redirect)
            $canonicalUrl = $this->getAbsoluteUrl("/quay-thu-{$canonicalSlug}");
            return $this->response->redirect($canonicalUrl, true, 301);
        }
        
        // Chỉ cache các URL canonical (sử dụng canonicalSlug nếu có, nếu không thì dùng provinceCode)
        $cacheKey = "quaythu_" . ($canonicalSlug ?: $provinceCode);
        $viewCache = $this->di->get('viewCache');
        $cachedContent = $viewCache->get($cacheKey);
        
        if ($cachedContent !== null) {
            // Cache hit - trả về nội dung đã cache ngay lập tức
            $this->response->setContent($cachedContent);
            $this->response->setHeader('X-Cache', 'HIT');
            $this->response->setHeader('X-Cache-Key', $cacheKey);
            return $this->response;
        }
        
        // Cache miss - thực hiện logic bình thường
        // Lấy province từ database
        $province = Provinces::findFirst($provinceId);
        
        if (!$province) {
            error_log("Province with ID {$provinceId} not found in database");
            return $this->response->redirect('/');
        }
        
        $provinceName = $province->name;
        $region = $province->region;
        $provinceCodeUpper = strtoupper($provinceCode);
        
        // SEO data cho quay thử tỉnh
        $seoData = \App\Library\SeoHelper::getSeoData('quaythu_province', null, [
            'provinceName' => $provinceName,
            'provinceCode' => $provinceCodeUpper,
            'region' => $region === 'XSMN' ? 'Miền Nam' : ($region === 'XSMT' ? 'Miền Trung' : $region)
        ]);
        
        $this->view->setVar('seo_title', $seoData['seo_title']);
        $this->view->setVar('seo_description', $seoData['seo_description']);
        $this->view->setVar('seo_keywords', $seoData['seo_keywords']);
        $this->view->setVar('canonical_url', $seoData['canonical_url']);
        $this->view->setVar('selectedProvinceName', $provinceName);
        $this->view->setVar('selectedProvinceCode', $provinceCodeUpper);
        $this->view->setVar('provinceCode', $provinceCode);
        $this->view->setVar('region', $region);
        
        $regionName = $region === 'XSMT' ? 'Quay thử XSMT' : 'Quay thử XSMN';
        $regionUrl = $region === 'XSMT' ? '/quay-thu-xsmt' : '/quay-thu-xsmn';
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Quay thử', 'url' => $this->getAbsoluteUrl('/quay-thu-xo-so')],
                ['name' => $regionName, 'url' => $this->getAbsoluteUrl($regionUrl)],
                ['name' => "Quay thử {$provinceName}", 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        
        // Render view và lưu cache
        $viewName = ($region === 'XSMN') ? 'quaythumn' : 'quaythumt';
        
        if ($region === 'XSMN' || $region === 'XSMT') {
            // Cache miss - render view và lưu cache
            $this->view->pick('quaythu/' . $viewName);
            
            // Render view để lấy nội dung
            $this->view->start();
            $this->view->render('quaythu', $viewName . '.phtml');
            $this->view->finish();
            
            $content = $this->view->getContent();
            
            // Lưu vào cache
            $viewCache->set($cacheKey, $content, 86400);
            
            $this->response->setContent($content);
            $this->response->setHeader('X-Cache', 'MISS');
            $this->response->setHeader('X-Cache-Key', $cacheKey);
            
            return $this->response;
        } else {
            return $this->response->redirect('/');
        }
    }

}
