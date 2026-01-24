<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No data provided']);
    exit;
}

$month = (int)$input['month'];
$year = (int)$input['year'];
$project_id = (int)$input['project_id'];

if ($project_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dự án không hợp lệ']);
    exit;
}

// 1. Check Permission
if (!is_admin() && !has_permission('edit_attendance')) {
    echo json_encode(['status' => 'error', 'message' => 'Không có quyền thực hiện.']);
    exit;
}

// 2. Check Lock
$lock = db_fetch_row("SELECT is_locked FROM attendance_locks WHERE month = ? AND year = ? AND (project_id = 0 OR project_id = ?) AND is_locked = 1 LIMIT 1", 
                     [$month, $year, $project_id]);

if ($lock) {
    echo json_encode(['status' => 'error', 'message' => 'Bảng công đã bị KHÓA.']);
    exit;
}

// 3. Find employees belonging to this project currently
// We only sync records for employees who are currently assigned to this project
$employees = db_fetch_all("SELECT id FROM employees WHERE current_project_id = ? AND status = 'working'", [$project_id]);
$emp_ids = array_column($employees, 'id');

if (empty($emp_ids)) {
    echo json_encode(['status' => 'success', 'message' => 'Không có nhân viên nào trong dự án này để đồng bộ.', 'count' => 0]);
    exit;
}

// 4. Update Attendance Records
// Update attendance records for these employees, in this month/year, 
// where the project_id is DIFFERENT from the current project_id.
$emp_list = implode(',', $emp_ids);
$sql = "UPDATE attendance 
        SET project_id = ? 
        WHERE employee_id IN ($emp_list) 
          AND MONTH(date) = ? 
          AND YEAR(date) = ? 
          AND project_id != ?";

try {
    $stmt = db_query($sql, [$project_id, $month, $year, $project_id]);
    $count = $stmt->rowCount();
    
    echo json_encode([
        'status' => 'success', 
        'message' => "Đã đồng bộ $count dòng chấm công về dự án hiện tại.", 
        'count' => $count
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi Database: ' . $e->getMessage()]);
}
?>