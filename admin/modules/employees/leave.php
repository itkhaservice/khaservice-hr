<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) redirect('index.php');

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch Leave Balance
$balance = db_fetch_row("SELECT * FROM employee_leave_balances WHERE employee_id = ? AND year = ?", [$id, $year]);

// Fetch Leave History FROM Attendance Table (where symbol starts with P)
$leave_history = db_fetch_all("
    SELECT date, timekeeper_symbol, 
           (CASE WHEN timekeeper_symbol = 'P' THEN 1.0 ELSE 0.5 END) as day_count,
           project_id
    FROM attendance
    WHERE employee_id = ? AND YEAR(date) = ? AND (timekeeper_symbol = 'P' OR timekeeper_symbol = '1/P')
    ORDER BY date DESC
", [$id, $year]);

// Fetch Other Leave (CĐ, Ô, NB...)
$other_leave = db_fetch_all("
    SELECT date, timekeeper_symbol, project_id
    FROM attendance
    WHERE employee_id = ? AND YEAR(date) = ? AND timekeeper_symbol IN ('CĐ', 'Ô', 'VR', 'NB', 'KL', 'TS')
    ORDER BY date DESC
", [$id, $year]);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <div>
                <h1 class="page-title">Quản lý Phép: <?php echo $employee['fullname']; ?></h1>
                <p style="color: var(--text-sub);">Dữ liệu dựa trên Bảng Chấm Công năm <?php echo $year; ?></p>
            </div>
            <div class="header-actions" style="display: flex; gap: 8px; align-items: center;">
                <button class="btn btn-primary btn-sm" onclick="$('#updateQuotaModal').fadeIn();"><i class="fas fa-edit"></i> Thiết lập quỹ phép</button>
                <select class="form-control" style="width: 110px; height: 34px; padding: 0 10px; font-size: 0.85rem;" onchange="location.href='?id=<?php echo $id; ?>&year='+this.value">
                    <?php for($y=2024; $y<=2026; $y++) echo "<option value='$y' ".($y==$year?'selected':'').">Năm $y</option>"; ?>
                </select>
                <a href="../reports/leave_report.php?year=<?php echo $year; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <?php
        // Xử lý cập nhật Quỹ phép
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quota'])) {
            $total = (float)$_POST['total_days'];
            $carried = (float)$_POST['carried_over'];
            db_query("INSERT INTO employee_leave_balances (employee_id, year, total_days, carried_over) 
                      VALUES (?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE total_days = ?, carried_over = ?", 
                      [$id, $year, $total, $carried, $total, $carried]);
            set_toast('success', 'Đã cập nhật quỹ phép năm ' . $year);
            echo "<script>location.href='leave.php?id=$id&year=$year';</script>";
            exit;
        }
        ?>

        <!-- Leave Stats -->
        <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 25px;">
            <div class="card" style="padding: 15px; border-left: 4px solid #3b82f6;">
                <div style="font-size: 0.8rem; color: var(--text-sub);">TỔNG QUỸ PHÉP <?php echo $year; ?></div>
                <div style="font-size: 1.5rem; font-weight: 800;"><?php echo ($balance['total_days'] ?? 12) + ($balance['carried_over'] ?? 0); ?></div>
            </div>
            <div class="card" style="padding: 15px; border-left: 4px solid #ef4444;">
                <div style="font-size: 0.8rem; color: var(--text-sub);">ĐÃ NGHỈ (P)</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #ef4444;"><?php echo $balance['used_days'] ?? 0; ?></div>
            </div>
            <div class="card" style="padding: 15px; border-left: 4px solid #10b981;">
                <div style="font-size: 0.8rem; color: var(--text-sub);">CÒN LẠI</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #10b981;">
                    <?php echo (($balance['total_days'] ?? 12) + ($balance['carried_over'] ?? 0)) - ($balance['used_days'] ?? 0); ?>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <!-- Phép (P) History -->
            <div class="card">
                <h3>Chi tiết Nghỉ phép (P)</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ngày nghỉ</th>
                                <th>Ký hiệu</th>
                                <th>Số ngày trừ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leave_history)): ?>
                                <tr><td colspan="3" class="text-center">Chưa có ngày nghỉ phép nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($leave_history as $lh): ?>
                                    <tr>
                                        <td><strong><?php echo date('d/m/Y', strtotime($lh['date'])); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo $lh['timekeeper_symbol']; ?></span></td>
                                        <td><?php echo $lh['day_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Other Absence History -->
            <div class="card">
                <h3>Nghỉ loại khác (CĐ, Ô, VR, NB...)</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ngày nghỉ</th>
                                <th>Ký hiệu</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($other_leave)): ?>
                                <tr><td colspan="3" class="text-center">Chưa có ghi nhận nghỉ khác.</td></tr>
                            <?php else: ?>
                                <?php foreach ($other_leave as $ol): ?>
                                    <tr>
                                        <td><strong><?php echo date('d/m/Y', strtotime($ol['date'])); ?></strong></td>
                                        <td><span class="badge badge-secondary"><?php echo $ol['timekeeper_symbol']; ?></span></td>
                                        <td><small>Dữ liệu từ BCC</small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Thiết lập Quỹ phép -->
<div id="updateQuotaModal" class="modal-overlay">
    <div class="modal-box" style="width: 400px; text-align: left;">
        <h3 class="modal-title">Thiết lập Quỹ phép năm <?php echo $year; ?></h3>
        <form method="POST">
            <div class="form-group">
                <label>Tổng ngày phép tiêu chuẩn (VD: 12.0)</label>
                <input type="number" step="0.5" name="total_days" class="form-control" value="<?php echo $balance['total_days'] ?? 12; ?>" required>
            </div>
            <div class="form-group">
                <label>Phép năm cũ chuyển sang (VD: 2.5)</label>
                <input type="number" step="0.5" name="carried_over" class="form-control" value="<?php echo $balance['carried_over'] ?? 0; ?>" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="$('#updateQuotaModal').fadeOut();">Hủy</button>
                <button type="submit" name="update_quota" class="btn btn-primary">Lưu thiết lập</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>