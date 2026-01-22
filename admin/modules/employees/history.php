<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) {
    redirect('index.php');
}

// Fetch Status History
$status_history = db_fetch_all("
    SELECT h.*, u.fullname as creator_name 
    FROM employee_status_history h
    LEFT JOIN users u ON h.created_by = u.id
    WHERE h.employee_id = ?
    ORDER BY h.created_at DESC
", [$id]);

// Fetch Attendance Logs (Last 100 changes)
$att_logs = db_fetch_all("
    SELECT l.*, u.fullname as changer_name, p.name as proj_name
    FROM attendance_logs l
    LEFT JOIN users u ON l.changed_by = u.id
    LEFT JOIN projects p ON l.project_id = p.id
    WHERE l.employee_id = ?
    ORDER BY l.changed_at DESC
    LIMIT 100
", [$id]);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <div>
                <h1 class="page-title">Lịch sử Nhân sự: <?php echo $employee['fullname']; ?></h1>
                <p style="color: var(--text-sub);"><i class="fas fa-id-card"></i> Mã NV: <?php echo $employee['code']; ?></p>
            </div>
            <div class="header-actions">
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <!-- Column 1: Status Changes (Joining/Leaving) -->
            <div class="card">
                <h3 style="margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px;">
                    <i class="fas fa-history text-primary"></i> Quá trình Công tác
                </h3>
                <div class="timeline" style="position: relative; padding-left: 30px;">
                    <div style="position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>
                    
                    <?php if (empty($status_history)): ?>
                        <p style="color: #94a3b8;">Chưa có dữ liệu lịch sử.</p>
                    <?php else: ?>
                        <?php foreach ($status_history as $h): ?>
                            <div class="timeline-item" style="position: relative; margin-bottom: 25px;">
                                <div style="position: absolute; left: -25px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-color); border: 2px solid #fff; box-shadow: 0 0 0 3px #f1f5f9;"></div>
                                <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 5px;">
                                    <?php 
                                        $s_map = ['working' => 'Đi làm / Tái tuyển dụng', 'resigned' => 'Nghỉ việc', 'maternity_leave' => 'Nghỉ thai sản', 'unpaid_leave' => 'Nghỉ không lương'];
                                        echo $s_map[$h['new_status']] ?? $h['new_status'];
                                    ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-sub);">
                                    <i class="far fa-calendar-alt"></i> Ngày áp dụng: <?php echo date('d/m/Y', strtotime($h['change_date'])); ?>
                                </div>
                                <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-top: 8px; font-size: 0.85rem; border: 1px solid #e2e8f0;">
                                    <strong>Ghi chú:</strong> <?php echo $h['note']; ?><br>
                                    <small style="color: #64748b;"><i class="fas fa-user-edit"></i> Thực hiện bởi: <?php echo $h['creator_name']; ?> lúc <?php echo date('H:i d/m/Y', strtotime($h['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: Attendance Audit Logs -->
            <div class="card">
                <h3 style="margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px;">
                    <i class="fas fa-user-shield text-danger"></i> Nhật ký Chỉnh sửa Công (Audit)
                </h3>
                <div class="table-container" style="max-height: 600px; overflow-y: auto;">
                    <table class="table" style="font-size: 0.85rem;">
                        <thead style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>Ngày công</th>
                                <th>Loại</th>
                                <th>Cũ -> Mới</th>
                                <th>Người sửa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($att_logs)): ?>
                                <tr><td colspan="4" style="text-align:center; padding: 20px; color: #94a3b8;">Chưa có log chỉnh sửa nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($att_logs as $l): ?>
                                    <tr>
                                        <td><strong><?php echo date('d/m/Y', strtotime($l['attendance_date'])); ?></strong></td>
                                        <td>
                                            <?php if ($l['field_type'] == 'symbol'): ?>
                                                <span class="badge badge-info">Ký hiệu</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Giờ OT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="text-decoration: line-through; color: #94a3b8;"><?php echo $l['old_value'] ?: '(trống)'; ?></span> 
                                            <i class="fas fa-long-arrow-alt-right"></i> 
                                            <span style="font-weight: 700; color: var(--primary-dark);"><?php echo $l['new_value'] ?: '(trống)'; ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo $l['changer_name']; ?></div>
                                            <small style="color: #94a3b8;"><?php echo date('d/m H:i', strtotime($l['changed_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php include '../../../includes/footer.php'; ?>
