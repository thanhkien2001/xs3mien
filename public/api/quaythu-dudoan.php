<?php
/**
 * API để lưu/lấy kết quả quay thử dự đoán
 * Mỗi bài viết (slug) có file JSON riêng
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Thư mục lưu trữ
$storageDir = __DIR__ . '/../cache/quaythu-dudoan/';

// Đảm bảo thư mục tồn tại
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// Lấy action từ request
$action = $_GET['action'] ?? 'get';
$slug = $_GET['slug'] ?? null;

// Validate slug
if (!$slug) {
    echo json_encode(['success' => false, 'message' => 'Slug is required']);
    exit;
}

// Sanitize slug để tránh path traversal
$slug = basename($slug);
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (empty($slug)) {
    echo json_encode(['success' => false, 'message' => 'Invalid slug']);
    exit;
}

$filename = $storageDir . $slug . '.json';

/**
 * GET: Lấy kết quả theo slug
 */
if ($action === 'get') {
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $data = json_decode($content, true);
        
        echo json_encode([
            'success' => true,
            'slug' => $slug,
            'results' => $data['results'] ?? []
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'slug' => $slug,
            'results' => []
        ]);
    }
    exit;
}

/**
 * POST: Lưu kết quả
 */
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['results'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    // Kiểm tra xem file đã tồn tại chưa
    // Nếu đã tồn tại thì không ghi đè (giữ nguyên kết quả đầu tiên)
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $existingData = json_decode($content, true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Already exists',
            'slug' => $slug,
            'results' => $existingData['results'] ?? []
        ]);
        exit;
    }
    
    // Lưu kết quả mới
    $saveData = [
        'slug' => $slug,
        'created_at' => date('Y-m-d H:i:s'),
        'results' => $data['results']
    ];
    
    if (file_put_contents($filename, json_encode($saveData, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => 'Saved successfully',
            'slug' => $slug,
            'results' => $saveData['results']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save'
        ]);
    }
    exit;
}

/**
 * DELETE: Xóa kết quả (dành cho admin)
 */
if ($action === 'clear') {
    if (file_exists($filename)) {
        if (unlink($filename)) {
            echo json_encode([
                'success' => true,
                'message' => 'Cleared successfully',
                'slug' => $slug
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
    }
    exit;
}

// Action không hợp lệ
echo json_encode(['success' => false, 'message' => 'Invalid action']);

