<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$where = "WHERE e.status = 'working'";
$params = [$year];

// Security: Project Filter
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    if (empty($allowed_projs)) {
        $where .= " AND 1=0";
    } else {
        $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
        $where .= " AND e.current_project_id IN ($in_placeholder)";
        $params = array_merge($params, $allowed_projs);
        
        // If user tries to filter a project they don't own
        if ($project_id > 0 && !in_array($project_id, $allowed_projs)) {
            $project_id = 0;
        }
    }
}

if ($dept_id) {
    $where .= " AND e.department_id = ?";
    $params[] = $dept_id;
}
if ($project_id) {
    $where .= " AND e.current_project_id = ?";
    $params[] = $project_id;
}

$report_data = [];
if ($project_id > 0 || ($allowed_projs !== 'ALL' && !empty($allowed_projs))) {
    // Fetch all employees with their leave balances
    $sql = "SELECT e.id, e.code, e.fullname, d.name as dept_name, p.name as proj_name,
                   lb.total_days, lb.carried_over, lb.used_days
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN projects p ON e.current_project_id = p.id
            LEFT JOIN positions pos ON e.position_id = pos.id
            LEFT JOIN employee_leave_balances lb ON e.id = lb.employee_id AND lb.year = ?
            $where
            ORDER BY d.stt ASC, pos.stt ASC, e.fullname ASC";

    $report_data = db_fetch_all($sql, $params);
}

// Get global leave accrual rate from settings
$accrual_setting = db_fetch_row("SELECT setting_value FROM settings WHERE setting_key = 'leave_monthly_accrual'");
$monthly_rate = ($accrual_setting && $accrual_setting['setting_value'] > 0) ? (float)$accrual_setting['setting_value'] : 1.0;

// Calculate accrued months (e.g., if Jan -> 1, if Dec -> 12)
$current_year = (int)date('Y');
if ($year < $current_year) {
    $accrued_months = 12; // Past years are fully accrued
} elseif ($year > $current_year) {
    $accrued_months = 0;  // Future years not accrued yet
} else {
    $accrued_months = (int)date('n'); // Current year up to current month
}

$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");

// Filter allowed projects for dropdown
$proj_sql = "SELECT * FROM projects WHERE 1=1";
if ($allowed_projs !== 'ALL') {
    $proj_sql .= " AND id IN (" . implode(',', $allowed_projs) . ")";
}
$proj_sql .= " ORDER BY name ASC";
$projects = db_fetch_all($proj_sql);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<style>
/* DARK MODE CHO TRANG BÁO CÁO PHÉP */
body.dark-mode .filter-section { 
    background-color: #1e293b; 
    border-color: var(--primary-color); 
}
body.dark-mode .filter-section select { 
    background-color: #0f172a; 
    border-color: #334155; 
    color: #cbd5e1; 
}
body.dark-mode .card { 
    background-color: #1e293b; 
    border-color: #334155; 
}
body.dark-mode .table thead th {
    background-color: #334155;
    color: #94a3b8;
    border-bottom-color: #475569;
}
body.dark-mode .table td {
    border-bottom-color: #334155;
    color: #cbd5e1;
}
body.dark-mode .table tr:hover {
    background-color: rgba(255,255,255,0.02);
}
body.dark-mode .table td[style*="background: rgba(16, 185, 129, 0.05)"] {
    background-color: rgba(16, 185, 129, 0.1) !important;
}
body.dark-mode .text-center[style*="dashed"] {
    border-color: #334155 !important;
    color: #475569 !important;
}
</style>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Báo cáo Tổng hợp Phép năm <?php echo $year; ?></h1>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> In báo cáo</button>
                <button class="btn btn-info"><i class="fas fa-file-excel"></i> Xuất Excel</button>
            </div>
        </div>

        <form method="GET" class="filter-section">
            <select name="year">
                <?php for($y=2024; $y<=2026; $y++) echo "<option value='$y' ".($y==$year?'selected':'').">Năm $y</option>"; ?>
            </select>
            <select name="dept_id">
                <option value="">-- Tất cả phòng ban --</option>
                <?php foreach($departments as $d) echo "<option value='{$d['id']}' ".($d['id']==$dept_id?'selected':'').">{$d['name']}</option>"; ?>
            </select>
            <select name="project_id">
                <option value="0">-- Dự án --</option>
                <?php foreach($projects as $p) echo "<option value='{$p['id']}' ".($p['id']==$project_id?'selected':'').">{$p['name']}</option>"; ?>
            </select>
            
            <div style="display: flex; gap: 5px;">
                <button type="submit" class="btn btn-secondary" style="flex: 1;"><i class="fas fa-filter"></i> Lọc</button>
                <?php if ($project_id > 0 || $dept_id > 0 || $year != date('Y')): ?>
                    <a href="leave_report.php" class="btn btn-danger" title="Xóa lọc" style="min-width: 45px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card">
            <?php if ($project_id == 0): ?>
                <div style="text-align: center; padding: 50px; color: #94a3b8; border: 2px dashed #e2e8f0;">
                    <i class="fas fa-calendar-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <h3>Vui lòng chọn Dự án</h3>
                    <p>Chọn một dự án để xem báo cáo tổng hợp phép năm của nhân viên.</p>
                </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Đơn vị / Dự án</th>
                            <th class="text-center">Tổng năm</th>
                            <th class="text-center" title="Số phép được cộng dồn theo tháng tính đến hiện tại">Được hưởng</th>
                            <th class="text-center">Dư cũ</th>
                            <th class="text-center">Đã nghỉ</th>
                            <th class="text-center">Còn lại</th>
                            <th width="80" class="text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                            <tr><td colspan="8" class="text-center" style="padding: 30px;">Không có dữ liệu phù hợp với bộ lọc.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $r): 
                                $total_year = ($r['total_days'] ?? 12);
                                $accrued = $accrued_months * $monthly_rate;
                                $carried = ($r['carried_over'] ?? 0);
                                $used = ($r['used_days'] ?? 0);
                                
                                // Remaining calculation based on ACCRUED leave
                                $remaining = ($accrued + $carried) - $used;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $r['fullname']; ?></strong><br>
                                        <small class="text-sub"><?php echo $r['code']; ?> - <?php echo $r['dept_name']; ?></small>
                                    </td>
                                    <td>
                                        <small><i class="fas fa-building text-sub"></i> <?php echo $r['proj_name'] ?: 'Chưa gán'; ?></small>
                                    </td>
                                    <td class="text-center"><?php echo $total_year; ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-info" title="Tính đến tháng <?php echo $accrued_months; ?>"><?php echo $accrued; ?></span>
                                    </td>
                                    <td class="text-center"><?php echo $carried; ?></td>
                                    <td class="text-center text-danger"><strong><?php echo $used; ?></strong></td>
                                    <td class="text-center text-success" style="font-weight: 800; font-size: 1.1rem; background: rgba(16, 185, 129, 0.05);">
                                        <?php echo $remaining; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="../employees/leave.php?id=<?php echo $r['id']; ?>&year=<?php echo $year; ?>" class="btn btn-secondary btn-sm" title="Xem lịch sử chi tiết">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php include '../../../includes/footer.php'; ?>