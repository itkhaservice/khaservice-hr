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
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;
$proj_id = isset($_GET['proj_id']) ? (int)$_GET['proj_id'] : 0;
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build Query
$where = "WHERE 1=1";
$params = [];

if ($kw) {
    $where .= " AND (e.fullname LIKE ? OR e.code LIKE ? OR e.phone LIKE ? OR e.identity_card LIKE ?)";
    $params[] = "%$kw%";
    $params[] = "%$kw%";
    $params[] = "%$kw%";
    $params[] = "%$kw%";
}
if ($dept_id) {
    $where .= " AND e.department_id = ?";
    $params[] = $dept_id;
}
if ($proj_id) {
    $where .= " AND e.current_project_id = ?";
    $params[] = $proj_id;
}
if ($status) {
    $where .= " AND e.status = ?";
    $params[] = $status;
}

// Get Total
$total_sql = "SELECT COUNT(*) as count FROM employees e $where";
$total_records = db_fetch_row($total_sql, $params)['count'];

// Get Data
$sql = "SELECT e.*, d.name as dept_name, p.name as proj_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN projects p ON e.current_project_id = p.id 
        $where 
        ORDER BY e.id DESC 
        LIMIT $offset, $limit";
$employees = db_fetch_all($sql, $params);

$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");

// Generate Link Template
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
            <h1 class="page-title">Quản lý Nhân sự</h1>
            <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm nhân viên</a>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" placeholder="Tên, mã, SĐT, CCCD..." style="width:200px;">
            <select name="dept_id">
                <option value="">-- Phòng ban --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $dept_id == $d['id'] ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="proj_id">
                <option value="">-- Dự án --</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $proj_id == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="working" <?php echo $status == 'working' ? 'selected' : ''; ?>>Đang làm việc</option>
                <option value="resigned" <?php echo $status == 'resigned' ? 'selected' : ''; ?>>Đã nghỉ việc</option>
            </select>
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <?php if ($kw || $dept_id || $proj_id || $status): ?>
                <a href="index.php" class="btn btn-danger">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Phòng ban</th>
                            <th>Dự án hiện tại</th>
                            <th>Chức vụ</th>
                            <th>SĐT</th>
                            <th>Trạng thái</th>
                            <th width="120">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr><td colspan="8" style="text-align:center;">Không tìm thấy nhân viên nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($employees as $e): ?>
                                <tr>
                                    <td><strong><?php echo $e['code']; ?></strong></td>
                                    <td><?php echo $e['fullname']; ?></td>
                                    <td><?php echo $e['dept_name']; ?></td>
                                    <td><?php echo $e['proj_name']; ?></td>
                                    <td><?php echo $e['position']; ?></td>
                                    <td><?php echo $e['phone']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $e['status'] == 'working' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $e['status'] == 'working' ? 'Đang làm việc' : 'Đã nghỉ'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $e['id']; ?>" title="Sửa"><i class="fas fa-edit text-warning"></i></a> &nbsp;
                                        <a href="documents.php?id=<?php echo $e['id']; ?>" title="Hồ sơ"><i class="fas fa-file-alt text-info"></i></a> &nbsp;
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $e['id']; ?>)" title="Xóa"><i class="fas fa-trash text-danger"></i></a>
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
                </div>
                <div class="pagination-wrapper">
                    <?php echo paginate($total_records, $page, $limit, $link_template); ?>
                </div>
            </div>
        </div>
    </div>

<script>
function confirmDelete(id) {
    Modal.confirm('Bạn có chắc chắn muốn xóa nhân viên này? Dữ liệu liên quan (Hợp đồng, Hồ sơ) cũng sẽ bị xóa.', () => {
        location.href = 'delete.php?id=' + id;
    });
}
</script>

<?php include '../../../includes/footer.php'; ?>
