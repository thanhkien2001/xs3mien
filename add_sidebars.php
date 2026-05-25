<?php
/**
 * Script để thêm sidebar vào tất cả các file .phtml trong folder app/views/bikip/
 */

$bikipDir = __DIR__ . '/app/views/bikip/';
$files = glob($bikipDir . '*.phtml');

$sidebarLeft = '            <!-- SIDEBAR LEFT (col-xl-200) -->
            <div class="col-xl-200 mx-lg-3 mx-lg-0 pr-md-3 pr-lg-0 order-2 order-lg-1 font-14">
                <?php $this->partial(\'partials/sidebar_left\'); ?>
            </div>

            <!-- SIDEBAR RIGHT (col-xl-300) -->
            <div class="col-xl-300 mt-3 mt-md-0 order-1 order-lg-2">
                <?php $this->partial(\'partials/sidebar_right\'); ?>
            </div>
';

$total = count($files);
$success = 0;
$failed = 0;
$skipped = 0;

echo "Bắt đầu thêm sidebar vào {$total} file...\n\n";

foreach ($files as $index => $file) {
    $index++;
    $filename = basename($file);
    
    echo "[{$index}/{$total}] Đang xử lý: {$filename}\n";
    
    $content = file_get_contents($file);
    
    if (empty($content)) {
        echo "  ❌ File rỗng\n";
        $failed++;
        continue;
    }
    
    // Kiểm tra xem đã có sidebar chưa
    if (strpos($content, 'col-xl-200') !== false || strpos($content, 'col-xl-300') !== false) {
        echo "  ⏭️  Đã có sidebar, bỏ qua\n";
        $skipped++;
        continue;
    }
    
    // Tìm vị trí đóng thẻ col-xl-610 (sau </div> đóng col-xl-610, trước </div> đóng row)
    // Cấu trúc: </div> (đóng col-xl-610) rồi đến </div> (đóng row) rồi đến </div> (đóng container) rồi đến </main>
    // Cần chèn sidebar sau đóng col-xl-610, trước đóng row
    // Pattern: tìm </div> đóng col-xl-610, sau đó là </div> đóng row
    $pattern = '/(<\/div>\s*)(<\/div>\s*<\/div>\s*<\/main>)/s';
    
    // Tìm vị trí có col-xl-610 và đóng nó
    // Tìm pattern: </div> (đóng col-xl-610) ... </div> (đóng row) ... </div> (đóng container) ... </main>
    if (preg_match('/col-xl-610[^>]*>.*?<\/div>\s*(<\/div>\s*<\/div>\s*<\/main>)/s', $content, $matches)) {
        // Tìm vị trí chính xác: sau </div> đóng col-xl-610, trước </div> đóng row
        // Tìm </div> đóng col-xl-610 (có thể có nhiều </div> bên trong)
        // Cần tìm </div> đóng col-xl-610 (div có class col-xl-610)
        $pattern = '/(<\/div>\s*)(<\/div>\s*<\/div>\s*<\/main>)/s';
        
        // Tìm vị trí: sau khi đóng col-xl-610 (tìm từ cuối lên)
        // Tìm </div> cuối cùng trước </div> đóng row
        $pos = strrpos($content, '</div>');
        if ($pos !== false) {
            // Tìm </div> đóng row (</div> trước </div> đóng container)
            $beforeRowClose = substr($content, 0, $pos);
            $rowClosePos = strrpos($beforeRowClose, '</div>');
            
            if ($rowClosePos !== false) {
                // Vị trí chèn: sau </div> đóng col-xl-610, trước </div> đóng row
                $insertPos = $rowClosePos + strlen('</div>');
                $newContent = substr($content, 0, $insertPos) . "\n" . $sidebarLeft . substr($content, $insertPos);
                
                file_put_contents($file, $newContent);
                echo "  ✅ Đã thêm sidebar\n";
                $success++;
            } else {
                // Fallback: tìm pattern đơn giản hơn
                // Tìm </div> đóng col-xl-610 (sau khi có col-xl-610)
                if (preg_match('/(<\/div>\s*)(<\/div>\s*<\/div>\s*<\/main>)/s', $content, $matches)) {
                    $beforeMatch = substr($content, 0, strpos($content, $matches[0]));
                    if (strpos($beforeMatch, 'col-xl-610') !== false) {
                        $newContent = str_replace(
                            $matches[0],
                            $matches[1] . "\n" . $sidebarLeft . $matches[2],
                            $content
                        );
                        
                        file_put_contents($file, $newContent);
                        echo "  ✅ Đã thêm sidebar\n";
                        $success++;
                    } else {
                        echo "  ❌ Không tìm thấy col-xl-610 trước vị trí này\n";
                        $failed++;
                    }
                } else {
                    echo "  ❌ Không tìm thấy vị trí chèn phù hợp\n";
                    $failed++;
                }
            }
        } else {
            echo "  ❌ Không tìm thấy vị trí chèn phù hợp\n";
            $failed++;
        }
    } else {
        // Fallback: tìm pattern đơn giản
        // Tìm </div> đóng col-xl-610, sau đó là </div> đóng row
        if (preg_match('/(<\/div>\s*)(<\/div>\s*<\/div>\s*<\/main>)/s', $content, $matches)) {
            $beforeMatch = substr($content, 0, strpos($content, $matches[0]));
            if (strpos($beforeMatch, 'col-xl-610') !== false) {
                $newContent = str_replace(
                    $matches[0],
                    $matches[1] . "\n" . $sidebarLeft . $matches[2],
                    $content
                );
                
                file_put_contents($file, $newContent);
                echo "  ✅ Đã thêm sidebar\n";
                $success++;
            } else {
                echo "  ❌ Không tìm thấy col-xl-610 trước vị trí này\n";
                $failed++;
            }
        } else {
            echo "  ❌ Không tìm thấy vị trí chèn phù hợp\n";
            $failed++;
        }
    }
}

echo "\n";
echo "Hoàn thành!\n";
echo "Thành công: {$success}\n";
echo "Bỏ qua (đã có): {$skipped}\n";
echo "Thất bại: {$failed}\n";
echo "Tổng: {$total}\n";

