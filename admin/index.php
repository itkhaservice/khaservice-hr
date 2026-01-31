<?php
require_once '../config/db.php';
include '../includes/header.php';
include '../includes/sidebar.php';

// Real data for dashboard
$total_employees = get_count("SELECT COUNT(*) as count FROM employees WHERE status = 'working'");
$total_projects = get_count("SELECT COUNT(*) as count FROM projects WHERE status = 'active'");
$working_today = get_count("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND check_out IS NULL");

// Counting missing mandatory documents
$total_mandatory = 5; 
$missing_files_sql = "
    SELECT COUNT(*) as count FROM (
        SELECT employee_id FROM documents 
        WHERE doc_type IN ('CCCD', 'HK', 'SYLL', 'BC', 'GKSK') AND is_submitted = 1
        GROUP BY employee_id
        HAVING COUNT(DISTINCT doc_type) < $total_mandatory
    ) as t
";
$no_docs_sql = "SELECT COUNT(*) as count FROM employees e WHERE NOT EXISTS (SELECT 1 FROM documents d WHERE d.employee_id = e.id)";
$missing_files = get_count($missing_files_sql) + get_count($no_docs_sql);

// Pending Material Proposals Stat
$total_pending_proposals = get_count("SELECT COUNT(*) as count FROM material_proposals WHERE status = 'pending'");

