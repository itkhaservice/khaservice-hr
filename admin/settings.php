<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Security: Check Permission
require_permission('manage_system');

include '../includes/header.php';
include '../includes/sidebar.php';

// Fetch all settings
$raw_settings = db_fetch_all("SELECT * FROM settings");
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Fetch Departments & Positions
$departments = db_fetch_all("SELECT * FROM departments ORDER BY stt ASC, name ASC");
$positions = db_fetch_all("SELECT p.*, d.name as dept_name FROM positions p JOIN departments d ON p.department_id = d.id ORDER BY d.stt ASC, p.stt ASC");

// Fetch Document Settings
$doc_settings = db_fetch_all("SELECT * FROM document_settings ORDER BY id ASC");
?>

<div class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title"><i class="fas fa-cog"></i> Cài đặt hệ thống</h1>
        </div>

        <div class="card" style="padding: 0;">
            <div class="tab-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="company"><i class="fas fa-building"></i> Thông tin Công ty</button>
                    <button class="tab-btn" data-tab="organization"><i class="fas fa-sitemap"></i> Phòng ban - Chức vụ</button>
                    <button class="tab-btn" data-tab="documents"><i class="fas fa-file-alt"></i> Cấu hình Hồ sơ</button>
                    <button class="tab-btn" data-tab="salary"><i class="fas fa-money-check-alt"></i> Cấu hình Tiền lương</button>
                </div>

                <div class="tab-content active" id="company">
                    <form id="company-form" class="settings-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tên công ty</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo $settings['company_name'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Mã số thuế</label>
                                <input type="text" name="company_tax_code" class="form-control" value="<?php echo $settings['company_tax_code'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Địa chỉ</label>
                                <input type="text" name="company_address" class="form-control" value="<?php echo $settings['company_address'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="company_phone" class="form-control" value="<?php echo $settings['company_phone'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Email liên hệ</label>
                                <input type="email" name="company_email" class="form-control" value="<?php echo $settings['company_email'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Website</label>
                                <input type="text" name="company_website" class="form-control" value="<?php echo $settings['company_website'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
                        </div>
                    </form>
                </div>

                <div class="tab-content" id="organization">
                    <div class="split-layout">
                        <div class="card-inner">
                            <div class="card-header-inner">
                                <h3><i class="fas fa-door-open"></i> Danh sách Phòng ban</h3>
                                <button class="btn btn-sm btn-success" onclick="openDeptModal()"><i class="fas fa-plus"></i> Thêm</button>
                            </div>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Mã</th>
                                            <th>Tên phòng ban</th>
                                            <th width="80"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departments as $d): ?>
                                            <tr>
                                                <td><?php echo $d['stt']; ?></td>
                                                <td><code><?php echo $d['code']; ?></code></td>
                                                <td><strong><?php echo $d['name']; ?></strong></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm" onclick="editDept(<?php echo htmlspecialchars(json_encode($d)); ?>)"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-sm text-danger" onclick="deleteDept(<?php echo $d['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card-inner">
                            <div class="card-header-inner">
                                <h3><i class="fas fa-user-tie"></i> Danh sách Chức vụ</h3>
                                <button class="btn btn-sm btn-success" onclick="openPosModal()"><i class="fas fa-plus"></i> Thêm</button>
                            </div>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Phòng ban</th>
                                            <th>Tên chức vụ</th>
                                            <th width="80"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($positions as $p): ?>
                                            <tr>
                                                <td><small class="text-sub"><?php echo $p['dept_name']; ?></small></td>
                                                <td><strong><?php echo $p['name']; ?></strong></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm" onclick="editPos(<?php echo htmlspecialchars(json_encode($p)); ?>)"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-sm text-danger" onclick="deletePos(<?php echo $p['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="documents">
                    <div class="card-inner">
                        <div class="card-header-inner">
                            <h3><i class="fas fa-file-medical"></i> Danh mục Hồ sơ Nhân viên</h3>
                            <button class="btn btn-sm btn-success" onclick="openDocModal()"><i class="fas fa-plus"></i> Thêm loại hồ sơ</button>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mã loại</th>
                                        <th>Tên hồ sơ</th>
                                        <th class="text-center">Bắt buộc</th>
                                        <th class="text-center">Nộp nhiều bản</th>
                                        <th width="100"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doc_settings as $ds): ?>
                                        <tr>
                                            <td><code><?php echo $ds['code']; ?></code></td>
                                            <td><strong><?php echo $ds['name']; ?></strong></td>
                                            <td class="text-center">
                                                <?php echo $ds['is_required'] ? '<span class="badge badge-danger">Có</span>' : '<span class="badge badge-secondary">Không</span>'; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $ds['is_multiple'] ? '<span class="badge badge-primary">Có</span>' : '<span class="badge badge-secondary">Không</span>'; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm" onclick="editDoc(<?php echo htmlspecialchars(json_encode($ds)); ?>)"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm text-danger" onclick="deleteDoc(<?php echo $ds['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="salary">
                    <form id="salary-settings-form" class="settings-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tỷ lệ tính lương Tăng ca (Ngày thường)</label>
                                <input type="number" step="0.1" name="salary_ot_rate_normal" class="form-control" value="<?php echo $settings['salary_ot_rate_normal'] ?? '1.5'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tỷ lệ tính lương Tăng ca (Chủ nhật)</label>
                                <input type="number" step="0.1" name="salary_ot_rate_sunday" class="form-control" value="<?php echo $settings['salary_ot_rate_sunday'] ?? '2.0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tỷ lệ tính lương Tăng ca (Lễ tết)</label>
                                <input type="number" step="0.1" name="salary_ot_rate_holiday" class="form-control" value="<?php echo $settings['salary_ot_rate_holiday'] ?? '3.0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Đoàn phí mặc định (VNĐ)</label>
                                <input type="text" name="salary_union_fee_default" class="form-control input-money" value="<?php echo number_format($settings['salary_union_fee_default'] ?? 0); ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu cấu hình lương</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Department -->
