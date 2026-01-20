<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// --- HANDLE COMPANY SETTINGS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $keys = ['company_name', 'company_address', 'admin_email', 'company_phone', 'company_website'];
    foreach ($keys as $key) {
        $val = clean_input($_POST[$key] ?? '');
        // Upsert logic
        db_query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $val, $val]);
    }
    set_toast('success', 'Đã lưu cấu hình công ty!');
}

// --- HANDLE DEPARTMENTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_dept'])) {
    $name = clean_input($_POST['dept_name']);
    $code = clean_input($_POST['dept_code']);
    
    if ($name && $code) {
        try {
            db_query("INSERT INTO departments (name, code) VALUES (?, ?)", [$name, $code]);
            set_toast('success', 'Thêm phòng ban thành công!');
        } catch (PDOException $e) {
            set_toast('error', 'Mã phòng ban đã tồn tại!');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_dept'])) {
    $id = (int)$_POST['dept_id'];
    $name = clean_input($_POST['dept_name']);
    $code = clean_input($_POST['dept_code']);
    
    db_query("UPDATE departments SET name = ?, code = ? WHERE id = ?", [$name, $code, $id]);
    set_toast('success', 'Cập nhật phòng ban thành công!');
}

if (isset($_GET['del_dept'])) {
    $id = (int)$_GET['del_dept'];
    // Check usage
    $count = db_fetch_row("SELECT COUNT(*) as c FROM employees WHERE department_id = ?", [$id])['c'];
    if ($count > 0) {
        set_toast('error', 'Không thể xóa phòng ban đang có nhân viên!');
    } else {
        db_query("DELETE FROM departments WHERE id = ?", [$id]);
        set_toast('success', 'Đã xóa phòng ban!');
    }
    redirect('settings.php#departments');
}

// --- FETCH DATA ---
// Settings
$settings_raw = db_fetch_all("SELECT * FROM settings");
$settings = [];
foreach ($settings_raw as $s) $settings[$s['setting_key']] = $s['setting_value'];

// Departments
$departments = db_fetch_all("SELECT * FROM departments ORDER BY id ASC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info" onclick="this.querySelector('.user-dropdown').classList.toggle('show')">
            <span><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
            <div class="user-avatar">A</div>
            <div class="user-dropdown">
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Cấu hình Hệ thống</h1>
        </div>

        <div class="card">
            <div class="tabs">
                <div class="tab-item active" data-tab="company">Thông tin Công ty</div>
                <div class="tab-item" data-tab="departments">Quản lý Phòng ban</div>
            </div>

            <!-- Tab 1: Company Info -->
            <div id="company" class="tab-content active">
                <form method="POST" style="max-width: 800px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Tên Công ty</label>
                            <input type="text" name="company_name" class="form-control" value="<?php echo $settings['company_name'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Admin</label>
                            <input type="email" name="admin_email" class="form-control" value="<?php echo $settings['admin_email'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="company_phone" class="form-control" value="<?php echo $settings['company_phone'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="text" name="company_website" class="form-control" value="<?php echo $settings['company_website'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ trụ sở</label>
                        <input type="text" name="company_address" class="form-control" value="<?php echo $settings['company_address'] ?? ''; ?>">
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="save_settings" class="btn btn-primary"><i class="fas fa-save"></i> Lưu cấu hình</button>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Departments -->
            <div id="departments" class="tab-content">
                <div style="display: flex; gap: 20px;">
                    <!-- Add/Edit Form -->
                    <div style="flex: 1; min-width: 300px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; height: fit-content;">
                        <h4 id="deptFormTitle" style="margin-top: 0; margin-bottom: 20px; color: var(--primary-dark);">Thêm phòng ban mới</h4>
                        <form method="POST" id="deptForm">
                            <input type="hidden" name="dept_id" id="deptId">
                            <div class="form-group">
                                <label>Mã phòng ban <span style="color:red;">*</span></label>
                                <input type="text" name="dept_code" id="deptCode" class="form-control" required placeholder="VD: HCNS">
                            </div>
                            <div class="form-group">
                                <label>Tên phòng ban <span style="color:red;">*</span></label>
                                <input type="text" name="dept_name" id="deptName" class="form-control" required placeholder="VD: Hành chính Nhân sự">
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="add_dept" id="deptBtn" class="btn btn-primary" style="flex:1;">Thêm mới</button>
                                <button type="button" id="deptCancel" class="btn btn-secondary" style="display:none;" onclick="resetDeptForm()">Hủy</button>
                            </div>
                        </form>
                    </div>

                    <!-- List -->
                    <div style="flex: 2;">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Mã PB</th>
                                        <th>Tên phòng ban</th>
                                        <th width="100" style="text-align:center;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $d): ?>
                                        <tr>
                                            <td><?php echo $d['id']; ?></td>
                                            <td><span class="badge badge-secondary"><?php echo $d['code']; ?></span></td>
                                            <td><strong><?php echo $d['name']; ?></strong></td>
                                            <td style="text-align:center;">
                                                <a href="javascript:void(0)" onclick="editDept(<?php echo htmlspecialchars(json_encode($d)); ?>)" class="text-primary-hover" style="margin-right: 10px;"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0)" onclick="confirmDelDept(<?php echo $d['id']; ?>)" class="text-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    // Tab switching logic
    if(window.location.hash) {
        var hash = window.location.hash.substring(1);
        showTab(hash);
    }
    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        showTab(tabId);
    });
});

function showTab(tabId) {
    $('.tab-item').removeClass('active');
    $('.tab-content').removeClass('active');
    $(`.tab-item[data-tab="${tabId}"]`).addClass('active');
    $('#' + tabId).addClass('active');
    window.location.hash = tabId;
}

// Department Logic
function editDept(data) {
    $('#deptFormTitle').text('Sửa phòng ban');
    $('#deptId').val(data.id);
    $('#deptCode').val(data.code);
    $('#deptName').val(data.name);
    
    $('#deptBtn').attr('name', 'edit_dept').html('<i class="fas fa-save"></i> Cập nhật');
    $('#deptCancel').show();
}

function resetDeptForm() {
    $('#deptFormTitle').text('Thêm phòng ban mới');
    $('#deptForm')[0].reset();
    $('#deptId').val('');
    
    $('#deptBtn').attr('name', 'add_dept').html('Thêm mới');
    $('#deptCancel').hide();
}

function confirmDelDept(id) {
    Modal.confirm('Bạn có chắc muốn xóa phòng ban này?', () => {
        location.href = 'settings.php?del_dept=' + id;
    });
}
</script>

<style>
.text-primary-hover:hover { color: var(--primary-color); }
.text-danger:hover { color: #b91c1c; }
</style>

<?php include '../includes/footer.php'; ?>