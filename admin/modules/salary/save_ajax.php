<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

if (!has_permission('view_salary')) {
    echo json_encode(['status' => 'error', 'message' => 'Từ chối quyền truy cập.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['status' => 'error', 'message' => 'Dữ liệu trống.']); exit; }

$emp_id = (int)$input['emp_id'];
$basic = (float)$input['basic_salary'];
$insurance = (float)$input['insurance_salary'];
$allowance = (float)$input['allowance_total'];
$adv_default = (float)$input['salary_advances_default'];
$tax_p = (float)$input['income_tax_percent'];

// 1. Cập nhật Cấu hình Định mức
$sql_fix = "INSERT INTO employee_salaries (employee_id, basic_salary, insurance_salary, allowance_total, salary_advances_default, income_tax_percent) 
            VALUES (?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE basic_salary = ?, insurance_salary = ?, allowance_total = ?, salary_advances_default = ?, income_tax_percent = ?";
db_query($sql_fix, [$emp_id, $basic, $insurance, $allowance, $adv_default, $tax_p, $basic, $insurance, $allowance, $adv_default, $tax_p]);

// 2. Cập nhật Biến động Tháng (Chỉ Đoàn phí và Thưởng)
if (isset($input['month']) && isset($input['year'])) {
    $month = (int)$input['month'];
    $year = (int)$input['year'];
    $union = (float)$input['union_fee'];
    $bonus = (float)$input['bonus_amount'];

    // Tiền tạm ứng trong bảng payroll sẽ mặc định lấy từ cấu hình cố định nếu không nhập riêng
    $sql_var = "INSERT INTO payroll (employee_id, month, year, union_fee, bonus_amount, salary_advances) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE union_fee = ?, bonus_amount = ?, salary_advances = ?";
    
    db_query($sql_var, [$emp_id, $month, $year, $union, $bonus, $adv_default, $union, $bonus, $adv_default]);
}

echo json_encode(['status' => 'success', 'message' => 'Đã lưu cấu hình lương thành công!']);