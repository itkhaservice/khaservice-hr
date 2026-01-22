<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No data provided']);
    exit;
}

$month = (int)$input['month'];
$year = (int)$input['year'];
$project_id = (int)$input['project_id'];
$changes = $input['changes']; // Array of {emp_id, day, symbol, ot}

// 1. Security Check: Is User Allowed for this Project?
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL' && !in_array($project_id, $allowed_projs)) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền chấm công cho dự án này.']);
    exit;
}

// 2. Lock Check: Is this month locked?
// Check Global Lock (Project 0) or Specific Project Lock
$lock = db_fetch_row("SELECT is_locked FROM attendance_locks WHERE month = ? AND year = ? AND (project_id = 0 OR project_id = ?) AND is_locked = 1 LIMIT 1", 
                     [$month, $year, $project_id]);

if ($lock) {
    echo json_encode(['status' => 'error', 'message' => 'Tháng chấm công này đã bị KHOÁ. Không thể chỉnh sửa.']);
    exit;
}

// 3. Process Changes
$success_count = 0;
foreach ($changes as $c) {
    $emp_id = (int)$c['emp_id'];
    $day = (int)$c['day'];
    $symbol = strtoupper(trim($c['symbol'])); // Ký hiệu luôn viết hoa
    $ot = (float)$c['ot'];
    
    $date = "$year-$month-$day";
    
    // Validate Symbol (Optional: Check against attendance_symbols table)
    // For speed, we just allow saving what they type, or strictly filter.
    // Let's allow saving, but maybe warn later.
    
    // Check existing
    $existing = db_fetch_row("SELECT id, timekeeper_symbol, overtime_hours FROM attendance WHERE employee_id = ? AND date = ?", [$emp_id, $date]);
    
    if ($existing) {
        // Logging for Audit
        if ($existing['timekeeper_symbol'] !== $symbol) {
            db_query("INSERT INTO attendance_logs (employee_id, project_id, attendance_date, old_value, new_value, field_type, changed_by) VALUES (?, ?, ?, ?, ?, 'symbol', ?)", 
                     [$emp_id, $project_id, $date, $existing['timekeeper_symbol'], $symbol, $_SESSION['user_id']]);
        }
        if ((float)$existing['overtime_hours'] !== $ot) {
            db_query("INSERT INTO attendance_logs (employee_id, project_id, attendance_date, old_value, new_value, field_type, changed_by) VALUES (?, ?, ?, ?, ?, 'ot', ?)", 
                     [$emp_id, $project_id, $date, $existing['overtime_hours'], $ot, $_SESSION['user_id']]);
        }

        // Update
        if ($symbol === '' && $ot == 0) {
             db_query("UPDATE attendance SET timekeeper_symbol = NULL, overtime_hours = 0, is_manual_import = 0 WHERE id = ?", [$existing['id']]);
        } else {
             db_query("UPDATE attendance SET project_id = ?, timekeeper_symbol = ?, overtime_hours = ?, is_manual_import = 0 WHERE id = ?", [$project_id, $symbol, $ot, $existing['id']]);
        }
    } else {
        // Insert only if there is data
        if ($symbol !== '' || $ot > 0) {
            // Log as new entry
            db_query("INSERT INTO attendance_logs (employee_id, project_id, attendance_date, old_value, new_value, field_type, changed_by) VALUES (?, ?, ?, '', ?, 'symbol', ?)", 
                     [$emp_id, $project_id, $date, $symbol, $_SESSION['user_id']]);
            if ($ot > 0) {
                db_query("INSERT INTO attendance_logs (employee_id, project_id, attendance_date, old_value, new_value, field_type, changed_by) VALUES (?, ?, ?, '0', ?, 'ot', ?)", 
                         [$emp_id, $project_id, $date, $ot, $_SESSION['user_id']]);
            }

            db_query("INSERT INTO attendance (employee_id, project_id, date, timekeeper_symbol, overtime_hours, is_manual_import) VALUES (?, ?, ?, ?, ?, 0)", 
                     [$emp_id, $project_id, $date, $symbol, $ot]);
        }
    }
    $success_count++;
}

echo json_encode(['status' => 'success', 'message' => "Đã lưu thành công $success_count mục."]);
?>