<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Filter params
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$proj_id = isset($_GET['proj_id']) ? (int)$_GET['proj_id'] : 0;

$where = "WHERE p.month = ? AND p.year = ?";
$params = [$month, $year];

$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    if (empty($allowed_projs)) {
        $where .= " AND 1=0";
    } else {
        $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
        $where .= " AND p.project_id IN ($in_placeholder)";
        $params = array_merge($params, $allowed_projs);
    }
}

if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
if ($proj_id) { $where .= " AND p.project_id = ?"; $params[] = $proj_id; }

$proposals = db_fetch_all("
    SELECT p.*, pr.name as project_name, e.fullname as creator_name
    FROM material_proposals p
    JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN employees e ON p.created_by = e.id
    $where
    ORDER BY p.created_at DESC
", $params);

$projects = db_fetch_all("SELECT * FROM projects WHERE status = 'active' ORDER BY name ASC");

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý Đề xuất Vật tư</h1>
            <div class="header-actions">
                <?php if (has_permission('manage_system')): ?>
                    <a href="report_consolidation.php" class="btn btn-info"><i class="fas fa-file-invoice"></i> Tổng hợp mua hàng</a>
                    <a href="supplies.php" class="btn btn-secondary"><i class="fas fa-boxes"></i> Danh mục Vật tư</a>
                <?php endif; ?>
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo đề xuất mới</a>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                <select name="month" class="form-control" style="flex: 0 0 100px;">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-control" style="flex: 0 0 120px;">
                    <?php for($y=date('Y')-1; $y<=date('Y')+1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <select name="proj_id" class="form-control" style="flex: 1; min-width: 200px;">
                    <option value="0">-- Tất cả Dự án --</option>
                    <?php foreach ($projects as $p): 
                        if ($allowed_projs !== 'ALL' && !in_array($p['id'], $allowed_projs)) continue;
                    ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $proj_id == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-control" style="flex: 0 0 150px;">
                    <option value="">-- Trạng thái --</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                    <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                    <option value="purchasing" <?php echo $status == 'purchasing' ? 'selected' : ''; ?>>Đang mua hàng</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Đã từ chối</option>
                </select>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Dự án</th>
                            <th>Ngày tạo</th>
                            <th>Nội dung</th>
                            <th style="text-align: right;">Tổng dự kiến</th>
                            <th style="text-align: right;">Tổng duyệt</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th width="80" style="text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proposals)): ?>
                            <tr><td colspan="8" class="text-center" style="padding: 30px; color: #94a3b8;">Không tìm thấy phiếu đề xuất nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($proposals as $p): 
                                $status_badge = [
                                    'draft' => 'badge-secondary',
                                    'pending' => 'badge-warning',
                                    'approved' => 'badge-success',
                                    'purchasing' => 'badge-info',
                                    'partially_delivered' => 'badge-info',
                                    'completed' => 'badge-secondary',
                                    'rejected' => 'badge-danger',
                                    'cancelled' => 'badge-danger'
                                ];
                                $status_text = [
                                    'draft' => 'Bản nháp',
                                    'pending' => 'Chờ duyệt',
                                    'approved' => 'Đã duyệt',
                                    'purchasing' => 'Đang mua',
                                    'partially_delivered' => 'Giao 1 phần',
                                    'completed' => 'Đã nhận đủ',
                                    'rejected' => 'Từ chối',
                                    'cancelled' => 'Đã hủy'
                                ];
                            ?>
                                <tr>
                                    <td><strong><?php echo $p['code']; ?></strong></td>
                                    <td><?php echo $p['project_name']; ?></td>
                                    <td><small><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></small></td>
                                    <td><?php echo $p['title']; ?></td>
                                    <td style="text-align: right; font-weight: 500;"><?php echo number_format($p['total_amount_est']); ?></td>
                                    <td style="text-align: right; font-weight: 700; color: var(--primary-color);">
                                        <?php echo $p['total_amount_final'] > 0 ? number_format($p['total_amount_final']) : '-'; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?php echo $status_badge[$p['status']]; ?>">
                                            <?php echo $status_text[$p['status']]; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 5px;">
                                            <a href="view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-info" style="padding: 5px 8px;" title="Chi tiết"><i class="fas fa-eye"></i></a>
                                            
                                            <?php if ($p['created_by'] == $_SESSION['user_id'] && in_array($p['status'], ['draft', 'cancelled'])): ?>
                                                <a href="edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning" style="padding: 5px 8px;" title="Sửa"><i class="fas fa-edit"></i></a>
                                            <?php endif; ?>

                                            <?php
                                            $can_del = false;
                                            if (has_permission('manage_system')) $can_del = true;
                                            elseif ($p['created_by'] == $_SESSION['user_id'] && in_array($p['status'], ['draft', 'cancelled'])) $can_del = true;
                                            
                                            if ($can_del):
                                            ?>
                                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $p['id']; ?>)" class="btn btn-sm btn-danger" style="padding: 5px 8px;" title="Xóa"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
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

<script>
function confirmDelete(id) {
    Modal.confirm('Bạn có chắc chắn muốn xóa phiếu đề xuất này? Hành động này không thể hoàn tác.', () => {
        window.location.href = 'delete.php?id=' + id;
    });
}
</script>
</div>
<?php include '../../../includes/footer.php'; ?>