// Pending Documents Logic
$pending_docs = db_fetch_all("
    SELECT d.*, e.fullname, e.code as emp_code, s.name as doc_name
    FROM documents d
    JOIN employees e ON d.employee_id = e.id
    JOIN document_settings s ON d.doc_type = s.code
    WHERE d.approval_status = 'pending'
    ORDER BY d.created_at DESC
") ?: [];

// Pending Material Proposals List
$pending_proposals = db_fetch_all("
    SELECT p.*, pr.name as proj_name 
    FROM material_proposals p 
    JOIN projects pr ON p.project_id = pr.id 
    WHERE p.status = 'pending' 
    ORDER BY p.created_at DESC 
    LIMIT 5
") ?: [];

// Recruitment Warning Logic
$projects_shortage = [];
$total_shortage = 0;
$projects = db_fetch_all("SELECT * FROM projects WHERE status = 'active'") ?: [];

foreach ($projects as $p) {
    $positions_req = db_fetch_all("SELECT * FROM project_positions WHERE project_id = ?", [$p['id']]) ?: [];
    foreach ($positions_req as $req) {
        $actual = get_count("SELECT COUNT(*) as count FROM employees WHERE current_project_id = ? AND position = ? AND status = 'working'", [$p['id'], $req['position_name']]);
        if ($actual < $req['count_required']) {
            $missing = $req['count_required'] - $actual;
            $total_shortage += $missing;
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
    ORDER BY a.updated_at DESC LIMIT 5
") ?: [];
?>

<div class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Tổng quan Hệ thống</h1>
            <div class="header-actions">
                <span class="badge badge-secondary"><?php echo date('d/m/Y'); ?></span>
            </div>
        </div>

        <!-- Stats Cards Grid -->
        <div class="dashboard-grid">
            <div class="card" style="margin-bottom: 0; border-left: 5px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-sub); margin-bottom: 5px;">Tổng nhân sự</div>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo $total_employees; ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(36, 162, 92, 0.1); color: var(--primary-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0; border-left: 5px solid #0ea5e9;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-sub); margin-bottom: 5px;">Dự án hoạt động</div>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo $total_projects; ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0; border-left: 5px solid #f59e0b; cursor: pointer;" onclick="location.href='modules/proposals/index.php?status=pending'">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-sub); margin-bottom: 5px;">Đề xuất chờ duyệt</div>
                        <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;"><?php echo $total_pending_proposals; ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0; border-left: 5px solid #ef4444;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-sub); margin-bottom: 5px;">Hồ sơ chưa đủ</div>
                        <div style="font-size: 2rem; font-weight: 800; color: #ef4444;"><?php echo $missing_files; ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            <!-- Left Side -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                
                <!-- Pending Material Proposals -->
                <?php if (!empty($pending_proposals)): ?>
                <div class="card" style="border-top: 4px solid #f59e0b; padding: 0; overflow: hidden;">
                    <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #f59e0b; font-size: 1.1rem;"><i class="fas fa-shopping-cart"></i> Đề xuất vật tư mới nhất</h3>
                        <a href="modules/proposals/index.php?status=pending" class="btn btn-sm btn-primary">Xử lý ngay</a>
                    </div>
                    <div class="table-container" style="border: none; border-radius: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Dự án / Nội dung</th>
                                    <th style="text-align:right;">Tổng dự kiến</th>
                                    <th style="text-align:right; padding-right: 20px;">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_proposals as $pp): ?>
                                    <tr>
                                        <td style="padding: 12px 15px;">
                                            <div style="font-weight: 700; color: var(--text-main);"><?php echo $pp['proj_name']; ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-sub);"><?php echo $pp['title']; ?></div>
                                        </td>
                                        <td style="text-align:right; font-weight:700; color: var(--primary-color); vertical-align: middle;">
                                            <?php echo number_format($pp['total_amount_est']); ?> đ
                                        </td>
                                        <td style="text-align:right; color:var(--text-sub); vertical-align: middle; padding-right: 20px;">
                                            <div style="font-weight: 600;"><?php echo date('H:i', strtotime($pp['created_at'])); ?></div>
                                            <div style="font-size: 0.75rem;"><?php echo date('d/m/y', strtotime($pp['created_at'])); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pending Documents Warning -->
                <?php if (!empty($pending_docs)): ?>
                <div class="card" style="border-top: 4px solid #3b82f6; padding: 0; overflow: hidden;">
                    <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #3b82f6; font-size: 1.1rem;"><i class="fas fa-file-import"></i> Hồ sơ nhân sự chờ duyệt</h3>
                        <a href="modules/employees/pending_docs.php" class="btn btn-sm btn-primary">Duyệt ngay</a>
                    </div>
                    <div class="table-container" style="border: none; border-radius: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Loại hồ sơ</th>
                                    <th style="text-align:right; padding-right: 20px;">Ngày nộp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_docs as $pd): ?>
                                    <tr>
                                        <td><strong><?php echo $pd['fullname']; ?></strong> (<?php echo $pd['emp_code']; ?>)</td>
                                        <td><span class="badge badge-secondary"><?php echo $pd['doc_name']; ?></span></td>
                                        <td style="text-align:right; padding-right: 20px;"><?php echo date('d/m/Y', strtotime($pd['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recruitment Warning Section -->
                <?php if (!empty($projects_shortage)): ?>
                <div class="card" style="border-top: 4px solid #ef4444; padding: 0; overflow: hidden;">
                    <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #ef4444; font-size: 1.1rem;"><i class="fas fa-exclamation-circle"></i> Cảnh báo thiếu nhân sự</h3>
                        <span class="badge badge-danger"><?php echo count($projects_shortage); ?> vị trí</span>
                    </div>
                    <div class="table-container" style="border: none; border-radius: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Dự án</th>
                                    <th>Vị trí thiếu</th>
                                    <th style="text-align:center;">Định biên</th>
                                    <th style="text-align:center;">Cần tuyển</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects_shortage as $s): ?>
                                    <tr>
                                        <td><strong><?php echo $s['project_name']; ?></strong></td>
                                        <td><?php echo $s['position']; ?></td>
                                        <td style="text-align:center;"><?php echo $s['actual']; ?>/<?php echo $s['required']; ?></td>
                                        <td style="text-align:center;"><span style="font-weight: 800; color: #ef4444;">+<?php echo $s['missing']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Attendance Activity (RE-ADDED) -->
                <div class="card" style="padding: 0; overflow: hidden;">
                    <div style="padding: 20px;">
                        <h3 style="margin: 0; font-size: 1.1rem;"><i class="fas fa-history"></i> Hoạt động chấm công mới nhất</h3>
                    </div>
                    <div class="table-container" style="border: none; border-radius: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Dự án / Nhân viên</th>
                                    <th style="text-align:right; padding-right: 20px;">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_logs)): ?>
                                    <tr><td colspan="2" style="text-align:center; padding: 30px; color: var(--text-sub);">Chưa có hoạt động mới</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_logs as $log): ?>
                                        <tr>
                                            <td style="padding: 12px 15px;">
                                                <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; margin-bottom: 2px;"><?php echo $log['proj_name']; ?></div>
                                                <div style="font-size: 0.85rem; color: var(--text-sub);">Nhân viên: <?php echo $log['fullname']; ?></div>
                                            </td>
                                            <td style="text-align:right; padding-right: 20px; vertical-align: middle;">
                                                <div style="font-weight: 700; color: var(--primary-color); font-size: 1rem;"><?php echo date('H:i', strtotime($log['updated_at'])); ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-sub);"><?php echo date('d/m/Y', strtotime($log['updated_at'])); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                <?php
                $today_d = (int)date('j'); $curr_m = (int)date('n'); $curr_y = (int)date('Y');
                if ($today_d <= 5) { $target_m = ($curr_m == 1) ? 12 : $curr_m - 1; $target_y = ($curr_m == 1) ? $curr_y - 1 : $curr_y; } else { $target_m = $curr_m; $target_y = $curr_y; }
                $att_progress = db_fetch_all("SELECT p.id, p.name, l.is_locked, l.locked_at FROM projects p LEFT JOIN attendance_locks l ON p.id = l.project_id AND l.month = $target_m AND l.year = $target_y WHERE p.status = 'active' ORDER BY l.is_locked DESC, p.name ASC");
                ?>
                <div class="card" style="padding: 0; overflow: hidden; border-top: 4px solid var(--primary-color);">
                    <div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 1rem;"><i class="fas fa-tasks"></i> Chốt công T<?php echo "$target_m/$target_y"; ?></h3>
                        <span class="badge badge-secondary"><?php echo count($att_progress); ?> Dự án</span>
                    </div>
                    <div class="table-container" style="border: none; border-radius: 0; max-height: 250px;">
                        <table class="table">
                            <tbody>
                                <?php if(empty($att_progress)): ?><tr><td class="text-center" style="color: var(--text-sub);">Không có dự án hoạt động</td></tr>
                                <?php else: foreach($att_progress as $ap): $is_done = ($ap['is_locked'] == 1); ?>
                                    <tr>
                                        <td style="padding: 10px 15px;"><div style="font-weight: 600; color: var(--text-main);"><?php echo $ap['name']; ?></div></td>
                                        <td style="width: 40px; text-align: right; padding-right: 15px;"><?php if($is_done): ?><i class="fas fa-check-circle" style="color: var(--primary-color); font-size: 1.2rem;"></i><?php else: ?><i class="far fa-circle" style="color: #cbd5e1; font-size: 1.2rem;"></i><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3 style="margin-top: 0; font-size: 1rem;">Lối tắt nhanh</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="modules/proposals/add.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-cart-plus"></i> Tạo đề xuất vật tư</a>
                        <a href="modules/attendance/index.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-calendar-check"></i> Chấm công hôm nay</a>
                        <a href="modules/reports/index.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-chart-bar"></i> Báo cáo tổng hợp</a>
                    </div>
                </div>

                <div class="card" style="background: var(--primary-color); color: #fff;">
                    <h3 style="margin-top: 0; font-size: 1rem;">Mẹo quản trị</h3>
                    <p style="font-size: 0.85rem; opacity: 0.9; line-height: 1.6;">Hãy kiểm tra mục <strong>Đề xuất vật tư</strong> mới hàng ngày để kịp thời tổng hợp mua hàng vào cuối tháng (ngày 25-28).</p>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
