<?php
/**
 * Script để crawl HTML và tải các ảnh từ thẻ có class "bg-white border-radius-4 py-3"
 * về thư mục /var/www/xs-clone.code/public/media/thumb/So-mo/
 */

// Đọc danh sách URL
$hrefsFile = __DIR__ . '/hrefs_list.txt';
$urls = file($hrefsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$outputDir = __DIR__ . '/public/media/thumb/So-mo/';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Lấy danh sách file ảnh đã có
$existingImages = [];
if (is_dir($outputDir)) {
    $files = glob($outputDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $existingImages[basename($file)] = true;
        }
    }
}

$total = count($urls);
$totalImages = 0;
$downloaded = 0;
$skipped = 0;
$failed = 0;

echo "Bắt đầu crawl và tải ảnh từ {$total} URL...\n\n";

foreach ($urls as $index => $url) {
    $index++;
    
    // Bỏ qua dòng trống hoặc URL không hợp lệ
    $url = trim($url);
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        continue;
    }
    
    echo "[{$index}/{$total}] Đang crawl: {$url}\n";
    
    // Crawl HTML
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($html)) {
        echo "  ❌ Lỗi: HTTP {$httpCode} hoặc HTML rỗng\n";
        $failed++;
        continue;
    }
    
    // Parse HTML
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    
    // Tìm thẻ có class "bg-white border-radius-4 py-3"
    $contentDivs = $xpath->query('//div[contains(@class, "bg-white") and contains(@class, "border-radius-4") and contains(@class, "py-3")]');
    
    if ($contentDivs->length === 0) {
        echo "  ⚠️  Không tìm thấy thẻ content\n";
        continue;
    }
    
    $contentDiv = $contentDivs->item(0);
    
    // Tìm tất cả các thẻ img trong thẻ content
    $images = $xpath->query('.//img', $contentDiv);
    
    if ($images->length === 0) {
        echo "  ⚠️  Không tìm thấy ảnh\n";
        continue;
    }
    
    echo "  📷 Tìm thấy {$images->length} ảnh\n";
    $totalImages += $images->length;
    
    $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
    
    foreach ($images as $img) {
        // Kiểm tra xem có phải DOMElement không
        if (!($img instanceof DOMElement)) {
            continue;
        }
        
        $src = $img->getAttribute('src');
        
        if (empty($src)) {
            continue;
        }
        
        // Convert relative URL thành absolute URL
        if (strpos($src, 'http') !== 0) {
            if (strpos($src, '/') === 0) {
                $src = $baseUrl . $src;
            } else {
                $src = $baseUrl . '/' . $src;
            }
        }
        
        // Lấy tên file từ URL - giữ nguyên tên file gốc
        $imageUrl = parse_url($src, PHP_URL_PATH);
        $filename = basename($imageUrl);
        
        // Đảm bảo giữ nguyên tên file (bao gồm cả chữ hoa/thường)
        // Không thay đổi tên file, giữ nguyên như trong src
        
        // Bỏ qua nếu không có extension ảnh
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            continue;
        }
        
        // Kiểm tra xem file đã tồn tại chưa (case-sensitive)
        if (isset($existingImages[$filename])) {
            echo "    ⏭️  Bỏ qua (đã có): {$filename}\n";
            $skipped++;
            continue;
        }
        
        // Kiểm tra thêm với case-insensitive (để tránh trùng tên khác case)
        $filenameLower = strtolower($filename);
        $found = false;
        foreach ($existingImages as $existingName => $value) {
            if (strtolower($existingName) === $filenameLower) {
                echo "    ⏭️  Bỏ qua (đã có - khác case): {$filename} (đã có: {$existingName})\n";
                $skipped++;
                $found = true;
                break;
            }
        }
        if ($found) {
            continue;
        }
        
        // Tải ảnh
        echo "    ⬇️  Đang tải: {$filename}\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $src);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($imageData)) {
            $outputFile = $outputDir . $filename;
            if (file_put_contents($outputFile, $imageData)) {
                $existingImages[$filename] = true;
                echo "      ✅ Đã tải: {$filename}\n";
                $downloaded++;
            } else {
                echo "      ❌ Lỗi khi lưu file: {$filename}\n";
                $failed++;
            }
        } else {
            echo "      ❌ Lỗi khi tải: {$filename} (HTTP {$httpCode})\n";
            $failed++;
        }
        
        // Nghỉ một chút để tránh quá tải
        usleep(500000); // 0.5 giây
    }
    
    // Nghỉ 1 giây giữa các URL
    sleep(1);
}

echo "\n";
echo "Hoàn thành!\n";
echo "Tổng số ảnh tìm thấy: {$totalImages}\n";
echo "Đã tải: {$downloaded}\n";
echo "Bỏ qua (đã có): {$skipped}\n";
echo "Thất bại: {$failed}\n";
echo "Tổng URL: {$total}\n";

