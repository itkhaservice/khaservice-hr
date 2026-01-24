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

// --- DATA PROCESSING FOR ORGANIZATION TAB ---
// 1. Get Departments
$departments = db_fetch_all("SELECT * FROM departments ORDER BY stt ASC, name ASC");
// 2. Get Positions
$positions = db_fetch_all("SELECT * FROM positions ORDER BY stt ASC, name ASC");

// 3. Group Positions by Department
$org_tree = [];
foreach ($departments as $dept) {
    $org_tree[$dept['id']] = $dept;
    $org_tree[$dept['id']]['children'] = [];
}
foreach ($positions as $pos) {
    if (isset($org_tree[$pos['department_id']])) {
        $org_tree[$pos['department_id']]['children'][] = $pos;
    }
}
// ---------------------------------------------

// Fetch Document Settings
$doc_settings = db_fetch_all("SELECT * FROM document_settings ORDER BY id ASC");
?>

<div class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Cài đặt hệ thống</h1>
        </div>

        <div class="settings-layout">
            <!-- Settings Sidebar -->
            <div class="settings-sidebar card">
                <div class="nav flex-column nav-pills">
                    <button class="nav-link active" data-tab="company">
                        <i class="fas fa-building"></i> <span>Thông tin chung</span>
                    </button>
                    <button class="nav-link" data-tab="organization">
                        <i class="fas fa-sitemap"></i> <span>Cơ cấu Tổ chức</span>
                    </button>
                    <button class="nav-link" data-tab="documents">
                        <i class="fas fa-file-contract"></i> <span>Cấu hình Hồ sơ</span>
                    </button>
                    <button class="nav-link" data-tab="attendance">
                        <i class="fas fa-clock"></i> <span>Công & Phép</span>
                    </button>
                    <button class="nav-link" data-tab="salary">
                        <i class="fas fa-coins"></i> <span>Cấu hình Lương</span>
                    </button>
                    <?php if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1'): ?>
                    <button class="nav-link" data-tab="sync">
                        <i class="fas fa-sync-alt"></i> <span>Đồng bộ Dữ liệu</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                
                <!-- Tab: Company Info -->
                <div class="tab-pane active" id="company">
                    <div class="card">
                        <div class="card-header-simple">
                            <h3>Thông tin Doanh nghiệp</h3>
                            <p class="text-muted">Thông tin này sẽ hiển thị trên các báo cáo và phiếu lương.</p>
                        </div>
                        <form id="company-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tên công ty</label>
                                    <input type="text" name="company_name" class="form-control" value="<?php echo $settings['company_name'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Mã số thuế</label>
                                    <input type="text" name="company_tax_code" class="form-control" value="<?php echo $settings['company_tax_code'] ?? ''; ?>">
                                </div>
                                <div class="form-group span-2">
                                    <label>Địa chỉ trụ sở</label>
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
                                <div class="form-group span-2">
                                    <label>Website</label>
                                    <input type="text" name="company_website" class="form-control" value="<?php echo $settings['company_website'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab: Organization (Redesigned) -->
                <div class="tab-pane" id="organization">
                    <div class="section-header-flex">
                        <div class="section-title">
                            <h3>Sơ đồ Tổ chức</h3>
                            <p class="text-muted">Quản lý Phòng ban và Chức vụ trực thuộc</p>
                        </div>
                        <button class="btn btn-sm btn-success" onclick="openDeptModal()"><i class="fas fa-plus"></i> Thêm Phòng ban</button>
                    </div>

                    <div class="org-grid">
                        <?php foreach ($org_tree as $dept): ?>
                            <div class="org-card">
                                <div class="org-header">
                                    <div class="org-title">
                                        <span class="badge badge-primary"><?php echo $dept['code']; ?></span>
                                        <strong><?php echo $dept['name']; ?></strong>
                                    </div>
                                    <div class="org-actions">
                                        <button onclick="editDept(<?php echo htmlspecialchars(json_encode($dept)); ?>)" title="Sửa phòng ban"><i class="fas fa-pen"></i></button>
                                        <button class="text-danger" onclick="deleteDept(<?php echo $dept['id']; ?>)" title="Xóa phòng ban"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div class="org-body">
                                    <?php if (empty($dept['children'])): ?>
                                        <div class="empty-state">Chưa có chức vụ</div>
                                    <?php else: ?>
                                        <ul class="pos-list">
                                            <?php foreach ($dept['children'] as $pos): ?>
                                                <li>
                                                    <div class="pos-info">
                                                        <i class="fas fa-user-tie text-muted" style="font-size: 0.8rem;"></i>
                                                        <span><?php echo $pos['name']; ?></span>
                                                        <?php if($pos['code']): ?><small class="text-muted">(<?php echo $pos['code']; ?>)</small><?php endif; ?>
                                                    </div>
                                                    <div class="pos-actions">
                                                        <i class="fas fa-pen text-primary" onclick="editPos(<?php echo htmlspecialchars(json_encode($pos)); ?>)"></i>
                                                        <i class="fas fa-times text-danger" onclick="deletePos(<?php echo $pos['id']; ?>)"></i>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="org-footer">
                                    <button class="btn-add-pos" onclick="openPosModal(<?php echo $dept['id']; ?>)">
                                        <i class="fas fa-plus-circle"></i> Thêm chức vụ
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab: Documents -->
                <div class="tab-pane" id="documents">
                    <div class="section-header-flex">
                        <div class="section-title">
                            <h3>Cấu hình Hồ sơ</h3>
                            <p class="text-muted">Định nghĩa các loại giấy tờ bắt buộc trong hồ sơ nhân viên</p>
                        </div>
                        <button class="btn btn-sm btn-success" onclick="openDocModal()"><i class="fas fa-plus"></i> Thêm mới</button>
                    </div>

                    <div class="card">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="100">Mã</th>
                                        <th>Tên loại hồ sơ</th>
                                        <th class="text-center">Bắt buộc</th>
                                        <th class="text-center">Nhiều bản</th>
                                        <th width="100" class="text-right">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doc_settings as $ds): ?>
                                        <tr>
                                            <td><code><?php echo $ds['code']; ?></code></td>
                                            <td><strong><?php echo $ds['name']; ?></strong></td>
                                            <td class="text-center">
                                                <?php echo $ds['is_required'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-muted" style="opacity:0.3"></i>'; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $ds['is_multiple'] ? '<i class="fas fa-check-circle text-primary"></i>' : '<i class="fas fa-times-circle text-muted" style="opacity:0.3"></i>'; ?>
                                            </td>
                                            <td class="text-right">
                                                <button class="btn-icon text-primary" onclick="editDoc(<?php echo htmlspecialchars(json_encode($ds)); ?>)"><i class="fas fa-edit"></i></button>
                                                <button class="btn-icon text-danger" onclick="deleteDoc(<?php echo $ds['id']; ?>)"><i class="fas fa-trash-alt"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab: Attendance -->
                <div class="tab-pane" id="attendance">
                    <div class="section-header-flex">
                        <div class="section-title">
                            <h3>Công & Phép</h3>
                            <p class="text-muted">Thiết lập ngày nghỉ tiêu chuẩn và định mức phép năm</p>
                        </div>
                    </div>

                    <div class="card">
                        <form id="attendance-settings-form">
                            <div class="form-section-title">Ngày nghỉ hàng tuần</div>
                            <div class="form-group">
                                <label class="custom-checkbox">
                                    <input type="checkbox" checked disabled> 
                                    <span>Chủ nhật (Mặc định)</span>
                                </label>
                                <label class="custom-checkbox" style="margin-top: 10px;">
                                    <?php 
                                        $weekly_off = explode(',', $settings['attendance_weekly_off'] ?? ''); 
                                    ?>
                                    <input type="checkbox" name="attendance_weekly_off[]" value="6" <?php echo in_array('6', $weekly_off) ? 'checked' : ''; ?>> 
                                    <span>Thứ 7 (Check nếu công ty nghỉ cả Thứ 7)</span>
                                </label>
                                <small class="text-muted d-block mt-2">Ngày được chọn sẽ không tính vào công chuẩn (Standard Working Days).</small>
                            </div>

                            <div class="form-section-title mt-4">Cấu hình Phép năm</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Định mức phép được cấp mỗi tháng</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="leave_monthly_accrual" class="form-control" value="<?php echo $settings['leave_monthly_accrual'] ?? '1.0'; ?>">
                                        <span class="input-group-addon">ngày</span>
                                    </div>
                                    <small class="text-muted">Mặc định: 1.0 ngày/tháng (12 ngày/năm).</small>
                                </div>
                            </div>

                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu cấu hình</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab: Salary -->
                <div class="tab-pane" id="salary">
                    <div class="section-header-flex">
                        <div class="section-title">
                            <h3>Tham số Lương & Bảo hiểm</h3>
                            <p class="text-muted">Cấu hình các hệ số tính toán lương tự động</p>
                        </div>
                    </div>

                    <div class="card">
                        <form id="salary-settings-form">
                            <div class="form-section-title">Hệ số Làm thêm giờ (OT)</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Ngày thường</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="salary_ot_rate_normal" class="form-control" value="<?php echo $settings['salary_ot_rate_normal'] ?? '1.5'; ?>">
                                        <span class="input-group-addon">x</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Chủ nhật (Ngày nghỉ tuần)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="salary_ot_rate_sunday" class="form-control" value="<?php echo $settings['salary_ot_rate_sunday'] ?? '2.0'; ?>">
                                        <span class="input-group-addon">x</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Ngày Lễ, Tết</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="salary_ot_rate_holiday" class="form-control" value="<?php echo $settings['salary_ot_rate_holiday'] ?? '3.0'; ?>">
                                        <span class="input-group-addon">x</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section-title mt-4">Tỷ lệ đóng Bảo hiểm (%)</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>BHXH (Hưu trí & Tử tuất)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="insurance_bhxh_percent" class="form-control" value="<?php echo $settings['insurance_bhxh_percent'] ?? '8'; ?>">
                                        <span class="input-group-addon">%</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>BHYT (Y tế)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="insurance_bhyt_percent" class="form-control" value="<?php echo $settings['insurance_bhyt_percent'] ?? '1.5'; ?>">
                                        <span class="input-group-addon">%</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>BHTN (Thất nghiệp)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="insurance_bhtn_percent" class="form-control" value="<?php echo $settings['insurance_bhtn_percent'] ?? '1'; ?>">
                                        <span class="input-group-addon">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section-title mt-4">Các khoản khấu trừ khác</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Đoàn phí Công đoàn (Mặc định)</label>
                                    <div class="input-group">
                                        <input type="text" name="salary_union_fee_default" class="form-control input-money" value="<?php echo number_format($settings['salary_union_fee_default'] ?? 0); ?>">
                                        <span class="input-group-addon">VNĐ</span>
                                    </div>
                                    <small class="text-muted">Áp dụng cho nhân viên chưa cấu hình riêng.</small>
                                </div>
                            </div>

                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu cấu hình</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab: Sync Data (Localhost Only) -->
                <?php if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1'): ?>
                <div class="tab-pane" id="sync">
                    <div class="card" style="height: 600px; overflow: hidden; padding: 0;">
                        <iframe src="/khaservice-hr/tools/dashboard_sync.php" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- SHARED MODALS -->
<!-- Modal Dept -->
<div id="deptModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="deptModalTitle">Thêm Phòng ban</h3>
            <button class="btn-close" onclick="closeModal('deptModal')">&times;</button>
        </div>
        <form id="deptForm">
            <input type="hidden" name="id" id="deptId">
            <div class="form-group">
                <label>Mã phòng ban <span class="text-danger">*</span></label>
                <input type="text" name="code" id="deptCode" class="form-control" placeholder="Ví dụ: HR, IT, ACC" required>
            </div>
            <div class="form-group">
                <label>Tên phòng ban <span class="text-danger">*</span></label>
                <input type="text" name="name" id="deptName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Thứ tự hiển thị</label>
                <input type="number" name="stt" id="deptStt" class="form-control" value="99">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('deptModal')">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pos -->
<div id="posModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="posModalTitle">Thêm Chức vụ</h3>
            <button class="btn-close" onclick="closeModal('posModal')">&times;</button>
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
                <label>Tên chức vụ <span class="text-danger">*</span></label>
                <input type="text" name="name" id="posName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Mã chức vụ</label>
                <input type="text" name="code" id="posCode" class="form-control">
            </div>
            <div class="form-group">
                <label>Thứ tự</label>
                <input type="number" name="stt" id="posStt" class="form-control" value="99">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('posModal')">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Doc -->
<div id="docModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="docModalTitle">Cấu hình Hồ sơ</h3>
            <button class="btn-close" onclick="closeModal('docModal')">&times;</button>
        </div>
        <form id="docForm">
            <input type="hidden" name="id" id="docId">
            <div class="form-group">
                <label>Mã hồ sơ <span class="text-danger">*</span></label>
                <input type="text" name="code" id="docCode" class="form-control" placeholder="Ví dụ: CCCD" required>
            </div>
            <div class="form-group">
                <label>Tên loại hồ sơ <span class="text-danger">*</span></label>
                <input type="text" name="name" id="docName" class="form-control" required>
            </div>
            <div class="form-group checkbox-group">
                <label class="custom-checkbox">
                    <input type="checkbox" name="is_required" id="docRequired" value="1"> 
                    <span>Bắt buộc nộp</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" name="is_multiple" id="docMultiple" value="1"> 
                    <span>Cho phép nhiều tệp</span>
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('docModal')">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<style>
/* New Organization Grid */
.org-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
}
.org-card {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    position: relative;
}
.org-card::before {
    content: "";
    position: absolute;
    top: 0; left: 0; width: 4px; height: 100%;
    background: var(--primary-color);
    opacity: 0.7;
}
.org-card:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-5px);
    border-color: var(--primary-light);
}
.org-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.org-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.org-title strong {
    font-size: 1rem;
    color: var(--text-main);
    letter-spacing: -0.01em;
}
.org-title .badge {
    align-self: flex-start;
    font-size: 0.65rem;
    padding: 2px 8px;
}
.org-actions {
    display: flex;
    gap: 5px;
}
.org-actions button {
    background: #fff;
    border: 1px solid #e2e8f0;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: #64748b;
    transition: 0.2s;
}
.org-actions button:hover { 
    background: var(--bg-main);
    color: var(--primary-color);
    border-color: var(--primary-color);
}
.org-actions button.text-danger:hover {
    color: #dc2626;
    border-color: #dc2626;
    background: #fee2e2;
}

