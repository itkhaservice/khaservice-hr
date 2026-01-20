<?php
$current_uri = $_SERVER['REQUEST_URI'];
// Simple helper to check active state
function is_active($path) {
    global $current_uri;
    return strpos($current_uri, $path) !== false ? 'active' : '';
}
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span>KHASERVICE HR</span>
        <!-- Optional Icon for collapsed state -->
        <i class="fas fa-cube" style="display:none;"></i>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="/khaservice-hr/admin/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($current_uri, 'modules') === false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="/khaservice-hr/admin/modules/projects/index.php" class="<?php echo is_active('/modules/projects/'); ?>">
                <i class="fas fa-building"></i> <span>Quản lý Dự án</span>
            </a>
        </li>
        <li>
            <a href="/khaservice-hr/admin/modules/employees/index.php" class="<?php echo is_active('/modules/employees/'); ?>">
                <i class="fas fa-user-tie"></i> <span>Quản lý Nhân sự</span>
            </a>
        </li>
        <li>
            <a href="/khaservice-hr/admin/modules/contracts/index.php" class="<?php echo is_active('/modules/contracts/'); ?>">
                <i class="fas fa-file-contract"></i> <span>Hợp đồng & BH</span>
            </a>
        </li>
        <li>
            <a href="/khaservice-hr/admin/modules/attendance/index.php" class="<?php echo is_active('/modules/attendance/'); ?>">
                <i class="fas fa-clock"></i> <span>Chấm công</span>
            </a>
        </li>
        <li>
            <a href="/khaservice-hr/admin/modules/reports/index.php" class="<?php echo is_active('/modules/reports/'); ?>">
                <i class="fas fa-chart-bar"></i> <span>Báo cáo</span>
            </a>
        </li>
        <li>
            <a href="/khaservice-hr/admin/settings.php" class="<?php echo is_active('/settings.php'); ?>">
                <i class="fas fa-cogs"></i> <span>Cấu hình</span>
            </a>
        </li>
    </ul>
</nav>