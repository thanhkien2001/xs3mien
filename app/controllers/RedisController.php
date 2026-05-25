<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

/**
 * @phpstan-ignore-file
 */

class RedisController extends Controller
{
    /** @var \Redis|null */
    private $redis;

    public function onConstruct()
    {
        // Kết nối Redis
        try {
            // @phpstan-ignore-next-line
            $this->redis = new \Redis();
            // @phpstan-ignore-next-line
            $this->redis->connect('127.0.0.1', 6379);
            // @phpstan-ignore-next-line
            $this->redis->ping(); // Test connection
            // Chọn database 1
            // @phpstan-ignore-next-line
            $this->redis->select(1);
        } catch (\Exception $e) {
            $this->redis = null;
        }
    }

    /**
     * Chuyển đổi database Redis
     */
    private function selectDatabase($dbNumber = 1)
    {
        if (!$this->redis) {
            return false;
        }
        
        try {
            $this->redis->select($dbNumber);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Xóa cache theo danh sách keys
     */
    public function clearCacheAction()
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');

        if (!$this->redis) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Redis connection failed'
            ]);
            return $response;
        }

        try {
            // Đảm bảo sử dụng database 1
            $this->selectDatabase(1);
            
            $request = $this->request->getJsonRawBody();
            $keys = $request->keys ?? [];

            if (empty($keys)) {
                $response->setStatusCode(400);
                $response->setJsonContent([
                    'success' => false,
                    'message' => 'No keys provided'
                ]);
                return $response;
            }

            $deletedCount = 0;
            $results = [];

            foreach ($keys as $key) {
                try {
                    $result = $this->redis->del($key);
                    if ($result > 0) {
                        $deletedCount++;
                        $results[] = [
                            'key' => $key,
                            'deleted' => true
                        ];
                    } else {
                        $results[] = [
                            'key' => $key,
                            'deleted' => false,
                            'message' => 'Key not found'
                        ];
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'key' => $key,
                        'deleted' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $response->setStatusCode(200);
            $response->setJsonContent([
                'success' => true,
                'message' => "Deleted {$deletedCount} cache keys",
                'total_keys' => count($keys),
                'deleted_count' => $deletedCount,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Xóa cache theo pattern
     */
    public function clearCacheByPatternAction()
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');

        if (!$this->redis) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Redis connection failed'
            ]);
            return $response;
        }

        try {
            // Đảm bảo sử dụng database 1
            $this->selectDatabase(1);
            
            $request = $this->request->getJsonRawBody();
            $pattern = $request->pattern ?? '';

            if (empty($pattern)) {
                $response->setStatusCode(400);
                $response->setJsonContent([
                    'success' => false,
                    'message' => 'No pattern provided'
                ]);
                return $response;
            }

            // Tìm tất cả keys theo pattern
            $keys = $this->redis->keys($pattern);
            
            if (empty($keys)) {
                $response->setStatusCode(200);
                $response->setJsonContent([
                    'success' => true,
                    'message' => 'No keys found matching pattern',
                    'pattern' => $pattern,
                    'deleted_count' => 0
                ]);
                return $response;
            }

            // Xóa tất cả keys tìm được
            $deletedCount = $this->redis->del($keys);

            $response->setStatusCode(200);
            $response->setJsonContent([
                'success' => true,
                'message' => "Deleted {$deletedCount} cache keys matching pattern",
                'pattern' => $pattern,
                'total_keys' => count($keys),
                'deleted_count' => $deletedCount,
                'keys' => $keys
            ]);

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Error clearing cache by pattern: ' . $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Lấy thông tin Redis
     */
    public function infoAction()
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');

        if (!$this->redis) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Redis connection failed'
            ]);
            return $response;
        }

        try {
            // Đảm bảo sử dụng database 1
            $this->selectDatabase(1);
            
            $info = $this->redis->info();
            $dbSize = $this->redis->dbSize();

            $response->setStatusCode(200);
            $response->setJsonContent([
                'success' => true,
                'message' => 'Redis connection successful',
                'db_size' => $dbSize,
                'info' => $info
            ]);

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Error getting Redis info: ' . $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Xóa tất cả cache Mega645
     */
    public function clearMega645CacheAction()
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');

        if (!$this->redis) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Redis connection failed'
            ]);
            return $response;
        }

        try {
            // Đảm bảo sử dụng database 1
            $this->selectDatabase(1);
            
            // Các keys cần xóa cho Mega645
            $mega645Keys = [
                'MEGA_home_mega_latest_v1',
                'MEGA_frequency_limit20_orderASC',
                'MEGA_frequency_limit20_orderDESC',
                'MEGA_latest',
                'MEGA_patterns',
                'Mega645_statistics_limit100',
            ];

            // Xóa keys cụ thể
            $deletedCount = 0;
            $results = [];

            foreach ($mega645Keys as $key) {
                try {
                    $result = $this->redis->del($key);
                    if ($result > 0) {
                        $deletedCount++;
                        $results[] = [
                            'key' => $key,
                            'deleted' => true
                        ];
                    } else {
                        $results[] = [
                            'key' => $key,
                            'deleted' => false,
                            'message' => 'Key not found'
                        ];
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'key' => $key,
                        'deleted' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Xóa theo pattern
            $patterns = [
                'MEGA_*',
                'Mega645_*',
                'models_Mega645_*',
                'models_MEGA_*'
            ];

            $patternResults = [];
            foreach ($patterns as $pattern) {
                try {
                    $keys = $this->redis->keys($pattern);
                    if (!empty($keys)) {
                        $deleted = $this->redis->del($keys);
                        $patternResults[] = [
                            'pattern' => $pattern,
                            'keys_found' => count($keys),
                            'deleted' => $deleted
                        ];
                        $deletedCount += $deleted;
                    }
                } catch (\Exception $e) {
                    $patternResults[] = [
                        'pattern' => $pattern,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $response->setStatusCode(200);
            $response->setJsonContent([
                'success' => true,
                'message' => "Cleared Mega645 cache successfully",
                'total_deleted' => $deletedCount,
                'specific_keys' => $results,
                'pattern_results' => $patternResults
            ]);

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Error clearing Mega645 cache: ' . $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Chuyển đổi database Redis
     */
    public function selectDbAction()
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');

        if (!$this->redis) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Redis connection failed'
            ]);
            return $response;
        }

        try {
            $request = $this->request->getJsonRawBody();
            $dbNumber = $request->db ?? 1;

            if (!is_numeric($dbNumber) || $dbNumber < 0 || $dbNumber > 15) {
                $response->setStatusCode(400);
                $response->setJsonContent([
                    'success' => false,
                    'message' => 'Invalid database number. Must be between 0-15'
                ]);
                return $response;
            }

            $success = $this->selectDatabase((int)$dbNumber);
            
            if ($success) {
                $response->setStatusCode(200);
                $response->setJsonContent([
                    'success' => true,
                    'message' => "Switched to database {$dbNumber}",
                    'current_db' => $dbNumber
                ]);
            } else {
                $response->setStatusCode(500);
                $response->setJsonContent([
                    'success' => false,
                    'message' => 'Failed to switch database'
                ]);
            }

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setJsonContent([
                'success' => false,
                'message' => 'Error switching database: ' . $e->getMessage()
            ]);
        }

        return $response;
    }
}
