<?php
require_once '../config/db.php';
include '../includes/header.php';
include '../includes/sidebar.php';

// Real data for dashboard
$total_employees = db_fetch_row("SELECT COUNT(*) as count FROM employees WHERE status = 'working'")['count'];
$total_projects = db_fetch_row("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")['count'];
$working_today = db_fetch_row("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND check_out IS NULL")['count'];

// Counting missing mandatory documents
// This is a bit more complex, we'll count employees who don't have all 5 key documents
$total_mandatory = 5; // CCCD, HK, SYLL, BC, GKSK
$missing_files_sql = "
    SELECT COUNT(*) as count FROM (
        SELECT employee_id FROM documents 
        WHERE doc_type IN ('CCCD', 'HK', 'SYLL', 'BC', 'GKSK') AND is_submitted = 1
        GROUP BY employee_id
        HAVING COUNT(DISTINCT doc_type) < $total_mandatory
    ) as t
";
// Add employees who haven't submitted ANY documents
$no_docs_sql = "SELECT COUNT(*) as count FROM employees e WHERE NOT EXISTS (SELECT 1 FROM documents d WHERE d.employee_id = e.id)";
$missing_files = db_fetch_row($missing_files_sql)['count'] + db_fetch_row($no_docs_sql)['count'];

// Recent activity
$recent_logs = db_fetch_all("
    SELECT a.*, e.fullname, p.name as proj_name 
    FROM attendance a 
    JOIN employees e ON a.employee_id = e.id 
    JOIN projects p ON a.project_id = p.id 
    ORDER BY a.created_at DESC LIMIT 5
");
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
        <h1 class="page-title">Tổng quan Hệ thống</h1>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <!-- Card 1 -->
            <div class="card" style="border-left: 4px solid #108042;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: #777;">Tổng nhân sự</div>
                        <div style="font-size: 1.8rem; font-weight: bold;"><?php echo $total_employees; ?></div>
                    </div>
                    <i class="fas fa-users" style="font-size: 2rem; color: #108042; opacity: 0.2;"></i>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="card" style="border-left: 4px solid #17a2b8;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: #777;">Dự án hoạt động</div>
                        <div style="font-size: 1.8rem; font-weight: bold;"><?php echo $total_projects; ?></div>
                    </div>
                    <i class="fas fa-building" style="font-size: 2rem; color: #17a2b8; opacity: 0.2;"></i>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="card" style="border-left: 4px solid #ffc107;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: #777;">Đang làm việc</div>
                        <div style="font-size: 1.8rem; font-weight: bold;"><?php echo $working_today; ?></div>
                    </div>
                    <i class="fas fa-hard-hat" style="font-size: 2rem; color: #ffc107; opacity: 0.2;"></i>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="card" style="border-left: 4px solid #dc3545;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: #777;">Thiếu hồ sơ</div>
                        <div style="font-size: 1.8rem; font-weight: bold;"><?php echo $missing_files; ?></div>
                    </div>
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545; opacity: 0.2;"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Hoạt động chấm công gần đây</h3>
            <?php if (empty($recent_logs)): ?>
                <p style="color: #777; font-style: italic;">Chưa có dữ liệu hoạt động...</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Dự án</th>
                                <th>Thời gian</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo $log['fullname']; ?></strong></td>
                                    <td><?php echo $log['proj_name']; ?></td>
                                    <td><?php echo date('H:i d/m/Y', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <?php if ($log['check_out']): ?>
                                            <span class="badge badge-secondary">Ra ca</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Vào ca</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
