<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// 1. Quyền truy cập
if (!is_hr_staff()) {
    echo "<div class='main-content'><div class='content-wrapper'><h3>Bạn không có quyền truy cập trang này.</h3></div></div>";
    include '../../../includes/footer.php';
    exit;
}

$allowed_projs = get_allowed_projects();
$root_path = "../../../upload/documents/";

// 2. Xử lý xóa
if (isset($_GET['delete'])) {
    $item_to_delete = $_GET['delete'];
    $full_delete_path = realpath($root_path . $item_to_delete);
    $base_real_path = realpath($root_path);
    if ($full_delete_path && strpos($full_delete_path, $base_real_path) === 0) {
        if (is_dir($full_delete_path)) {
            $files = scandir($full_delete_path);
            if (count($files) <= 2) rmdir($full_delete_path);
        } else {
            unlink($full_delete_path);
        }
    }
    redirect("file_manager.php?dir=" . urlencode($_GET['dir'] ?? ''));
}

// 3. Tham số đường dẫn & Lọc
$current_dir = $_GET['dir'] ?? '';
$current_dir = str_replace(['..', './', '\\'], ['', '', '/'], $current_dir);
$current_dir = trim($current_dir, '/');

$search_kw = trim($_GET['kw'] ?? '');
$f_dept = (int)($_GET['department_id'] ?? 0);
$f_proj = (int)($_GET['project_id'] ?? 0);
$f_status = $_GET['status'] ?? '';

// Nếu chọn lọc -> Quay về gốc để tìm kiếm theo tiêu chí
if ($f_dept || $f_proj || $f_status) {
    $current_dir = ''; 
}

$allowed_folder_names = [];
if ($current_dir === '') {
    $sql = "SELECT fullname, identity_card FROM employees WHERE 1=1";
    $sql_params = [];

    if ($allowed_projs !== 'ALL') {
        if (empty($allowed_projs)) $sql .= " AND 1=0";
        else {
            $in = implode(',', array_fill(0, count($allowed_projs), '?'));
            $sql .= " AND current_project_id IN ($in)";
            $sql_params = array_merge($sql_params, $allowed_projs);
        }
    }

    if ($f_dept) { $sql .= " AND department_id = ?"; $sql_params[] = $f_dept; }
    if ($f_proj) { $sql .= " AND current_project_id = ?"; $sql_params[] = $f_proj; }
    if ($f_status) { $sql .= " AND status = ?"; $sql_params[] = $f_status; }
    if ($search_kw) { $sql .= " AND fullname LIKE ?"; $sql_params[] = "%$search_kw%"; }

    $emps = db_fetch_all($sql, $sql_params);
    foreach ($emps as $e) {
        $allowed_folder_names[] = get_emp_folder_name($e['fullname'], $e['identity_card']);
    }
}

// 4. Đọc file thực tế
$scan_target = $root_path . $current_dir;
$display_dirs = []; $display_files = [];

if (is_dir($scan_target)) {
    $items = scandir($scan_target);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full_item_path = $scan_target . '/' . $item;
        $is_dir = is_dir($full_item_path);

        if ($current_dir === '') {
            if ($is_dir && in_array($item, $allowed_folder_names)) $display_dirs[] = $item;
        } else {
            if (!$search_kw || stripos($item, $search_kw) !== false) {
                if ($is_dir) $display_dirs[] = $item;
                else $display_files[] = $item;
            }
        }
    }
}

