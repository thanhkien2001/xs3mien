<?php
/**
 * Script để copy tất cả file HTML từ public/so-mo/ sang app/views/giacmo/
 * và đổi đuôi thành .phtml
 */

$sourceDir = __DIR__ . '/public/so-mo/';
$targetDir = __DIR__ . '/app/views/giacmo/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$files = glob($sourceDir . '*.html');

$total = count($files);
$success = 0;
$failed = 0;
$skipped = 0;

echo "Bắt đầu copy {$total} file...\n\n";

foreach ($files as $index => $sourceFile) {
    $index++;
    $filename = basename($sourceFile);
    $targetFilename = str_replace('.html', '.phtml', $filename);
    $targetFile = $targetDir . $targetFilename;
    
    echo "[{$index}/{$total}] Đang xử lý: {$filename}\n";
    
    // Kiểm tra file nguồn
    if (!file_exists($sourceFile)) {
        echo "  ❌ File nguồn không tồn tại\n";
        $failed++;
        continue;
    }
    
    // Kiểm tra file đích đã tồn tại chưa
    if (file_exists($targetFile)) {
        echo "  ⏭️  File đích đã tồn tại, bỏ qua\n";
        $skipped++;
        continue;
    }
    
    // Copy file
    if (copy($sourceFile, $targetFile)) {
        echo "  ✅ Đã copy: {$filename} -> {$targetFilename}\n";
        $success++;
    } else {
        echo "  ❌ Lỗi khi copy file\n";
        $failed++;
    }
}

echo "\n";
echo "Hoàn thành!\n";
echo "Thành công: {$success}\n";
echo "Bỏ qua (đã tồn tại): {$skipped}\n";
echo "Thất bại: {$failed}\n";
echo "Tổng: {$total}\n";

