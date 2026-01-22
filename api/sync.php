<?php
/**
 * API ĐỒNG BỘ DỮ LIỆU (HOSTING -> LOCAL SERVER)
 * Bảo mật bằng Secret API Key
 */
require_once '../config/db.php';

header('Content-Type: application/json');

// 1. Cấu hình bảo mật (Nên để trong bảng settings hoặc .env)
$SECRET_KEY = "KHA_SERVICE_SECURE_SYNC_2026"; 

// 2. Kiểm tra quyền truy cập
$headers = getallheaders();
$received_key = $headers['X-API-KEY'] ?? $_GET['key'] ?? '';

if ($received_key !== $SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Truy cập bị từ chối. API Key không hợp lệ.']);
    exit;
}

// 3. Lấy mốc thời gian đồng bộ cuối cùng từ phía máy chủ công ty gửi lên
$last_sync = $_GET['last_sync'] ?? '2000-01-01 00:00:00';

$tables_to_sync = [
    'employees', 
    'projects', 
    'attendance', 
    'attendance_logs', 
    'leave_requests', 
    'payroll', 
    'employee_status_history',
    'documents'
];

$sync_data = [];

foreach ($tables_to_sync as $table) {
    // Tìm các dòng mới thêm hoặc mới sửa kể từ lần sync cuối
    // Giả định các bảng có cột created_at hoặc updated_at
    // Nếu bảng không có, ta lấy toàn bộ (cần tối ưu sau)
    $sql = "SELECT * FROM $table WHERE 1=1";
    
    // Kiểm tra xem bảng có cột thời gian không
    $check_col = db_fetch_row("SHOW COLUMNS FROM $table LIKE 'created_at'");
    if ($check_col) {
        $sql .= " AND created_at > ?";
    }
    
    $sync_data[$table] = db_fetch_all($sql, $check_col ? [$last_sync] : []);
}

// 4. Trả về dữ liệu
echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => $sync_data
]);
