<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) {
    set_toast('error', 'Không tìm thấy nhân viên!');
    redirect('index.php');
}

// Security: Check Permissions (Project Scope)
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    $emp_proj_id = $employee['current_project_id'];
    // If employee has a project, check if manager owns it. 
    // If employee has NO project, maybe allow? Or deny? Usually deny or allow HR to assign.
    // Assuming strict project management:
    if (!$emp_proj_id || !in_array($emp_proj_id, $allowed_projs)) {
        echo "<div class='main-content'><div class='content-wrapper'>
                <div class='alert alert-danger'>
                    <h3><i class='fas fa-lock'></i> Truy cập bị từ chối</h3>
                    <p>Nhân viên này không thuộc dự án bạn quản lý.</p>
                    <a href='index.php' class='btn btn-secondary'>Quay lại</a>
                </div>
              </div></div>";
        include '../../../includes/footer.php';
        exit;
    }
}

// Handle Employee Info Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_employee'])) {
    $code = clean_input($_POST['code']);
    $fullname = clean_input($_POST['fullname']);
    $gender = clean_input($_POST['gender']);
    $dob = clean_input($_POST['dob']);
    $phone = clean_input($_POST['phone']);
    $email = clean_input($_POST['email']);
    $identity_card = clean_input($_POST['identity_card']);
    $department_id = (int)$_POST['department_id'];
    $position_id = (int)$_POST['position_id'];
    
    // Get position name for backward compatibility or if needed
    $pos_info = db_fetch_row("SELECT name FROM positions WHERE id = ?", [$position_id]);
    $position_name = $pos_info ? $pos_info['name'] : '';

    $current_project_id = (int)$_POST['current_project_id'];
    $start_date = clean_input($_POST['start_date']);
    $status = clean_input($_POST['status']);
    
    $avatar = $employee['avatar'];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = "avatar_" . $id . "_" . time() . "." . $ext;
            $target = "../../../upload/avatars/" . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                if ($employee['avatar'] && file_exists("../../../" . $employee['avatar'])) {
                    unlink("../../../" . $employee['avatar']);
                }
                $avatar = "upload/avatars/" . $filename;
            }
        }
    }

    $sql = "UPDATE employees SET 
                code = ?, fullname = ?, avatar = ?, gender = ?, dob = ?, phone = ?, email = ?, 
                identity_card = ?, department_id = ?, position_id = ?, position = ?, 
                current_project_id = ?, start_date = ?, status = ? 
            WHERE id = ?";
    
    $params = [$code, $fullname, $avatar, $gender, $dob, $phone, $email, $identity_card, $department_id, $position_id, $position_name, $current_project_id, $start_date, $status, $id];
    
    if (db_query($sql, $params)) {
        set_toast('success', 'Cập nhật thông tin nhân viên thành công!');
        redirect("edit.php?id=$id");
    } else {
        set_toast('error', 'Có lỗi xảy ra, vui lòng thử lại!');
    }
}

// Handle Account Management
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_account'])) {
    $username = clean_input($_POST['acc_username']);
    $password = $_POST['acc_password'];
    $role = clean_input($_POST['acc_role']);
    $acc_status = (int)$_POST['acc_status'];
    $is_update = (int)$_POST['is_update'];
    
    $target_user_id = 0;

    if ($is_update) {
        // Update User
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, role = ?, status = ? WHERE employee_id = ?";
            db_query($sql, [$hash, $role, $acc_status, $id]);
        } else {
            $sql = "UPDATE users SET role = ?, status = ? WHERE employee_id = ?";
            db_query($sql, [$role, $acc_status, $id]);
        }
        
        // Get User ID
        $u_row = db_fetch_row("SELECT id FROM users WHERE employee_id = ?", [$id]);
        if($u_row) $target_user_id = $u_row['id'];
        
        set_toast('success', 'Đã cập nhật tài khoản!');
        
    } else {
        // Create User
        $exist = db_fetch_row("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exist) {
            set_toast('error', 'Tên đăng nhập đã tồn tại!');
            redirect("edit.php?id=$id#account");
        } elseif (empty($password)) {
            set_toast('error', 'Mật khẩu là bắt buộc khi tạo mới!');
            redirect("edit.php?id=$id#account");
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, fullname, email, role, status, employee_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if (db_query($sql, [$username, $hash, $employee['fullname'], $employee['email'], $role, $acc_status, $id])) {
                set_toast('success', 'Đã tạo tài khoản thành công!');
                // Get New User ID
                $u_row = db_fetch_row("SELECT id FROM users WHERE username = ?", [$username]);
                if($u_row) $target_user_id = $u_row['id'];
            } else {
                set_toast('error', 'Lỗi tạo tài khoản.');
            }
        }
    }

    // Handle Project Assignment (Only for Managers)
    if ($target_user_id) {
        // 1. Reset all projects currently managed by this user
        db_query("UPDATE projects SET manager_id = NULL WHERE manager_id = ?", [$target_user_id]);

        // 2. If role is manager, assign projects
        if ($role == 'manager') {
            $projects_to_assign = [];
            
            // From Checkbox List
            if (isset($_POST['managed_projects']) && is_array($_POST['managed_projects'])) {
                $projects_to_assign = $_POST['managed_projects'];
            }
            
            // From "Assign Current" Checkbox
            if (isset($_POST['assign_current_project']) && $employee['current_project_id']) {
                $projects_to_assign[] = $employee['current_project_id'];
            }
            
            $projects_to_assign = array_unique($projects_to_assign);

            foreach ($projects_to_assign as $pid) {
                db_query("UPDATE projects SET manager_id = ? WHERE id = ?", [$target_user_id, (int)$pid]);
            }
        }
    }

    redirect("edit.php?id=$id#account");
}

