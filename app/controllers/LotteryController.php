<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Models\LotteryResults;
use App\Models\Provinces;

class LotteryController extends ControllerBase
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
    
    private array $allowedOriginsLocal = [
        'http://xs-clone.code',
        'https://xs-clone.code',
        'http://soicau247.com',
        'https://soicau247.com',
        'http://www.soicau247.com',
        'https://www.soicau247.com',
    ];

    private array $allowedOriginsProd = [
        'http://soicau247.com',
        'https://soicau247.com',
        'http://www.soicau247.com',
        'https://www.soicau247.com',
    ];

    private function isProd(): bool
    {
        return getenv('IS_PROD') === 'true';
    }

    private function cookieNames(): array
    {
        if ($this->isProd()) {
            return ['csrf' => '__Host-ws_csrf', 'token' => '__Host-ws_token'];
        }
        // Local: không dùng __Host- vì chưa có HTTPS
        return ['csrf' => 'ws_csrf', 'token' => 'ws_token'];
    }

    private function allowedOrigins(): array
    {
        return $this->isProd() ? $this->allowedOriginsProd : $this->allowedOriginsLocal;
    }

    private function assertHttpsIfProd(): void
    {
        // Bỏ qua HTTPS check cho local development
        // if ($this->isProd() && !$this->request->isSecure()) {
        //     $this->response->setStatusCode(403, 'HTTPS required')->send();
        //     exit;
        // }
    }

    private function assertOriginOrReferer(): void
    {
        $allowed = $this->allowedOrigins();
        $origin  = $this->request->getHeader('Origin') ?: '';
        $referer = $this->request->getHeader('Referer') ?: '';

        $ok = false;
        foreach ($allowed as $o) {
            if ($origin === $o) {
                $ok = true;
                break;
            }
            if ($referer && str_starts_with($referer, rtrim($o, '/') . '/')) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            $this->response->setStatusCode(403, 'Origin/Referer not allowed')
                ->setJsonContent(['error' => 'Origin/Referer not allowed'])
                ->send();
            exit;
        }
    }

    public function csrfAction()
    {
        $this->assertHttpsIfProd();
        $this->assertOriginOrReferer();

        $names = $this->cookieNames();
        $csrf  = bin2hex(random_bytes(16));

        $this->cookies->set(
            $names['csrf'],
            $csrf,
            time() + 3600,
            '/',
            true,                   
            '',                     
            false,                   
            ['samesite' => 'Strict']
        );

        return $this->response->setJsonContent(['ok' => true]);
    }

    public function generateTokenAction()
    {
        $this->assertHttpsIfProd();
        $this->assertOriginOrReferer();

        if (!$this->request->isPost() || $this->request->getContentType() !== 'application/json') {
            echo "[DEBUG] Request method/content-type không hợp lệ\n";
            return $this->response->setStatusCode(405)->setJsonContent(['error' => 'Method/Content-Type not allowed']);
        }

        $names     = $this->cookieNames();
        $csrfCookie = $this->cookies->get($names['csrf'])->getValue() ?? '';
        $csrfHeader = $this->request->getHeader('X-CSRF-Token') ?? '';
        echo "[DEBUG] CSRF cookie: {$csrfCookie}, header: {$csrfHeader}\n";
        if (!$csrfCookie || !$csrfHeader || !hash_equals($csrfCookie, $csrfHeader)) {
            echo "[DEBUG] CSRF check failed\n";
            return $this->response->setStatusCode(403)->setJsonContent(['error' => 'CSRF validation failed']);
        }

        $data   = $this->request->getJsonRawBody(true) ?: [];
        $region = $data['region'] ?? null;
        echo "[DEBUG] Region nhận được: {$region}\n";
        if (!in_array($region, ['mn', 'mt', 'mb'], true)) {
            echo "[DEBUG] Region không hợp lệ\n";
            return $this->response->setStatusCode(400)->setJsonContent(['error' => 'Region không hợp lệ']);
        }

        /** @var Redis $redis */
        $redis = $this->di->getShared('redis');
        $ip    = $this->request->getClientAddress();
        $ua    = $this->request->getUserAgent();
        echo "[DEBUG] IP: {$ip}, UA: {$ua}\n";

        $rlKey = sprintf('rl:gen:%s:%s:%s', $ip, sha1($ua), $region);
        $reqs  = (int)($redis->get($rlKey) ?: 0);
        echo "[DEBUG] Rate limit key: {$rlKey}, requests: {$reqs}\n";
        if ($reqs >= 100) {
            echo "[DEBUG] Quá nhiều request trong 1 phút\n";
            return $this->response->setStatusCode(429)->setJsonContent(['error' => 'Quá nhiều yêu cầu']);
        }
        $ttl = $redis->ttl($rlKey);
        if ($ttl < 0) $ttl = 60;
        $redis->set($rlKey, $reqs + 1, $ttl);

        $token = bin2hex(random_bytes(16));
        $meta  = json_encode([
            'ip'     => $ip,
            'ua'     => $ua,
            'region' => $region,
            'iat'    => time(),
        ], JSON_UNESCAPED_SLASHES);
        echo "[DEBUG] Tạo token: {$token}, meta: {$meta}\n";

        $redis->setEx('ws_token_' . $token, 100, $meta);

        $this->cookies->set(
            $names['token'],
            $token,
            time() + 300,
            '/',
            true,                   
            '',                     
            true,
            ['samesite' => 'Strict']
        );

        echo "[DEBUG] Token đã lưu vào Redis và set cookie\n";
        return $this->response->setJsonContent(['ok' => true]);
    }

    public function liveAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('xsmn_live', null);
        $this->view->setVars($seoData);
        
        // Browser cache cho static content (5 phút)
        $this->response->setHeader('Cache-Control', 'public, max-age=300');
        $this->response->setHeader('Pragma', 'cache');
        
        $currentDayOfWeek = date('N');

        $provincesCacheKey = 'models_provinces_XSMN_' . date('Y-m-d');
        
        try {
            $allProvinces = $this->cacheRemember($provincesCacheKey, 86400, function() {
                return Provinces::find([
                    'conditions' => 'region = :region:',
                    'bind' => ['region' => 'XSMN'],
                    'order' => 'key_id ASC'
                ]);
            });
        } catch (\Exception $e) {
            $allProvinces = Provinces::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => 'XSMN'],
                'order' => 'key_id ASC'
            ]);
        }

        $provinceMap = [];
        $provincesForView = [];

        foreach ($allProvinces as $province) {
            $drawDays = explode(',', $province->draw_days);
            if (in_array($currentDayOfWeek, $drawDays)) {
                $provinceMap[$province->getKeyId()] = $province->getName();
                $provincesForView[] = [
                    'keyid' => $province->getKeyId(),
                    'name' => $province->getName(),
                    'code' => $province->getCode()
                ];
            }
        }

        $this->view->setVar('provinces', $provinceMap);
        $this->view->setVar('provincesForView', $provincesForView);
        $this->view->setVar('currentDay', $currentDayOfWeek);

        $dayNamesCacheKey = 'models_day_names';
        
        try {
            $dayNames = $this->cacheRemember($dayNamesCacheKey, 86400, function() {
                return [
                    1 => 'Thứ 2',
                    2 => 'Thứ 3',
                    3 => 'Thứ 4',
                    4 => 'Thứ 5',
                    5 => 'Thứ 6',
                    6 => 'Thứ 7',
                    7 => 'Chủ nhật'
                ];
            });
        } catch (\Exception $e) {
            $dayNames = [
                1 => 'Thứ 2',
                2 => 'Thứ 3',
                3 => 'Thứ 4',
                4 => 'Thứ 5',
                5 => 'Thứ 6',
                6 => 'Thứ 7',
                7 => 'Chủ nhật'
            ];
        }

        $this->view->setVar('currentDayName', $dayNames[$currentDayOfWeek]);
        $this->view->setVar('apiUrl', '/lottery/proxyGenerateToken');
        $this->view->setVar('baseUrl', 'https://xskt_phalcon.code:8443');
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Xổ số Miền Nam', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    public function livemtAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('xsmt_live', null);
        $this->view->setVars($seoData);
        $this->response->setHeader('Cache-Control', 'public, max-age=300');
        $this->response->setHeader('Pragma', 'cache');
        
        $currentDayOfWeek = date('N');

        $provincesCacheKey = 'models_provinces_XSMT_' . date('Y-m-d');
        
        try {
            $allProvinces = $this->cacheRemember($provincesCacheKey, 86400, function() {
                return Provinces::find([
                    'conditions' => 'region = :region:',
                    'bind' => ['region' => 'XSMT'],
                    'order' => 'key_id ASC'
                ]);
            });
        } catch (\Exception $e) {
            $allProvinces = Provinces::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => 'XSMT'],
                'order' => 'key_id ASC'
            ]);
        }

        $provinceMap = [];
        $provincesForView = [];

        foreach ($allProvinces as $province) {
            $drawDays = explode(',', $province->getDrawDays());
            if (in_array($currentDayOfWeek, $drawDays)) {
                $provinceMap[$province->getKeyId()] = $province->getName();
                $provincesForView[] = [
                    'keyid' => $province->getKeyId(),
                    'name' => $province->getName(),
                    'code' => $province->getCode()
                ];
            }
        }

        $this->view->setVar('provinces', $provinceMap);
        $this->view->setVar('provincesForView', $provincesForView);
        $this->view->setVar('currentDay', $currentDayOfWeek);
        $dayNamesCacheKey = 'models_day_names';
        
        try {
            $dayNames = $this->cacheRemember($dayNamesCacheKey, 86400, function() {
                return [
                    1 => 'Thứ 2',
                    2 => 'Thứ 3',
                    3 => 'Thứ 4',
                    4 => 'Thứ 5',
                    5 => 'Thứ 6',
                    6 => 'Thứ 7',
                    7 => 'Chủ nhật'
                ];
            });
        } catch (\Exception $e) {
            $dayNames = [
                1 => 'Thứ 2',
                2 => 'Thứ 3',
                3 => 'Thứ 4',
                4 => 'Thứ 5',
                5 => 'Thứ 6',
                6 => 'Thứ 7',
                7 => 'Chủ nhật'
            ];
        }

        $this->view->setVar('currentDayName', $dayNames[$currentDayOfWeek]);
        $this->view->setVar('baseUrl', 'https://xskt_phalcon.code:8443');
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Xổ số Miền Trung', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
    public function livembAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // SEO data
        $seoData = \App\Library\SeoHelper::getSeoData('xsmb_live', null);
        $this->view->setVars($seoData);
        $this->response->setHeader('Cache-Control', 'public, max-age=300');
        $this->response->setHeader('Pragma', 'cache');
        $currentDayOfWeek = date('N');
        $provincesCacheKey = 'models_provinces_XSMB_' . date('Y-m-d');
        try {
            $allProvinces = $this->cacheRemember($provincesCacheKey, 86400, function() {
                return Provinces::find([
                    'conditions' => 'region = :region:',
                    'bind' => ['region' => 'XSMB'],
                    'order' => 'key_id ASC'
                ]);
            });
        } catch (\Exception $e) {
            $allProvinces = Provinces::find([
                'conditions' => 'region = :region:',
                'bind' => ['region' => 'XSMB'],
                'order' => 'key_id ASC'
            ]);
        }

        $provinceMap = [];
        $provincesForView = [];
        foreach ($allProvinces as $province) {
            $drawDays = explode(',', $province->getDrawDays());
            if (in_array($currentDayOfWeek, $drawDays)) {
                $provinceMap[$province->getKeyId()] = $province->getName();
                $provincesForView[] = [
                    'keyid' => $province->getKeyId(),
                    'name' => $province->getName(),
                    'code' => $province->getCode()
                ];
            }
        }
        $this->view->setVar('provinces', $provinceMap);
        $this->view->setVar('provincesForView', $provincesForView);

        $this->view->setVar('currentDay', $currentDayOfWeek);
        $dayNamesCacheKey = 'models_day_names';
        
        try {
            $dayNames = $this->cacheRemember($dayNamesCacheKey, 86400, function() {
                return [
                    1 => 'Thứ 2',
                    2 => 'Thứ 3',
                    3 => 'Thứ 4',
                    4 => 'Thứ 5',
                    5 => 'Thứ 6',
                    6 => 'Thứ 7',
                    7 => 'Chủ nhật'
                ];
            });
        } catch (\Exception $e) {
            $dayNames = [
                1 => 'Thứ 2',
                2 => 'Thứ 3',
                3 => 'Thứ 4',
                4 => 'Thứ 5',
                5 => 'Thứ 6',
                6 => 'Thứ 7',
                7 => 'Chủ nhật'
            ];
        }

        $this->view->setVar('currentDayName', $dayNames[$currentDayOfWeek]);
        $this->view->setVar('baseUrl', 'https://xskt_phalcon.code:8443');
        
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Trực tiếp kết quả Xổ số Miền Bắc', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
    }
}
