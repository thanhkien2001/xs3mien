<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Library\PerformanceHelper;

class BikipController extends ControllerBase
{
    /**
     * Helper method to get absolute URL for breadcrumb
     */
    private function getAbsoluteUrl($path = '')
    {
        $config = $this->getDI()->get('config');
        $baseUrl = $config->application->baseUrl;
        
        if (empty($path)) {
            $path = $this->request->getURI();
        }
        
        if ($path !== '/' && substr($path, -1) !== '/' && substr($path, -5) !== '.html') {
            $path .= '.html';
        }
        
        return $baseUrl . $path;
    }

    /**
     * Parse HTML và tách các bài viết
     */
    private function parseArticles($limit = 19, $offset = 0)
    {
        // Parse từ file HTML gốc
        $htmlFile = __DIR__ . '/../../bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110.html';
        if (!file_exists($htmlFile)) {
            return ['articles' => [], 'total' => 0];
        }
        
        $html = file_get_contents($htmlFile);
        
        // Tìm phần ajax_content
        preg_match('/<div id="ajax_content">(.*?)<\/div>\s*<button class="btnLoadMore"/s', $html, $matches);
        if (!isset($matches[1])) {
            return ['articles' => [], 'total' => 0];
        }
        
        $ajaxContent = $matches[1];
        
        // Tách các bài viết (mỗi bài là một <div class="row">)
        preg_match_all('/<div class="row">.*?<\/div>\s*<\/div>/s', $ajaxContent, $articleMatches);
        
        $articles = $articleMatches[0] ?? [];
        $total = count($articles);
        
        // Lấy các bài viết theo limit và offset
        $selectedArticles = array_slice($articles, $offset, $limit);
        
        return [
            'articles' => $selectedArticles,
            'total' => $total
        ];
    }

    /**
     * Trang danh sách bi kíp soi cầu lô đề
     */
    public function indexAction()
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // Parse và lấy 19 bài đầu (theo data-limit trong HTML)
        $result = $this->parseArticles(19, 0);
        $articles = $result['articles'];
        $total = $result['total'];
        
        // Generate SEO data
        $seoData = PerformanceHelper::generateCachedSeoData('custom', 'bi_kip', null, [
            'page_title' => 'Bi kíp về phương pháp soi cầu lô đề chuẩn',
            'page_type' => 'bikip'
        ], $this->cache);
        
        $this->view->setVars($seoData);
        $this->view->setVar('articles', $articles);
        $this->view->setVar('totalArticles', $total);
        $this->view->setVar('currentPage', 1);
        $this->view->setVar('limit', 19);
        
        // Schema data
        $this->view->setVar('page_schema_type', 'webpage');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Bi kíp chơi lô đề', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        
        $this->view->pick('bikip/index');
    }

    /**
     * Load more articles (AJAX)
     */
    public function loadMorePageAction()
    {
        $this->view->disable();
        
        $page = (int)$this->dispatcher->getParam('page', null, 2);
        $page = max(2, $page);
        $limit = 19; // Theo data-limit trong HTML
        $offset = ($page - 1) * $limit;
        
        $result = $this->parseArticles($limit, $offset);
        $articles = $result['articles'];
        
        if (empty($articles)) {
            $this->response->setStatusCode(404, 'Not Found');
            return $this->response;
        }
        
        $html = implode('', $articles);
        
        $this->response->setContent($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $this->response;
    }

    /**
     * Trang detail bài viết bi kíp
     */
    public function detailAction($slug)
    {
        $this->view->customindex = '/css/indexheader.css';
        
        // Remove .html extension if present
        $slug = str_replace('.html', '', $slug);
        
        // Tìm file view tương ứng
        $config = $this->getDI()->get('config');
        $viewFile = $config->application->viewsDir . 'bikip/' . $slug . '.phtml';
        
        if (!file_exists($viewFile)) {
            return $this->response->setStatusCode(404, 'Not Found');
        }
        
        // Generate SEO data
        $seoData = PerformanceHelper::generateCachedSeoData('bikip_detail', null, null, [
            'page_title' => 'Bi kíp về phương pháp soi cầu lô đề',
            'page_type' => 'bikip_detail',
            'slug' => $slug
        ], $this->cache);
        
        $seoData['seo_title'] = $seoData['seo_title'] ?? 'Bi kíp về phương pháp soi cầu lô đề - ' . $slug;
        $seoData['seo_description'] = $seoData['seo_description'] ?? 'Bi kíp chơi lô đề, phương pháp soi cầu lô đề chuẩn, kinh nghiệm chơi lô đề hiệu quả';
        $seoData['seo_keywords'] = $seoData['seo_keywords'] ?? 'bi kíp lô đề, soi cầu lô đề, phương pháp soi cầu, kinh nghiệm lô đề';
        
        $this->view->setVars($seoData);
        
        // Schema data
        $this->view->setVar('page_schema_type', 'article');
        $this->view->setVar('page_schema_data', [
            'title' => $seoData['seo_title'],
            'description' => $seoData['seo_description'],
            'url' => $this->getAbsoluteUrl(),
            'breadcrumbs' => [
                ['name' => 'Trang chủ', 'url' => $this->getAbsoluteUrl('/')],
                ['name' => 'Bi kíp chơi lô đề', 'url' => $this->getAbsoluteUrl('/bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110')],
                ['name' => 'Chi tiết', 'url' => $this->getAbsoluteUrl()]
            ]
        ]);
        
        // Render view
        $this->view->pick('bikip/' . $slug);
    }
}

