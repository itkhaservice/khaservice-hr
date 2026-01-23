<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// --- HANDLE COMPANY SETTINGS & SALARY CONFIG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $keys = [
        'company_name', 'company_address', 'admin_email', 'company_phone', 'company_website',
        'insurance_bhxh_percent', 'insurance_bhyt_percent', 'insurance_bhtn_percent', 'union_fee_amount'
    ];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = clean_input($_POST[$key]);
            
            // Remove commas for money fields before saving
            if ($key === 'union_fee_amount') {
                $val = str_replace(',', '', $val);
            }
            
            db_query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $val, $val]);
        }
    }
    set_toast('success', 'Đã lưu cấu hình thành công!');
    $tab = clean_input($_POST['current_tab'] ?? 'company');
    redirect('settings.php#' . $tab);
}

// --- HANDLE DEPARTMENTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_dept'])) {
    $name = clean_input($_POST['dept_name']);
    $code = clean_input($_POST['dept_code']);
    
    if ($name && $code) {
        try {
            db_query("INSERT INTO departments (name, code) VALUES (?, ?)", [$name, $code]);
            set_toast('success', 'Thêm phòng ban thành công!');
        } catch (PDOException $e) {
            set_toast('error', 'Mã phòng ban đã tồn tại!');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_dept'])) {
    $id = (int)$_POST['dept_id'];
    $name = clean_input($_POST['dept_name']);
    $code = clean_input($_POST['dept_code']);
    
    db_query("UPDATE departments SET name = ?, code = ? WHERE id = ?", [$name, $code, $id]);
    set_toast('success', 'Cập nhật phòng ban thành công!');
}

if (isset($_GET['del_dept'])) {
    $id = (int)$_GET['del_dept'];
    // Check usage
    $count = db_fetch_row("SELECT COUNT(*) as c FROM employees WHERE department_id = ?", [$id])['c'];
    if ($count > 0) {
        set_toast('error', 'Không thể xóa phòng ban đang có nhân viên!');
    } else {
        db_query("DELETE FROM departments WHERE id = ?", [$id]);
        set_toast('success', 'Đã xóa phòng ban!');
    }
    redirect('settings.php#departments');
}

// --- HANDLE DOCUMENT SETTINGS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctype'])) {
    $name = clean_input($_POST['doc_name']);
    $code = clean_input($_POST['doc_code']);
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $is_multiple = isset($_POST['is_multiple']) ? 1 : 0;
    
    if ($name && $code) {
        try {
            db_query("INSERT INTO document_settings (name, code, is_required, is_multiple) VALUES (?, ?, ?, ?)", [$name, $code, $is_required, $is_multiple]);
            set_toast('success', 'Thêm loại hồ sơ thành công!');
        } catch (PDOException $e) {
            set_toast('error', 'Mã hồ sơ đã tồn tại!');
        }
    }
    redirect('settings.php#documents');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_doctype'])) {
    $id = (int)$_POST['doc_id'];
    $name = clean_input($_POST['doc_name']);
    $code = clean_input($_POST['doc_code']);
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $is_multiple = isset($_POST['is_multiple']) ? 1 : 0;
    
    db_query("UPDATE document_settings SET name = ?, code = ?, is_required = ?, is_multiple = ? WHERE id = ?", [$name, $code, $is_required, $is_multiple, $id]);
    set_toast('success', 'Cập nhật loại hồ sơ thành công!');
    redirect('settings.php#documents');
}

if (isset($_GET['del_doctype'])) {
    $id = (int)$_GET['del_doctype'];
    // Check usage
    $code = db_fetch_row("SELECT code FROM document_settings WHERE id = ?", [$id])['code'];
    $count = db_fetch_row("SELECT COUNT(*) as c FROM documents WHERE doc_type = ?", [$code])['c'];
    
    if ($count > 0) {
        set_toast('error', 'Không thể xóa loại hồ sơ đang được sử dụng!');
    } else {
        db_query("DELETE FROM document_settings WHERE id = ?", [$id]);
        set_toast('success', 'Đã xóa loại hồ sơ!');
    }
    redirect('settings.php#documents');
}

