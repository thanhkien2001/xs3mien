<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class AdminAuthController extends ControllerBase
{
    private const ADMIN_USERNAME = 'superadmin';
    private const ADMIN_PASSWORD = 'admin@@123';
    private const SESSION_KEY = 'admin_logged_in';
    private const SESSION_USERNAME = 'admin_username';
    
    public function initialize()
    {
        $this->view->disableLevel([
            \Phalcon\Mvc\View::LEVEL_LAYOUT      => true,
            \Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT => true
        ]);
    }
    
    public function loginAction()
    {
        if ($this->isLoggedIn()) {
            return $this->response->redirect('/admin/seo-fields');
        }
        
        if ($this->request->isPost()) {
            $username = $this->request->getPost('username', 'string', '');
            $password = $this->request->getPost('password', 'string', '');
            
            if ($this->authenticate($username, $password)) {
                $this->session->set(self::SESSION_KEY, true);
                $this->session->set(self::SESSION_USERNAME, $username);
                
                return $this->response->redirect('/admin/seo-fields');
            } else {
                $this->view->error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            }
        }
        
        $this->view->pick('admin/auth/login');
    }
    
    public function logoutAction()
    {
        $this->session->remove(self::SESSION_KEY);
        $this->session->remove(self::SESSION_USERNAME);
        
        return $this->response->redirect('/admin/login');
    }
    
    public function checkAction()
    {
        $this->view->disable();
        
        $response = new Response();
        $response->setJsonContent([
            'logged_in' => $this->isLoggedIn(),
            'username' => $this->session->get(self::SESSION_USERNAME, '')
        ]);
        
        return $response;
    }
    
    /**
     * Kiểm tra xem admin đã đăng nhập chưa
     */
    public static function isLoggedInStatic($session): bool
    {
        return $session->has(self::SESSION_KEY) && $session->get(self::SESSION_KEY) === true;
    }
    
    /**
     * Kiểm tra xem admin đã đăng nhập chưa (instance method)
     */
    private function isLoggedIn(): bool
    {
        return $this->session->has(self::SESSION_KEY) && $this->session->get(self::SESSION_KEY) === true;
    }
    
    /**
     * Xác thực thông tin đăng nhập
     */
    private function authenticate(string $username, string $password): bool
    {
        return $username === self::ADMIN_USERNAME && $password === self::ADMIN_PASSWORD;
    }
    
    /**
     * Middleware kiểm tra đăng nhập
     */
    public static function requireAuth($controller, $response, $session): bool
    {
        if (!self::isLoggedInStatic($session)) {
            $response->redirect('/admin/login');
            return false;
        }
        return true;
    }
}
