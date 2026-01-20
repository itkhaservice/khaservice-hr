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

// Security: Project Filter
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    if (empty($allowed_projs)) {
        $where .= " AND 1=0";
    } else {
        $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
        $where .= " AND e.current_project_id IN ($in_placeholder)";
        $params = array_merge($params, $allowed_projs);
    }
}

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
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <h1 class="page-title">Quản lý Hợp đồng & Bảo hiểm</h1>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" placeholder="Tìm nhân viên..." style="width:250px;">
            <button type="submit" class="btn btn-secondary">Tìm kiếm</button>
        </form>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-container" style="border: none; border-radius: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="80" style="text-align:center;">Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Số Hợp đồng</th>
                            <th>Thời hạn HĐ</th>
                            <th style="text-align:center;">Trạng thái HĐ</th>
                            <th style="text-align:center;">BHXH</th>
                            <th width="120" style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 40px; color: #94a3b8;">Không tìm thấy dữ liệu phù hợp</td></tr>
                        <?php else: ?>
                            <?php foreach ($list as $row): ?>
                                <tr>
                                    <td style="text-align:center;"><strong><?php echo $row['code']; ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main);"><?php echo $row['fullname']; ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['contract_number']): ?>
                                            <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569;"><?php echo $row['contract_number']; ?></code>
                                        <?php else: ?>
                                            <span style="color: #cbd5e1;">Chưa cập nhật</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($row['contract_end']) {
                                                $end_ts = strtotime($row['contract_end']);
                                                echo '<div style="font-weight: 500;">' . date('d/m/Y', $end_ts) . '</div>';
                                                
                                                if ($end_ts < time()) {
                                                    echo '<small class="text-danger" style="font-weight:600;"><i class="fas fa-times-circle"></i> Đã hết hạn</small>';
                                                } elseif ($end_ts < time() + 30*86400) {
                                                    echo '<small class="text-warning" style="font-weight:600;"><i class="fas fa-exclamation-circle"></i> Sắp hết hạn</small>';
                                                }
                                            } else {
                                                echo '<span style="color: #cbd5e1;">--</span>';
                                            }
                                        ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($row['contract_number']): ?>
                                            <span class="badge badge-success">Đang hiệu lực</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Chưa có HĐ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($row['bhxh_status']): ?>
                                            <span class="badge badge-info"><i class="fas fa-check"></i> Đã đóng</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Chưa tham gia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 0.75rem;">
                                            <i class="fas fa-edit"></i> Cập nhật
                                        </a>
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
