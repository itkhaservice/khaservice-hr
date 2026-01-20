<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$id]);

if (!$project) {
    redirect('index.php');
}

// Handle Project Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_project'])) {
    $stt = (int)$_POST['stt'];
    $code = clean_input($_POST['code']);
    $name = clean_input($_POST['name']);
    $address = clean_input($_POST['address']);
    $status = clean_input($_POST['status']);

    $sql = "UPDATE projects SET stt = ?, code = ?, name = ?, address = ?, status = ? WHERE id = ?";
    if (db_query($sql, [$stt, $code, $name, $address, $status, $id])) {
        set_toast('success', 'Cập nhật dự án thành công!');
        $project = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$id]);
    }
}

// Handle Shift Management
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_shift'])) {
    $s_name = clean_input($_POST['s_name']);
    $s_type = clean_input($_POST['s_type']);
    $s_start = clean_input($_POST['s_start']);
    $s_end = clean_input($_POST['s_end']);
    
    if ($s_name && $s_start && $s_end) {
        db_query("INSERT INTO shifts (project_id, name, type, start_time, end_time) VALUES (?, ?, ?, ?, ?)", 
                 [$id, $s_name, $s_type, $s_start, $s_end]);
        set_toast('success', 'Đã thêm ca làm việc mới!');
    } else {
        set_toast('error', 'Vui lòng nhập đầy đủ thông tin ca!');
    }
}

if (isset($_GET['del_shift'])) {
    $sid = (int)$_GET['del_shift'];
    db_query("DELETE FROM shifts WHERE id = ? AND project_id = ?", [$sid, $id]);
    set_toast('success', 'Đã xóa ca làm việc!');
    redirect("edit.php?id=$id#shifts");
}

$shifts = db_fetch_all("SELECT * FROM shifts WHERE project_id = ? ORDER BY start_time ASC", [$id]);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info">
            <span>Quản trị viên</span>
            <div class="user-avatar">AD</div>
        </div>
    </header>

    <div class="content-wrapper">
        <form action="" method="POST" id="editProjectForm">
            <div class="action-header">
                <div>
                    <h1 class="page-title">Chỉnh sửa: <?php echo $project['name']; ?></h1>
                </div>
                <div class="header-actions">
                    <button type="submit" name="update_project" class="btn btn-primary"><i class="fas fa-save"></i> Lưu dữ liệu</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div class="card">
                <div class="tabs">
                    <div class="tab-item active" data-tab="general">Thông tin chung</div>
                    <div class="tab-item" data-tab="shifts">Cấu hình ca làm việc</div>
                    <div class="tab-item" data-tab="notes">Ghi chú vận hành</div>
                </div>

                <!-- Tab: Thông tin chung -->
                <div id="general" class="tab-content active">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Số thứ tự</label>
                            <input type="number" name="stt" class="form-control" value="<?php echo $project['stt']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Mã dự án <span style="color:red;">*</span></label>
                            <input type="text" name="code" class="form-control" required value="<?php echo $project['code']; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên tòa nhà / dự án <span style="color:red;">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?php echo $project['name']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ đầy đủ</label>
                        <input type="text" name="address" class="form-control" value="<?php echo $project['address']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Trạng thái vận hành</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                            <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="pending" <?php echo $project['status'] == 'pending' ? 'selected' : ''; ?>>Tạm dừng</option>
                        </select>
                    </div>
                </div>

                <!-- Tab: Cấu hình ca -->
                <div id="shifts" class="tab-content">
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid var(--border-color);">
                        <h4 style="margin-bottom: 15px; color: var(--primary-dark);">Thêm ca làm việc mới</h4>
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Tên ca</label>
                                <input type="text" name="s_name" class="form-control" placeholder="VD: Ca sáng 1">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Loại</label>
                                <select name="s_type" class="form-control">
                                    <option value="8h">8 tiếng</option>
                                    <option value="12h">12 tiếng</option>
                                    <option value="24h">24 tiếng</option>
                                    <option value="office">Hành chính</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Giờ vào</label>
                                <input type="time" name="s_start" class="form-control">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Giờ ra</label>
                                <input type="time" name="s_end" class="form-control">
                            </div>
                            <button type="submit" name="add_shift" class="btn btn-primary"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tên ca làm việc</th>
                                    <th>Loại ca</th>
                                    <th>Khung giờ làm việc</th>
                                    <th width="100" style="text-align:center;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shifts)): ?>
                                    <tr><td colspan="4" style="text-align:center; padding: 20px; color: #94a3b8;">Chưa có ca làm việc nào được thiết lập.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($shifts as $s): ?>
                                        <tr>
                                            <td><strong><?php echo $s['name']; ?></strong></td>
                                            <td><span class="badge badge-info"><?php echo $s['type']; ?></span></td>
                                            <td><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($s['start_time'])); ?> - <?php echo date('H:i', strtotime($s['end_time'])); ?></td>
                                            <td style="text-align:center;">
                                                <a href="javascript:void(0)" class="btn btn-danger btn-sm" onclick="confirmDeleteShift(<?php echo $s['id']; ?>)" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Ghi chú -->
                <div id="notes" class="tab-content">
                    <div class="form-group">
                        <label>Ghi chú đặc thù dự án</label>
                        <textarea class="form-control" rows="8" placeholder="Nhập thông tin cần lưu ý khi vận hành tại dự án này..."></textarea>
                    </div>
                </div>
            </div>
        </form>
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

    function showTab(tabId) {
        $('.tab-item').removeClass('active');
        $('.tab-content').removeClass('active');
        
        $(`.tab-item[data-tab="${tabId}"]`).addClass('active');
        $('#' + tabId).addClass('active');
        window.location.hash = tabId;
    }
});

function confirmDeleteShift(sid) {
    Modal.confirm('Bạn có chắc chắn muốn xóa ca làm việc này không?', () => {
        location.href = '?id=<?php echo $id; ?>&del_shift=' + sid;
    });
}
</script>

<style>
.btn-sm { padding: 6px 10px; font-size: 12px; }
</style>

<?php include '../../../includes/footer.php'; ?>