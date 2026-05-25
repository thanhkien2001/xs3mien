<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối Redis
$redis = new Redis();
try {
    $redis->connect('127.0.0.1', 6379);
} catch (Exception $e) {
    sendSSEMessage(['error' => 'Kết nối Redis thất bại: ' . $e->getMessage()]);
    exit;
}

// Hàm gửi thông điệp SSE
function sendSSEMessage($data, $event = 'message')
{
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Vòng lặp chính để kiểm tra cập nhật
while (true) {
    try {
        // Lấy dữ liệu từ Redis
        $data = $redis->get('xs_live_' . date('Y-m-d'));
        if ($data) {
            $lotteryData = json_decode($data, true);
            if ($lotteryData) {
                // Gửi dữ liệu đến client
                sendSSEMessage($lotteryData);
            } else {
                sendSSEMessage(['error' => 'Dữ liệu Redis không hợp lệ']);
            }
        } else {
            sendSSEMessage(['status' => 0, 'results' => []]);
        }
    } catch (Exception $e) {
        sendSSEMessage(['error' => 'Lỗi Redis: ' . $e->getMessage()]);
    }

    // Kiểm tra kết nối client
    if (connection_aborted()) {
        break;
    }

    // Nghỉ 1 giây trước khi kiểm tra tiếp
    sleep(1);
}
