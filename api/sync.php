<?php
/**
 * API ĐỒNG BỘ DỮ LIỆU (HOSTING -> LOCAL)
 * Phiên bản: 2.0 (Hỗ trợ phân trang & chọn bảng)
 */
require_once '../config/db.php';

// Tăng giới hạn bộ nhớ và thời gian thực thi cho tác vụ nặng
ini_set('memory_limit', '256M');
set_time_limit(300);

header('Content-Type: application/json');

// 1. Cấu hình bảo mật
$SECRET_KEY = "KHA_SERVICE_SECURE_SYNC_2026"; 

// 2. Kiểm tra quyền
$received_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['key'] ?? '';
if ($received_key !== $SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API Key']);
    exit;
}

// 3. Nhận tham số
$table = isset($_GET['table']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['table']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$last_sync = isset($_GET['last_sync']) ? $_GET['last_sync'] : '2000-01-01 00:00:00';

// Danh sách bảng được phép đồng bộ
$allowed_tables = [
    'employees', 'projects', 'departments', 'positions',
    'attendance', 'attendance_logs', 'attendance_locks',
    'payroll', 'employee_salaries',
    'documents', 'document_settings',
    'settings', 'users'
];

if (!in_array($table, $allowed_tables)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Table not found or not allowed',
        'allowed_tables' => $allowed_tables
    ]);
    exit;
}

// 4. Truy vấn dữ liệu
try {
    // Đếm tổng số dòng thay đổi
    $count_sql = "SELECT COUNT(*) as total FROM `$table`";
    $data_sql = "SELECT * FROM `$table`";
    $params = [];

    // Kiểm tra cột thời gian để sync incremental (nếu có)
    // Ưu tiên updated_at, sau đó đến created_at
    $cols = db_fetch_all("SHOW COLUMNS FROM `$table`");
    $col_names = array_column($cols, 'Field');
    
    $time_col = null;
    if (in_array('updated_at', $col_names)) $time_col = 'updated_at';
    elseif (in_array('created_at', $col_names)) $time_col = 'created_at';

    if ($time_col && $last_sync != 'full') {
        $count_sql .= " WHERE `$time_col` > ?";
        $data_sql .= " WHERE `$time_col` > ?";
        $params[] = $last_sync;
    }

    // Thực hiện đếm
    $total_row = db_fetch_row($count_sql, $params);
    $total = $total_row['total'];

    // Lấy dữ liệu với Limit/Offset
    $data_sql .= " LIMIT $limit OFFSET $offset";
    $data = db_fetch_all($data_sql, $params);

    echo json_encode([
        'status' => 'success',
        'table' => $table,
        'total_changes' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'count' => count($data),
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>