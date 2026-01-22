<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if (!is_admin() && !has_permission('view_salary')) {
    header("Location: /khaservice-hr/404.php?error=no_permission");
    exit;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Xử lý TÍNH LƯƠNG
if (isset($_POST['calculate_payroll'])) {
    // 1. Lấy cài đặt hệ số chung
    $settings_raw = db_fetch_all("SELECT * FROM settings WHERE setting_key LIKE 'insurance_%' OR setting_key = 'union_fee_amount'");
    $g_settings = []; foreach ($settings_raw as $s) $g_settings[$s['setting_key']] = $s['setting_value'];

    // 2. Lấy danh sách nhân viên
    $employees = db_fetch_all("
        SELECT e.id, s.basic_salary, s.insurance_salary, s.allowance_total, s.income_tax_percent, s.salary_advances_default
        FROM employees e 
        LEFT JOIN employee_salaries s ON e.id = s.employee_id 
        WHERE e.status = 'working'" . ($project_id ? " AND e.current_project_id = $project_id" : "")
    );
    
    // Tự động tính công chuẩn của tháng (Tổng ngày - Chủ nhật)
    $work_days_standard = get_standard_working_days($month, $year);

    foreach ($employees as $e) {
        $basic_sal = (float)($e['basic_salary'] ?? 0);
        $ins_sal = (float)($e['insurance_salary'] ?? 0);
        if ($basic_sal <= 0) continue;

        // 3. Đếm ngày công CHI TIẾT theo đúng ký hiệu nghiệp vụ
        $att = db_fetch_row("
            SELECT 
                -- Công làm việc thực tế (X, ĐH, F1, Nb...)
                SUM(CASE 
                    WHEN UPPER(timekeeper_symbol) IN ('X', 'ĐH', 'F1', 'NB') THEN 1.0 
                    WHEN UPPER(timekeeper_symbol) IN ('1/2', '1/F1') THEN 0.5 
                    ELSE 0 
                END) as real_work_days,
                
                -- Công nghỉ hưởng lương (P, CĐ, VR)
                SUM(CASE 
                    WHEN UPPER(timekeeper_symbol) IN ('P', 'CĐ', 'VR') THEN 1.0 
                    WHEN UPPER(timekeeper_symbol) = '1/P' THEN 0.5 
                    ELSE 0 
                END) as paid_leave_days,
                
                -- Công lễ tết (L, T)
                SUM(CASE 
                    WHEN UPPER(timekeeper_symbol) IN ('L', 'T') THEN 1.0 
                    WHEN UPPER(timekeeper_symbol) = '1/LT' THEN 0.5 
                    ELSE 0 
                END) as holiday_paid_days,
                
                -- Công làm ngày lễ (F) - tính x3
                SUM(CASE 
                    WHEN UPPER(timekeeper_symbol) = 'F' THEN 1.0 
                    ELSE 0 
                END) as holiday_work_days,
                
                SUM(overtime_hours) as ot_hours
            FROM attendance 
            WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
        ", [$e['id'], $month, $year]);

        $real_work = (float)($att['real_work_days'] ?? 0);
        $paid_leave = (float)($att['paid_leave_days'] ?? 0);
        $holiday_paid = (float)($att['holiday_paid_days'] ?? 0);
        $holiday_work = (float)($att['holiday_work_days'] ?? 0);
        $ot_hours = (float)($att['ot_hours'] ?? 0);

        // Lấy biến động tháng nếu có
        $p_var = db_fetch_row("SELECT salary_advances, union_fee, bonus_amount FROM payroll WHERE employee_id = ? AND month = ? AND year = ?", [$e['id'], $month, $year]);
        
        $advances = (float)($p_var['salary_advances'] ?? $e['salary_advances_default'] ?? 0);
        $union = (float)($p_var['union_fee'] ?? 0);
        $bonus = (float)($p_var['bonus_amount'] ?? 0);

        // 5. TÍNH TOÁN THEO ĐƠN GIÁ NGÀY CÔNG
        $day_rate = $basic_sal / $work_days_standard;
        
        $salary_actual_work = $real_work * $day_rate;
        $salary_paid_leave = $paid_leave * $day_rate;
        $salary_holiday_paid = $holiday_paid * $day_rate;
        $salary_holiday_work = $holiday_work * ($day_rate * 3); 
        $ot_amount = $ot_hours * ($day_rate / 8 * 1.5); 
        
        $total_income = $salary_actual_work + $salary_paid_leave + $salary_holiday_paid + $salary_holiday_work + $ot_amount + (float)$e['allowance_total'] + $bonus;
        
        // GIẢM TRỪ BẢO HIỂM (Dựa trên Lương hợp đồng)
        $bhxh_p = (float)($g_settings['insurance_bhxh_percent'] ?? 8);
        $bhyt_p = (float)($g_settings['insurance_bhyt_percent'] ?? 1.5);
        $bhtn_p = (float)($g_settings['insurance_bhtn_percent'] ?? 1);
        $bhxh_amount = $ins_sal * (($bhxh_p + $bhyt_p + $bhtn_p) / 100);
        
        if ($union == 0) $union = $ins_sal * 0.01;
        $tax_amount = $total_income * ((float)$e['income_tax_percent'] / 100);
        
        $net_salary = $total_income - $bhxh_amount - $advances - $union - $tax_amount;

        // 6. LƯU KẾT QUẢ
        db_query("INSERT INTO payroll (employee_id, month, year, standard_days, total_work_days, paid_leave_days, holiday_paid_days, holiday_work_days, total_ot_hours, salary_actual, ot_amount, total_allowance, bhxh_amount, salary_advances, union_fee, income_tax, bonus_amount, net_salary) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE standard_days=?, total_work_days=?, paid_leave_days=?, holiday_paid_days=?, holiday_work_days=?, total_ot_hours=?, salary_actual=?, ot_amount=?, total_allowance=?, bhxh_amount=?, salary_advances=?, union_fee=?, income_tax=?, bonus_amount=?, net_salary=?", 
                  [$e['id'], $month, $year, $work_days_standard, $real_work, $paid_leave, $holiday_paid, $holiday_work, $ot_hours, $salary_actual_work, $ot_amount, $e['allowance_total'], $bhxh_amount, $advances, $union, $tax_amount, $bonus, $net_salary,
                   $work_days_standard, $real_work, $paid_leave, $holiday_paid, $holiday_work, $ot_hours, $salary_actual_work, $ot_amount, $e['allowance_total'], $bhxh_amount, $advances, $union, $tax_amount, $bonus, $net_salary]);
    }
    set_toast('success', 'Đã tính toán bảng lương chi tiết cho dự án!');
    header("Location: index.php?month=$month&year=$year&project_id=$project_id");
    exit;
}

$projects = db_fetch_all("SELECT * FROM projects WHERE status = 'active' ORDER BY name ASC");
$payroll_data = db_fetch_all("
    SELECT p.*, e.fullname, e.code, pr.name as proj_name
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN projects pr ON e.current_project_id = pr.id
    WHERE p.month = ? AND p.year = ? " . ($project_id ? " AND e.current_project_id = $project_id" : "") . "
    ORDER BY e.fullname ASC
", [$month, $year]);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Bảng lương tháng <?php echo "$month/$year"; ?></h1>
            <div class="header-actions">
                <form method="POST" style="display:inline;">
                    <button type="submit" name="calculate_payroll" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt"></i> TÍNH CHI TIẾT</button>
                </form>
                <button class="btn btn-secondary btn-sm" onclick="window.open('print_all.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&project_id=<?php echo $project_id; ?>', '_blank')">
                    <i class="fas fa-print"></i> IN PHIẾU LƯƠNG
                </button>
                <a href="config.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&project_id=<?php echo $project_id; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-cog"></i> Cấu hình</a>
            </div>
        </div>

        <form method="GET" class="filter-section" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
            <div style="min-width: 160px;">
                <label style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 6px;">Thời gian</label>
                <div style="display: flex; gap: 6px;">
                    <select name="month" class="form-control" style="width: 80px; height: 38px;" onchange="this.form.submit()">
                        <?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".($i==$month?'selected':'').">T$i</option>"; ?>
                    </select>
                    <select name="year" class="form-control" style="width: 95px; height: 38px;" onchange="this.form.submit()">
                        <?php for($y=2024;$y<=2026;$y++) echo "<option value='$y' ".($y==$year?'selected':'').">$y</option>"; ?>
                    </select>
                </div>
            </div>
            <div style="flex: 1; min-width: 250px;">
                <label style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 6px;">Dự án</label>
                <select name="project_id" class="form-control" onchange="this.form.submit()" style="height: 38px;">
                    <option value="0">-- Tất cả dự án --</option>
                    <?php foreach($projects as $p) echo "<option value='{$p['id']}' ".($p['id']==$project_id?'selected':'').">{$p['name']}</option>"; ?>
                </select>
            </div>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th class="text-center">Công</th>
                            <th class="text-center">Phép</th>
                            <th class="text-center">Lễ</th>
                            <th class="text-center">OT</th>
                            <th>Thưởng</th>
                            <th>Giảm trừ</th>
                            <th style="background: #f0fdf4;">THỰC LĨNH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payroll_data)): ?>
                            <tr><td colspan="8" class="text-center" style="padding: 30px; color: #94a3b8;">Chưa có dữ liệu. Nhấn "TÍNH CHI TIẾT" để bắt đầu.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payroll_data as $p): ?>
                                <tr>
                                    <td><strong><?php echo $p['fullname']; ?></strong><br><small class="text-sub"><?php echo $p['proj_name']; ?></small></td>
                                    <td class="text-center"><strong><?php echo $p['total_work_days']; ?></strong></td>
                                    <td class="text-center"><?php echo $p['paid_leave_days']; ?></td>
                                    <td class="text-center"><?php echo ($p['holiday_paid_days'] + $p['holiday_work_days']); ?></td>
                                    <td class="text-center"><?php echo $p['total_ot_hours']; ?>h</td>
                                    <td class="text-success"><?php echo number_format($p['bonus_amount']); ?></td>
                                    <td class="text-danger">
                                        <?php 
                                            $deductions = $p['bhxh_amount'] + $p['salary_advances'] + $p['union_fee'] + $p['income_tax'];
                                            echo "-".number_format($deductions); 
                                        ?>
                                    </td>
                                    <td style="font-weight: 800; color: #166534; background: #f0fdf4; font-size: 1rem;">
                                        <?php echo number_format($p['net_salary']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../../../includes/footer.php'; ?>
