<?php
/**
 * Database Optimization Script for XSKT Phalcon Application
 * Run this script to create optimized indexes for better query performance
 * 
 * Usage: php optimize_database.php
 */

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql;

// Include the application's autoloader
include __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = include __DIR__ . '/app/config/config.php';

try {
    // Create database connection
    $db = new Mysql([
        'host'     => $config['database']['host'],
        'username' => $config['database']['username'],
        'password' => $config['database']['password'],
        'dbname'   => $config['database']['dbname'],
        'port'   => $config['database']['port'],
        'charset'  => $config['database']['charset'] ?? 'utf8mb4',
        \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]);

    // Function to check if an index exists
    function indexExists($db, $tableName, $indexName) {
        $sql = "SHOW INDEX FROM `$tableName` WHERE Key_name = :indexName";
        $result = $db->fetchAll($sql, \Phalcon\Db\Enum::FETCH_ASSOC, ['indexName' => $indexName]);
        return count($result) > 0;
    }

    echo "🔗 Connected to database: " . $config['database']['dbname'] . "\n";

    // Array of optimization queries
    $optimizationQueries = [
        // Basic indexes
        [
            'name' => 'Index for draw_type and draw_date queries',
            'index_name' => 'idx_lottery_draw_type_date',
            'table' => 'lottery_results',
            'sql' => "CREATE INDEX idx_lottery_draw_type_date ON lottery_results (draw_type, draw_date DESC)"
        ],
        [
            'name' => 'Index for province_id and draw_date queries',
            'index_name' => 'idx_lottery_province_date',
            'table' => 'lottery_results',
            'sql' => "CREATE INDEX idx_lottery_province_date ON lottery_results (province_id, draw_date DESC)"
        ],
        [
            'name' => 'Composite index for region + province + date queries',
            'index_name' => 'idx_lottery_type_province_date',
            'table' => 'lottery_results',
            'sql' => "CREATE INDEX idx_lottery_type_province_date ON lottery_results (draw_type, province_id, draw_date DESC)"
        ],
        [
            'name' => 'Index for special_prize last 2 digits analysis',
            'index_name' => 'idx_lottery_special_prize_last2',
            'table' => 'lottery_results',
            'sql' => "CREATE INDEX idx_lottery_special_prize_last2 ON lottery_results (draw_type, draw_date DESC) USING BTREE"
        ],
        [
            'name' => 'Index for provinces by region',
            'index_name' => 'idx_provinces_region',
            'table' => 'provinces',
            'sql' => "CREATE INDEX idx_provinces_region ON provinces (region, name)"
        ],
        
        // Covering indexes for performance
        [
            'name' => 'Covering index for basic lottery queries',
            'index_name' => 'idx_lottery_covering_basic',
            'table' => 'lottery_results',
            'sql' => "CREATE INDEX idx_lottery_covering_basic ON lottery_results (draw_type, draw_date DESC, province_id, special_prize)"
        ],
        [
            'name' => 'Index for frequency analysis',
            'index_name' => 'idx_lottery_frequency',
            'table' => 'lottery_results',
            'sql' => "CREATE INDEX idx_lottery_frequency ON lottery_results (draw_type, draw_date DESC, special_prize)"
        ]
    ];

    $successCount = 0;
    $totalCount = count($optimizationQueries);

    echo "\n📊 Starting database optimization...\n";
    echo "Total optimization steps: $totalCount\n\n";

    foreach ($optimizationQueries as $i => $query) {
        try {
            echo sprintf("[%d/%d] %s... ", $i + 1, $totalCount, $query['name']);
            
            if (indexExists($db, $query['table'], $query['index_name'])) {
                echo "☑️ Already exists\n";
                $successCount++;
                continue;
            }

            $startTime = microtime(true);
            $statement = $db->prepare($query['sql']);
            $statement->execute();
            $statement->fetchAll(); // Fetch all results to clear the buffer
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000, 2);
            echo "✅ Done ({$duration}ms)\n";
            $successCount++;
            
        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
        }
    }

    echo "\n🎉 Optimization completed!\n";
    echo "Successfully applied: $successCount/$totalCount optimizations\n";

    echo "\n📈 Running table analysis...\n";
    
    // Analyze tables for better query planning
    $analyzeTables = ['lottery_results', 'provinces'];
    foreach ($analyzeTables as $table) {
        try {
            echo "Analyzing table: $table... ";
            $statement = $db->prepare("ANALYZE TABLE $table");
            $statement->execute();
            $statement->fetchAll(); // Fetch all results to clear the buffer
            echo "✅ Done\n";
        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
        }
    }

    // Show index information
    echo "\n📋 Current indexes on lottery_results:\n";
    $statement = $db->prepare("SHOW INDEX FROM lottery_results");
    $statement->execute();
    $indexes = $statement->fetchAll(\Phalcon\Db\Enum::FETCH_ASSOC);
    foreach ($indexes as $index) {
        if ($index['Key_name'] !== 'PRIMARY') {
            echo "  - {$index['Key_name']} on {$index['Column_name']}\n";
        }
    }

    // Performance recommendations
    echo "\n💡 Performance Recommendations:\n";
    echo "1. Monitor slow query log for further optimization opportunities\n";
    echo "2. Consider partitioning lottery_results table by draw_date if data grows large\n";
    echo "3. Review and update cache lifetimes based on query patterns\n";
    echo "4. Run this optimization script after major data updates\n";

    // Cache warming suggestion
    echo "\n🔥 Cache Warming:\n";
    echo "Consider running the statistics controllers once to warm up the cache:\n";
    echo "- Visit /thongkemb/thongkexsmb\n";
    echo "- Visit /thongkemn/thongkexsmn  \n";
    echo "- Visit /thongkemt/thongkexsmt\n";

} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in app/config/config.php\n";
    exit(1);
}
