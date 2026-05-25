<?php

namespace App\Controllers;

use App\Controllers\ControllerBase;
use App\Library\LotteryDataHelper;
use App\Library\ApiSecurityHelper;
use App\Library\SeoHelper;

class VietlottLiveController extends ControllerBase
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
    
    public function mega645Action()
    {
        // SEO data
        $seoData = SeoHelper::getLiveVietlottSeoData('mega645');
        $this->view->seo_title = $seoData['title'];
        $this->view->seo_description = $seoData['description'];
        $this->view->seo_keywords = $seoData['keywords'];
        $this->view->canonical_url = $seoData['canonical_url'];

        // Structured data
        // $structuredData = SeoHelper::generateLiveVietlottStructuredData('mega-6-45', 'Mega 6/45');
        // $this->view->structured_data_json = json_encode($structuredData, JSON_UNESCAPED_UNICODE);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['title'],
            'description' => $seoData['description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Mega 6/45', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->customindex    = '/css/indexheader.css';
        $this->view->pick('vietlottlive/mega645');
    }

    public function power655Action()
    {
        // SEO data
        $seoData = SeoHelper::getLiveVietlottSeoData('power655');
        $this->view->seo_title = $seoData['title'];
        $this->view->seo_description = $seoData['description'];
        $this->view->seo_keywords = $seoData['keywords'];
        $this->view->canonical_url = $seoData['canonical_url'];

        // Structured data
        // $structuredData = SeoHelper::generateLiveVietlottStructuredData('power-6-55', 'Power 6/55');
        // $this->view->structured_data_json = json_encode($structuredData, JSON_UNESCAPED_UNICODE);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['title'],
            'description' => $seoData['description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Power 6/55', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->customindex    = '/css/indexheader.css';
        $this->view->pick('vietlottlive/power655');
    }

    public function max3dAction()
    {
        // SEO data
        $seoData = SeoHelper::getLiveVietlottSeoData('max3d');
        $this->view->seo_title = $seoData['title'];
        $this->view->seo_description = $seoData['description'];
        $this->view->seo_keywords = $seoData['keywords'];
        $this->view->canonical_url = $seoData['canonical_url'];

        // Structured data
        // $structuredData = SeoHelper::generateLiveVietlottStructuredData('max-3d', 'Max 3D');
        // $this->view->structured_data_json = json_encode($structuredData, JSON_UNESCAPED_UNICODE);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['title'],
            'description' => $seoData['description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Max 3D', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->customindex    = '/css/indexheader.css';
        $this->view->pick('vietlottlive/max3d');
    }

    public function max3dproAction()
    {
        // SEO data
        $seoData = SeoHelper::getLiveVietlottSeoData('max3dpro');
        $this->view->seo_title = $seoData['title'];
        $this->view->seo_description = $seoData['description'];
        $this->view->seo_keywords = $seoData['keywords'];
        $this->view->canonical_url = $seoData['canonical_url'];

        // Structured data
        // $structuredData = SeoHelper::generateLiveVietlottStructuredData('max-3d-pro', 'Max 3D Pro');
        // $this->view->structured_data_json = json_encode($structuredData, JSON_UNESCAPED_UNICODE);

        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['title'],
            'description' => $seoData['description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Max 3D Pro', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);

        $this->view->customindex    = '/css/indexheader.css';
        $this->view->pick('vietlottlive/max3dpro');
    }

    // API endpoints
    public function checkMega645Action()
    {
        try {
            $this->response->setContentType('application/json');

            // Tạm thời bypass security check để debug
            // TODO: Enable security check sau khi fix xong

            $lastCheck = $this->request->getQuery('last_check', 'int', 0);

            $result = LotteryDataHelper::getData('mega645', $lastCheck);

            // Kiểm tra dữ liệu có đúng ngày hôm nay không
            if (isset($result['data']) && !empty($result['data'])) {
                $today = date('Y-m-d');
                $dataDate = date('Y-m-d', strtotime($result['data']['draw_date']));

                if ($dataDate !== $today) {
                    $result['error'] = 'Data not available for today';
                    $result['message'] = 'No data available for current date';
                    $result['data'] = null;
                }
            }

            $this->response->setJsonContent($result);

            return $this->response;
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->response;
        }
    }

    public function checkPower655Action()
    {
        try {
            $this->response->setContentType('application/json');

            // Tạm thời bypass security check để debug
            // TODO: Enable security check sau khi fix xong

            $lastCheck = $this->request->getQuery('last_check', 'int', 0);

            $result = LotteryDataHelper::getData('power655', $lastCheck);

            // Kiểm tra dữ liệu có đúng ngày hôm nay không
            if (isset($result['data']) && !empty($result['data'])) {
                $today = date('Y-m-d');
                $dataDate = date('Y-m-d', strtotime($result['data']['draw_date']));

                if ($dataDate !== $today) {
                    $result['error'] = 'Data not available for today';
                    $result['message'] = 'No data available for current date';
                    $result['data'] = null;
                }
            }

            $this->response->setJsonContent($result);

            return $this->response;
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->response;
        }
    }

    public function checkMax3dAction()
    {
        try {
            $this->response->setContentType('application/json');

            // Tạm thời bypass security check để debug
            // TODO: Enable security check sau khi fix xong

            $lastCheck = $this->request->getQuery('last_check', 'int', 0);

            $result = LotteryDataHelper::getData('max3d', $lastCheck);

            // Kiểm tra dữ liệu có đúng ngày hôm nay không
            if (isset($result['data']) && !empty($result['data'])) {
                $today = date('Y-m-d');
                $dataDate = date('Y-m-d', strtotime($result['data']['draw_date']));

                if ($dataDate !== $today) {
                    $result['error'] = 'Data not available for today';
                    $result['message'] = 'No data available for current date';
                    $result['data'] = null;
                }
            }

            $this->response->setJsonContent($result);

            return $this->response;
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->response;
        }
    }

    public function checkMax3dproAction()
    {
        try {
            $this->response->setContentType('application/json');

            // Tạm thời bypass security check để debug
            // TODO: Enable security check sau khi fix xong

            $lastCheck = $this->request->getQuery('last_check', 'int', 0);

            $result = LotteryDataHelper::getData('max3dpro', $lastCheck);

            // Kiểm tra dữ liệu có đúng ngày hôm nay không
            if (isset($result['data']) && !empty($result['data'])) {
                $today = date('Y-m-d');
                $dataDate = date('Y-m-d', strtotime($result['data']['draw_date']));

                if ($dataDate !== $today) {
                    $result['error'] = 'Data not available for today';
                    $result['message'] = 'No data available for current date';
                    $result['data'] = null;
                }
            }

            $this->response->setJsonContent($result);

            return $this->response;
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->response;
        }
    }

    /**
     * Get current API keys for frontend
     */
    public function getApiKeysAction()
    {
        try {
            $this->response->setContentType('application/json');

            // Tạm thời bypass security check để debug
            // TODO: Enable security check sau khi fix xong

            // Trả về API keys hiện tại
            $apiKeys = [
                'mega645' => ApiSecurityHelper::getCurrentApiKey('live_mega645'),
                'power655' => ApiSecurityHelper::getCurrentApiKey('live_power655'),
                'max3d' => ApiSecurityHelper::getCurrentApiKey('live_max3d'),
                'max3dpro' => ApiSecurityHelper::getCurrentApiKey('live_max3dpro'),
                'timestamp' => time(),
                'date' => date('Y-m-d H:i:s')
            ];

            $this->response->setJsonContent($apiKeys);
            return $this->response;
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->response;
        }
    }

    /**
     * Check API security (API key + rate limiting + additional security)
     */
    private function checkApiSecurity($endpoint)
    {
        $apiKey = $this->request->getHeader('X-API-Key') ?: $this->request->getQuery('api_key');
        $secretToken = $this->request->getHeader('X-Secret-Token') ?: $this->request->getQuery('secret_token');
        $clientIp = ApiSecurityHelper::getClientIp();
        $userAgent = $this->request->getHeader('User-Agent') ?: '';
        $referer = $this->request->getHeader('Referer') ?: '';

        // Enhanced security check with secret token
        $securityCheck = ApiSecurityHelper::performEnhancedSecurityCheck($userAgent, $referer, $clientIp, $secretToken);
        if ($securityCheck !== true) {
            return $securityCheck;
        }

        // Check API key
        if (!ApiSecurityHelper::validateApiKey($apiKey, $endpoint)) {
            return [
                'error' => 'Invalid API key',
                'code' => 'INVALID_API_KEY',
                'message' => 'API key is required and must be valid'
            ];
        }

        // Check rate limit
        if (!ApiSecurityHelper::checkRateLimit($clientIp, $endpoint)) {
            $remaining = ApiSecurityHelper::getRemainingRequests($clientIp, $endpoint);
            return [
                'error' => 'Rate limit exceeded',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => 60,
                'remaining_requests' => $remaining
            ];
        }

        return true;
    }
}
