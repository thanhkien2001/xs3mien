<?php
namespace App\Library;

class LotteryDataHelper
{
    private static $config;
    
    public static function init()
    {
        if (!self::$config) {
            $di = \Phalcon\Di\Di::getDefault();
            self::$config = $di->get('config')->data;
        }
    }
    
    public static function getDataDir()
    {
        self::init();
        return self::$config['lottery_dir'];
    }
    
    public static function getFilePath($type)
    {
        self::init();
        $dir = self::$config['lottery_dir'];
        $file = self::$config['lottery_files'][$type] ?? null;
        
        if (!$file) {
            throw new \Exception("Invalid lottery type: {$type}");
        }

        return $dir . $file;
    }
    
    public static function getTimestampFile($type)
    {
        $dataFile = self::getFilePath($type);
        return str_replace('.json', '_timestamp.txt', $dataFile);
    }
    
    public static function saveData($type, $data)
    {
        $file = self::getFilePath($type);
        $timestampFile = self::getTimestampFile($type);
        
        // Tạo thư mục nếu chưa có
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
        touch($timestampFile);
        
        return true;
    }
    
    public static function getData($type, $lastCheck = 0)
    {
        $file = self::getFilePath($type);
        $timestampFile = self::getTimestampFile($type);
        
        if (!file_exists($file) || !file_exists($timestampFile)) {
            return ['status' => 'no_data'];
        }
        
        $lastModified = filemtime($timestampFile);
        
        if ($lastModified <= $lastCheck) {
            return ['status' => 'no_update'];
        }
        
        $data = json_decode(file_get_contents($file), true);
        return [
            'status' => 'updated',
            'data' => $data,
            'timestamp' => $lastModified
        ];
    }
}