// --- HANDLE POSITIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_position'])) {
    $dept_id = (int)$_POST['dept_id'];
    $name = clean_input($_POST['pos_name']);
    
    if ($dept_id && $name) {
        db_query("INSERT INTO positions (department_id, name) VALUES (?, ?)", [$dept_id, $name]);
        set_toast('success', 'Thêm chức vụ thành công!');
    }
    redirect('settings.php#departments');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_position'])) {
    $id = (int)$_POST['pos_id'];
    $name = clean_input($_POST['pos_name']);
    
    db_query("UPDATE positions SET name = ? WHERE id = ?", [$name, $id]);
    set_toast('success', 'Cập nhật chức vụ thành công!');
    redirect('settings.php#departments');
}

if (isset($_GET['del_position'])) {
    $id = (int)$_GET['del_position'];
    // Check usage
    $count = db_fetch_row("SELECT COUNT(*) as c FROM employees WHERE position_id = ?", [$id])['c'];
    if ($count > 0) {
        set_toast('error', 'Không thể xóa chức vụ đang có nhân viên!');
    } else {
        db_query("DELETE FROM positions WHERE id = ?", [$id]);
        set_toast('success', 'Đã xóa chức vụ!');
    }
    redirect('settings.php#departments');
}

// --- FETCH DATA ---
// Settings
$settings_raw = db_fetch_all("SELECT * FROM settings");
$settings = [];
foreach ($settings_raw as $s) $settings[$s['setting_key']] = $s['setting_value'];

// Departments
$departments = db_fetch_all("SELECT * FROM departments ORDER BY id ASC");

// Positions
$positions = db_fetch_all("SELECT * FROM positions ORDER BY department_id ASC, id ASC");
$positions_by_dept = [];
foreach ($positions as $p) {
    $positions_by_dept[$p['department_id']][] = $p;
}

