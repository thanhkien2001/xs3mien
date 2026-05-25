<?php
/**
 * Script để crawl HTML từ các URL trong hrefs_list.txt
 * Chỉ lấy phần content giống như file mẫu
 */

// Đọc danh sách URL
$hrefsFile = __DIR__ . '/hrefs_list.txt';
$urls = file($hrefsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$outputDir = __DIR__ . '/public/so-mo/';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$total = count($urls);
$success = 0;
$failed = 0;

echo "Bắt đầu crawl {$total} URL...\n\n";

foreach ($urls as $index => $url) {
    $index++;
    
    // Bỏ qua dòng trống hoặc URL không hợp lệ
    $url = trim($url);
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        continue;
    }
    
    // Extract tên file từ URL
    $path = parse_url($url, PHP_URL_PATH);
    $filename = basename($path);
    
    // Nếu không có .html, bỏ qua
    if (strpos($filename, '.html') === false) {
        continue;
    }
    
    $outputFile = $outputDir . $filename;
    
    // Nếu file đã tồn tại, bỏ qua
    if (file_exists($outputFile)) {
        echo "[{$index}/{$total}] Đã tồn tại: {$filename}\n";
        $success++;
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
    
    // Parse HTML và chỉ lấy phần <main>
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    
    // Tìm phần <main>
    $mainNodes = $xpath->query('//main');
    
    if ($mainNodes->length === 0) {
        echo "  ❌ Không tìm thấy thẻ <main>\n";
        $failed++;
        continue;
    }
    
    $mainNode = $mainNodes->item(0);
    
    // Xóa các div có class col-xl-200 và col-xl-300
    $divsToRemove = $xpath->query('//div[contains(@class, "col-xl-200") or contains(@class, "col-xl-300")]', $mainNode);
    foreach ($divsToRemove as $div) {
        if ($div->parentNode) {
            $div->parentNode->removeChild($div);
        }
    }
    
    $mainHtml = $dom->saveHTML($mainNode);
    
    // Lưu file
    file_put_contents($outputFile, $mainHtml);
    
    echo "  ✅ Đã lưu: {$filename}\n";
    $success++;
    
    // Nghỉ 1 giây để tránh quá tải server
    sleep(1);
}

echo "\n";
echo "Hoàn thành!\n";
echo "Thành công: {$success}\n";
echo "Thất bại: {$failed}\n";
echo "Tổng: {$total}\n";

