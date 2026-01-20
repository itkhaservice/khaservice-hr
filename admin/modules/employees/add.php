<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

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
    
    $pos_info = db_fetch_row("SELECT name FROM positions WHERE id = ?", [$position_id]);
    $position_name = $pos_info ? $pos_info['name'] : '';

    $current_project_id = (int)$_POST['current_project_id'];
    $start_date = clean_input($_POST['start_date']);
    $status = clean_input($_POST['status']);

    // Handle Avatar Upload
    $avatar = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = "avatar_" . time() . "_" . rand(100,999) . "." . $ext;
            $target = "../../../upload/avatars/" . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                $avatar = "upload/avatars/" . $filename;
            }
        }
    }

    $sql = "INSERT INTO employees (code, fullname, avatar, gender, dob, phone, email, identity_card, department_id, position_id, position, current_project_id, position, start_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    // Note: There was a double 'position' in my mental SQL, fixed below:
    $sql = "INSERT INTO employees (code, fullname, avatar, gender, dob, phone, email, identity_card, department_id, position_id, position, current_project_id, start_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [$code, $fullname, $avatar, $gender, $dob, $phone, $email, $identity_card, $department_id, $position_id, $position_name, $current_project_id, $start_date, $status];
    
    if (db_query($sql, $params)) {
        set_toast('success', 'Thêm nhân viên mới thành công!');
        redirect('index.php');
    } else {
        set_toast('error', 'Có lỗi xảy ra, vui lòng thử lại!');
    }
}

$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");
$positions = db_fetch_all("SELECT * FROM positions ORDER BY name ASC");

// Security: Get Allowed Projects
$allowed_projs = get_allowed_projects();

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="action-header">
                <h1 class="page-title">Thêm Nhân viên mới</h1>
                <div class="header-actions">
                    <button type="submit" name="save_employee" class="btn btn-primary"><i class="fas fa-save"></i> Lưu nhân viên</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px;">
                <!-- Left Column: Avatar Selection -->
                <div>
                    <div class="card" style="text-align: center;">
                        <div class="avatar-preview-container" style="margin-bottom: 20px;">
                            <img id="avatarPreview" src="https://ui-avatars.com/api/?name=NV&size=200&background=cbd5e1&color=fff" alt="Avatar" style="width: 200px; height: 200px; border-radius: 12px; object-fit: cover; border: 4px solid #f1f5f9; box-shadow: var(--shadow-sm);">
                        </div>
                        <label for="avatarInput" class="btn btn-secondary btn-sm" style="cursor: pointer;">
                            <i class="fas fa-camera"></i> Chọn ảnh khuôn mặt
                        </label>
                        <input type="file" id="avatarInput" name="avatar" style="display: none;" accept="image/*" onchange="previewImage(this)">
                        
                        <p style="margin-top: 15px; font-size: 0.8rem; color: #64748b;">Hỗ trợ: JPG, PNG, WEBP. Tối đa 2MB.</p>
                    </div>
                </div>

                <!-- Right Column: Form Tabs -->
                <div class="card" style="padding: 0;">
                    <div class="tabs" style="padding: 0 25px; border-bottom: 1px solid #e2e8f0; margin-bottom: 0;">
                        <div class="tab-item active" data-tab="personal">Thông tin cá nhân</div>
                        <div class="tab-item" data-tab="job">Công việc</div>
                    </div>

                    <div style="padding: 25px;">
                        <div id="personal" class="tab-content active">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Mã nhân viên <span style="color:red;">*</span></label>
                                    <input type="text" name="code" class="form-control" required placeholder="Ví dụ: NV001">
                                </div>
                                <div class="form-group">
                                    <label>Họ và tên <span style="color:red;">*</span></label>
                                    <input type="text" name="fullname" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Giới tính</label>
                                    <select name="gender" class="form-control">
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                        <option value="Khác">Khác</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <input type="date" name="dob" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Số CCCD</label>
                                    <input type="text" name="identity_card" class="form-control">
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
                                            <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
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
                                            // Filter allowed projects
                                            if ($allowed_projs !== 'ALL' && !in_array($p['id'], $allowed_projs)) continue;
                                        ?>
                                            <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Ngày bắt đầu làm việc</label>
                                    <input type="date" name="start_date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select name="status" class="form-control">
                                        <option value="working">Đang làm việc</option>
                                        <option value="resigned">Đã nghỉ việc</option>
                                        <option value="maternity_leave">Nghỉ thai sản</option>
                                        <option value="unpaid_leave">Nghỉ không lương</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php include '../../../includes/footer.php'; ?>

<script>
const allPositions = <?php echo json_encode($positions); ?>;

$(document).ready(function() {
    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        $('.tab-item').removeClass('active');
        $('.tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });
});

function updatePositions() {
    const deptId = $('#departmentSelect').val();
    const $posSelect = $('#positionSelect');
    
    $posSelect.empty().append('<option value="">-- Chọn chức vụ --</option>');
    
    if (deptId) {
        const filtered = allPositions.filter(p => p.department_id == deptId);
        filtered.forEach(p => {
            $posSelect.append(`<option value="${p.id}">${p.name}</option>`);
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
