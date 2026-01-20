<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_id = (int)$_POST['employee_id'];
    $proj_id = (int)$_POST['project_id'];
    $shift_id = (int)$_POST['shift_id'];
    $date = date('Y-m-d');
    $check_in = date('Y-m-d H:i:s');

    // Check if already checked in today
    $exists = db_fetch_row("SELECT id FROM attendance WHERE employee_id = ? AND date = ?", [$emp_id, $date]);
    
    if ($exists) {
        echo "<script>alert('Nhân viên này đã có dữ liệu chấm công hôm nay!'); window.history.back();</script>";
        exit;
    }

    $sql = "INSERT INTO attendance (employee_id, project_id, shift_id, date, check_in, status) VALUES (?, ?, ?, ?, ?, 'working')";
    if (db_query($sql, [$emp_id, $proj_id, $shift_id, $date, $check_in])) {
        redirect('index.php');
    }
}

$employees = db_fetch_all("SELECT e.*, p.name as proj_name FROM employees e LEFT JOIN projects p ON e.current_project_id = p.id WHERE e.status = 'working' ORDER BY e.fullname ASC");
$projects = db_fetch_all("SELECT * FROM projects WHERE status = 'active' ORDER BY name ASC");
$shifts = db_fetch_all("SELECT * FROM shifts"); // Will filter by JS

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info">
            <span>Admin</span>
            <div class="user-avatar">A</div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Vào ca thủ công</h1>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nhân viên <span style="color:red;">*</span></label>
                    <select name="employee_id" id="employee_select" class="form-control" required onchange="updateProject(this)">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?php echo $e['id']; ?>" data-project="<?php echo $e['current_project_id']; ?>">
                                <?php echo $e['fullname']; ?> (<?php echo $e['code']; ?>) - <?php echo $e['proj_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Dự án <span style="color:red;">*</span></label>
                    <select name="project_id" id="project_select" class="form-control" required onchange="updateShifts(this.value)">
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ca làm việc <span style="color:red;">*</span></label>
                    <select name="shift_id" id="shift_select" class="form-control" required>
                        <option value="">-- Chọn ca làm việc --</option>
                        <!-- Dynamic via JS -->
                    </select>
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 40px; font-weight: bold;">
                        <i class="fas fa-sign-in-alt"></i> XÁC NHẬN VÀO CA
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
const allShifts = <?php echo json_encode($shifts); ?>;

function updateProject(select) {
    const projId = $(select).find(':selected').data('project');
    if (projId) {
        $('#project_select').val(projId).change();
    }
}

function updateShifts(projId) {
    const $shiftSelect = $('#shift_select');
    $shiftSelect.empty().append('<option value="">-- Chọn ca làm việc --</option>');
    
    if (!projId) return;

    const filtered = allShifts.filter(s => s.project_id == projId);
    filtered.forEach(s => {
        $shiftSelect.append(`<option value="${s.id}">${s.name} (${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)})</option>`);
    });
}
</script>

<?php include '../../../includes/footer.php'; ?>
