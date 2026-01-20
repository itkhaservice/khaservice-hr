<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Pagination & Filters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$kw = isset($_GET['kw']) ? clean_input($_GET['kw']) : '';
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build Query
$where = "WHERE 1=1";
$params = [];

if ($kw) {
    $where .= " AND (name LIKE ? OR code LIKE ? OR address LIKE ?)";
    $params[] = "%$kw%";
    $params[] = "%$kw%";
    $params[] = "%$kw%";
}
if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

// Get Total
$total_sql = "SELECT COUNT(*) as count FROM projects $where";
$total_records = db_fetch_row($total_sql, $params)['count'];

// Get Data
$sql = "SELECT * FROM projects $where ORDER BY stt ASC, id ASC LIMIT $offset, $limit";
$projects = db_fetch_all($sql, $params);

// Generate Link Template for Pagination
$query_string = $_GET;
unset($query_string['page']);
$link_template = "index.php?" . http_build_query($query_string) . "&page={page}";
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
            <h1 class="page-title">Quản lý Dự án</h1>
            <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dự án</a>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" placeholder="Tìm tên, mã, địa chỉ...">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Tạm dừng</option>
            </select>
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <?php if ($kw || $status): ?>
                <a href="index.php" class="btn btn-danger">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="50">STT</th>
                            <th>Mã dự án</th>
                            <th>Tên dự án</th>
                            <th>Địa chỉ</th>
                            <th>Trạng thái</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="6" style="text-align:center;">Không tìm thấy dữ liệu</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $p): ?>
                                <tr>
                                    <td><?php echo $p['stt']; ?></td>
                                    <td><strong><?php echo $p['code']; ?></strong></td>
                                    <td><?php echo $p['name']; ?></td>
                                    <td><?php echo $p['address']; ?></td>
                                    <td>
                                        <?php 
                                            $status_class = [
                                                'active' => 'badge-success',
                                                'completed' => 'badge-info',
                                                'pending' => 'badge-warning'
                                            ][$p['status']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php 
                                                echo [
                                                    'active' => 'Đang hoạt động',
                                                    'completed' => 'Hoàn thành',
                                                    'pending' => 'Tạm dừng'
                                                ][$p['status']] ?? $p['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $p['id']; ?>" title="Xem & Cấu hình ca"><i class="fas fa-eye text-primary"></i></a> &nbsp;
                                        <a href="edit.php?id=<?php echo $p['id']; ?>" title="Sửa"><i class="fas fa-edit text-warning"></i></a> &nbsp;
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $p['id']; ?>)" title="Xóa"><i class="fas fa-trash text-danger"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- List Footer -->
            <div class="list-footer">
                <div class="display-count">
                    <span>Hiển thị:</span>
                    <select onchange="location.href='index.php?<?php echo http_build_query(array_merge($_GET, ['limit' => ''])); ?>' + this.value">
                        <?php foreach ([5, 10, 15, 20, 50] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $limit == $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span> dòng / trang</span>
                </div>
                <div class="pagination-wrapper">
                    <?php echo paginate($total_records, $page, $limit, $link_template); ?>
                </div>
            </div>
        </div>
    </div>

<script>
function confirmDelete(id) {
    Modal.confirm('Bạn có chắc chắn muốn xóa dự án này? Hành động này không thể hoàn tác.', () => {
        location.href = 'delete.php?id=' + id;
    });
}
</script>

<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    color: #fff;
}
.badge-success { background-color: #28a745; }
.badge-info { background-color: #17a2b8; }
.badge-warning { background-color: #ffc107; color: #333; }
.badge-secondary { background-color: #6c757d; }
.text-primary { color: #007bff; }
.text-warning { color: #ffc107; }
.text-danger { color: #dc3545; }
</style>

<?php include '../../../includes/footer.php'; ?>