<div id="deptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="deptModalTitle">Thêm Phòng ban</h3>
            <span class="close" onclick="closeModal('deptModal')">&times;</span>
        </div>
        <form id="deptForm">
            <input type="hidden" name="id" id="deptId">
            <div class="form-group">
                <label>Mã phòng ban</label>
                <input type="text" name="code" id="deptCode" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Tên phòng ban</label>
                <input type="text" name="name" id="deptName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>STT hiển thị</label>
                <input type="number" name="stt" id="deptStt" class="form-control" value="99">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Position -->
<div id="posModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="posModalTitle">Thêm Chức vụ</h3>
            <span class="close" onclick="closeModal('posModal')">&times;</span>
        </div>
        <form id="posForm">
            <input type="hidden" name="id" id="posId">
            <div class="form-group">
                <label>Thuộc phòng ban</label>
                <select name="department_id" id="posDeptId" class="form-control" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tên chức vụ</label>
                <input type="text" name="name" id="posName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Mã chức vụ (Tùy chọn)</label>
                <input type="text" name="code" id="posCode" class="form-control">
            </div>
            <div class="form-group">
                <label>STT hiển thị</label>
                <input type="number" name="stt" id="posStt" class="form-control" value="99">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Documents -->
<div id="docModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="docModalTitle">Cấu hình Hồ sơ</h3>
            <span class="close" onclick="closeModal('docModal')">&times;</span>
        </div>
        <form id="docForm">
            <input type="hidden" name="id" id="docId">
            <div class="form-group">
                <label>Mã loại hồ sơ (Viết tắt, không dấu)</label>
                <input type="text" name="code" id="docCode" class="form-control" placeholder="Ví dụ: CCCD, HK, SYLL" required>
            </div>
            <div class="form-group">
                <label>Tên gọi đầy đủ</label>
                <input type="text" name="name" id="docName" class="form-control" required>
            </div>
            <div class="form-group" style="display: flex; gap: 20px; align-items: center; margin-top: 10px;">
                <label style="margin:0; display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_required" id="docRequired" value="1"> Bắt buộc nộp
                </label>
                <label style="margin:0; display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_multiple" id="docMultiple" value="1"> Cho phép nộp nhiều tệp
                </label>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<style>
.tab-container { display: flex; flex-direction: column; }
.tabs { display: flex; border-bottom: 1px solid #eee; background: #f8fafc; padding: 0 10px; border-radius: 8px 8px 0 0; }
.tab-btn { padding: 15px 25px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b; border-bottom: 3px solid transparent; transition: 0.3s; display: flex; align-items: center; gap: 10px; }
.tab-btn:hover { color: var(--primary-color); }
.tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); background: rgba(36, 162, 92, 0.05); }

.tab-content { display: none; padding: 25px; }
.tab-content.active { display: block; }

.settings-form { max-width: 800px; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
.form-actions { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }

.split-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
.card-inner { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.card-header-inner { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; }
.card-header-inner h3 { margin: 0; font-size: 1rem; color: #1e293b; }

/* Modal Styles */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.modal-content { background: #fff; margin: 5% auto; padding: 0; border-radius: 12px; width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: slideDown 0.3s ease-out; }
@keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 1.2rem; }
.close { cursor: pointer; font-size: 1.5rem; opacity: 0.5; }
.close:hover { opacity: 1; }
#deptForm, #posForm, #docForm { padding: 20px; }
.modal-footer { margin-top: 20px; text-align: right; }

.input-money { text-align: right; font-weight: 600; }

body.dark-mode .tabs { background: #1e293b; border-bottom-color: #334155; }
body.dark-mode .tab-btn { color: #94a3b8; }
body.dark-mode .tab-btn.active { background: rgba(255,255,255,0.02); }
body.dark-mode .card-inner { background: #1e293b; border-color: #334155; }
body.dark-mode .card-header-inner { background: #1e293b; border-bottom-color: #334155; }
body.dark-mode .card-header-inner h3 { color: #f1f5f9; }
body.dark-mode .modal-content { background: #1e293b; color: #f1f5f9; }
body.dark-mode .modal-header { border-bottom-color: #334155; }
</style>

<script>
// Tab Switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
        localStorage.setItem('active_settings_tab', btn.dataset.tab);
    });
});

// Restore active tab
const savedTab = localStorage.getItem('active_settings_tab');
if (savedTab) {
    const tabBtn = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
    if (tabBtn) tabBtn.click();
}

// Money format
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('input-money')) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if (value === '') value = '0';
        e.target.value = new Intl.NumberFormat('en-US').format(parseInt(value));
    }
});

// Generic AJAX save for settings table
function saveSettings(formId, action) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = { action: action, settings: {} };
        formData.forEach((value, key) => {
            if (this.querySelector(`[name="${key}"]`).classList.contains('input-money')) {
                value = value.replace(/,/g, '');
            }
            data.settings[key] = value;
        });

        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

        fetch('modules/system/settings_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(res => {
            if (res.status === 'success') {
                Toast.show('success', 'Thành công', 'Đã lưu cài đặt');
            } else { Toast.show('error', 'Lỗi', res.message); }
        })
        .catch(err => {
            console.error('Error:', err);
            Toast.show('error', 'Lỗi hệ thống', 'Không thể kết nối với máy chủ hoặc phản hồi không hợp lệ');
        })
        .finally(() => { btn.disabled = false; btn.innerHTML = originalText; });
    });
}

