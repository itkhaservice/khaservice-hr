<header class="main-header">
    <div class="toggle-sidebar" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </div>
    <div class="user-info" onclick="this.querySelector('.user-dropdown').classList.toggle('show')">
        <span><?php echo $_SESSION['user_fullname'] ?? $_SESSION['user_name'] ?? 'Quản trị viên'; ?></span>
        <div class="user-avatar"><?php echo mb_substr($_SESSION['user_fullname'] ?? 'A', 0, 1); ?></div>
        <div class="user-dropdown">
            <?php 
                // Determine the correct path to change_password and logout
                // based on current file location
                $current_dir = dirname($_SERVER['PHP_SELF']);
                $base_path = (strpos($current_dir, 'modules') !== false) ? '../../' : '';
            ?>
            <a href="<?php echo $base_path; ?>change_password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a>
            <a href="<?php echo $base_path; ?>logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>
</header>
