<?php
/**
 * Script để crawl HTML từ các URL trong bikip_hrefs_list.txt
 * Lấy nội dung trong thẻ col-xl-610, tải ảnh và tạo file .phtml
 */

$hrefsFile = __DIR__ . '/bikip_hrefs_list.txt';
$urls = file($hrefsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$outputDir = __DIR__ . '/app/views/bikip/';
$imageDir = __DIR__ . '/public/media/kinh-nghiem-lo-de/';

// Tạo thư mục nếu chưa có
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}

// Lấy danh sách file ảnh đã có
$existingImages = [];
if (is_dir($imageDir)) {
    $files = glob($imageDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $existingImages[basename($file)] = true;
        }
    }
}

$total = count($urls);
$success = 0;
$failed = 0;
$skippedImages = 0;
$downloadedImages = 0;

echo "Bắt đầu crawl {$total} URL...\n\n";

foreach ($urls as $index => $url) {
    $index++;
    
    // Bỏ qua dòng trống hoặc URL không hợp lệ
    $url = trim($url);
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        continue;
    }
    
    // Lấy slug từ URL để tạo tên file
    $urlPath = parse_url($url, PHP_URL_PATH);
    $slug = basename($urlPath, '.html');
    
    if (empty($slug)) {
        echo "[{$index}/{$total}] ❌ Không thể lấy slug từ URL: {$url}\n";
        $failed++;
        continue;
    }
    
    echo "[{$index}/{$total}] Đang crawl: {$url}\n";
    echo "  Slug: {$slug}\n";
    
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
    
    // Tìm thẻ có class col-xl-610
    $contentDivs = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " col-xl-610 ")]');
    
    if ($contentDivs->length === 0) {
        echo "  ❌ Không tìm thấy thẻ col-xl-610\n";
        $failed++;
        continue;
    }
    
    $contentDiv = $contentDivs->item(0);
    
    // Tìm tất cả các ảnh trong nội dung
    $images = $xpath->query('.//img', $contentDiv);
    $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
    
    echo "  📷 Tìm thấy {$images->length} ảnh\n";
    
    foreach ($images as $img) {
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
        
        // Bỏ qua nếu không có extension ảnh
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            continue;
        }
        
        // Kiểm tra xem file đã tồn tại chưa (case-sensitive)
        if (isset($existingImages[$filename])) {
            echo "    ⏭️  Bỏ qua (đã có): {$filename}\n";
            $skippedImages++;
            continue;
        }
        
        // Kiểm tra thêm với case-insensitive
        $filenameLower = strtolower($filename);
        $found = false;
        foreach ($existingImages as $existingName => $value) {
            if (strtolower($existingName) === $filenameLower) {
                echo "    ⏭️  Bỏ qua (đã có - khác case): {$filename} (đã có: {$existingName})\n";
                $skippedImages++;
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
            $outputFile = $imageDir . $filename;
            if (file_put_contents($outputFile, $imageData)) {
                $existingImages[$filename] = true;
                echo "      ✅ Đã tải: {$filename}\n";
                $downloadedImages++;
            } else {
                echo "      ❌ Lỗi khi lưu file: {$filename}\n";
            }
        } else {
            echo "      ❌ Lỗi khi tải: {$filename} (HTTP {$httpCode})\n";
        }
        
        // Nghỉ một chút để tránh quá tải
        usleep(500000); // 0.5 giây
    }
    
    // Lấy HTML của thẻ col-xl-610 (bao gồm toàn bộ nội dung bên trong)
    $contentHtml = $dom->saveHTML($contentDiv);
    
    // Wrap trong main và container như demo.html
    // Demo.html có cấu trúc: main > container > row (breadcrumb) > row (col-xl-610)
    // Nhưng chúng ta chỉ cần lấy phần col-xl-610 và wrap nó
    $finalHtml = '<main>' . "\n" .
        '    <div class="container">' . "\n" .
        '        <div class="row">' . "\n" .
        $contentHtml . "\n" .
        '        </div>' . "\n" .
        '    </div>' . "\n" .
        '</main>';
    
    // Lưu file .phtml
    $outputFile = $outputDir . $slug . '.phtml';
    file_put_contents($outputFile, $finalHtml);
    
    echo "  ✅ Đã lưu: {$slug}.phtml\n";
    $success++;
    
    // Nghỉ 1 giây giữa các URL
    sleep(1);
}

echo "\n";
echo "Hoàn thành!\n";
echo "Thành công: {$success}\n";
echo "Thất bại: {$failed}\n";
echo "Ảnh đã tải: {$downloadedImages}\n";
echo "Ảnh bỏ qua (đã có): {$skippedImages}\n";
echo "Tổng URL: {$total}\n";

