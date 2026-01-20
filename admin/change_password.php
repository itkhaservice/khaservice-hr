<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';
include '../includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // Get current user
    $user = db_fetch_row("SELECT password FROM users WHERE id = ?", [$user_id]);

    $is_valid = false;
    
    // Check if password matches (support both hash and legacy plaintext)
    if (password_verify($current_pass, $user['password'])) {
        $is_valid = true;
    } elseif ($current_pass === $user['password']) {
        $is_valid = true;
    }

    if (!$is_valid) {
        set_toast('error', 'Mật khẩu hiện tại không chính xác!');
    } elseif ($new_pass !== $confirm_pass) {
        set_toast('error', 'Mật khẩu mới không khớp nhau!');
    } elseif (strlen($new_pass) < 6) {
        set_toast('error', 'Mật khẩu mới phải từ 6 ký tự trở lên!');
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        db_query("UPDATE users SET password = ? WHERE id = ?", [$hashed_pass, $user_id]);
        set_toast('success', 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.');
        // Logout to force re-login with new password logic
        echo "<script>setTimeout(() => window.location.href = 'logout.php', 1500);</script>";
    }
}
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
                <a href="change_password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Đổi mật khẩu</h1>
        </div>

        <div style="display: flex; justify-content: center; padding-top: 20px;">
            <div class="card" style="width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Nhập mật khẩu hiện tại">
                    </div>
                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="Tối thiểu 6 ký tự">
                    </div>
                    <div class="form-group">
                        <label>Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                    </div>
                    <div style="margin-top: 25px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-key"></i> Cập nhật mật khẩu</button>
                        <a href="index.php" class="btn btn-secondary" style="width: 100px;">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