// Fetch Account Info
$account = db_fetch_row("SELECT * FROM users WHERE employee_id = ?", [$id]);

// Handle Account Deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_account') {
    if ($account) {
        // Remove manager role from projects first
        db_query("UPDATE projects SET manager_id = NULL WHERE manager_id = ?", [$account['id']]);
        
        // Delete user
        db_query("DELETE FROM users WHERE id = ?", [$account['id']]);
        
        set_toast('success', 'Đã xóa tài khoản đăng nhập!');
        redirect("edit.php?id=$id#account");
    }
}

// Fetch Managed Projects (if account exists)
$managed_project_ids = [];
if ($account) {
    $mp_rows = db_fetch_all("SELECT id FROM projects WHERE manager_id = ?", [$account['id']]);
    foreach ($mp_rows as $mp) {
        $managed_project_ids[] = $mp['id'];
    }
}

$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");
$positions = db_fetch_all("SELECT * FROM positions ORDER BY name ASC");

// Determine current project name for UI
$current_proj_name = 'Chưa có';
foreach($projects as $p) {
    if($p['id'] == $employee['current_project_id']) {
        $current_proj_name = $p['name'];
        break;
    }
}
$is_managing_current = in_array($employee['current_project_id'], $managed_project_ids);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="action-header">
                <h1 class="page-title">Thông tin Nhân viên: <?php echo $employee['fullname']; ?></h1>
                <div class="header-actions">
                    <button type="submit" name="save_employee" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px;">
                <!-- Left Column -->
                <div>
                    <div class="card" style="text-align: center;">
                        <div class="avatar-preview-container" style="margin-bottom: 20px;">
                            <?php 
                                $avatar_path = !empty($employee['avatar']) ? "/khaservice-hr/" . $employee['avatar'] : "https://ui-avatars.com/api/?name=" . urlencode($employee['fullname']) . "&size=200&background=24a25c&color=fff";
                            ?>
                            <img id="avatarPreview" src="<?php echo $avatar_path; ?>" alt="Avatar" style="width: 200px; height: 200px; border-radius: 12px; object-fit: cover; border: 4px solid #f1f5f9; box-shadow: var(--shadow-sm);">
                        </div>
                        <label for="avatarInput" class="btn btn-secondary btn-sm" style="cursor: pointer;">
                            <i class="fas fa-camera"></i> Đổi ảnh khuôn mặt
                        </label>
                        <input type="file" id="avatarInput" name="avatar" style="display: none;" accept="image/*" onchange="previewImage(this)">
                        
                        <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">
                        
                        <div style="text-align: left;">
                            <div class="form-group">
                                <label>Mã nhân viên</label>
                                <div style="font-weight: 700; font-size: 1.1rem; color: var(--primary-dark);"><?php echo $employee['code']; ?></div>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <div>
                                    <span class="badge <?php echo $employee['status'] == 'working' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $employee['status'] == 'working' ? 'Đang làm việc' : 'Nghỉ việc'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card" style="margin-top: 25px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px;">Phím tắt</h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="documents.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="justify-content: flex-start;">
                                <i class="fas fa-folder-open"></i> Quản lý Hồ sơ
                            </a>
                            <a href="../contracts/index.php?employee_id=<?php echo $id; ?>" class="btn btn-secondary" style="justify-content: flex-start;">
                                <i class="fas fa-file-contract"></i> Hợp đồng lao động
                            </a>
                            <a href="../attendance/index.php?employee_id=<?php echo $id; ?>" class="btn btn-secondary" style="justify-content: flex-start;">
                                <i class="fas fa-calendar-check"></i> Lịch sử chấm công
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Tabs -->
                <div class="card" style="padding: 0;">
                    <div class="tabs" style="padding: 0 25px; border-bottom: 1px solid #e2e8f0; margin-bottom: 0;">
                        <div class="tab-item active" data-tab="personal">Thông tin cá nhân</div>
                        <div class="tab-item" data-tab="job">Công việc</div>
                        <div class="tab-item" data-tab="account">Tài khoản</div>
                    </div>

                    <div style="padding: 25px;">
                        <div id="personal" class="tab-content active">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Mã nhân viên <span style="color:red;">*</span></label>
                                    <input type="text" name="code" class="form-control" value="<?php echo $employee['code']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Họ và tên <span style="color:red;">*</span></label>
                                    <input type="text" name="fullname" class="form-control" value="<?php echo $employee['fullname']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Giới tính</label>
                                    <select name="gender" class="form-control">
                                        <option value="Nam" <?php echo $employee['gender'] == 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                        <option value="Nữ" <?php echo $employee['gender'] == 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <input type="date" name="dob" class="form-control" value="<?php echo $employee['dob']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo $employee['phone']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo $employee['email']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Số CCCD</label>
                                    <input type="text" name="identity_card" class="form-control" value="<?php echo $employee['identity_card']; ?>">
                                </div>
                            </div>
                        </div>

                        <div id="job" class="tab-content">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Phòng ban</label>
                                    <select name="department_id" id="departmentSelect" class="form-control" onchange="updatePositions()">
                                        <option value="">-- Chọn ban --</option>
                                        <?php foreach ($departments as $d): ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo $employee['department_id'] == $d['id'] ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Chức vụ</label>
                                    <select name="position_id" id="positionSelect" class="form-control">
                                        <option value="">-- Chọn chức vụ --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Dự án hiện tại</label>
                                    <select name="current_project_id" class="form-control">
                                        <option value="">-- Chọn dự án --</option>
                                        <?php foreach ($projects as $p): 
                                            // Only show allowed projects
                                            if ($allowed_projs !== 'ALL' && !in_array($p['id'], $allowed_projs)) continue;
                                        ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo $employee['current_project_id'] == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select name="status" class="form-control">
                                        <option value="working" <?php echo $employee['status'] == 'working' ? 'selected' : ''; ?>>Đang làm việc</option>
                                        <option value="resigned" <?php echo $employee['status'] == 'resigned' ? 'selected' : ''; ?>>Đã nghỉ việc</option>
                                    </select>
                                </div>
                            </div>
                        </div>
        </form>
                        <!-- Account Tab -->
                        <div id="account" class="tab-content">
                            <form action="" method="POST">
                                <?php if ($account): ?>
                                    <div class="alert alert-info" style="background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                                        <i class="fas fa-info-circle"></i> Nhân viên này đã có tài khoản.
                                    </div>
                                    <input type="hidden" name="is_update" value="1">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" class="form-control" value="<?php echo $account['username']; ?>" readonly style="background: #f1f5f9;">
                                        <input type="hidden" name="acc_username" value="<?php echo $account['username']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Mật khẩu mới</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="acc_password" id="acc_pass" class="form-control" placeholder="Nhập mật khẩu mới...">
                                            <button type="button" class="password-toggle-btn" data-target="acc_pass"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning" style="background: #fffbeb; color: #92400e; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                                        <i class="fas fa-exclamation-circle"></i> Chưa có tài khoản.
                                    </div>
                                    <input type="hidden" name="is_update" value="0">
                                    <div class="form-group">
                                        <label>Username <span style="color:red;">*</span></label>
                                        <input type="text" name="acc_username" class="form-control" required value="<?php echo strtolower($employee['code']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Mật khẩu <span style="color:red;">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" name="acc_password" id="new_acc_pass" class="form-control" required>
                                            <button type="button" class="password-toggle-btn" data-target="new_acc_pass"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div class="form-group">
                                        <label>Quyền hạn</label>
                                        <select name="acc_role" id="accRoleSelect" class="form-control">
                                            <option value="staff" <?php echo ($account && $account['role'] == 'staff') ? 'selected' : ''; ?>>Nhân viên (Staff)</option>
                                            <option value="manager" <?php echo ($account && $account['role'] == 'manager') ? 'selected' : ''; ?>>Quản lý (Manager)</option>
                                            <option value="admin" <?php echo ($account && $account['role'] == 'admin') ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Trạng thái</label>
                                        <select name="acc_status" class="form-control">
                                            <option value="1" <?php echo ($account && $account['status'] == 1) ? 'selected' : ''; ?>>Kích hoạt</option>
                                            <option value="0" <?php echo ($account && $account['status'] == 0) ? 'selected' : ''; ?>>Khóa</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Project Assignment Section (Hidden unless Manager) -->
                                <div id="projectAssignment" style="display: none; background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                                    <label style="font-weight: 600; color: var(--primary-color); display: block; margin-bottom: 10px;">
                                        <i class="fas fa-tasks"></i> Phân công quản lý Dự án
                                    </label>
                                    
                                    <?php if ($employee['current_project_id']): ?>
                                        <div style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #0284c7; cursor: pointer;">
                                                <input type="checkbox" name="assign_current_project" value="1" <?php echo $is_managing_current ? 'checked' : ''; ?>>
                                                Quản lý dự án hiện tại: <?php echo $current_proj_name; ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>

                                    <div style="max-height: 200px; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <?php foreach ($projects as $proj): 
                                            // Skip if it's the current one (handled above), OR show it too? 
                                            // Better to show all for clarity, but mark if checked.
                                            $is_managed = in_array($proj['id'], $managed_project_ids);
                                        ?>
                                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                                                <input type="checkbox" name="managed_projects[]" value="<?php echo $proj['id']; ?>" <?php echo $is_managed ? 'checked' : ''; ?>>
                                                <?php echo $proj['name']; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small style="color: #64748b; display: block; margin-top: 5px;">* Chọn các dự án mà nhân viên này sẽ làm Quản lý (Manager).</small>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <button type="submit" name="save_account" class="btn btn-primary">
                                        <i class="fas fa-key"></i> <?php echo $account ? 'Cập nhật tài khoản' : 'Tạo tài khoản'; ?>
                                    </button>
                                    
                                    <?php if ($account): ?>
                                        <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">
                                            <i class="fas fa-trash"></i> Xóa tài khoản
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </div>

