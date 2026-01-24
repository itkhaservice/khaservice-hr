<header class="main-header">
    <script>
        // Apply expanded state to main-content immediately
        if (localStorage.getItem('sidebarState') === 'collapsed') {
            document.currentScript.closest('.main-content').classList.add('expanded');
        }
    </script>
    <div class="toggle-sidebar" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <div style="flex: 1;"></div>

    <div id="start-tour" onclick="startProductTour(true)" style="margin-right: 10px; cursor: pointer; font-size: 1.2rem; color: var(--text-sub); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;" title="Hướng dẫn sử dụng">
        <i class="fas fa-question-circle"></i>
    </div>

    <div id="theme-toggle" style="margin-right: 20px; cursor: pointer; font-size: 1.2rem; color: var(--text-sub); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;">
        <i class="fas fa-moon"></i>
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
