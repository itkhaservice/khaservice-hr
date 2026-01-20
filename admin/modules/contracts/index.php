<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
// Header is included via topbar inside main-content, no need to include header.php separately if topbar handles everything. 
// However, topbar usually includes the visible top bar, while header.php includes <html> <head> etc.
// Let's check the structure again. Usually header.php starts the HTML. 
// Based on previous files, we need header.php for <head>, then sidebar, then main-content with topbar.
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
        <div class="action-header">
            <h1 class="page-title">Quản lý Hợp đồng & Bảo hiểm</h1>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-section" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap;">
            <div style="position: relative; flex: 1; min-width: 200px;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-sub);"></i>
                <input type="text" name="kw" class="form-control" value="<?php echo htmlspecialchars($kw); ?>" placeholder="Tìm tên nhân viên, mã NV..." style="padding-left: 45px; width: 100%; height: 42px;">
            </div>
            
            <button type="submit" class="btn btn-primary" style="white-space: nowrap; height: 42px; padding: 0 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-filter"></i> <span>Lọc dữ liệu</span>
            </button>

            <?php if ($kw): ?>
                <a href="index.php" class="btn btn-secondary" title="Xóa bộ lọc" style="width: 42px; height: 42px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>

            <div class="header-actions" style="flex-shrink: 0;">
                <span class="badge badge-info" style="padding: 0 20px; font-size: 0.9rem; border-radius: 8px; height: 42px; display: flex; align-items: center; white-space: nowrap; border: 1px solid rgba(0,0,0,0.05);">
                    <i class="fas fa-users" style="margin-right: 8px;"></i> Tổng số: <strong><?php echo $total_records; ?></strong> <span class="hide-mobile" style="margin-left:4px;"> NV</span>
                </span>
            </div>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="80" style="text-align:center;">Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Số Hợp đồng</th>
                            <th>Thời hạn HĐ</th>
                            <th style="text-align:center;">Trạng thái</th>
                            <th style="text-align:center;">Bảo hiểm</th>
                            <th width="100" style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-sub); font-style: italic;">Không tìm thấy dữ liệu phù hợp</td></tr>
                        <?php else: ?>
                            <?php foreach ($list as $row): ?>
                                <tr>
                                    <td style="text-align:center;"><strong><?php echo $row['code']; ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main);"><?php echo $row['fullname']; ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['contract_number']): ?>
                                            <span class="badge badge-secondary" style="font-family: monospace; font-size: 0.85rem;"><?php echo $row['contract_number']; ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-sub); opacity: 0.5;">- Chưa có -</span>
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
                                                echo '<span style="color: var(--text-sub); opacity: 0.5;">--</span>';
                                            }
                                        ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($row['contract_number']): ?>
                                            <span class="badge badge-success">Đang hiệu lực</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="opacity: 0.7;">Chưa ký HĐ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($row['bhxh_status']): ?>
                                            <span class="badge badge-info"><i class="fas fa-shield-alt"></i> Đã tham gia</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="opacity: 0.7;">Chưa tham gia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Cập nhật thông tin">
                                            <i class="fas fa-edit"></i>
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
                    <span style="color: var(--text-sub); margin-right: 10px;">Hiển thị:</span>
                    <select onchange="location.href='index.php?<?php echo http_build_query(array_merge($_GET, ['limit' => ''])); ?>' + this.value" style="padding: 5px 10px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--card-bg); color: var(--text-main);">
                        <?php foreach ([10, 20, 50] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $limit == $l ? 'selected' : ''; ?>><?php echo $l; ?> dòng</option>
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
/* CSS Override specifically for this page if needed, otherwise rely on admin_style.css */
/* Ensuring text colors adapt to dark mode via variables */
.text-danger { color: var(--danger-text) !important; }
.text-warning { color: var(--warning-text) !important; }
</style>

<?php include '../../../includes/footer.php'; ?>