<?php include '../../../includes/footer.php'; ?>

<script>
// All positions data from PHP
const allPositions = <?php echo json_encode($positions); ?>;
const currentPositionId = <?php echo $employee['position_id'] ?? 'null'; ?>;

$(document).ready(function() {
    // Hash navigation
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        if ($('.tab-item[data-tab="' + hash + '"]').length) {
            switchTab(hash);
        }
    }

    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        switchTab(tabId);
    });

    // Role Change Logic
    $('#accRoleSelect').on('change', function() {
        toggleProjectAssignment();
    });

    updatePositions(currentPositionId);
    toggleProjectAssignment(); // Init state
});

function confirmDeleteAccount() {
    Modal.confirm('Bạn có chắc chắn muốn xóa tài khoản đăng nhập của nhân viên này? Họ sẽ không thể truy cập hệ thống nữa.', () => {
        window.location.href = 'edit.php?id=<?php echo $id; ?>&action=delete_account';
    });
}

function switchTab(tabId) {
    $('.tab-item').removeClass('active');
    $('.tab-content').removeClass('active');
    $('.tab-item[data-tab="' + tabId + '"]').addClass('active');
    $('#' + tabId).addClass('active');
}

function updatePositions(selectedId = null) {
    const deptId = $('#departmentSelect').val();
    const $posSelect = $('#positionSelect');
    $posSelect.empty().append('<option value="">-- Chọn chức vụ --</option>');
    if (deptId) {
        const filtered = allPositions.filter(p => p.department_id == deptId);
        filtered.forEach(p => {
            const selected = (selectedId && p.id == selectedId) ? 'selected' : '';
            $posSelect.append(`<option value="${p.id}" ${selected}>${p.name}</option>`);
        });
    }
}

function toggleProjectAssignment() {
    const role = $('#accRoleSelect').val();
    if (role === 'manager') {
        $('#projectAssignment').slideDown();
    } else {
        $('#projectAssignment').slideUp();
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { $('#avatarPreview').attr('src', e.target.result); }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.avatar-preview-container img { transition: transform 0.3s ease; }
.avatar-preview-container img:hover { transform: scale(1.02); }
.btn-sm { padding: 8px 15px; font-size: 0.85rem; }
</style>