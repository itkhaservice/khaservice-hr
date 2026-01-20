<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Mock Settings (In real app, store in 'settings' table)
$settings = [
    'company_name' => 'Công ty TNHH Khaservice',
    'company_address' => 'TP. Hồ Chí Minh',
    'admin_email' => 'admin@khaservice.vn',
    'system_version' => '1.0.0'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Save logic would go here
    echo "<script>alert('Lưu cấu hình thành công (Demo)!');</script>";
}

include '../includes/header.php';
include '../includes/sidebar.php';
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
        <h1 class="page-title">Cấu hình Hệ thống</h1>

        <div class="card">
            <form action="" method="POST">
                <div class="form-group">
                    <label>Tên công ty</label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo $settings['company_name']; ?>">
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input type="text" name="company_address" class="form-control" value="<?php echo $settings['company_address']; ?>">
                </div>
                <div class="form-group">
                    <label>Email quản trị</label>
                    <input type="email" name="admin_email" class="form-control" value="<?php echo $settings['admin_email']; ?>">
                </div>
                <div class="form-group">
                    <label>Phiên bản hệ thống</label>
                    <input type="text" class="form-control" value="<?php echo $settings['system_version']; ?>" disabled>
                </div>
                
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu cấu hình</button>
            </form>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
