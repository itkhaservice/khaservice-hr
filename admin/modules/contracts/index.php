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

// Query
$where = "WHERE e.status = 'working'";
$params = [];

if ($kw) {
    $where .= " AND (e.fullname LIKE ? OR e.code LIKE ?)";
    $params[] = "%$kw%";
    $params[] = "%$kw%";
}

// Count
$total_sql = "SELECT COUNT(*) as count FROM employees e $where";
$total_records = db_fetch_row($total_sql, $params)['count'];

// Get Data (Left join to check existence)
$sql = "SELECT e.id, e.code, e.fullname, 
        c.contract_number, c.end_date as contract_end, c.status as contract_status,
        i.insurance_number, i.bhxh_status
        FROM employees e
        LEFT JOIN contracts c ON e.id = c.employee_id AND c.status = 'active'
        LEFT JOIN insurances i ON e.id = i.employee_id
        $where
        ORDER BY e.id DESC
        LIMIT $offset, $limit";
$list = db_fetch_all($sql, $params);

// Pagination Link
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
        <h1 class="page-title">Quản lý Hợp đồng & Bảo hiểm</h1>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" placeholder="Tìm nhân viên..." style="width:250px;">
            <button type="submit" class="btn btn-secondary">Tìm kiếm</button>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Số Hợp đồng</th>
                            <th>Ngày hết hạn HĐ</th>
                            <th>Trạng thái HĐ</th>
                            <th>BHXH</th>
                            <th width="100">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr><td colspan="7" style="text-align:center;">Không tìm thấy dữ liệu</td></tr>
                        <?php else: ?>
                            <?php foreach ($list as $row): ?>
                                <tr>
                                    <td><?php echo $row['code']; ?></td>
                                    <td><strong><?php echo $row['fullname']; ?></strong></td>
                                    <td><?php echo $row['contract_number'] ? $row['contract_number'] : '<span style="color:#999;">--</span>'; ?></td>
                                    <td>
                                        <?php 
                                            if ($row['contract_end']) {
                                                echo date('d/m/Y', strtotime($row['contract_end']));
                                                if (strtotime($row['contract_end']) < time()) {
                                                    echo ' <span class="badge badge-danger">Hết hạn</span>';
                                                } elseif (strtotime($row['contract_end']) < time() + 30*86400) {
                                                    echo ' <span class="badge badge-warning">Sắp hết</span>';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($row['contract_number']): ?>
                                            <span class="badge badge-success">Đang hiệu lực</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['bhxh_status']): ?>
                                            <span class="badge badge-success">Đã tham gia</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Chưa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Cập nhật</a>
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
                        <?php foreach ([10, 20, 50] as $l): ?>
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

<style>
.badge-secondary { background-color: #6c757d; }
.badge-danger { background-color: #dc3545; }
.badge-warning { background-color: #ffc107; color: #000; }
.badge-success { background-color: #28a745; }
</style>

<?php include '../../../includes/footer.php'; ?>
