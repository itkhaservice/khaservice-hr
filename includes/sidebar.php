<?php
$current_uri = $_SERVER['REQUEST_URI'];
// Calculate Pending Docs
$pending_docs_count = 0;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM documents WHERE approval_status = 'pending'");
        if ($stmt) $pending_docs_count = $stmt->fetch()['c'];
    } catch (Exception $e) {}
}
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <!-- <span>KHASERVICE HR</span> -->
        <i class="fas fa-cube" style="display:none;"></i>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo BASE_URL; ?>admin/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($current_uri, 'modules') === false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>
        
        <?php if (has_permission('manage_projects')): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/projects/index.php" class="<?php echo is_active('/modules/projects/'); ?>">
                <i class="fas fa-building"></i> <span>Quản lý Dự án</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('view_all_employees')): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/employees/index.php" class="<?php echo is_active('/modules/employees/index.php'); ?>">
                <i class="fas fa-user-tie"></i> <span>Quản lý Nhân sự</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('edit_employee')): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/employees/pending_docs.php" class="<?php echo is_active('pending_docs.php'); ?>" style="position: relative;">
                <i class="fas fa-file-signature"></i> <span>Duyệt hồ sơ</span>
                <?php if ($pending_docs_count > 0): ?>
                    <span class="badge badge-danger" style="position: absolute; right: 10px; top: 12px; font-size: 0.7rem; padding: 2px 6px;"><?php echo $pending_docs_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/contracts/index.php" class="<?php echo is_active('/modules/contracts/'); ?>">
                <i class="fas fa-file-contract"></i> <span>Hợp đồng & BH</span>
            </a>
        </li>

        <?php if (has_permission('view_attendance')): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/attendance/index.php" class="<?php echo is_active('/modules/attendance/'); ?>">
                <i class="fas fa-clock"></i> <span>Chấm công</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('view_salary')): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/salary/index.php" class="<?php echo is_active('/modules/salary/'); ?>">
                <i class="fas fa-file-invoice-dollar"></i> <span>Quản lý Tiền lương</span>
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/reports/index.php" class="<?php echo is_active('/modules/reports/index.php'); ?>">
                <i class="fas fa-chart-bar"></i> <span>Báo cáo Nhân sự</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/reports/leave_report.php" class="<?php echo is_active('leave_report.php'); ?>">
                <i class="fas fa-calendar-alt"></i> <span>Báo cáo Phép năm</span>
            </a>
        </li>

        <?php if (has_permission('manage_system')): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/system/roles.php" class="<?php echo is_active('/modules/system/roles.php'); ?>">
                <i class="fas fa-user-shield"></i> <span>Quản lý Phân quyền</span>
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="<?php echo BASE_URL; ?>admin/modules/support/index.php" class="<?php echo is_active('/modules/support/'); ?>">
                <i class="fas fa-life-ring"></i> <span>Hỗ trợ hệ thống</span>
            </a>
        </li>
    </ul>
</nav>