// Document Types
$doc_types = db_fetch_all("SELECT * FROM document_settings ORDER BY id ASC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Cấu hình Hệ thống</h1>
        </div>

        <div class="card">
            <div class="tabs">
                <div class="tab-item active" data-tab="company">Thông tin Công ty</div>
                <div class="tab-item" data-tab="departments">Quản lý Phòng ban</div>
                <div class="tab-item" data-tab="documents">Cấu hình Hồ sơ</div>
                <div class="tab-item" data-tab="salary">Cấu hình Tiền lương</div>
            </div>

            <!-- Tab 1: Company Info -->
            <div id="company" class="tab-content active">
                <form method="POST" style="max-width: 800px;">
                    <input type="hidden" name="current_tab" value="company">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Tên Công ty</label>
                            <input type="text" name="company_name" class="form-control" value="<?php echo $settings['company_name'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Admin</label>
                            <input type="email" name="admin_email" class="form-control" value="<?php echo $settings['admin_email'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="company_phone" class="form-control" value="<?php echo $settings['company_phone'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="text" name="company_website" class="form-control" value="<?php echo $settings['company_website'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ trụ sở</label>
                        <input type="text" name="company_address" class="form-control" value="<?php echo $settings['company_address'] ?? ''; ?>">
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="save_settings" class="btn btn-primary"><i class="fas fa-save"></i> Lưu cấu hình</button>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Departments -->
            <div id="departments" class="tab-content">
                <div style="display: flex; gap: 20px;">
                    <!-- Add/Edit Form -->
                    <div class="bg-subtle" style="flex: 1; min-width: 300px; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); height: fit-content;">
                        <h4 id="deptFormTitle" style="margin-top: 0; margin-bottom: 20px; color: var(--primary-color);">Thêm phòng ban mới</h4>
                        <form method="POST" id="deptForm">
                            <input type="hidden" name="dept_id" id="deptId">
                            <div class="form-group">
                                <label>Mã phòng ban <span style="color:red;">*</span></label>
                                <input type="text" name="dept_code" id="deptCode" class="form-control" required placeholder="VD: HCNS">
                            </div>
                            <div class="form-group">
                                <label>Tên phòng ban <span style="color:red;">*</span></label>
                                <input type="text" name="dept_name" id="deptName" class="form-control" required placeholder="VD: Hành chính Nhân sự">
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="add_dept" id="deptBtn" class="btn btn-primary" style="flex:1;">Thêm mới</button>
                                <button type="button" id="deptCancel" class="btn btn-secondary" style="display:none;" onclick="resetDeptForm()">Hủy</button>
                            </div>
                        </form>
                    </div>

                    <!-- List -->
                    <div style="flex: 2;">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Mã PB</th>
                                        <th>Tên phòng ban</th>
                                        <th>Cơ cấu chức vụ</th>
                                        <th width="100" style="text-align:center;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $d): ?>
                                        <tr>
                                            <td style="vertical-align:top;"><?php echo $d['id']; ?></td>
                                            <td style="vertical-align:top;"><span class="badge badge-secondary"><?php echo $d['code']; ?></span></td>
                                            <td style="vertical-align:top;"><strong><?php echo $d['name']; ?></strong></td>
                                            <td>
                                                <div style="margin-bottom: 8px;">
                                                    <a href="javascript:void(0)" onclick="openPosModal(<?php echo $d['id']; ?>, '<?php echo $d['name']; ?>')" class="badge badge-success" style="cursor:pointer;">+ Thêm chức vụ</a>
                                                </div>
                                                <ul style="list-style: none; padding: 0; font-size: 0.9rem;">
                                                    <?php if (isset($positions_by_dept[$d['id']])): ?>
                                                        <?php foreach ($positions_by_dept[$d['id']] as $p): ?>
                                                            <li class="border-dashed" style="display: flex; justify-content: space-between; padding: 4px 0;">
                                                                <span>- <?php echo $p['name']; ?></span>
                                                                <span style="opacity: 0.6;">
                                                                    <a href="javascript:void(0)" onclick="editPos(<?php echo htmlspecialchars(json_encode($p)); ?>, '<?php echo $d['name']; ?>')" title="Sửa"><i class="fas fa-edit"></i></a>
                                                                    <a href="javascript:void(0)" onclick="confirmDelPos(<?php echo $p['id']; ?>)" title="Xóa" style="margin-left:5px; color:#dc2626;"><i class="fas fa-trash"></i></a>
                                                                </span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <li style="color: #999; font-style: italic;">Chưa có chức vụ</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </td>
                                            <td style="vertical-align:top; text-align:center;">
                                                <a href="javascript:void(0)" onclick="editDept(<?php echo htmlspecialchars(json_encode($d)); ?>)" class="text-primary-hover" style="margin-right: 10px;"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0)" onclick="confirmDelDept(<?php echo $d['id']; ?>)" class="text-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Position Modal -->
            <div id="posModal" class="modal-overlay">
                <div class="modal-box">
                    <h3 id="posModalTitle" class="modal-title">Thêm chức vụ</h3>
                    <p id="posModalSubtitle" style="color:#666; margin-bottom:15px; font-style:italic;"></p>
                    <form method="POST">
                        <input type="hidden" name="dept_id" id="posDeptId">
                        <input type="hidden" name="pos_id" id="posId">
                        <div class="form-group">
                            <label>Tên chức vụ <span style="color:red;">*</span></label>
                            <input type="text" name="pos_name" id="posName" class="form-control" required placeholder="VD: Trưởng phòng">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="$('#posModal').hide()">Đóng</button>
                            <button type="submit" name="add_position" id="posBtn" class="btn btn-primary">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 3: Document Types -->
            <div id="documents" class="tab-content">
                <div style="display: flex; gap: 20px;">
                    <!-- Add/Edit Form -->
                    <div class="bg-subtle" style="flex: 1; min-width: 300px; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); height: fit-content;">
                        <h4 id="docFormTitle" style="margin-top: 0; margin-bottom: 20px; color: var(--primary-color);">Thêm loại hồ sơ</h4>
                        <form method="POST" id="docForm">
                            <input type="hidden" name="doc_id" id="docId">
                            <div class="form-group">
                                <label>Mã hồ sơ <span style="color:red;">*</span></label>
                                <input type="text" name="doc_code" id="docCode" class="form-control" required placeholder="VD: CCCD">
                            </div>
                            <div class="form-group">
                                <label>Tên loại hồ sơ <span style="color:red;">*</span></label>
                                <input type="text" name="doc_name" id="docName" class="form-control" required placeholder="VD: Căn cước công dân">
                            </div>
                            <div class="form-group" style="display:flex; gap:20px;">
                                <label style="display:flex; align-items:center; cursor:pointer;">
                                    <input type="checkbox" name="is_required" id="docRequired" value="1" checked style="margin-right:8px;"> Bắt buộc
                                </label>
                                <label style="display:flex; align-items:center; cursor:pointer;">
                                    <input type="checkbox" name="is_multiple" id="docMultiple" value="1" style="margin-right:8px;"> Cho phép nhiều file
                                </label>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="add_doctype" id="docBtn" class="btn btn-primary" style="flex:1;">Thêm mới</button>
                                <button type="button" id="docCancel" class="btn btn-secondary" style="display:none;" onclick="resetDocForm()">Hủy</button>
                            </div>
                        </form>
                    </div>

                    <!-- List -->
                    <div style="flex: 2;">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Mã</th>
                                        <th>Tên loại hồ sơ</th>
                                        <th>Tính chất</th>
                                        <th width="100" style="text-align:center;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doc_types as $d): ?>
                                        <tr>
                                            <td><?php echo $d['id']; ?></td>
                                            <td><span class="badge badge-secondary"><?php echo $d['code']; ?></span></td>
                                            <td><strong><?php echo $d['name']; ?></strong></td>
                                            <td>
                                                <?php if($d['is_required']): ?><span class="badge badge-warning">Bắt buộc</span><?php endif; ?>
                                                <?php if($d['is_multiple']): ?><span class="badge badge-info">Nhiều file</span><?php endif; ?>
                                            </td>
                                            <td style="text-align:center;">
                                                <a href="javascript:void(0)" onclick="editDoc(<?php echo htmlspecialchars(json_encode($d)); ?>)" class="text-primary-hover" style="margin-right: 10px;"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0)" onclick="confirmDelDoc(<?php echo $d['id']; ?>)" class="text-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tab 4: Salary Config -->
            <div id="salary" class="tab-content">
                <form method="POST" style="max-width: 800px;">
                    <input type="hidden" name="current_tab" value="salary">
                    <h4 style="margin-top: 0; margin-bottom: 20px; color: var(--primary-color);">Hệ số bảo hiểm & Phí cố định (%)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>BHXH Nhân viên đóng (%)</label>
                            <input type="number" step="0.1" name="insurance_bhxh_percent" class="form-control" value="<?php echo $settings['insurance_bhxh_percent'] ?? '8'; ?>">
                        </div>
                        <div class="form-group">
                            <label>BHYT Nhân viên đóng (%)</label>
                            <input type="number" step="0.1" name="insurance_bhyt_percent" class="form-control" value="<?php echo $settings['insurance_bhyt_percent'] ?? '1.5'; ?>">
                        </div>
                        <div class="form-group">
                            <label>BHTN Nhân viên đóng (%)</label>
                            <input type="number" step="0.1" name="insurance_bhtn_percent" class="form-control" value="<?php echo $settings['insurance_bhtn_percent'] ?? '1'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Đoàn phí Công đoàn (Số tiền cố định)</label>
                        <input type="text" name="union_fee_amount" class="form-control input-money" value="<?php echo number_format($settings['union_fee_amount'] ?? 0); ?>">
                        <small style="color: #94a3b8;">* Nếu tính theo % lương đóng BH, vui lòng nhập 0 và hệ thống sẽ tự tính 1%.</small>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="save_settings" class="btn btn-primary"><i class="fas fa-save"></i> Lưu cấu hình lương chung</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<style>
