<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = clean_input($_POST['code']);
    $fullname = clean_input($_POST['fullname']);
    $gender = clean_input($_POST['gender']);
    $dob = clean_input($_POST['dob']);
    $phone = clean_input($_POST['phone']);
    $email = clean_input($_POST['email']);
    $identity_card = clean_input($_POST['identity_card']);
    $department_id = (int)$_POST['department_id'];
    $current_project_id = (int)$_POST['current_project_id'];
    $position = clean_input($_POST['position']);
    $start_date = clean_input($_POST['start_date']);
    $status = clean_input($_POST['status']);

    $sql = "INSERT INTO employees (code, fullname, gender, dob, phone, email, identity_card, department_id, current_project_id, position, start_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [$code, $fullname, $gender, $dob, $phone, $email, $identity_card, $department_id, $current_project_id, $position, $start_date, $status];
    
    if (db_query($sql, $params)) {
        redirect('index.php');
    }
}

$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");

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
        <form action="" method="POST">
            <div class="action-header">
                <h1 class="page-title">Thêm Nhân viên mới</h1>
                <div class="header-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div class="card">
                <div class="tabs">
                    <div class="tab-item active" onclick="showTab('personal')">Thông tin cá nhân</div>
                    <div class="tab-item" onclick="showTab('job')">Công việc</div>
                    <div class="tab-item" onclick="showTab('other')">Khác</div>
                </div>

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
                            <label>Phòng ban</label>
                            <select name="department_id" class="form-control">
                                <option value="">-- Chọn phòng ban --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Dự án</label>
                            <select name="current_project_id" class="form-control">
                                <option value="">-- Chọn dự án --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Chức vụ</label>
                            <input type="text" name="position" class="form-control" placeholder="Ví dụ: Kỹ thuật viên">
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

                <div id="other" class="tab-content">
                    <p style="color: #777; font-style: italic;">Tính năng quản lý hồ sơ đính kèm và hợp đồng chi tiết sẽ khả dụng sau khi lưu thông tin cơ bản.</p>
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
