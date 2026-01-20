<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$id]);

if (!$project) {
    redirect('index.php');
}

// Fetch shifts for this project
$shifts = db_fetch_all("SELECT * FROM shifts WHERE project_id = ? ORDER BY start_time ASC", [$id]);

// Fetch some stats (count employees)
$emp_count = db_fetch_row("SELECT COUNT(*) as count FROM employees WHERE current_project_id = ?", [$id])['count'];

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
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
        <div class="action-header">
            <div>
                <h1 class="page-title" style="margin-bottom: 5px;"><?php echo $project['name']; ?></h1>
                <p style="color: var(--text-sub);"><i class="fas fa-map-marker-alt"></i> <?php echo $project['address']; ?></p>
            </div>
            <div class="header-actions">
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px;">
            <!-- Left Column: Basic Info -->
            <div class="card">
                <h3>Thông tin chung</h3>
                <div style="margin-top: 20px;">
                    <div style="margin-bottom: 15px;">
                        <small style="color: var(--text-sub); display: block;">Mã dự án</small>
                        <strong style="font-size: 1.1rem;"><?php echo $project['code']; ?></strong>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <small style="color: var(--text-sub); display: block;">Trạng thái</small>
                        <?php 
                            $status_class = [
                                'active' => 'badge-success',
                                'completed' => 'badge-info',
                                'pending' => 'badge-warning'
                            ][$project['status']] ?? 'badge-secondary';
                        ?>
                        <span class="badge <?php echo $status_class; ?>">
                            <?php 
                                echo [
                                    'active' => 'Đang hoạt động',
                                    'completed' => 'Hoàn thành',
                                    'pending' => 'Tạm dừng'
                                ][$project['status']] ?? $project['status'];
                            ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <small style="color: var(--text-sub); display: block;">Nhân sự đang trực</small>
                        <strong style="font-size: 1.2rem; color: var(--primary-color);"><?php echo $emp_count; ?></strong> nhân viên
                    </div>
                </div>
            </div>

            <!-- Right Column: Shifts -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Danh sách ca làm việc</h3>
                    <a href="edit.php?id=<?php echo $id; ?>#shifts" class="btn btn-secondary btn-sm"><i class="fas fa-cog"></i> Cấu hình ca</a>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tên ca</th>
                                <th>Loại</th>
                                <th>Khung giờ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                                <tr><td colspan="4" style="text-align:center; padding: 30px; color: #999;">Chưa có cấu hình ca làm việc cho dự án này.</td></tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $s): ?>
                                    <tr>
                                        <td><strong><?php echo $s['name']; ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo $s['type']; ?></span></td>
                                        <td>
                                            <i class="far fa-clock"></i> 
                                            <?php echo date('H:i', strtotime($s['start_time'])); ?> - <?php echo date('H:i', strtotime($s['end_time'])); ?>
                                        </td>
                                        <td><span class="badge badge-success">Sẵn sàng</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 25px;">
            <h3>Nhân sự tham gia dự án</h3>
            <p style="color: #999; font-style: italic; padding: 20px 0;">Tính năng hiển thị danh sách nhân sự chi tiết đang được cập nhật...</p>
        </div>
    </div>

<style>
.btn-sm { padding: 5px 12px; font-size: 12px; }
</style>

<?php include '../../../includes/footer.php'; ?>
