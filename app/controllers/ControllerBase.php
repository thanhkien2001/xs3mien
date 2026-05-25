<?php
declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Library\ScheduleUrlHelper;

class ControllerBase extends Controller
{
    /** @var \Phalcon\Cache\Cache */
    public $cache;
    
    /** @var \Phalcon\Cache\Cache */
    public $responseCache;

    /**
     * Simple helper to get cache instance
     */
    public function getCache()
    {
        if ($this->cache === null) {
            $this->cache = $this->di->get('modelsCache');
        }
        return $this->cache;
    }
    
    /**
     * Get response cache instance
     */
    public function getResponseCache()
    {
        if ($this->responseCache === null) {
            $this->responseCache = $this->di->get('responseCache');
        }
        return $this->responseCache;
    }

    /**
     * Cache remember helper
     */
    public function cacheRemember(string $key, int $ttl, callable $callback)
    {
        $cache = $this->getCache();
        $data = $cache->get($key);
        if ($data !== null) {
            return $data;
        }
        $data = $callback();
        $cache->set($key, $data, $ttl);
        return $data;
    }
    
    /**
     * Response cache helper
     */
    public function responseCacheRemember(string $key, int $ttl, callable $callback)
    {
        $cache = $this->getResponseCache();
        $data = $cache->get($key);
        if ($data !== null) {
            return $data;
        }
        $data = $callback();
        $cache->set($key, $data, $ttl);
        return $data;
    }
    
    public function beforeExecuteRoute()
    {
        // Set response headers for performance
        $this->response->setHeader('X-Content-Type-Options', 'nosniff');
        $this->response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->response->setHeader('X-XSS-Protection', '1; mode=block');
        
        $tz   = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTime('today', $tz);

        $dateKey = $date->format('Y-m-d');
        $scheduleSlots = $this->cacheRemember('slots_'.$dateKey, 43000, function() use ($date) {
            return ScheduleUrlHelper::buildSlots($date);
        });
        $scheduleSlots1 = $this->cacheRemember('slots1_'.$dateKey, 43000, function() use ($date) {
            return ScheduleUrlHelper::buildSlots1($date);
        });
        $this->view->setVar('scheduleSlots', $scheduleSlots);
        $this->view->setVar('scheduleSlots1', $scheduleSlots1);
    }
    
    /**
     * Set cache headers for the response
     */
    protected function setCacheHeaders(int $maxAge = 3600, bool $public = true)
    {
        $cacheControl = $public ? 'public' : 'private';
        $this->response->setHeader('Cache-Control', $cacheControl . ', max-age=' . $maxAge);
        $this->response->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }
    
    /**
     * Enable response compression
     */
    protected function enableCompression()
    {
        if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 1);
            ini_set('zlib.output_compression_level', 6);
        }
    }
}
