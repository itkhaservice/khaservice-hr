<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if (!is_admin() && !has_permission('view_salary')) {
    header("Location: <?php echo BASE_URL; ?>404.php?error=no_permission");
    exit;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$kw = isset($_GET['kw']) ? clean_input($_GET['kw']) : '';

$projects = db_fetch_all("SELECT id, name FROM projects WHERE status = 'active' ORDER BY name ASC");

$employees = [];
if ($project_id > 0) {
    $params = [$month, $year, $project_id];
    $where = "WHERE e.status = 'working' AND e.current_project_id = ?";
    if ($kw) { $where .= " AND (e.fullname LIKE ? OR e.code LIKE ?)"; $params[] = "%$kw%"; $params[] = "%$kw%"; }

    $employees = db_fetch_all("
        SELECT e.id, e.code, e.fullname, d.name as dept_name, 
               s.basic_salary, s.insurance_salary, s.allowance_total, s.income_tax_percent, s.salary_advances_default,
               p.union_fee, p.bonus_amount
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions pos ON e.position_id = pos.id
        LEFT JOIN employee_salaries s ON e.id = s.employee_id
        LEFT JOIN payroll p ON e.id = p.employee_id AND p.month = ? AND p.year = ?
        $where
        ORDER BY d.stt ASC, pos.stt ASC, e.fullname ASC
    ", $params);
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Cấu hình Lương: <?php echo "$month/$year"; ?></h1>
            <div class="header-actions">
                <a href="index.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&project_id=<?php echo $project_id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-calculator"></i> Xem bảng lương</a>
            </div>
        </div>

        <form method="GET" class="filter-section">
            <select name="month" class="form-control" style="width: 100px;">
                <?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".($i==$month?'selected':'').">Tháng $i</option>"; ?>
            </select>
            <select name="year" class="form-control" style="width: 100px;">
                <?php for($y=2024;$y<=2026;$y++) echo "<option value='$y' ".($y==$year?'selected':'').">Năm $y</option>"; ?>
            </select>
            <select name="project_id" class="form-control" style="min-width: 200px;">
                <option value="0">-- CHỌN DỰ ÁN --</option>
                <?php foreach($projects as $p) echo "<option value='{$p['id']}' ".($p['id']==$project_id?'selected':'').">{$p['name']}</option>"; ?>
            </select>
            <input type="text" name="kw" value="<?php echo $kw; ?>" class="form-control" placeholder="Tên, mã NV...">

            <div style="display: flex; gap: 5px;">
                <button type="submit" class="btn btn-secondary" style="min-width: 100px;"><i class="fas fa-filter"></i> Lọc</button>
                <?php if ($project_id > 0 || $kw != ''): ?>
                    <a href="config.php" class="btn btn-danger" title="Xóa lọc" style="min-width: 45px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($project_id == 0): ?>
            <div class="card" style="text-align: center; padding: 50px; color: #94a3b8; border: 2px dashed #e2e8f0;">
                <i class="fas fa-city" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                <h3>Vui lòng chọn Dự án</h3>
            </div>
        <?php else: ?>
            <div id="salary-config-card" class="card" style="padding: 0; position: relative;">
                <div style="padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: flex-end; background: #f8fafc;">
                    <a href="javascript:void(0)" onclick="toggleCardFullScreen()" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-color);">
                        <i class="fas fa-expand"></i> Phóng to bảng
                    </a>
                </div>
                <div class="table-container" style="max-height: calc(100vh - 350px);">
                    <table class="table" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th rowspan="2">Nhân viên</th>
                                <th colspan="5" class="text-center header-fixed">CẤU HÌNH LƯƠNG ĐỊNH MỨC</th>
                                <th colspan="2" class="text-center header-variable">BIẾN ĐỘNG THÁNG <?php echo "$month/$year"; ?></th>
                                <th rowspan="2" width="60" class="text-center">Lưu</th>
                            </tr>
                            <tr>
                                <th class="text-center header-fixed">Lương khoán</th>
                                <th class="text-center header-fixed">Lương HĐ</th>
                                <th class="text-center header-fixed">Phụ cấp</th>
                                <th class="text-center header-fixed">Tạm ứng</th>
                                <th class="text-center header-fixed">Thuế (%)</th>
                                <th class="text-center header-variable">Thưởng</th>
                                <th class="text-center header-variable">Đoàn phí</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr><td colspan="9" class="text-center" style="padding: 30px;">Dự án này chưa có nhân viên hoặc không tìm thấy kết quả.</td></tr>
                            <?php else: ?>
                                <?php foreach ($employees as $e): ?>
                                    <tr data-emp-id="<?php echo $e['id']; ?>">
                                        <td>
                                            <strong><?php echo $e['fullname']; ?></strong><br>
                                            <small class="text-sub"><?php echo $e['code']; ?></small>
                                        </td>
                                        <td><input type="text" class="form-control input-money basic_salary" value="<?php echo number_format($e['basic_salary'] ?? 0); ?>"></td>
                                        <td><input type="text" class="form-control input-money insurance_salary" value="<?php echo number_format($e['insurance_salary'] ?? 0); ?>"></td>
                                        <td><input type="text" class="form-control input-money allowance_total" value="<?php echo number_format($e['allowance_total'] ?? 0); ?>"></td>
                                        <td><input type="text" class="form-control input-money salary_advances_default" style="color: #ef4444;" value="<?php echo number_format($e['salary_advances_default'] ?? 0); ?>"></td>
                                        <td><input type="number" step="0.1" class="form-control income_tax_percent" value="<?php echo $e['income_tax_percent'] ?? 0; ?>" style="text-align:center; height:36px;"></td>
                                        
                                        <td><input type="text" class="form-control input-money bonus_amount" style="color: #10b981;" value="<?php echo number_format($e['bonus_amount'] ?? 0); ?>"></td>
                                        <td><input type="text" class="form-control input-money union_fee" style="color: #64748b;" value="<?php echo number_format($e['union_fee'] ?? 0); ?>"></td>
                                        
                                        <td class="text-center">
                                            <button type="button" class="btn btn-success btn-sm btn-save-row"><i class="fas fa-save"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

<style>
.input-money { text-align: right; font-weight: 600; padding: 5px 10px; height: 36px; min-width: 95px; border-radius: 6px; }
.header-fixed { background: #f0fdf4 !important; color: #166534; }
.header-variable { background: #fffbeb !important; color: #92400e; }
.table tbody tr:hover { background-color: #f1f5f9; }

/* Fullscreen Styles for Card */
#salary-config-card:fullscreen {
    width: 100vw !important;
    height: 100vh !important;
    padding: 20px !important;
    background: #fff !important;
    display: flex;
    flex-direction: column;
}
#salary-config-card:fullscreen .table-container {
    flex: 1 !important;
    max-height: none !important;
    height: auto !important;
}
body.dark-mode #salary-config-card:fullscreen { background: #1e293b !important; }
</style>

<script>
function toggleCardFullScreen() {
    const card = document.getElementById('salary-config-card');
    if (!document.fullscreenElement) {
        if (card.requestFullscreen) card.requestFullscreen();
        else if (card.webkitRequestFullscreen) card.webkitRequestFullscreen();
        else if (card.msRequestFullscreen) card.msRequestFullscreen();
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
    }
}

// Update icon on fullscreen change
document.addEventListener('fullscreenchange', function() {
    const btn = document.querySelector('a[onclick="toggleCardFullScreen()"]');
    if (document.fullscreenElement) {
        btn.innerHTML = '<i class="fas fa-compress"></i> Thu nhỏ bảng';
    } else {
        btn.innerHTML = '<i class="fas fa-expand"></i> Phóng to bảng';
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('input-money')) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if (value === '') value = '0';
        e.target.value = new Intl.NumberFormat('en-US').format(parseInt(value));
    }
});

document.querySelectorAll('.btn-save-row').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const data = {
            action: 'save_v3',
            emp_id: row.getAttribute('data-emp-id'),
            month: <?php echo $month; ?>,
            year: <?php echo $year; ?>,
            basic_salary: row.querySelector('.basic_salary').value.replace(/,/g, '') || 0,
            insurance_salary: row.querySelector('.insurance_salary').value.replace(/,/g, '') || 0,
            allowance_total: row.querySelector('.allowance_total').value.replace(/,/g, '') || 0,
            salary_advances_default: row.querySelector('.salary_advances_default').value.replace(/,/g, '') || 0,
            income_tax_percent: row.querySelector('.income_tax_percent').value || 0,
            bonus_amount: row.querySelector('.bonus_amount').value.replace(/,/g, '') || 0,
            union_fee: row.querySelector('.union_fee').value.replace(/,/g, '') || 0
        };

        const originalBtn = this.innerHTML;
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('save_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                Toast.show('success', 'Thành công', 'Dữ liệu đã được lưu');
                row.style.backgroundColor = '#f0fdf4';
                setTimeout(() => row.style.backgroundColor = '', 1000);
            } else { Toast.show('error', 'Lỗi', res.message); }
        })
        .finally(() => { this.disabled = false; this.innerHTML = originalBtn; });
    });
});
</script>

<?php include '../../../includes/footer.php'; ?>