<?php
namespace App\Library;

class ApiSecurityHelper
{
    private static $apiKeys = [
        'backend_mega645' => 'backend_mega645_secret_2024',
        'backend_power655' => 'backend_power655_secret_2024',
        'backend_max3d' => 'backend_max3d_secret_2024',
        'backend_max3dpro' => 'backend_max3dpro_secret_2024'
    ];
    
    private static $rateLimits = [];
    private static $maxRequests = 60;
    private static $timeWindow = 60; 
    
    private static $blockedUserAgents = [
        'grok',
        'chatgpt',
        'claude',
        'openai',
        'anthropic',
        'bot',
        'crawler',
        'spider',
        'scraper',
        'curl',
        'wget',
        'python-requests',
        'postman'
    ];
    private static $allowedIps = [
        '127.0.0.1',           
        '::1',                 
        '116.111.185.160',
        '159.223.37.96'    
    ];
    private static $allowedDomains = [
        'xskt_phalcon.code',
        'localhost',
        '127.0.0.1',
        'ngrok.io',              
        'ngrok-free.app',        
        'ngrok.app',
        'a6768a882283.ngrok-free.app',
        'soicau247.com',
        'www.soicau247.com',
    ];
    
    private static $secretToken = 'xskt_phalcon_secret_token_2024_secure';
    
    /**
     * Validate API key
     */
    public static function validateApiKey($key, $endpoint = null)
    {
        if (empty($key)) {
            return false;
        }
        
        if ($endpoint && in_array($endpoint, ['live_mega645', 'live_power655', 'live_max3d', 'live_max3dpro'])) {
            $expectedKey = self::getCurrentApiKey($endpoint);
            return $key === $expectedKey;
        }
        
        if (!in_array($key, self::$apiKeys)) {
            return false;
        }
        
        if ($endpoint && isset(self::$apiKeys[$endpoint])) {
            return $key === self::$apiKeys[$endpoint];
        }
        
        return true;
    }
    
    /**
     * Check rate limit
     */
    public static function checkRateLimit($clientIp, $endpoint = 'default')
    {
        $key = $clientIp . '_' . $endpoint;
        $now = time();
        
        if (isset(self::$rateLimits[$key])) {
            self::$rateLimits[$key] = array_filter(
                self::$rateLimits[$key], 
                function($timestamp) use ($now) {
                    return ($now - $timestamp) < self::$timeWindow;
                }
            );
        } else {
            self::$rateLimits[$key] = [];
        }
        
        if (count(self::$rateLimits[$key]) >= self::$maxRequests) {
            return false;
        }
        
        self::$rateLimits[$key][] = $now;
        
        return true;
    }
    
    /**
     * Get client IP
     */
    public static function getClientIp()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return '127.0.0.1';
    }
    
    /**
     * Generate API key (for admin use)
     */
    public static function generateApiKey($endpoint)
    {
        return $endpoint . '_' . bin2hex(random_bytes(16));
    }
    
    /**
     * Get remaining requests
     */
    public static function getRemainingRequests($clientIp, $endpoint = 'default')
    {
        $key = $clientIp . '_' . $endpoint;
        $now = time();
        
        if (!isset(self::$rateLimits[$key])) {
            return self::$maxRequests;
        }
        
        // Clean old entries
        self::$rateLimits[$key] = array_filter(
            self::$rateLimits[$key], 
            function($timestamp) use ($now) {
                return ($now - $timestamp) < self::$timeWindow;
            }
        );
        
        return self::$maxRequests - count(self::$rateLimits[$key]);
    }
    
    /**
     * Check if User-Agent is blocked
     */
    public static function isUserAgentBlocked($userAgent)
    {
        if (empty($userAgent)) {
            return true; // Block empty user agents
        }
        
        $userAgentLower = strtolower($userAgent);
        
        foreach (self::$blockedUserAgents as $blocked) {
            if (strpos($userAgentLower, $blocked) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is allowed
     */
    public static function isIpAllowed($ip)
    {
        if (!self::$ipWhitelistEnabled) {
            return true; 
        }
        
        if (empty(self::$allowedIps)) {
            return true; 
        }
        
        return in_array($ip, self::$allowedIps);
    }
    
    /**
     * Check if referer domain is allowed
     */
    public static function isRefererAllowed($referer)
    {
        if (empty($referer)) {
            return true; 
        }
        
        if (empty(self::$allowedDomains)) {
            return true; 
        }
        
        $parsedUrl = parse_url($referer);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }
        
        $host = strtolower($parsedUrl['host']);
        
        foreach (self::$allowedDomains as $allowedDomain) {
            if ($host === $allowedDomain || strpos($host, '.' . $allowedDomain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Comprehensive security check
     */
    public static function performSecurityCheck($userAgent, $referer, $clientIp)
    {
        if (self::isUserAgentBlocked($userAgent)) {
            return [
                'error' => 'Access denied',
                'code' => 'BLOCKED_USER_AGENT',
                'message' => 'Your client is not allowed to access this API'
            ];
        }
        
        if (!self::isIpAllowed($clientIp)) {
            return [
                'error' => 'Access denied',
                'code' => 'IP_NOT_ALLOWED',
                'message' => 'Your IP address is not allowed to access this API'
            ];
        }
        
        if (!self::isRefererAllowed($referer)) {
            return [
                'error' => 'Access denied',
                'code' => 'INVALID_REFERER',
                'message' => 'Invalid referer domain'
            ];
        }
        
        return true;
    }
    
    /**
     * Get current API key for endpoint (for debugging/admin)
     */
    public static function getCurrentApiKey($endpoint)
    {
        $today = date('Y-m-d');
        
        switch ($endpoint) {
            case 'live_mega645':
                return 'mega645_secure_' . hash('sha256', 'mega645_' . $today);
            case 'live_power655':
                return 'power655_secure_' . hash('sha256', 'power655_' . $today);
            case 'live_max3d':
                return 'max3d_secure_' . hash('sha256', 'max3d_' . $today);
            case 'live_max3dpro':
                return 'max3dpro_secure_' . hash('sha256', 'max3dpro_' . $today);
            default:
                return isset(self::$apiKeys[$endpoint]) ? self::$apiKeys[$endpoint] : null;
        }
    }
    
    /**
     * Validate secret token
     */
    public static function validateSecretToken($token)
    {
        return !empty($token) && $token === self::$secretToken;
    }
    
    /**
     * Get secret token (for frontend use)
     */
    public static function getSecretToken()
    {
        return self::$secretToken;
    }
    
    /**
     * Enhanced security check with secret token
     */
    public static function performEnhancedSecurityCheck($userAgent, $referer, $clientIp, $secretToken = null)
    {
        if (!self::validateSecretToken($secretToken)) {
            return [
                'error' => 'Access denied',
                'code' => 'INVALID_SECRET_TOKEN',
                'message' => 'Secret token is required and must be valid'
            ];
        }
        
        return self::performSecurityCheck($userAgent, $referer, $clientIp);
    }
}