.input-money { text-align: right; font-weight: 600; color: var(--primary-dark); }
.text-primary-hover:hover { color: var(--primary-color); }
.text-danger:hover { color: #b91c1c; }

/* Force Z-Index for Settings Page */
#sidebarToggle, #theme-toggle, #start-tour {
    position: relative !important;
    z-index: 9999 !important;
    pointer-events: auto !important;
}
</style>

<script>
$(document).ready(function() {
    // Tab switching logic
    if(window.location.hash) {
        var hash = window.location.hash.substring(1);
        showTab(hash);
    }
    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        showTab(tabId);
    });

    // Money formatting logic
    $('.input-money').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== "") {
            $(this).val(new Intl.NumberFormat('en-US').format(value));
        }
    });
});

function showTab(tabId) {
    $('.tab-item').removeClass('active');
    $('.tab-content').removeClass('active');
    $(`.tab-item[data-tab="${tabId}"]`).addClass('active');
    $('#' + tabId).addClass('active');
    
    // Prevent auto-scroll by using pushState instead of location.hash
    if(history.pushState) {
        history.pushState(null, null, '#' + tabId);
    } else {
        window.location.hash = tabId;
    }
}

// Department Logic
function editDept(data) {
    $('#deptFormTitle').text('Sửa phòng ban');
    $('#deptId').val(data.id);
    $('#deptCode').val(data.code);
    $('#deptName').val(data.name);
    
    $('#deptBtn').attr('name', 'edit_dept').html('<i class="fas fa-save"></i> Cập nhật');
    $('#deptCancel').show();
}

