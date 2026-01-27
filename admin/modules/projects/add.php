<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Fetch departments and positions for headcount
$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$all_positions = db_fetch_all("SELECT * FROM positions ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = clean_input($_POST['code']);
    $name = clean_input($_POST['name']);
    $address = clean_input($_POST['address']);
    $status = clean_input($_POST['status']);
    $headcount_total = (int)$_POST['headcount_required'];

    $sql = "INSERT INTO projects (code, name, address, status, headcount_required) VALUES (?, ?, ?, ?, ?)";
    if (db_query($sql, [$code, $name, $address, $status, $headcount_total])) {
        $project_id = db_last_insert_id();

        // 1. Save Detailed Headcount (Project Positions)
        if (isset($_POST['pos_name']) && is_array($_POST['pos_name'])) {
            foreach ($_POST['pos_name'] as $index => $pos_name) {
                $dept_id = (int)$_POST['pos_dept_id'][$index];
                $count_req = (int)$_POST['pos_count'][$index];
                if (!empty($pos_name) && $count_req > 0) {
                    db_query("INSERT INTO project_positions (project_id, department_id, position_name, count_required) VALUES (?, ?, ?, ?)", 
                             [$project_id, $dept_id > 0 ? $dept_id : null, $pos_name, $count_req]);
                }
            }
        }

        // 2. Save Shifts
        if (isset($_POST['shift_name']) && is_array($_POST['shift_name'])) {
            foreach ($_POST['shift_name'] as $index => $s_name) {
                $s_start = $_POST['shift_start'][$index];
                $s_end = $_POST['shift_end'][$index];
                $s_type = $_POST['shift_type'][$index];
                if (!empty($s_name)) {
                    db_query("INSERT INTO shifts (project_id, name, start_time, end_time, type) VALUES (?, ?, ?, ?, ?)", 
                             [$project_id, $s_name, $s_start, $s_end, $s_type]);
                }
            }
        }

        set_toast('success', 'Thêm dự án thành công!');
        redirect('index.php');
    }
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <form action="" method="POST">
            <div class="action-header">
                <h1 class="page-title">Thêm Dự án mới</h1>
                <div class="header-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Dự án</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div class="card">
                <div class="tabs">
                    <div class="tab-item active" onclick="showTab('general')">Thông tin chung</div>
                    <div class="tab-item" onclick="showTab('positions')">Định biên nhân sự</div>
                    <div class="tab-item" onclick="showTab('shifts')">Cấu hình ca làm việc</div>
                </div>

                <!-- TAB: GENERAL -->
                <div id="general" class="tab-content active">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Mã dự án <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required placeholder="Ví dụ: PJ001">
                        </div>
                        <div class="form-group">
                            <label>Tên dự án <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Tên tòa nhà / dự án">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" name="address" class="form-control" placeholder="Địa chỉ dự án">
                    </div>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="active">Đang hoạt động</option>
                                <option value="completed">Đã hoàn thành</option>
                                <option value="pending">Tạm dừng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tổng định biên dự kiến</label>
                            <input type="number" name="headcount_required" id="total_hc_input" class="form-control" value="0" min="0" readonly style="background: #f1f5f9;">
                            <small class="text-muted">Tự động tính từ tab Định biên.</small>
                        </div>
                    </div>
                </div>

                <!-- TAB: POSITIONS (HEADCOUNT) -->
                <div id="positions" class="tab-content">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin:0;">Chi tiết định biên theo Chức vụ</h4>
                        <button type="button" class="btn btn-sm btn-success" onclick="addRow('pos-table')"><i class="fas fa-plus"></i> Thêm vị trí</button>
                    </div>
                    <div class="table-container">
                        <table class="table" id="pos-table">
                            <thead>
                                <tr>
                                    <th>Phòng ban</th>
                                    <th>Chức vụ / Vị trí</th>
                                    <th width="120">Số lượng</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="pos-row">
                                    <td>
                                        <select name="pos_dept_id[]" class="form-control">
                                            <option value="0">-- Phòng ban --</option>
                                            <?php foreach($departments as $d): ?>
                                                <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="pos_name[]" class="form-control" placeholder="Tên chức vụ..." list="pos-list">
                                    </td>
                                    <td>
                                        <input type="number" name="pos_count[]" class="form-control hc-count" value="1" min="1" onchange="calculateTotalHC()">
                                    </td>
                                    <td><button type="button" class="btn-icon text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <datalist id="pos-list">
                        <?php foreach($all_positions as $p): ?>
                            <option value="<?php echo $p['name']; ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- TAB: SHIFTS -->
                <div id="shifts" class="tab-content">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin:0;">Cấu hình ca làm việc</h4>
                        <button type="button" class="btn btn-sm btn-success" onclick="addRow('shift-table')"><i class="fas fa-plus"></i> Thêm ca</button>
                    </div>
                    <div class="table-container">
                        <table class="table" id="shift-table">
                            <thead>
                                <tr>
                                    <th>Tên ca</th>
                                    <th width="150">Bắt đầu</th>
                                    <th width="150">Kết thúc</th>
                                    <th width="150">Loại ca</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="shift-row">
                                    <td><input type="text" name="shift_name[]" class="form-control" placeholder="VD: Ca sáng, Ca 12h..."></td>
                                    <td><input type="time" name="shift_start[]" class="form-control" value="08:00"></td>
                                    <td><input type="time" name="shift_end[]" class="form-control" value="17:00"></td>
                                    <td>
                                        <select name="shift_type[]" class="form-control">
                                            <option value="8h">8 tiếng</option>
                                            <option value="12h">12 tiếng</option>
                                            <option value="24h">24 tiếng</option>
                                            <option value="office">Hành chính</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn-icon text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.tab-content { display: none; padding: 20px 0; animation: fadeIn 0.3s; }
.tab-content.active { display: block; }
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1.1rem; }
.text-danger { color: #ef4444; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Dark Mode support */
body.dark-mode .tab-item { color: #94a3b8; }
body.dark-mode .tab-item.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
body.dark-mode input[readonly] { background-color: #0f172a !important; color: #94a3b8; border-color: #334155; }
</style>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    event.currentTarget.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

function addRow(tableId) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    
    // Clear inputs
    newRow.querySelectorAll('input').forEach(i => {
        if(i.type === 'number') i.value = 1;
        else if(i.type !== 'time') i.value = '';
    });
    
    tbody.appendChild(newRow);
    calculateTotalHC();
}

function removeRow(btn) {
    const row = btn.closest('tr');
    const tbody = row.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
        calculateTotalHC();
    } else {
        Toast.error('Phải có ít nhất một dòng.');
    }
}

function calculateTotalHC() {
    let total = 0;
    document.querySelectorAll('.hc-count').forEach(input => {
        total += parseInt(input.value) || 0;
    });
    document.getElementById('total_hc_input').value = total;
}

// Initial calculation
window.onload = calculateTotalHC;
</script>
</div>
<?php include '../../../includes/footer.php'; ?>