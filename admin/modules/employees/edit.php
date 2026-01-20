<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) {
    set_toast('error', 'Không tìm thấy nhân viên!');
    redirect('index.php');
}

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
    
    $avatar = $employee['avatar']; // Keep old avatar by default

    // Handle Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = "avatar_" . $id . "_" . time() . "." . $ext;
            $target = "../../../upload/avatars/" . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                // Remove old file if exists
                if ($employee['avatar'] && file_exists("../../../" . $employee['avatar'])) {
                    unlink("../../../" . $employee['avatar']);
                }
                $avatar = "upload/avatars/" . $filename;
            }
        }
    }

    $sql = "UPDATE employees SET 
                code = ?, 
                fullname = ?, 
                avatar = ?,
                gender = ?, 
                dob = ?, 
                phone = ?, 
                email = ?, 
                identity_card = ?, 
                department_id = ?, 
                position_id = ?,
                position = ?,
                current_project_id = ?, 
                start_date = ?, 
                status = ? 
            WHERE id = ?";
    
    $params = [$code, $fullname, $avatar, $gender, $dob, $phone, $email, $identity_card, $department_id, $position_id, $position_name, $current_project_id, $start_date, $status, $id];
    
    if (db_query($sql, $params)) {
        set_toast('success', 'Cập nhật thông tin nhân viên thành công!');
        redirect("edit.php?id=$id");
    } else {
        set_toast('error', 'Có lỗi xảy ra, vui lòng thử lại!');
    }
}

$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");
$positions = db_fetch_all("SELECT * FROM positions ORDER BY name ASC");

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
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
                <a href="../../change_password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                <a href="../../logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </header>

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
                <!-- Left Column: Avatar & Summary -->
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
                                    <?php
                                    $status_map = [
                                        'working' => ['text' => 'Đang làm việc', 'class' => 'badge-success'],
                                        'resigned' => ['text' => 'Đã nghỉ việc', 'class' => 'badge-danger'],
                                        'maternity_leave' => ['text' => 'Nghỉ thai sản', 'class' => 'badge-warning'],
                                        'unpaid_leave' => ['text' => 'Nghỉ không lương', 'class' => 'badge-secondary'],
                                    ];
                                    $st = $status_map[$employee['status']] ?? ['text' => 'Không rõ', 'class' => 'badge-secondary'];
                                    ?>
                                    <span class="badge <?php echo $st['class']; ?>"><?php echo $st['text']; ?></span>
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

                <!-- Right Column: Form Tabs -->
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
                                        <option value="Khác" <?php echo $employee['gender'] == 'Khác' ? 'selected' : ''; ?>>Khác</option>
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
                                <div class="form-group">
                                    <label>Ngày cấp CCCD</label>
                                    <input type="date" name="identity_date" class="form-control" value="<?php echo $employee['identity_date']; ?>">
                                </div>
                            </div>
                        </div>

                        <div id="job" class="tab-content">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Phòng ban (Ban)</label>
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
                                        <?php foreach ($projects as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo $employee['current_project_id'] == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Ngày bắt đầu làm việc</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $employee['start_date']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select name="status" class="form-control">
                                        <option value="working" <?php echo $employee['status'] == 'working' ? 'selected' : ''; ?>>Đang làm việc</option>
                                        <option value="resigned" <?php echo $employee['status'] == 'resigned' ? 'selected' : ''; ?>>Đã nghỉ việc</option>
                                        <option value="maternity_leave" <?php echo $employee['status'] == 'maternity_leave' ? 'selected' : ''; ?>>Nghỉ thai sản</option>
                                        <option value="unpaid_leave" <?php echo $employee['status'] == 'unpaid_leave' ? 'selected' : ''; ?>>Nghỉ không lương</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="account" class="tab-content">
                            <p style="color: #64748b; font-style: italic;">Tính năng tạo tài khoản đăng nhập cho nhân viên để tự chấm công bằng App sẽ được cập nhật trong giai đoạn tiếp theo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php include '../../../includes/footer.php'; ?>

<script>
// All positions data from PHP
const allPositions = <?php echo json_encode($positions); ?>;
const currentPositionId = <?php echo $employee['position_id'] ?? 'null'; ?>;

$(document).ready(function() {
    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        $('.tab-item').removeClass('active');
        $('.tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });

    // Initialize positions based on current department
    updatePositions(currentPositionId);
});

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

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#avatarPreview').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.avatar-preview-container img {
    transition: transform 0.3s ease;
}
.avatar-preview-container img:hover {
    transform: scale(1.02);
}
.btn-sm { padding: 8px 15px; font-size: 0.85rem; }
</style>
