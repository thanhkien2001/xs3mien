<?php
/**
 * Image Optimization Script
 * Tối ưu hóa hình ảnh để giảm 70KB như Lighthouse đề xuất
 */

class ImageOptimizer {
    private $imageDir;
    private $optimizedDir;
    private $totalSavings = 0;
    private $processedFiles = 0;

    public function __construct($imageDir = 'public/img') {
        $this->imageDir = $imageDir;
        $this->optimizedDir = $imageDir . '/optimized';
        
        // Tạo thư mục optimized nếu chưa có
        if (!is_dir($this->optimizedDir)) {
            mkdir($this->optimizedDir, 0755, true);
        }
    }

    public function optimizeAll() {
        echo "🖼️ Bắt đầu tối ưu hóa hình ảnh...\n";
        
        $this->scanAndOptimize($this->imageDir);
        
        echo "\n✅ Hoàn thành tối ưu hóa!\n";
        echo "📊 Tổng số file đã xử lý: {$this->processedFiles}\n";
        echo "💾 Tổng dung lượng tiết kiệm: " . $this->formatBytes($this->totalSavings) . "\n";
    }

    private function scanAndOptimize($dir) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . '/' . $file;
            
            if (is_dir($filePath)) {
                $this->scanAndOptimize($filePath);
            } else {
                $this->optimizeImage($filePath);
            }
        }
    }

    private function optimizeImage($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return;
        }

        $originalSize = filesize($filePath);
        $relativePath = str_replace($this->imageDir . '/', '', $filePath);
        $optimizedPath = $this->optimizedDir . '/' . $relativePath;
        
        // Tạo thư mục con nếu cần
        $optimizedDir = dirname($optimizedPath);
        if (!is_dir($optimizedDir)) {
            mkdir($optimizedDir, 0755, true);
        }

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $this->optimizeJpeg($filePath, $optimizedPath);
                break;
            case 'png':
                $this->optimizePng($filePath, $optimizedPath);
                break;
            case 'gif':
                $this->optimizeGif($filePath, $optimizedPath);
                break;
        }

        if (file_exists($optimizedPath)) {
            $optimizedSize = filesize($optimizedPath);
            $savings = $originalSize - $optimizedSize;
            
            if ($savings > 0) {
                $this->totalSavings += $savings;
                $this->processedFiles++;
                
                echo "✅ {$relativePath}: " . $this->formatBytes($originalSize) . 
                     " → " . $this->formatBytes($optimizedSize) . 
                     " (Tiết kiệm: " . $this->formatBytes($savings) . ")\n";
            }
        }
    }

    private function optimizeJpeg($inputPath, $outputPath) {
        $image = imagecreatefromjpeg($inputPath);
        if ($image === false) return;

        // Tối ưu hóa JPEG với quality 85
        imagejpeg($image, $outputPath, 85);
        imagedestroy($image);
    }

    private function optimizePng($inputPath, $outputPath) {
        $image = imagecreatefrompng($inputPath);
        if ($image === false) return;

        // Tối ưu hóa PNG
        imagepng($image, $outputPath, 6); // Compression level 6
        imagedestroy($image);
    }

    private function optimizeGif($inputPath, $outputPath) {
        // Copy GIF vì không thể tối ưu hóa nhiều
        copy($inputPath, $outputPath);
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function generateWebP() {
        echo "\n🔄 Tạo WebP versions...\n";
        
        $this->scanAndConvertToWebP($this->imageDir);
        
        echo "✅ Hoàn thành tạo WebP!\n";
    }

    private function scanAndConvertToWebP($dir) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . '/' . $file;
            
            if (is_dir($filePath)) {
                $this->scanAndConvertToWebP($filePath);
            } else {
                $this->convertToWebP($filePath);
            }
        }
    }

    private function convertToWebP($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return;
        }

        $relativePath = str_replace($this->imageDir . '/', '', $filePath);
        $webpPath = $this->optimizedDir . '/' . pathinfo($relativePath, PATHINFO_FILENAME) . '.webp';
        
        // Tạo thư mục con nếu cần
        $webpDir = dirname($webpPath);
        if (!is_dir($webpDir)) {
            mkdir($webpDir, 0755, true);
        }

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $image = imagecreatefrompng($filePath);
                break;
            default:
                return;
        }

        if ($image !== false) {
            imagewebp($image, $webpPath, 80); // Quality 80
            imagedestroy($image);
            
            $originalSize = filesize($filePath);
            $webpSize = filesize($webpPath);
            $savings = $originalSize - $webpSize;
            
            if ($savings > 0) {
                echo "✅ {$relativePath} → " . basename($webpPath) . 
                     " (Tiết kiệm: " . $this->formatBytes($savings) . ")\n";
            }
        }
    }
}

// Chạy script
if (php_sapi_name() === 'cli') {
    $optimizer = new ImageOptimizer();
    $optimizer->optimizeAll();
    $optimizer->generateWebP();
} else {
    echo "Script này chỉ chạy được từ command line.\n";
    echo "Sử dụng: php optimize-images.php\n";
}
?>