.org-body {
    padding: 10px 0;
    flex: 1;
    background: #fff;
}
.empty-state {
    padding: 30px 20px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.85rem;
}
.pos-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.pos-list li {
    padding: 10px 20px;
    margin: 2px 10px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
    transition: 0.2s;
    border: 1px solid transparent;
}
.pos-list li:hover { 
    background: #f1f5f9;
    border-color: #e2e8f0;
}
.pos-info { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    color: var(--text-main);
    font-weight: 500;
}
.pos-info i { width: 16px; text-align: center; opacity: 0.5; }
.pos-actions { 
    opacity: 0; 
    transition: 0.2s; 
    display: flex; 
    gap: 12px; 
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 6px;
}
.pos-list li:hover .pos-actions { opacity: 1; }
.pos-actions i { cursor: pointer; font-size: 0.8rem; }
.pos-actions i:hover { transform: scale(1.2); }

.org-footer {
    padding: 15px 20px;
    background: #fff;
    border-top: 1px solid #f1f5f9;
}
.btn-add-pos {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    color: #64748b;
    width: 100%;
    padding: 8px;
    border-radius: 8px;
    font-size: 0.8rem;
    cursor: pointer;
    font-weight: 600;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-add-pos:hover {
    background: rgba(36, 162, 92, 0.05);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

/* Dark Mode Org */
body.dark-mode .org-card { background: #1e293b; border-color: #334155; }
body.dark-mode .org-header { background: #0f172a; border-bottom-color: #334155; }
body.dark-mode .org-title strong { color: #f1f5f9; }
body.dark-mode .org-actions button { background: #1e293b; border-color: #334155; color: #94a3b8; }
body.dark-mode .org-body { background: #1e293b; }
body.dark-mode .pos-list li:hover { background: rgba(255,255,255,0.03); border-color: #334155; }
body.dark-mode .pos-actions { background: #334155; }
body.dark-mode .org-footer { background: #1e293b; border-top-color: #334155; }
body.dark-mode .btn-add-pos { background: #0f172a; border-color: #334155; }

/* Sync Tab iframe styling */
#sync .card {
    height: 600px;
    overflow: hidden;
    padding: 0;
    border-radius: 12px;
}
#sync iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

/* Rest of Styles */
.settings-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 25px;
    align-items: start;
}

/* Sidebar Nav */
.settings-sidebar .nav-pills {
    padding: 10px;
}
.nav-pills .nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: var(--text-sub);
    border-radius: 6px;
    transition: all 0.2s;
    background: transparent;
    border: none;
    width: 100%;
    text-align: left;
    font-weight: 500;
    margin-bottom: 5px;
    cursor: pointer;
}
.nav-pills .nav-link i { width: 24px; text-align: center; margin-right: 10px; }
.nav-pills .nav-link:hover { background-color: var(--bg-main); color: var(--primary-color); }
.nav-pills .nav-link.active { background-color: rgba(36, 162, 92, 0.1); color: var(--primary-color); font-weight: 600; }

/* Content Area */
.tab-pane { display: none; animation: fadeIn 0.3s ease; }
.tab-pane.active { display: block; }

.card-header-simple { border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; }
.card-header-simple h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: var(--text-main); }
.card-header-flex { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); margin-bottom: 15px; }
.card-header-flex h3 { margin: 0 0 2px 0; font-size: 1.1rem; }

/* Section Header for Tabs */
.section-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    padding: 0 5px;
}
.section-title h3 {
    margin: 0 0 5px 0;
    font-size: 1.25rem;
    color: var(--text-main);
    font-weight: 700;
}
.section-title p {
    margin: 0;
    font-size: 0.9rem;
}

/* Forms */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid .span-2 { grid-column: span 2; }
.form-footer { margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: right; }
.form-section-title { font-weight: 600; color: var(--primary-dark); margin-bottom: 15px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }

/* Input Groups */
.input-group { display: flex; align-items: center; }
.input-group .form-control { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.input-group-addon { padding: 10px 15px; background: var(--bg-main); border: 1px solid var(--border-color); border-left: 0; border-radius: 0 6px 6px 0; color: var(--text-sub); font-size: 0.9rem; }

/* Buttons & Badges */
.btn-icon { background: none; border: none; cursor: pointer; padding: 5px; opacity: 0.7; transition: 0.2s; }
.btn-icon:hover { opacity: 1; transform: scale(1.1); }

/* Modal specific overrides */
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center; }
.modal-box { background: var(--card-bg); padding: 25px; border-radius: 12px; width: 450px; max-width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: popIn 0.3s; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-title { font-size: 1.2rem; font-weight: 700; margin: 0; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-sub); }
.checkbox-group { display: flex; gap: 20px; margin-top: 10px; }
.custom-checkbox { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }

/* Responsive */
@media (max-width: 768px) {
    .settings-layout { grid-template-columns: 1fr; }
    .settings-sidebar { margin-bottom: 20px; }
    .nav-pills { display: flex; overflow-x: auto; padding: 10px; gap: 10px; }
    .nav-pills .nav-link { white-space: nowrap; width: auto; margin: 0; }
    .form-grid { grid-template-columns: 1fr; }
    .form-grid .span-2 { grid-column: span 1; }
    .org-grid { grid-template-columns: 1fr; }
}

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes popIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

/* Dark Mode Overrides */
body.dark-mode .nav-pills .nav-link:hover { background-color: rgba(255,255,255,0.05); }
body.dark-mode .input-group-addon { background-color: #0f172a; border-color: #334155; }
</style>

<script>
// Tab Handling
const tabs = document.querySelectorAll('.nav-link');
const panes = document.querySelectorAll('.tab-pane');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        // Remove active
        tabs.forEach(t => t.classList.remove('active'));
        panes.forEach(p => p.classList.remove('active'));
        
        // Add active
        tab.classList.add('active');
        const target = tab.dataset.tab;
        document.getElementById(target).classList.add('active');
        
        // Save state
        localStorage.setItem('settings_active_tab', target);
    });
});

// Restore Tab
const savedTab = localStorage.getItem('settings_active_tab');
if (savedTab) {
    const activeBtn = document.querySelector(`.nav-link[data-tab="${savedTab}"]`);
    if (activeBtn) activeBtn.click();
}

// Money Formatter
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('input-money')) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = value ? new Intl.NumberFormat('en-US').format(parseInt(value)) : '';
    }
});

