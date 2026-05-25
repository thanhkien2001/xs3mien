<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;

class ErrorsController extends Controller
{
    public function notFoundAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        $this->response->setStatusCode(404, 'Not Found');
        
        // Set SEO meta for 404 page
        $this->view->seo_title = 'Không Tìm Thấy Trang - Soi Cầu 247';
        $this->view->seo_description = 'Trang bạn đang tìm kiếm không tồn tại. Quay lại trang chủ để xem kết quả xổ số mới nhất.';
        $this->view->seo_keywords = '404, không tìm thấy, soi cầu 247';
        
        // CRITICAL: Set noindex for 404 pages - must use $noindex boolean and $canonical_url
        $this->view->noindex = true;
        $this->view->canonical_url = 'https://soicau247.com/';
        
        $this->view->pick('errors/404');
        
        return $this->view;
    }
    
    public function serverErrorAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        $this->response->setStatusCode(500, 'Internal Server Error');
        
        $this->view->seo_title = 'Lỗi Hệ Thống - Soi Cầu 247';
        $this->view->seo_description = 'Hệ thống đang gặp sự cố tạm thời. Chúng tôi đang khắc phục và sẽ sớm hoạt động trở lại.';
        $this->view->seo_keywords = '500, lỗi hệ thống, soi cầu 247';
        
        // Set noindex for error pages
        $this->view->noindex = true;
        $this->view->canonical_url = 'https://soicau247.com/';
        
        $this->view->pick('errors/500');
        
        return $this->view;
    }
    
    public function unauthorizedAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        $this->response->setStatusCode(401, 'Unauthorized');
        
        // Set noindex for error pages
        $this->view->noindex = true;
        $this->view->canonical_url = 'https://soicau247.com/';
        
        $this->view->pick('errors/401');
        
        return $this->view;
    }
    
    public function forbiddenAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        $this->response->setStatusCode(403, 'Forbidden');
        
        // Set noindex for error pages
        $this->view->noindex = true;
        $this->view->canonical_url = 'https://soicau247.com/';
        
        $this->view->pick('errors/403');
        
        return $this->view;
    }
}