// 5. Dữ liệu Dropdowns
$all_depts = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$where_p = "1=1"; $params_p = [];
if ($allowed_projs !== 'ALL') {
    $where_p = empty($allowed_projs) ? "1=0" : "id IN (" . implode(',', array_fill(0, count($allowed_projs), '?')) . ")";
    $params_p = $allowed_projs;
}
$all_projects = db_fetch_all("SELECT id, name FROM projects WHERE $where_p ORDER BY name ASC", $params_p);
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý File Server</h1>
            <?php if ($current_dir !== ''): ?>
                <a href="file_manager.php" class="btn btn-secondary"><i class="fas fa-home"></i> Về thư mục gốc</a>
            <?php endif; ?>
        </div>

        <form method="GET" class="filter-section">
            <select name="department_id" onchange="this.form.submit()">
                <option value="">-- Phòng ban --</option>
                <?php foreach($all_depts as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $f_dept == $d['id'] ? 'selected' : ''; ?> >
                        <?php echo htmlspecialchars($d['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="project_id" onchange="this.form.submit()">
                <option value="">-- Dự án --</option>
                <?php foreach($all_projects as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $f_proj == $p['id'] ? 'selected' : ''; ?> >
                        <?php echo htmlspecialchars($p['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" onchange="this.form.submit()">
                <option value="">-- Trạng thái --</option>
                <option value="working" <?php echo $f_status == 'working' ? 'selected' : ''; ?>>Đang làm việc</option>
                <option value="resigned" <?php echo $f_status == 'resigned' ? 'selected' : ''; ?>>Đã nghỉ việc</option>
            </select>

            <input type="text" name="kw" placeholder="Tên nhân viên / File..." value="<?php echo htmlspecialchars($search_kw); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Lọc</button>
            <?php if ($search_kw || $f_dept || $f_proj || $f_status): ?>
                <a href="file_manager.php" class="btn btn-danger" style="min-width:auto;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-folder-open text-warning"></i>
                <span style="color: #64748b; font-weight: 500;">upload/documents/<?php echo htmlspecialchars($current_dir); ?></span>
            </div>

            <div class="table-container" style="border: none; border-radius: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="60"></th>
                            <th>Tên tệp tin / Thư mục</th>
                            <th width="150" style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($current_dir !== ''): 
                            $parent = dirname($current_dir);
                            if ($parent === '.') $parent = '';
                        ?>
                            <tr>
                                <td style="text-align:center;"><i class="fas fa-level-up-alt" style="color: #94a3b8;"></i></td>
                                <td colspan="2"><a href="?dir=<?php echo urlencode($parent); ?>" style="color: #64748b; font-weight: 500;">... (Quay lại)</a></td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($display_dirs as $d): 
                            $item_path = ($current_dir ? $current_dir . '/' : '') . $d;
                        ?>
                            <tr>
                                <td style="text-align:center;"><i class="fas fa-folder" style="color: #f59e0b; font-size: 1.2rem;"></i></td>
                                <td><a href="?dir=<?php echo urlencode($item_path); ?>" style="font-weight: 600; color: var(--text-main);"><?php echo $d; ?></a></td>
                                <td style="text-align:center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <a href="?dir=<?php echo urlencode($item_path); ?>" class="btn btn-sm btn-primary">Mở</a>
                                        <a href="javascript:void(0)" onclick="deleteItem('<?php echo htmlspecialchars($item_path); ?>')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php foreach ($display_files as $f): 
                            $item_path = ($current_dir ? $current_dir . '/' : '') . $f;
                            $fsize = 0; $fp = $scan_target . '/' . $f;
                            if(file_exists($fp)) $fsize = filesize($fp);
                        ?>
                            <tr>
                                <td style="text-align:center;"><i class="fas fa-file-alt" style="color: #3b82f6; font-size: 1.2rem;"></i></td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo $f; ?></div>
                                    <small style="color: #94a3b8;"><?php echo round($fsize / 1024, 1); ?> KB</small>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <a href="/khaservice-hr/upload/documents/<?php echo $item_path; ?>" target="_blank" class="btn btn-sm btn-secondary">Tải</a>
                                        <a href="javascript:void(0)" onclick="deleteItem('<?php echo htmlspecialchars($item_path); ?>')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($display_dirs) && empty($display_files)): ?>
                            <tr><td colspan="3" style="text-align:center; padding: 40px; color: #94a3b8; font-style: italic;">Thư mục trống hoặc không có kết quả phù hợp</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
function deleteItem(path) {
    Modal.confirm('Xác nhận xóa tệp tin/thư mục rỗng này?', () => {
        location.href = '?dir=<?php echo urlencode($current_dir); ?>&delete=' + encodeURIComponent(path);
    });
}
</script>

<?php include '../../../includes/footer.php'; ?>
