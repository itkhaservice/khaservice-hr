<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// 1. Personnel by Project
$report_projects = db_fetch_all("
    SELECT p.name, p.code, COUNT(e.id) as total 
    FROM projects p 
    LEFT JOIN employees e ON p.id = e.current_project_id AND e.status = 'working'
    GROUP BY p.id 
    ORDER BY total DESC
");

// 2. Personnel by Department
$report_depts = db_fetch_all("
    SELECT d.name, d.code, COUNT(e.id) as total 
    FROM departments d 
    LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'working'
    GROUP BY d.id 
    ORDER BY total DESC
");

// 3. Missing Documents Report
$mandatory_docs = ['CCCD', 'HK', 'SYLL', 'BC', 'GKSK'];
$missing_docs_list = db_fetch_all("
    SELECT e.id, e.code, e.fullname, p.name as proj_name,
    (SELECT GROUP_CONCAT(doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1) as submitted_types
    FROM employees e
    LEFT JOIN projects p ON e.current_project_id = p.id
    WHERE e.status = 'working'
");

// Filter the list in PHP for clarity
$final_missing_report = [];
foreach ($missing_docs_list as $row) {
    $submitted = $row['submitted_types'] ? explode(',', $row['submitted_types']) : [];
    $missing = array_diff($mandatory_docs, $submitted);
    
    if (!empty($missing)) {
        $row['missing_count'] = count($missing);
        $row['missing_labels'] = implode(', ', $missing);
        $final_missing_report[] = $row;
    }
}
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
        <h1 class="page-title">Báo cáo & Thống kê</h1>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- Project Report -->
            <div class="card">
                <h3><i class="fas fa-building text-primary"></i> Nhân sự theo Dự án</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Dự án</th>
                                <th style="text-align:right;">Số nhân viên</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_projects as $rp): ?>
                                <tr>
                                    <td><?php echo $rp['name']; ?> (<?php echo $rp['code']; ?>)</td>
                                    <td style="text-align:right;"><strong><?php echo $rp['total']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Department Report -->
            <div class="card">
                <h3><i class="fas fa-users text-success"></i> Nhân sự theo Phòng ban</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Phòng ban</th>
                                <th style="text-align:right;">Số nhân viên</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_depts as $rd): ?>
                                <tr>
                                    <td><?php echo $rd['name']; ?></td>
                                    <td style="text-align:right;"><strong><?php echo $rd['total']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Missing Documents Detailed Report -->
        <div class="card">
            <h3><i class="fas fa-exclamation-triangle text-danger"></i> Danh sách nhân viên thiếu hồ sơ bắt buộc</h3>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">(Bao gồm: CCCD, HK, SYLL, BC, GKSK)</p>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Dự án</th>
                            <th>Số loại thiếu</th>
                            <th>Chi tiết thiếu</th>
                            <th width="100">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($final_missing_report)): ?>
                            <tr><td colspan="6" style="text-align:center;">Tất cả nhân viên đã nộp đủ hồ sơ</td></tr>
                        <?php else: ?>
                            <?php foreach ($final_missing_report as $m): ?>
                                <tr>
                                    <td><?php echo $m['code']; ?></td>
                                    <td><strong><?php echo $m['fullname']; ?></strong></td>
                                    <td><?php echo $m['proj_name']; ?></td>
                                    <td style="text-align:center;"><span class="badge badge-danger"><?php echo $m['missing_count']; ?></span></td>
                                    <td style="color: #dc3545; font-size: 0.85rem;"><?php echo $m['missing_labels']; ?></td>
                                    <td>
                                        <a href="../employees/documents.php?id=<?php echo $m['id']; ?>" class="btn btn-primary btn-sm">Cập nhật</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<style>
.text-primary { color: #108042; }
.text-success { color: #28a745; }
.text-danger { color: #dc3545; }
.badge-danger { background-color: #dc3545; color: #fff; padding: 2px 8px; border-radius: 10px; }
.btn-sm { padding: 4px 8px; font-size: 12px; }
</style>

<?php include '../../../includes/footer.php'; ?>
