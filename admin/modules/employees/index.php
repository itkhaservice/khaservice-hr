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
$doc_status = isset($_GET['doc_status']) ? clean_input($_GET['doc_status']) : '';

// Mandatory Docs for Status Check
$mandatory_docs = ['CCCD', 'DXV', 'SYLL', 'CK', 'GKSK', 'HDLD'];
$mandatory_list = "'" . implode("','", $mandatory_docs) . "'";

// Build Query
$where = "WHERE 1=1";
$params = [];

// Permission Filter: Only show employees in managed projects
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    if (empty($allowed_projs)) {
        // Manager with no projects -> Sees no employees
        $where .= " AND 1=0";
    } else {
        $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
        // Employee belongs to one of the managed projects
        $where .= " AND e.current_project_id IN ($in_placeholder)";
        $params = array_merge($params, $allowed_projs);
    }
}

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

// Special Filter for Document Status
if ($doc_status) {
    if ($doc_status == 'complete') {
        // Must have all mandatory docs
        $where .= " AND (SELECT COUNT(DISTINCT doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1 AND d.doc_type IN ($mandatory_list)) >= " . count($mandatory_docs);
    } elseif ($doc_status == 'incomplete') {
        $where .= " AND (SELECT COUNT(DISTINCT doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1 AND d.doc_type IN ($mandatory_list)) < " . count($mandatory_docs);
    }
}

// Get Total
$total_sql = "SELECT COUNT(*) as count FROM employees e $where";
$total_records = db_fetch_row($total_sql, $params)['count'];

// Get Data with Doc Count and User Account Info
$sql = "SELECT e.*, d.name as dept_name, p.name as proj_name, pos.name as pos_name,
        (SELECT COUNT(DISTINCT doc_type) FROM documents doc WHERE doc.employee_id = e.id AND doc.is_submitted = 1 AND doc.doc_type IN ($mandatory_list)) as submitted_count,
        u.username, u.role, u.status as user_status, u.id as user_id
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN projects p ON e.current_project_id = p.id 
        LEFT JOIN positions pos ON e.position_id = pos.id
        LEFT JOIN users u ON e.id = u.employee_id
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
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý Nhân sự</h1>
            <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm nhân viên</a>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" class="form-control" placeholder="Tên, mã, SĐT, CCCD...">
            <select name="dept_id">
                <option value="">-- Phòng ban --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $dept_id == $d['id'] ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="proj_id">
                <option value="">-- Dự án --</option>
                <?php foreach ($projects as $p): 
                    // Hide projects not managed by user (if not admin)
                    if ($allowed_projs !== 'ALL' && !in_array($p['id'], $allowed_projs)) continue;
                ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $proj_id == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">-- Trạng thái làm việc --</option>
                <option value="working" <?php echo $status == 'working' ? 'selected' : ''; ?>>Đang làm việc</option>
                <option value="resigned" <?php echo $status == 'resigned' ? 'selected' : ''; ?>>Đã nghỉ việc</option>
            </select>
            <select name="doc_status">
                <option value="">-- Trạng thái hồ sơ --</option>
                <option value="complete" <?php echo $doc_status == 'complete' ? 'selected' : ''; ?>>Đã hoàn tất</option>
                <option value="incomplete" <?php echo $doc_status == 'incomplete' ? 'selected' : ''; ?>>Chưa hoàn tất</option>
            </select>
            
            <div style="display: flex; gap: 5px;">
                <button type="submit" class="btn btn-secondary" style="flex: 1;"><i class="fas fa-filter"></i> Lọc</button>
                <?php if ($kw || $dept_id || $proj_id || $status || $doc_status): ?>
                    <a href="index.php" class="btn btn-danger" title="Xóa lọc" style="min-width: 45px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Dự án hiện tại</th>
                            <th>Hồ sơ</th>
                            <th>Trạng thái</th>
                            <th width="120">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr><td colspan="8" style="text-align:center;">Không tìm thấy nhân viên nào</td></tr>
                        <?php else: ?>
                            <?php 
                                $total_req = count($mandatory_docs);
                                foreach ($employees as $e): 
                                    $is_complete = $e['submitted_count'] >= $total_req;
                            ?>
                                <tr>
                                    <td><strong><?php echo $e['code']; ?></strong></td>
                                    <td><?php echo $e['fullname']; ?></td>
                                    <td><?php echo $e['dept_name']; ?></td>
                                    <td><?php echo $e['pos_name']; ?></td>
                                    <td><?php echo $e['proj_name']; ?></td>
                                    <td>
                                        <?php if ($is_complete): ?>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Đủ</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning" title="Thiếu <?php echo $total_req - $e['submitted_count']; ?> loại"><i class="fas fa-exclamation-triangle"></i> Thiếu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $e['status'] == 'working' ? 'badge-info' : 'badge-danger'; ?>">
                                            <?php echo $e['status'] == 'working' ? 'Đang làm việc' : 'Đã nghỉ'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $e['id']; ?>" title="Sửa"><i class="fas fa-edit text-warning"></i></a> &nbsp;
                                        <a href="leave.php?id=<?php echo $e['id']; ?>" title="Quản lý Phép"><i class="fas fa-calendar-check text-success"></i></a> &nbsp;
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

<script>
function confirmDelete(id) {
    Modal.confirm('Bạn có chắc chắn muốn xóa nhân viên này? Dữ liệu liên quan (Hợp đồng, Hồ sơ) cũng sẽ bị xóa.', () => {
        location.href = 'delete.php?id=' + id;
    });
}
</script>

<?php include '../../../includes/footer.php'; ?>
