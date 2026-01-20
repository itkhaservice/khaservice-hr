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

// Pending Documents Logic
$pending_docs = db_fetch_all("
    SELECT d.*, e.fullname, e.code as emp_code, s.name as doc_name
    FROM documents d
    JOIN employees e ON d.employee_id = e.id
    JOIN document_settings s ON d.doc_type = s.code
    WHERE d.approval_status = 'pending'
    ORDER BY d.created_at DESC
");

// Recruitment Warning Logic
$projects_shortage = [];
$total_shortage = 0; // Initialize total shortage
$projects = db_fetch_all("SELECT * FROM projects WHERE status = 'active'");

foreach ($projects as $p) {
    // Check detailed staffing positions
    $positions_req = db_fetch_all("SELECT * FROM project_positions WHERE project_id = ?", [$p['id']]);
    
    foreach ($positions_req as $req) {
        $actual = db_fetch_row("SELECT COUNT(*) as c FROM employees WHERE current_project_id = ? AND position = ? AND status = 'working'", [$p['id'], $req['position_name']])['c'];
        
        if ($actual < $req['count_required']) {
            $missing = $req['count_required'] - $actual;
            $total_shortage += $missing; // Sum up the shortage
            $projects_shortage[] = [
                'project_name' => $p['name'],
                'position' => $req['position_name'],
                'missing' => $missing,
                'required' => $req['count_required'],
                'actual' => $actual
            ];
        }
    }
}

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
    <?php include '../includes/topbar.php'; ?>

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
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: #777;">Nhân sự còn thiếu</div>
                        <div style="font-size: 1.8rem; font-weight: bold; color: #dc2626;"><?php echo $total_shortage; ?></div>
                    </div>
                    <i class="fas fa-user-minus" style="font-size: 2rem; color: #f59e0b; opacity: 0.2;"></i>
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

        <!-- Pending Documents Warning Section -->
        <?php if (!empty($pending_docs)): ?>
        <div class="card" style="border-top: 4px solid #3b82f6; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #3b82f6;"><i class="fas fa-file-import"></i> HỒ SƠ CHỜ DUYỆT</h3>
                <span class="badge badge-info">Số lượng: <?php echo count($pending_docs); ?> hồ sơ</span>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead style="background-color: #dbeafe;">
                        <tr>
                            <th>Nhân viên</th>
                            <th>Loại hồ sơ</th>
                            <th>Ngày nộp</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_docs as $pd): ?>
                            <tr>
                                <td><strong><?php echo $pd['fullname']; ?></strong> (<?php echo $pd['emp_code']; ?>)</td>
                                <td><?php echo $pd['doc_name']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pd['created_at'])); ?></td>
                                <td>
                                    <a href="modules/employees/pending_docs.php" class="btn btn-primary btn-sm">Xử lý ngay</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recruitment Warning Section -->
        <?php if (!empty($projects_shortage)): ?>
        <div class="card" style="border-top: 4px solid #dc2626; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #dc2626;"><i class="fas fa-exclamation-circle"></i> CẢNH BÁO THIẾU NHÂN SỰ</h3>
                <span class="badge badge-danger">Cần tuyển gấp: <?php echo count($projects_shortage); ?> vị trí</span>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead style="background-color: #fee2e2;">
                        <tr>
                            <th>Dự án</th>
                            <th>Vị trí thiếu</th>
                            <th>Định biên</th>
                            <th>Hiện tại</th>
                            <th>Cần tuyển thêm</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects_shortage as $s): ?>
                            <tr>
                                <td><strong><?php echo $s['project_name']; ?></strong></td>
                                <td><?php echo $s['position']; ?></td>
                                <td><?php echo $s['required']; ?></td>
                                <td><?php echo $s['actual']; ?></td>
                                <td><span style="font-weight: bold; color: #dc2626; font-size: 1.1rem;">+<?php echo $s['missing']; ?></span></td>
                                <td>
                                    <a href="modules/employees/add.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Tuyển</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

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
