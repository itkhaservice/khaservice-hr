<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stt = (int)$_POST['stt'];
    $code = clean_input($_POST['code']);
    $name = clean_input($_POST['name']);
    $address = clean_input($_POST['address']);
    $status = clean_input($_POST['status']);

    $sql = "INSERT INTO projects (stt, code, name, address, status) VALUES (?, ?, ?, ?, ?)";
    if (db_query($sql, [$stt, $code, $name, $address, $status])) {
        set_toast('success', 'Thêm dự án thành công!');
        redirect('index.php');
    }
}

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
                <h1 class="page-title">Thêm Dự án mới</h1>
                <div class="header-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div class="card">
                <div class="tabs">
                    <div class="tab-item active" onclick="showTab('general')">Thông tin chung</div>
                    <div class="tab-item" onclick="showTab('shifts')">Cấu hình ca (Sau khi lưu)</div>
                </div>

                <div id="general" class="tab-content active">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Số thứ tự</label>
                            <input type="number" name="stt" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label>Mã dự án <span style="color:red;">*</span></label>
                            <input type="text" name="code" class="form-control" required placeholder="Ví dụ: PJ001">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên dự án <span style="color:red;">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Tên tòa nhà / dự án">
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" name="address" class="form-control" placeholder="Địa chỉ dự án">
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="active">Đang hoạt động</option>
                            <option value="completed">Đã hoàn thành</option>
                            <option value="pending">Tạm dừng</option>
                        </select>
                    </div>
                </div>

                <div id="shifts" class="tab-content">
                    <p style="color: #777; font-style: italic;">Bạn cần lưu thông tin dự án trước khi có thể cấu hình ca làm việc.</p>
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