function resetDeptForm() {
    $('#deptFormTitle').text('Thêm phòng ban mới');
    $('#deptForm')[0].reset();
    $('#deptId').val('');
    
    $('#deptBtn').attr('name', 'add_dept').html('Thêm mới');
    $('#deptCancel').hide();
}

function confirmDelDept(id) {
    Modal.confirm('Bạn có chắc muốn xóa phòng ban này?', () => {
        location.href = 'settings.php?del_dept=' + id;
    });
}

// Position Logic
function openPosModal(deptId, deptName) {
    $('#posModalTitle').text('Thêm chức vụ');
    $('#posModalSubtitle').text('Phòng ban: ' + deptName);
    $('#posDeptId').val(deptId);
    $('#posId').val('');
    $('#posName').val('');
    
    $('#posBtn').attr('name', 'add_position').text('Thêm mới');
    $('#posModal').css('display', 'flex');
}

function editPos(data, deptName) {
    $('#posModalTitle').text('Sửa chức vụ');
    $('#posModalSubtitle').text('Phòng ban: ' + deptName);
    $('#posDeptId').val(data.department_id);
    $('#posId').val(data.id);
    $('#posName').val(data.name);
    
    $('#posBtn').attr('name', 'edit_position').text('Cập nhật');
    $('#posModal').css('display', 'flex');
}

function confirmDelPos(id) {
    Modal.confirm('Bạn có chắc muốn xóa chức vụ này?', () => {
        location.href = 'settings.php?del_position=' + id;
    });
}

// Document Type Logic
function editDoc(data) {
    $('#docFormTitle').text('Sửa loại hồ sơ');
    $('#docId').val(data.id);
    $('#docCode').val(data.code);
    $('#docName').val(data.name);
    $('#docRequired').prop('checked', data.is_required == 1);
    $('#docMultiple').prop('checked', data.is_multiple == 1);
    
    $('#docBtn').attr('name', 'edit_doctype').html('<i class="fas fa-save"></i> Cập nhật');
    $('#docCancel').show();
}

function resetDocForm() {
    $('#docFormTitle').text('Thêm loại hồ sơ');
    $('#docForm')[0].reset();
    $('#docId').val('');
    $('#docRequired').prop('checked', true); // Default
    
    $('#docBtn').attr('name', 'add_doctype').html('Thêm mới');
    $('#docCancel').hide();
}

function confirmDelDoc(id) {
    Modal.confirm('Bạn có chắc muốn xóa loại hồ sơ này? Các tài liệu liên quan sẽ bị lỗi hiển thị.', () => {
        location.href = 'settings.php?del_doctype=' + id;
    });
}
</script>

<?php include '../includes/footer.php'; ?>