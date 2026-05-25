<?php

declare(strict_types=1);
namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Library\SeoFieldManager;

class AdminSeoFieldsController extends ControllerBase
{
    private $seoFieldManager;
    
    public function initialize()
    {
        $this->seoFieldManager = new SeoFieldManager();
    }
    
    public function beforeExecuteRoute()
    {
        if (!\App\Controllers\AdminAuthController::requireAuth($this, $this->response, $this->session)) {
            return false;
        }
    }
    
    public function indexAction()
    {
        $pageTypes = $this->seoFieldManager->getPageTypes();
        
        $viewPageTypes = [];
        foreach ($pageTypes as $pageType) {
            $subtypes = $this->seoFieldManager->getPageSubtypes($pageType);
            $viewPageTypes[$pageType] = $subtypes;
        }
        
        $this->view->pageTypes = $viewPageTypes;
        $this->view->seoFieldManager = $this->seoFieldManager;
        $this->view->disableLevel([
            \Phalcon\Mvc\View::LEVEL_LAYOUT      => true,
            \Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT => true
        ]);
        $this->view->pick('admin/seo-fields/index');
    }
    
    public function editAction()
    {
        $pageType = $this->dispatcher->getParam('pageType');
        $pageSubtype = $this->dispatcher->getParam('pageSubtype');
        
        if ($this->request->isPost()) {
            $fields = $this->request->getPost('fields');
            
            
            if (!empty($fields)) {
                foreach ($fields as $fieldName => $value) {
                    $this->seoFieldManager->setField($pageType, $pageSubtype, $fieldName, $value);
                }
                
                $this->flashSession->success('SEO fields đã được cập nhật thành công!');
                
                // Flush caches to reflect changes immediately
                if ($this->di->has('modelsCache')) {
                    $this->di->get('modelsCache')->flush();
                }
                if ($this->di->has('viewCache')) {
                    $this->di->get('viewCache')->flush();
                }
            } else {
                $this->flashSession->error('Không có dữ liệu fields để cập nhật!');
            }
            
            return $this->response->redirect('/admin/seo-fields');
        }
        
        $fields = $this->seoFieldManager->getFields($pageType, $pageSubtype);
        $pageTypes = $this->seoFieldManager->getPageTypes();
        $pageSubtypes = $this->seoFieldManager->getPageSubtypes($pageType);
        
        $this->view->pageType = $pageType;
        $this->view->pageSubtype = $pageSubtype;
        $this->view->fields = $fields;
        $this->view->pageTypes = $pageTypes;
        $this->view->pageSubtypes = $pageSubtypes;
        $this->view->seoFieldManager = $this->seoFieldManager;
        $this->view->disableLevel([
            \Phalcon\Mvc\View::LEVEL_LAYOUT      => true,
            \Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT => true
        ]);
        $this->view->pick('admin/seo-fields/edit');
    }
    
    public function previewAction()
    {
        $pageType = $this->request->getPost('pageType');
        $pageSubtype = $this->request->getPost('pageSubtype');
        $variables = $this->request->getPost('variables', 'array', []);
        
        $fields = $this->request->getPost('fields', 'array', []);
        
        $tempFields = [];
        foreach ($fields as $fieldName => $value) {
            $tempFields[$fieldName] = [
                'type' => 'text',
                'default' => $value,
                'required' => true
            ];
        }
        
        $seoData = $this->seoFieldManager->generateSeoData($pageType, $pageSubtype, $variables, $tempFields);
        
        $this->response->setJsonContent([
            'success' => true,
            'data' => $seoData
        ]);
        
        return $this->response;
    }
    
    public function initAction()
    {
        try {
            $this->seoFieldManager->createDefaultFile();
            $this->flashSession->success('File JSON đã được tạo với dữ liệu mặc định!');
        } catch (\Exception $e) {
            $this->flashSession->error('Lỗi: ' . $e->getMessage());
        }
        return $this->response->redirect('/admin/seo-fields');
    }
    
}
