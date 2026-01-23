<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// --- IMPORT LOGIC ---
$show_preview_modal = false;
$import_result_msg = '';
$preview_data = [];
$valid_count = 0;
$invalid_count = 0;

// 1. Handle Upload -> Preview
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if ($ext != 'xlsx') {
        $import_result_msg = 'Chỉ chấp nhận file Excel (.xlsx)';
        $msg_type = 'error';
    } else {
        if (!file_exists('../../../upload/temp')) mkdir('../../../upload/temp', 0777, true);
        $upload_path = '../../../upload/temp/' . time() . '_' . $file['name'];
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $python_script = __DIR__ . '/parse_excel.py';
            putenv("PYTHONIOENCODING=utf-8");
            $cmd = "python \"$python_script\" \"$upload_path\"";
            $output = shell_exec($cmd);
            unlink($upload_path); // Clean up
            
            $raw_data = json_decode($output, true);
            
            if (is_array($raw_data)) {
                // LOAD REFERENCE DATA FOR VALIDATION
                $depts = [];
                foreach(db_fetch_all("SELECT id, code FROM departments") as $r) $depts[strtoupper($r['code'])] = $r['id'];
                
                $positions = [];
                foreach(db_fetch_all("SELECT id, code, name FROM positions") as $r) $positions[strtoupper($r['code'])] = ['id'=>$r['id'], 'name'=>$r['name']];
                
                $projects = [];
                foreach(db_fetch_all("SELECT id, code FROM projects") as $r) $projects[strtoupper($r['code'])] = $r['id'];

                $all_cccds = db_fetch_all("SELECT identity_card FROM employees WHERE identity_card != ''");
                $existing_cccds = array_column($all_cccds, 'identity_card');

                // PROCESS & VALIDATE
                foreach ($raw_data as $row) {
                    $item = $row;
                    $errors = [];
                    
                    // 1. Mandatory Fields
                    if (empty($row['fullname'])) $errors[] = "Thiếu Họ tên";
                    if (empty($row['gender'])) $errors[] = "Thiếu Giới tính";
                    
                    // 2. Validate Codes
                    $d_code = strtoupper($row['dept_code']);
                    $p_code = strtoupper($row['pos_code']);
                    $pj_code = strtoupper($row['proj_code']);
                    
                    if (empty($d_code)) $errors[] = "Mã PB";
                    elseif (!isset($depts[$d_code])) $errors[] = "Mã PB không tồn tại";
                    else $item['dept_id'] = $depts[$d_code];

                    if (empty($p_code)) $errors[] = "Mã CV";
                    elseif (!isset($positions[$p_code])) $errors[] = "Mã CV không tồn tại";
                    else {
                        $item['pos_id'] = $positions[$p_code]['id'];
                        $item['pos_name'] = $positions[$p_code]['name'];
                    }

                    if (empty($pj_code)) $errors[] = "Mã DA";
                    elseif (!isset($projects[$pj_code])) $errors[] = "Mã DA không tồn tại";
                    else $item['proj_id'] = $projects[$pj_code];

                    // 3. Duplicate Check
                    if (!empty($row['identity_card']) && in_array($row['identity_card'], $existing_cccds)) {
                        $errors[] = "Trùng CCCD";
                    }

                    if (empty($errors)) {
                        $item['status_check'] = 'valid';
                        $valid_count++;
                    } else {
                        $item['status_check'] = 'invalid';
                        $item['error_msg'] = implode(', ', $errors);
                        $invalid_count++;
                    }
                    
                    // Set defaults
                    if (empty($item['start_date'])) $item['start_date'] = date('Y-m-d');
                    
                    $preview_data[] = $item;
                }
                $show_preview_modal = true;
            } else {
                $import_result_msg = 'Lỗi đọc dữ liệu Python: ' . $output;
                $msg_type = 'error';
            }
        } else {
            $import_result_msg = 'Lỗi upload file.';
            $msg_type = 'error';
        }
    }
}