// AJAX Save Form
function setupFormSave(formId, action) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

        const formData = new FormData(form);
        const data = { action: action, settings: {} };
        
        // Handle normal settings forms vs dynamic logic
        if (formId === 'company-form' || formId === 'salary-settings-form' || formId === 'attendance-settings-form') {
            // Helper to handle multiple values (like checkboxes)
            for (var pair of formData.entries()) {
                var key = pair[0];
                var val = pair[1];
                
                if (key.endsWith('[]')) {
                    var realKey = key.slice(0, -2);
                    if (!data.settings[realKey]) {
                        data.settings[realKey] = [];
                    }
                    data.settings[realKey].push(val);
                } else {
                    if (form.querySelector(`[name="${key}"]`) && form.querySelector(`[name="${key}"]`).classList.contains('input-money')) {
                        val = val.replace(/,/g, '');
                    }
                    data.settings[key] = val;
                }
            }
        }

        fetch('modules/system/settings_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') { Toast.success('Đã lưu cài đặt thành công!'); } 
            else { Toast.error(res.message || 'Có lỗi xảy ra'); }
        })
        .catch(() => Toast.error('Lỗi kết nối máy chủ'))
        .finally(() => { btn.disabled = false; btn.innerHTML = originalText; });
    });
}

setupFormSave('company-form', 'save_company');
setupFormSave('salary-settings-form', 'save_salary');
setupFormSave('attendance-settings-form', 'save_attendance');

