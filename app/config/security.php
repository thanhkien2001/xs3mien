<?php

/**
 * Security Configuration
 * Contains security-related settings and constants
 */

return [
    'encryption' => [
        'key' => getenv('ENCRYPTION_KEY') ?: 'default-32-character-key-change-me',
        'cipher' => 'AES-256-CBC',
    ],
    
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'default-jwt-secret-change-me',
        'algorithm' => 'HS256',
        'expiration' => 3600, // 1 hour
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 100, // per minute
        'time_window' => 60, // seconds
        'storage' => 'redis', // or 'file'
    ],
    
    'cors' => [
        'enabled' => true,
        'allowed_origins' => [
            'https://your-domain.com',
            'https://www.your-domain.com',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => [
            'Origin',
            'X-Requested-With',
            'Content-Type',
            'Accept',
            'Authorization',
            'Cache-Control',
            'Pragma'
        ],
        'allow_credentials' => true,
        'max_age' => 86400, // 24 hours
    ],
    
    'headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_xss_protection' => '1; mode=block',
        'x_content_type_options' => 'nosniff',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';",
        'strict_transport_security' => 'max-age=31536000; includeSubDomains; preload',
    ],
    
    'session' => [
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
        'lifetime' => 7200, // 2 hours
        'regenerate_id' => true,
        'use_strict_mode' => true,
    ],
    
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'max_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],
    
    'api' => [
        'key_rotation_days' => 30,
        'max_keys_per_user' => 3,
        'require_https' => true,
        'ip_whitelist_enabled' => false, // Set to true in production
        'allowed_ips' => [
            '127.0.0.1',
            '::1',
            // Add your server IPs here
        ],
    ],
    
    'logging' => [
        'security_events' => true,
        'failed_logins' => true,
        'api_abuse' => true,
        'suspicious_activity' => true,
        'log_file' => '/var/log/php/security.log',
    ],
    
    'blocked_user_agents' => [
        'grok',
        'chatgpt',
        'claude',
        'openai',
        'anthropic',
        // 'bot',
        // 'crawler',
        // 'spider',
        // 'scraper',
        'curl',
        'wget',
        'python-requests',
        'postman',
        'insomnia',
        'httpie',
    ],
    
    'blocked_ips' => [
        // Add blocked IPs here
    ],
    
    'allowed_domains' => [
        'your-domain.com',
        'www.your-domain.com',
        // Add your domains here
    ],
];