// 2. Handle Confirm Import
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_import'])) {
    $json_data = $_POST['import_data'];
    $data = json_decode($json_data, true);
    
    if (is_array($data)) {
        $count = 0;
        foreach ($data as $row) {
            if ($row['status_check'] == 'valid') {
                // Generate Code
                $code = "NV" . date('ymd') . rand(100,999);
                
                // Prepare values (Convert empty to NULL for SQL)
                $dob = !empty($row['dob']) ? $row['dob'] : null;
                $phone = !empty($row['phone']) ? $row['phone'] : null;
                $email = !empty($row['email']) ? $row['email'] : null;
                $identity_card = !empty($row['identity_card']) ? $row['identity_card'] : null;
                $start_date = !empty($row['start_date']) ? $row['start_date'] : date('Y-m-d');
                
                // Insert
                try {
                    db_query("INSERT INTO employees (code, fullname, gender, dob, phone, email, identity_card, department_id, position_id, position, current_project_id, start_date, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'working', NOW())", 
                              [
                                  $code, 
                                  $row['fullname'], 
                                  $row['gender'], 
                                  $dob, 
                                  $phone, 
                                  $email, 
                                  $identity_card, 
                                  $row['dept_id'], 
                                  $row['pos_id'], 
                                  $row['pos_name'], 
                                  $row['proj_id'], 
                                  $start_date
                              ]);
                    
                    // Log History
                    $new_id = db_last_insert_id();
                    db_query("INSERT INTO employee_status_history (employee_id, new_status, change_date, note, created_by) VALUES (?, 'working', ?, 'Import Excel', ?)", 
                             [$new_id, $start_date, $_SESSION['user_id']]);
                             
                    $count++;
                } catch (Exception $e) {
                    // Log error?
                }
            }
        }
        $import_result_msg = "Đã nhập thành công $count nhân viên.";
        $msg_type = 'success';
    }
}
// --- END IMPORT LOGIC ---

include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Pagination & Filters (Existing Logic)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$kw = isset($_GET['kw']) ? clean_input($_GET['kw']) : '';
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;
$proj_id = isset($_GET['proj_id']) ? (int)$_GET['proj_id'] : 0;
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$doc_status = isset($_GET['doc_status']) ? clean_input($_GET['doc_status']) : '';
$mandatory_docs = ['CCCD', 'DXV', 'SYLL', 'CK', 'GKSK', 'HDLD'];
$mandatory_list = "'" . implode("', '", $mandatory_docs) . "'";
$where = "WHERE 1=1";
$params = [];
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
    $where .= " AND (e.fullname LIKE ? OR e.code LIKE ? OR e.phone LIKE ? OR e.identity_card LIKE ? OR e.email LIKE ?)";
    $params = array_merge($params, ["%$kw%", "%$kw%", "%$kw%", "%$kw%", "%$kw%"]);
}
if ($dept_id) { $where .= " AND e.department_id = ?"; $params[] = $dept_id; }
if ($proj_id) { $where .= " AND e.current_project_id = ?"; $params[] = $proj_id; }
if ($status) { $where .= " AND e.status = ?"; $params[] = $status; }
if ($doc_status) {
    if ($doc_status == 'complete') {
        $where .= " AND (SELECT COUNT(DISTINCT doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1 AND d.doc_type IN ($mandatory_list)) >= " . count($mandatory_docs);
    } elseif ($doc_status == 'incomplete') {
        $where .= " AND (SELECT COUNT(DISTINCT doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1 AND d.doc_type IN ($mandatory_list)) < " . count($mandatory_docs);
    }
}
$total_records = 0;
$employees = [];
if ($proj_id > 0 || $kw != '') {
    $total_sql = "SELECT COUNT(*) as count FROM employees e $where";
    $total_records = db_fetch_row($total_sql, $params)['count'];
    $sql = "SELECT e.*, d.name as dept_name, p.name as proj_name, pos.name as pos_name,
            (SELECT COUNT(DISTINCT doc_type) FROM documents doc WHERE doc.employee_id = e.id AND doc.is_submitted = 1 AND doc.doc_type IN ($mandatory_list)) as submitted_count
            FROM employees e 
            LEFT JOIN departments d ON e.department_id = d.id 
            LEFT JOIN projects p ON e.current_project_id = p.id 
            LEFT JOIN positions pos ON e.position_id = pos.id
            $where 
            ORDER BY d.stt ASC, pos.stt ASC, e.fullname ASC 
            LIMIT $offset, $limit";
    $employees = db_fetch_all($sql, $params);
}
$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");
$query_string = $_GET;
unset($query_string['page']);
$link_template = "index.php?" . http_build_query($query_string) . "&page={page}";
?>

