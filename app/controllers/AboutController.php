<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Library\PerformanceHelper;
use App\Library\SchemaHelper;

class AboutController extends ControllerBase
{
    /**
     * Trang giới thiệu
     */
    public function indexAction()
    {
        // Sửa từ canonicalUrl thành canonical_url
        $this->view->setVar('canonical_url', $this->url->get($this->request->getURI()));
        
        $this->view->customindex = '/css/indexheader.css';
        $seoData = PerformanceHelper::generateCachedSeoData('about', null, null, [
            'page_title' => 'Giới Thiệu',
            'page_type' => 'about'
        ], $this->cache);
        
        $seoData['seo_title'] = $seoData['seo_title'] ?? 'Giới Thiệu - Soi Cầu 247';
        $seoData['seo_description'] = $seoData['seo_description'] ?? 'Giới thiệu về hệ thống tra cứu kết quả soi cầu 247 3 miền - XSMB, XSMT, XSMN nhanh chóng và chính xác nhất.';
        $seoData['seo_keywords'] = $seoData['seo_keywords'] ?? 'giới thiệu, xổ số, kết quả xổ số, xsmb, xsmt, xsmn';
        
        $this->view->setVars($seoData);
        
        $this->view->setVar('page_schema_type', 'about');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get('about'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Giới thiệu', 'url' => $this->url->get('about')]
            ]
        ]);

        $this->view->pick('about/index');
    }
    
    /**
     * Trang điều khoản sử dụng
     */
    public function termsAction()
    {
        // Sửa từ canonicalUrl thành canonical_url
        $this->view->setVar('canonical_url', $this->url->get($this->request->getURI()));
        
        $this->view->customindex = '/css/indexheader.css';
        
        $seoData = PerformanceHelper::generateCachedSeoData('terms', null, null, [
            'page_title' => 'Điều Khoản Sử Dụng',
            'page_type' => 'terms'
        ], $this->cache);
        
        $seoData['seo_title'] = $seoData['seo_title'] ?? 'Điều Khoản Sử Dụng - Soi Cầu 247';
        $seoData['seo_description'] = $seoData['seo_description'] ?? 'Điều khoản và điều kiện sử dụng dịch vụ tra cứu kết quả soi cầu 247. Vui lòng kỹ trước khi sử dụng.';
        $seoData['seo_keywords'] = $seoData['seo_keywords'] ?? 'điều khoản, điều khoản sử dụng, quy định, xổ số';
        
        $this->view->setVars($seoData);
        
        $this->view->setVar('page_schema_type', 'terms');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get('about/terms'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Điều khoản sử dụng', 'url' => $this->url->get('about/terms')]
            ]
        ]);

        $this->view->pick('about/terms');
    }
    
    /**
     * Trang liên hệ
     */
    public function contactAction()
    {
        // Sửa từ canonicalUrl thành canonical_url
        $this->view->setVar('canonical_url', $this->url->get($this->request->getURI()));
        
        $this->view->customindex = '/css/indexheader.css';
        
        $seoData = PerformanceHelper::generateCachedSeoData('contact', null, null, [
            'page_title' => 'Liên Hệ',
            'page_type' => 'contact'
        ], $this->cache);
        
        $seoData['seo_title'] = $seoData['seo_title'] ?? 'Liên Hệ - Soi Cầu 247';
        $seoData['seo_description'] = $seoData['seo_description'] ?? 'Liên hệ với chúng tôi để được hỗ trợ và giải đáp thắc mắc về dịch vụ tra cứu kết quả soi cầu 247.';
        $seoData['seo_keywords'] = $seoData['seo_keywords'] ?? 'liên hệ, hỗ trợ, góp ý, xổ số';
        
        $this->view->setVars($seoData);
        
        $this->view->setVar('page_schema_type', 'contact');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get('about/contact'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Liên hệ', 'url' => $this->url->get('about/contact')]
            ]
        ]);
        
        if ($this->request->isPost()) {
            $name = $this->request->getPost('name', 'string');
            $email = $this->request->getPost('email', 'email');
            $subject = $this->request->getPost('subject', 'string');
            $message = $this->request->getPost('message', 'string');
            $this->flash->success('Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất có thể.');
        }
        
        $this->view->pick('about/contact');
    }

    /**
     * Trang chính sách bảo mật
     */
    public function privacyAction()
    {
        $this->view->setVar('canonical_url', $this->url->get($this->request->getURI()));
        $this->view->customindex = '/css/indexheader.css';
        
        $seoData = PerformanceHelper::generateCachedSeoData('privacy', null, null, [
            'page_title' => 'Chính Sách Bảo Mật',
            'page_type' => 'privacy'
        ], $this->cache);
        
        $seoData['seo_title'] = $seoData['seo_title'] ?? 'Chính Sách Bảo Mật - Soi Cầu 247';
        $seoData['seo_description'] = $seoData['seo_description'] ?? 'Chính sách bảo mật thông tin cá nhân của người dùng trên hệ thống tra cứu kết quả soi cầu 247.';
        $seoData['seo_keywords'] = $seoData['seo_keywords'] ?? 'bảo mật, chính sách bảo mật, thông tin cá nhân, xổ số';
        
        $this->view->setVars($seoData);
        
        $this->view->setVar('page_schema_type', 'privacy');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->url->get('about/privacy'),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->url->get('/')],
                ['name' => 'Chính sách bảo mật', 'url' => $this->url->get('about/privacy')]
            ]
        ]);
        
        $this->view->pick('about/privacy');
    }
}