// Modal Logic
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.onclick = e => { if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none'; }

// Department CRUD
function openDeptModal() {
    document.getElementById('deptForm').reset();
    document.getElementById('deptId').value = '';
    document.getElementById('deptModalTitle').innerText = 'Thêm Phòng ban';
    openModal('deptModal');
}
function editDept(d) {
    document.getElementById('deptId').value = d.id;
    document.getElementById('deptCode').value = d.code;
    document.getElementById('deptName').value = d.name;
    document.getElementById('deptStt').value = d.stt;
    document.getElementById('deptModalTitle').innerText = 'Sửa Phòng ban';
    openModal('deptModal');
}
document.getElementById('deptForm').onsubmit = e => { e.preventDefault(); submitItemForm('deptForm', 'save_dept'); };
function deleteDept(id) { deleteItem('delete_dept', id, 'Xóa phòng ban này sẽ ảnh hưởng đến nhân viên trực thuộc?'); }

// Position CRUD
function openPosModal(preselectedDeptId = null) {
    document.getElementById('posForm').reset();
    document.getElementById('posId').value = '';
    document.getElementById('posModalTitle').innerText = 'Thêm Chức vụ';
    if (preselectedDeptId) {
        document.getElementById('posDeptId').value = preselectedDeptId;
    }
    openModal('posModal');
}
function editPos(p) {
    document.getElementById('posId').value = p.id;
    document.getElementById('posDeptId').value = p.department_id;
    document.getElementById('posName').value = p.name;
    document.getElementById('posCode').value = p.code;
    document.getElementById('posStt').value = p.stt;
    document.getElementById('posModalTitle').innerText = 'Sửa Chức vụ';
    openModal('posModal');
}
document.getElementById('posForm').onsubmit = e => { e.preventDefault(); submitItemForm('posForm', 'save_pos'); };
function deletePos(id) { deleteItem('delete_pos', id); }

// Document CRUD
function openDocModal() {
    document.getElementById('docForm').reset();
    document.getElementById('docId').value = '';
    document.getElementById('docModalTitle').innerText = 'Thêm Loại Hồ sơ';
    openModal('docModal');
}
function editDoc(d) {
    document.getElementById('docId').value = d.id;
    document.getElementById('docCode').value = d.code;
    document.getElementById('docName').value = d.name;
    document.getElementById('docRequired').checked = d.is_required == 1;
    document.getElementById('docMultiple').checked = d.is_multiple == 1;
    document.getElementById('docModalTitle').innerText = 'Sửa Loại Hồ sơ';
    openModal('docModal');
}
document.getElementById('docForm').onsubmit = e => { e.preventDefault(); submitItemForm('docForm', 'save_doc'); };
function deleteDoc(id) { deleteItem('delete_doc', id); }

// Helper: Submit Item
function submitItemForm(formId, action) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const data = { action: action };
    formData.forEach((val, key) => data[key] = val);
    
    // Checkbox special handling
    if (action === 'save_doc') {
        data.is_required = form.querySelector('[name="is_required"]').checked ? 1 : 0;
        data.is_multiple = form.querySelector('[name="is_multiple"]').checked ? 1 : 0;
    }

    fetch('modules/system/settings_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') { location.reload(); }
        else { Toast.error(res.message); }
    });
}

// Helper: Delete Item
function deleteItem(action, id, msg = 'Bạn có chắc chắn muốn xóa mục này?') {
    Modal.confirm(msg, () => {
        fetch('modules/system/settings_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, id: id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') { location.reload(); }
            else { Toast.error(res.message); }
        });
    });
}

// Sync Theme with Iframe
function syncIframeTheme() {
    const iframe = document.querySelector('iframe[src*="dashboard_sync.php"]');
    if (!iframe) return;
    
    const isDark = document.body.classList.contains('dark-mode');
    const msg = isDark ? 'theme-dark' : 'theme-light';
    iframe.contentWindow.postMessage(msg, '*');
}

// Initial Sync & Observer
window.addEventListener('load', syncIframeTheme);
// Observer for body class changes
const observer = new MutationObserver(syncIframeTheme);
observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
</script>

            </div> <!-- End settings-content -->
        </div> <!-- End settings-layout -->
    </div> <!-- End content-wrapper -->
</div> <!-- End main-content -->

<?php include '../includes/footer.php'; ?>