<!-- Scoped Modal Styles -->
<style>
    /* Scoped to .custom-import-modal to prevent conflicts */
    .custom-import-modal {
        display: none; 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); 
        backdrop-filter: blur(4px);
        z-index: 9999; /* Higher z-index */
        align-items: center; justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .custom-import-modal[style*="display: flex"] {
        opacity: 1;
    }

    /* Modal Content */
    .custom-import-modal .modal-box {
        background: #fff;
        color: #334155;
        padding: 30px; 
        border-radius: 12px; 
        width: 90%; max-width: 900px; max-height: 90vh; 
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        position: relative;
    }

    /* Header */
    .custom-import-modal .modal-header { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 20px; 
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 15px;
    }
    .custom-import-modal .modal-title { 
        margin: 0; font-size: 1.25rem; font-weight: 700; 
        color: #1e293b;
    }
    .custom-import-modal .modal-close { 
        background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; 
        transition: color 0.2s;
    }
    .custom-import-modal .modal-close:hover { color: #ef4444; }

    /* Alert */
    .custom-import-modal .alert-info {
        background-color: #eff6ff;
        border: 1px solid #dbeafe;
        color: #1e40af;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .custom-import-modal .alert-info ul { padding-left: 20px; margin: 10px 0; }
    .custom-import-modal .alert-info a { color: #2563eb; font-weight: 600; text-decoration: none; }
    .custom-import-modal .alert-info a:hover { text-decoration: underline; }

    /* Forms inside Modal ONLY */
    .custom-import-modal label { color: #475569; font-weight: 500; margin-bottom: 8px; display: block; }
    .custom-import-modal .form-control {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
        padding: 10px 15px;
        border-radius: 6px;
        width: 100%;
        display: block;
        box-sizing: border-box;
    }
    .custom-import-modal .form-control:focus {
        border-color: #3b82f6;
        background-color: #fff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Input File styling */
    .custom-import-modal input[type="file"] { padding: 8px; background: white; }
    .custom-import-modal input[type="file"]::file-selector-button {
        background-color: #e2e8f0;
        color: #475569;
        border: none;
        padding: 8px 12px;
        margin-right: 15px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .custom-import-modal input[type="file"]::file-selector-button:hover { background-color: #cbd5e1; }

    /* Preview Table */
    .custom-import-modal .preview-table {
        width: 100%; border-collapse: separate; border-spacing: 0; 
        font-size: 0.9rem; margin-top: 15px; 
        border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;
    }
    .custom-import-modal .preview-table th, .custom-import-modal .preview-table td {
        padding: 10px 12px; text-align: left; 
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
    }
    .custom-import-modal .preview-table th:last-child, .custom-import-modal .preview-table td:last-child { border-right: none; }
    .custom-import-modal .preview-table tr:last-child td { border-bottom: none; }

    .custom-import-modal .preview-table th {
        background: #f1f5f9; 
        color: #475569; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;
        position: sticky; top: 0; z-index: 10;
    }
    
    /* Rows */
    .custom-import-modal .row-valid { background-color: #f0fdf4; }
    .custom-import-modal .row-valid td { border-color: #dcfce7; }
    
    .custom-import-modal .row-invalid { background-color: #fef2f2; }
    .custom-import-modal .row-invalid td { border-color: #fee2e2; }
    
    .custom-import-modal .error-text { color: #dc2626; font-size: 0.8rem; display: block; margin-top: 4px; font-weight: 600; }
    .custom-import-modal .text-success { color: #16a34a; font-weight: 600; }
    .custom-import-modal .text-danger { color: #dc2626; font-weight: 600; }

    /* Footer Buttons */
    .custom-import-modal .modal-footer { margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px; }
    
    /* Scoped Buttons inside modal */
    .custom-import-modal .btn { border-radius: 6px; font-weight: 500; transition: all 0.2s; padding: 10px 20px; cursor: pointer; font-size: 0.95rem; }
    .custom-import-modal .btn-secondary { background: #64748b; border: none; color: white; }
    .custom-import-modal .btn-secondary:hover { background: #475569; }
    .custom-import-modal .btn-primary { background: #3b82f6; color: white; border: none; }
    .custom-import-modal .btn-primary:hover { background: #2563eb; }
    .custom-import-modal .btn-success { background: #22c55e; color: white; border: none; }
    .custom-import-modal .btn-success:hover { background: #16a34a; }
</style>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý Nhân sự</h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="openImportModal()" class="btn btn-success"><i class="fas fa-file-upload"></i> Nhập Excel</button>
                <a href="export.php" class="btn btn-info"><i class="fas fa-file-download"></i> Xuất Excel</a>
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm nhân viên</a>
            </div>
        </div>

        <?php if (!empty($import_result_msg)): ?>
            <div class="alert alert-<?php echo ($msg_type == 'success' ? 'success' : 'danger'); ?>">
                <?php echo $import_result_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Filters (Same as before) -->
        <form method="GET" class="filter-section">
            <input type="text" name="kw" value="<?php echo $kw; ?>" class="form-control" placeholder="Tìm kiếm nâng cao...">
            <select name="dept_id">
                <option value="">-- Phòng ban --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $dept_id == $d['id'] ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="proj_id">
                <option value="0">-- Tất cả Dự án --</option>
                <?php foreach ($projects as $p): 
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
                            <?php if ($proj_id == 0 && $kw == ''): ?>
                                <tr><td colspan="8" style="text-align:center; padding: 50px; color: #94a3b8;">
                                    <i class="fas fa-filter" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i><br>
                                    Vui lòng chọn <b>Dự án</b> hoặc nhập từ khóa tìm kiếm.
                                </td></tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding: 30px;">
                                        <div style="color: #64748b; margin-bottom: 10px;">Không tìm thấy nhân viên nào phù hợp.</div>
                                        <?php if ($proj_id > 0 && $kw != ''): ?>
                                            <a href="index.php?kw=<?php echo urlencode($kw); ?>&proj_id=0" class="btn btn-sm btn-primary">
                                                <i class="fas fa-search"></i> Thử tìm trong TOÀN BỘ hệ thống
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
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

<!-- Import Upload Modal -->
<div id="importModal" class="custom-import-modal" style="display: <?php echo $show_preview_modal ? 'none' : 'none'; ?>;">
    <div class="modal-box" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Nhập nhân viên từ Excel</h3>
            <button class="modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <strong>Hướng dẫn:</strong>
                <ul>
                    <li>Tải file mẫu và điền thông tin (Mã Phòng ban/Chức vụ/Dự án phải đúng).</li>
                    <li>Cột bắt buộc: <strong>Họ tên, Giới tính, Mã Phòng ban, Mã Chức vụ, Mã Dự án</strong>.</li>
                    <li>Các cột có thể bỏ trống: <strong>Ngày sinh, SĐT, Email, Số CCCD, Ngày bắt đầu</strong>.</li>
                </ul>
                <a href="download_template.php" class="btn btn-sm btn-link" style="padding-left: 0;">
                    <i class="fas fa-download"></i> Tải file mẫu (.xlsx)
                </a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Chọn file Excel (.xlsx)</label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Hủy</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-arrow-right"></i> Tiếp tục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<?php if ($show_preview_modal): ?>
<div id="previewModal" class="custom-import-modal" style="display: flex;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Xác nhận dữ liệu nạp (Preview)</h3>
            <button class="modal-close" onclick="location.href='index.php'">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; gap:15px; margin-bottom:15px; font-weight:600;">
                <span class="text-success">Hợp lệ: <?php echo $valid_count; ?></span>
                <span class="text-danger">Lỗi: <?php echo $invalid_count; ?></span>
            </div>
            
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd;">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Giới tính</th>
                            <th>Mã PB</th>
                            <th>Mã CV</th>
                            <th>Mã DA</th>
                            <th>CCCD</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview_data as $row): ?>
                            <tr class="<?php echo $row['status_check'] == 'valid' ? 'row-valid' : 'row-invalid'; ?>">
                                <td><?php echo $row['fullname']; ?></td>
                                <td><?php echo $row['gender']; ?></td>
                                <td><?php echo $row['dept_code']; ?></td>
                                <td><?php echo $row['pos_code']; ?></td>
                                <td><?php echo $row['proj_code']; ?></td>
                                <td><?php echo $row['identity_card']; ?></td>
                                <td>
                                    <?php if ($row['status_check'] == 'valid'): ?>
                                        <span class="text-success"><i class="fas fa-check"></i> OK</span>
                                    <?php else: ?>
                                        <span class="error-text"><i class="fas fa-times"></i> <?php echo $row['error_msg']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" style="margin-top: 20px; text-align: right;">
                <input type="hidden" name="import_data" value="<?php echo htmlspecialchars(json_encode($preview_data)); ?>">
                <button type="button" class="btn btn-secondary" onclick="location.href='index.php'">Hủy bỏ</button>
                <?php if ($valid_count > 0): ?>
                    <button type="submit" name="confirm_import" class="btn btn-primary" onclick="return confirm('Bạn có chắc chắn muốn nạp <?php echo $valid_count; ?> dòng hợp lệ không?')">
                        <i class="fas fa-check"></i>Xác nhận nạp (<?php echo $valid_count; ?>)
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-primary" disabled>Không có dữ liệu hợp lệ</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function confirmDelete(id) {
    if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
        location.href = 'delete.php?id=' + id;
    }
}
function openImportModal() {
    document.getElementById('importModal').style.display = 'flex';
}
function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
}
window.onclick = function(event) {
    var m1 = document.getElementById('importModal');
    if (event.target == m1) m1.style.display = "none";
}
</script>

<?php include '../../../includes/footer.php'; ?>