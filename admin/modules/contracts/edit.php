<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) {
    redirect('index.php');
}

// Security: Check Permissions
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    $emp_proj_id = $employee['current_project_id'];
    if (!$emp_proj_id || !in_array($emp_proj_id, $allowed_projs)) {
        die("Access Denied: You do not manage the project this employee belongs to.");
    }
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Contract
    $c_num = clean_input($_POST['contract_number']);
    $c_type = clean_input($_POST['contract_type']);
    $c_start = clean_input($_POST['c_start']);
    $c_end = clean_input($_POST['c_end']);
    $c_salary = clean_input($_POST['basic_salary']);
    
    // Simple update/insert logic for single active contract
    $has_contract = db_fetch_row("SELECT id FROM contracts WHERE employee_id = ?", [$id]);
    
    if ($has_contract) {
        db_query("UPDATE contracts SET contract_number=?, contract_type=?, start_date=?, end_date=?, basic_salary=?, updated_at=NOW() WHERE employee_id=?", 
                 [$c_num, $c_type, $c_start, $c_end, $c_salary, $id]);
    } else {
        db_query("INSERT INTO contracts (employee_id, contract_number, contract_type, start_date, end_date, basic_salary, status) VALUES (?, ?, ?, ?, ?, ?, 'active')", 
                 [$id, $c_num, $c_type, $c_start, $c_end, $c_salary]);
    }

    // 2. Insurance
    $i_num = clean_input($_POST['insurance_number']);
    $bhxh = isset($_POST['bhxh']) ? 1 : 0;
    $bhyt = isset($_POST['bhyt']) ? 1 : 0;
    $bhtn = isset($_POST['bhtn']) ? 1 : 0;
    $hospital = clean_input($_POST['hospital']);

    $has_ins = db_fetch_row("SELECT id FROM insurances WHERE employee_id = ?", [$id]);
    
    if ($has_ins) {
        db_query("UPDATE insurances SET insurance_number=?, bhxh_status=?, bhyt_status=?, bhtn_status=?, hospital_registration=? WHERE employee_id=?", 
                 [$i_num, $bhxh, $bhyt, $bhtn, $hospital, $id]);
    } else {
        db_query("INSERT INTO insurances (employee_id, insurance_number, bhxh_status, bhyt_status, bhtn_status, hospital_registration) VALUES (?, ?, ?, ?, ?, ?)", 
                 [$id, $i_num, $bhxh, $bhyt, $bhtn, $hospital]);
    }

    echo "<script>alert('Cập nhật thành công!');</script>";
}

// Fetch current data
$contract = db_fetch_row("SELECT * FROM contracts WHERE employee_id = ?", [$id]);
$insurance = db_fetch_row("SELECT * FROM insurances WHERE employee_id = ?", [$id]);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <form action="" method="POST">
            <div class="action-header">
                <h1 class="page-title">Hợp đồng & Bảo hiểm: <?php echo $employee['fullname']; ?></h1>
                <div class="header-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div class="card">
                <div class="tabs">
                    <div class="tab-item active" onclick="showTab('contract')">Thông tin Hợp đồng</div>
                    <div class="tab-item" onclick="showTab('insurance')">Thông tin Bảo hiểm</div>
                </div>

                <!-- Contract Tab -->
                <div id="contract" class="tab-content active">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Số Hợp đồng</label>
                            <input type="text" name="contract_number" class="form-control" value="<?php echo $contract['contract_number'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Loại Hợp đồng</label>
                            <select name="contract_type" class="form-control">
                                <option value="thu_viec" <?php echo ($contract['contract_type'] ?? '') == 'thu_viec' ? 'selected' : ''; ?>>Thử việc</option>
                                <option value="co_thoi_han" <?php echo ($contract['contract_type'] ?? '') == 'co_thoi_han' ? 'selected' : ''; ?>>Có thời hạn</option>
                                <option value="khong_thoi_han" <?php echo ($contract['contract_type'] ?? '') == 'khong_thoi_han' ? 'selected' : ''; ?>>Không thời hạn</option>
                                <option value="khoan_viec" <?php echo ($contract['contract_type'] ?? '') == 'khoan_viec' ? 'selected' : ''; ?>>Khoán việc</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="date" name="c_start" class="form-control" value="<?php echo $contract['start_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="date" name="c_end" class="form-control" value="<?php echo $contract['end_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Lương cơ bản</label>
                            <input type="number" name="basic_salary" class="form-control" value="<?php echo $contract['basic_salary'] ?? 0; ?>">
                        </div>
                    </div>
                </div>

                <!-- Insurance Tab -->
                <div id="insurance" class="tab-content">
                    <div class="form-group">
                        <label>Số sổ BHXH</label>
                        <input type="text" name="insurance_number" class="form-control" value="<?php echo $insurance['insurance_number'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Trạng thái tham gia</label>
                        <div style="display:flex; gap:20px; margin-top:10px;">
                            <label style="font-weight:normal;">
                                <input type="checkbox" name="bhxh" <?php echo ($insurance['bhxh_status'] ?? 0) ? 'checked' : ''; ?>> BHXH
                            </label>
                            <label style="font-weight:normal;">
                                <input type="checkbox" name="bhyt" <?php echo ($insurance['bhyt_status'] ?? 0) ? 'checked' : ''; ?>> BHYT
                            </label>
                            <label style="font-weight:normal;">
                                <input type="checkbox" name="bhtn" <?php echo ($insurance['bhtn_status'] ?? 0) ? 'checked' : ''; ?>> BHTN
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nơi đăng ký KCB ban đầu</label>
                        <input type="text" name="hospital" class="form-control" value="<?php echo $insurance['hospital_registration'] ?? ''; ?>" placeholder="Ví dụ: Bệnh viện Quận 1">
                    </div>
                </div>
            </div>
        </form>
    </div>

<script>
function showTab(tabId) {
    $('.tab-item').removeClass('active');
    $('.tab-content').removeClass('active');
    
    $(`.tab-item[onclick="showTab('${tabId}')"]`).addClass('active');
    $('#' + tabId).addClass('active');
}
</script>

<?php include '../../../includes/footer.php'; ?>
