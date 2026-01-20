<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Pagination & Filters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
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

// Pagination Link Template
$query_string = $_GET;
unset($query_string['page']);
$link_template = "index.php?" . http_build_query($query_string) . "&page={page}";
?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info" onclick="this.querySelector('.user-dropdown').classList.toggle('show')">
            <span><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
            <div class="user-avatar">A</div>
            <div class="user-dropdown">
                <a href="/khaservice-hr/admin/logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý Dự án</h1>
            <!-- Moved "Add New" button here, separated from Filters -->
            <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dự án mới</a>
        </div>

        <!-- Filter Section (Full Width) -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" placeholder="Tìm kiếm tên, mã, địa chỉ...">
            <select name="status">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Tạm dừng</option>
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Lọc dữ liệu</button>
            <?php if ($kw || $status): ?>
                <a href="index.php" class="btn btn-danger" style="min-width: auto;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="60" style="text-align:center;">STT</th>
                            <th>Mã dự án</th>
                            <th>Tên dự án</th>
                            <th>Địa chỉ</th>
                            <th>Trạng thái</th>
                            <th width="120" style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="6" style="text-align:center; padding: 30px;">Không tìm thấy dữ liệu</td></tr>
                        <?php else: ?>
                            <?php 
                                $stt = $offset; // Init STT based on offset
                                foreach ($projects as $p): 
                                    $stt++; // Increment STT
                            ?>
                                <tr>
                                    <td style="text-align:center; color: #94a3b8;"><?php echo $stt; ?></td>
                                    <td><strong><?php echo $p['code']; ?></strong></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $p['id']; ?>" class="text-primary-hover" style="font-weight: 500;">
                                            <?php echo $p['name']; ?>
                                        </a>
                                    </td>
                                    <td style="color: #64748b;"><?php echo $p['address']; ?></td>
                                    <td>
                                        <?php 
                                            $s_cls = ['active'=>'badge-success', 'completed'=>'badge-info', 'pending'=>'badge-warning'];
                                        ?>
                                        <span class="badge <?php echo $s_cls[$p['status']] ?? 'badge-secondary'; ?>">
                                            <?php echo $p['status'] == 'active' ? 'Hoạt động' : $p['status']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="view.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 5px 10px;" title="Chi tiết"><i class="fas fa-eye"></i></a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $p['id']; ?>)" class="btn btn-danger btn-sm" style="padding: 5px 10px;" title="Xóa"><i class="fas fa-trash"></i></a>
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
                        <?php foreach ([10, 20, 50, 100] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $limit == $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pagination-wrapper">
                    <?php echo paginate($total_records, $page, $limit, $link_template); ?>
                </div>
            </div>
        </div>
    </div>

<script>
function confirmDelete(id) {
    Modal.confirm('Bạn có chắc chắn muốn xóa dự án này?', () => {
        location.href = 'delete.php?id=' + id;
    });
}
</script>

<style>
.text-primary-hover:hover { color: var(--primary-color); text-decoration: underline; }
</style>

<?php include '../../../includes/footer.php'; ?>