saveSettings('company-form', 'save_company');
saveSettings('salary-settings-form', 'save_salary');

// Modal Helpers
function openModal(id) { document.getElementById(id).style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.onclick = function(event) { if (event.target.className === 'modal') event.target.style.display = 'none'; }

// DEPT Logic
function openDeptModal() {
    document.getElementById('deptId').value = '';
    document.getElementById('deptForm').reset();
    document.getElementById('deptModalTitle').innerText = 'Thêm Phòng ban';
    openModal('deptModal');
}
function editDept(data) {
    document.getElementById('deptId').value = data.id;
    document.getElementById('deptCode').value = data.code;
    document.getElementById('deptName').value = data.name;
    document.getElementById('deptStt').value = data.stt;
    document.getElementById('deptModalTitle').innerText = 'Sửa Phòng ban';
    openModal('deptModal');
}
document.getElementById('deptForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'save_dept');
});

// POS Logic
function openPosModal() {
    document.getElementById('posId').value = '';
    document.getElementById('posForm').reset();
    document.getElementById('posModalTitle').innerText = 'Thêm Chức vụ';
    openModal('posModal');
}
function editPos(data) {
    document.getElementById('posId').value = data.id;
    document.getElementById('posDeptId').value = data.department_id;
    document.getElementById('posName').value = data.name;
    document.getElementById('posCode').value = data.code;
    document.getElementById('posStt').value = data.stt;
    document.getElementById('posModalTitle').innerText = 'Sửa Chức vụ';
    openModal('posModal');
}
document.getElementById('posForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'save_pos');
});

// DOC Logic
function openDocModal() {
    document.getElementById('docId').value = '';
    document.getElementById('docForm').reset();
    document.getElementById('docModalTitle').innerText = 'Cấu hình Hồ sơ';
    openModal('docModal');
}
function editDoc(data) {
    document.getElementById('docId').value = data.id;
    document.getElementById('docCode').value = data.code;
    document.getElementById('docName').value = data.name;
    document.getElementById('docRequired').checked = data.is_required == 1;
    document.getElementById('docMultiple').checked = data.is_multiple == 1;
    document.getElementById('docModalTitle').innerText = 'Sửa Hồ sơ';
    openModal('docModal');
}
document.getElementById('docForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'save_doc');
});

function submitForm(form, action) {
    const formData = new FormData(form);
    const data = { action: action };
    formData.forEach((value, key) => { data[key] = value; });
    // Handle checkboxes
    if (action === 'save_doc') {
        data.is_required = form.querySelector('[name="is_required"]').checked ? 1 : 0;
        data.is_multiple = form.querySelector('[name="is_multiple"]').checked ? 1 : 0;
    }

    fetch('modules/system/settings_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
    })
    .then(res => {
        if (res.status === 'success') {
            location.reload();
        } else { Toast.show('error', 'Lỗi', res.message); }
    })
    .catch(err => {
        console.error('Error:', err);
        Toast.show('error', 'Lỗi hệ thống', 'Không thể kết nối với máy chủ');
    });
}

function deleteDept(id) { if(confirm('Xóa phòng ban sẽ ảnh hưởng đến nhân sự thuộc phòng này. Tiếp tục?')) { deleteItem('delete_dept', id); } }
function deletePos(id) { if(confirm('Xác nhận xóa chức vụ này?')) { deleteItem('delete_pos', id); } }
function deleteDoc(id) { if(confirm('Xác nhận xóa loại hồ sơ này?')) { deleteItem('delete_doc', id); } }

function deleteItem(action, id) {
    fetch('modules/system/settings_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, id: id })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') { location.reload(); }
        else { Toast.show('error', 'Lỗi', res.message); }
    });
}
</script>
</div>
<?php include '../includes/footer.php'; ?>
