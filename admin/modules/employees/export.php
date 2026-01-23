<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Check permissions
if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

// Set Headers for Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Danh_sach_nhan_vien_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (Excel support for Vietnamese)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Row
fputcsv($output, [
    'Mã NV', 
    'Họ và Tên', 
    'Phòng ban', 
    'Chức vụ', 
    'Dự án hiện tại', 
    'SĐT', 
    'Email', 
    'CCCD', 
    'Ngày vào làm', 
    'Trạng thái'
]);

// Build Query (Same logic as index, but without pagination limits)
// Reuse filters if passed (optional, for now export ALL active/filtered)
$where = "WHERE 1=1";
$params = [];

// Apply permissions
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    if (empty($allowed_projs)) {
        $where .= " AND 1=0";
    } else {
        $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
        $where .= " AND e.current_project_id IN ($in_placeholder)";
        $params = array_merge($params, $allowed_projs);
    }
}

// Data Query
$sql = "SELECT e.*, d.name as dept_name, p.name as proj_name, pos.name as pos_name
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN projects p ON e.current_project_id = p.id 
        LEFT JOIN positions pos ON e.position_id = pos.id
        $where 
        ORDER BY d.stt ASC, pos.stt ASC, e.fullname ASC";

$employees = db_fetch_all($sql, $params);

// Output Data
foreach ($employees as $row) {
    fputcsv($output, [
        $row['code'],
        $row['fullname'],
        $row['dept_name'],
        $row['pos_name'],
        $row['proj_name'],
        $row['phone'],
        $row['email'],
        $row['identity_card'] . " ", // Add space to prevent scientific notation in Excel
        $row['start_date'],
        $row['status'] == 'working' ? 'Đang làm việc' : 'Đã nghỉ'
    ]);
}

fclose($output);
exit;
?>
