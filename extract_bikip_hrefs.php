<?php
/**
 * Script để extract tất cả href từ file bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110.html
 */

$htmlFile = __DIR__ . '/bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110.html';
$outputFile = __DIR__ . '/bikip_hrefs_list.txt';

if (!file_exists($htmlFile)) {
    die("File không tồn tại: {$htmlFile}\n");
}

echo "Đang đọc file HTML...\n";
$html = file_get_contents($htmlFile);

// Parse HTML
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($dom);

// Tìm tất cả các thẻ <a> có thuộc tính href
$links = $xpath->query('//a[@href]');

$hrefs = [];
foreach ($links as $link) {
    if ($link instanceof DOMElement) {
        $href = $link->getAttribute('href');
        if (!empty($href)) {
            // Loại bỏ các href không hợp lệ hoặc chỉ là anchor (#)
            if ($href !== '#' && $href !== '/' && strpos($href, '#') !== 0) {
                $hrefs[] = $href;
            }
        }
    }
}

// Loại bỏ trùng lặp và sắp xếp
$uniqueHrefs = array_unique($hrefs);
sort($uniqueHrefs);

// Lưu vào file
$content = implode("\n", $uniqueHrefs);
file_put_contents($outputFile, $content);

echo "Hoàn thành!\n";
echo "Tổng số href tìm thấy: " . count($hrefs) . "\n";
echo "Số href unique: " . count($uniqueHrefs) . "\n";
echo "Đã lưu vào: {$outputFile}\n";

