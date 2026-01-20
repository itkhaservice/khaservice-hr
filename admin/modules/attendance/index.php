<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

$date = isset($_GET['date']) ? clean_input($_GET['date']) : date('Y-m-d');
$proj_id = isset($_GET['proj_id']) ? (int)$_GET['proj_id'] : 0;
$kw = isset($_GET['kw']) ? clean_input($_GET['kw']) : '';

// Build Query
$where = "WHERE a.date = ?";
$params = [$date];

if ($proj_id) {
    $where .= " AND a.project_id = ?";
    $params[] = $proj_id;
}
if ($kw) {
    $where .= " AND e.fullname LIKE ?";
    $params[] = "%$kw%";
}

$sql = "SELECT a.*, e.fullname, e.code as emp_code, p.name as proj_name, s.name as shift_name, s.start_time, s.end_time 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        JOIN projects p ON a.project_id = p.id 
        JOIN shifts s ON a.shift_id = s.id 
        $where 
        ORDER BY a.check_in DESC";
$logs = db_fetch_all($sql, $params);

$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");
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
            <h1 class="page-title">Nhật ký chấm công: <?php echo date('d/m/Y', strtotime($date)); ?></h1>
            <div class="header-actions">
                <a href="checkin.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Vào ca thủ công</a>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <input type="date" name="date" value="<?php echo $date; ?>">
            <select name="proj_id">
                <option value="">-- Tất cả dự án --</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $proj_id == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="kw" value="<?php echo $kw; ?>" placeholder="Tên nhân viên...">
            <button type="submit" class="btn btn-secondary">Xem</button>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Dự án</th>
                            <th>Ca làm việc</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" style="text-align:center;">Không có dữ liệu chấm công cho ngày này</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><strong><?php echo $l['fullname']; ?></strong><br><small><?php echo $l['emp_code']; ?></small></td>
                                    <td><?php echo $l['proj_name']; ?></td>
                                    <td><?php echo $l['shift_name']; ?><br><small><?php echo substr($l['start_time'],0,5); ?> - <?php echo substr($l['end_time'],0,5); ?></small></td>
                                    <td><?php echo $l['check_in'] ? date('H:i', strtotime($l['check_in'])) : '-'; ?></td>
                                    <td><?php echo $l['check_out'] ? date('H:i', strtotime($l['check_out'])) : '-'; ?></td>
                                    <td>
                                        <?php 
                                            $s_map = [
                                                'working' => ['text' => 'Đang làm', 'class' => 'badge-info'],
                                                'completed' => ['text' => 'Hoàn thành', 'class' => 'badge-success'],
                                                'late' => ['text' => 'Đi muộn', 'class' => 'badge-warning'],
                                                'early' => ['text' => 'Về sớm', 'class' => 'badge-warning'],
                                                'absent' => ['text' => 'Vắng mặt', 'class' => 'badge-danger'],
                                            ];
                                            $s = $s_map[$l['status']] ?? ['text' => $l['status'], 'class' => 'badge-secondary'];
                                        ?>
                                        <span class="badge <?php echo $s['class']; ?>"><?php echo $s['text']; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!$l['check_out']): ?>
                                            <a href="checkout_action.php?id=<?php echo $l['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận ra ca cho nhân viên này?')">Ra ca</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../../../includes/footer.php'; ?>
