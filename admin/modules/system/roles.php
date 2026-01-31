<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Chỉ ADMIN mới được vào trang này
if (!is_admin()) {
    header("Location: " . BASE_URL . "404.php?error=no_permission");
    exit;
}

// Xử lý thêm vai trò mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_role'])) {
    $name = strtoupper(clean_input($_POST['name']));
    $display_name = clean_input($_POST['display_name']);
    db_query("INSERT IGNORE INTO roles (name, display_name) VALUES (?, ?)", [$name, $display_name]);
    set_toast('success', 'Đã thêm vai trò mới!');
    redirect('roles.php');
}

// Xử lý xóa vai trò
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_role'])) {
    $del_role_id = (int)$_POST['role_id'];
    
    // Prevent deleting ADMIN role
    $check = db_fetch_row("SELECT name FROM roles WHERE id = ?", [$del_role_id]);
    if ($check && $check['name'] === 'ADMIN') {
        set_toast('error', 'Không thể xóa vai trò Quản trị hệ thống (ADMIN)!');
    } else {
        // 1. Reset users having this role
        db_query("UPDATE users SET role_id = NULL WHERE role_id = ?", [$del_role_id]);
        
        // 2. Delete permissions
        db_query("DELETE FROM role_permissions WHERE role_id = ?", [$del_role_id]);
        
        // 3. Delete role
        db_query("DELETE FROM roles WHERE id = ?", [$del_role_id]);
        
        set_toast('success', 'Đã xóa vai trò thành công!');
        redirect('roles.php');
    }
}

// Xử lý cập nhật quyền cho vai trò
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_perms'])) {
    $role_id = (int)$_POST['role_id'];
    $perms = $_POST['perms'] ?? [];
    
    // Xóa quyền cũ
    db_query("DELETE FROM role_permissions WHERE role_id = ?", [$role_id]);
    
    // Thêm quyền mới
    foreach ($perms as $pid) {
        db_query("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$role_id, (int)$pid]);
    }
    set_toast('success', 'Đã cập nhật quyền hạn!');
    redirect("roles.php?id=$role_id");
}

// Xử lý Gỡ vai trò khỏi User
if (isset($_GET['action']) && $_GET['action'] == 'remove_role' && isset($_GET['user_id'])) {
    $uid = (int)$_GET['user_id'];
    db_query("UPDATE users SET role_id = NULL WHERE id = ?", [$uid]);
    set_toast('success', 'Đã gỡ vai trò khỏi nhân viên!');
    redirect("roles.php?id=" . intval($_GET['role_id']));
}

// Xử lý Xóa tài khoản User
if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['user_id'])) {
    $uid = (int)$_GET['user_id'];
    db_query("DELETE FROM users WHERE id = ?", [$uid]);
    set_toast('success', 'Đã xóa tài khoản nhân viên!');
    redirect("roles.php?id=" . intval($_GET['role_id']));
}

$roles = db_fetch_all("SELECT * FROM roles ORDER BY id ASC");
$permissions = db_fetch_all("SELECT * FROM permissions ORDER BY module ASC, name ASC");

// Nhóm quyền theo Module để hiển thị cho đẹp
$grouped_perms = [];
foreach ($permissions as $p) {
    $grouped_perms[$p['module']][] = $p;
}

// Filter Data for Users List
$departments = db_fetch_all("SELECT * FROM departments ORDER BY name ASC");
$projects = db_fetch_all("SELECT * FROM projects ORDER BY name ASC");
$positions = db_fetch_all("SELECT * FROM positions ORDER BY name ASC");

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý Phân quyền HR</h1>
            <button class="btn btn-primary" onclick="$('#addRoleModal').css('display', 'flex').hide().fadeIn()"><i class="fas fa-plus"></i> Thêm Vai trò mới</button>
        </div>

        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px;">
            <!-- Danh sách vai trò -->
            <div class="card" style="padding: 0; height: fit-content;">
                <div style="padding: 15px; border-bottom: 1px solid var(--border-color); font-weight: bold; background: #f8fafc;">
                    DANH SÁCH VAI TRÒ
                </div>
                <div class="role-list">
                    <?php foreach ($roles as $r): ?>
                        <div class="role-item <?php echo (isset($_GET['id']) && $_GET['id'] == $r['id']) ? 'active' : ''; ?>" 
                             onclick="location.href='?id=<?php echo $r['id']; ?>'"
                             style="padding: 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: 0.2s;">
                            <div style="font-weight: 700; color: var(--primary-dark);"><?php echo $r['display_name']; ?></div>
                            <small style="color: #94a3b8;">Mã: <?php echo $r['name']; ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bảng gán quyền -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                <div class="card">
                    <?php 
                    $selected_role_id = isset($_GET['id']) ? (int)$_GET['id'] : ($roles[0]['id'] ?? 0);
                    if ($selected_role_id): 
                        $selected_role = db_fetch_row("SELECT * FROM roles WHERE id = ?", [$selected_role_id]);
                        $active_perms = array_column(db_fetch_all("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$selected_role_id]), 'permission_id');
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <div>
                                <h3 style="margin: 0; color: var(--primary-color);">Cấu hình: <?php echo $selected_role['display_name']; ?></h3>
                                <p style="color: #64748b; font-size: 0.9rem; margin: 5px 0 0;">Thiết lập quyền truy cập chức năng.</p>
                            </div>
                            <?php if ($selected_role['name'] !== 'ADMIN'): ?>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteRole(<?php echo $selected_role_id; ?>)">
                                    <i class="fas fa-trash"></i> Xóa Vai trò
                                </button>
                                <form id="deleteRoleForm" method="POST" style="display:none;">
                                    <input type="hidden" name="delete_role" value="1">
                                    <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
                                </form>
                            <?php endif; ?>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                                <?php foreach ($grouped_perms as $module => $perms): ?>
                                    <div class="perm-group" style="margin-bottom: 15px;">
                                        <h4 style="text-transform: uppercase; font-size: 0.75rem; color: #94a3b8; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px;">
                                            Module: <?php echo $module; ?>
                                        </h4>
                                        <?php foreach ($perms as $p): ?>
                                            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; cursor: pointer; font-size: 0.9rem;">
                                                <input type="checkbox" name="perms[]" value="<?php echo $p['id']; ?>" 
                                                    <?php echo in_array($p['id'], $active_perms) ? 'checked' : ''; ?>
                                                    <?php echo $selected_role['name'] === 'ADMIN' ? 'disabled checked' : ''; ?>>
                                                <span><?php echo $p['name']; ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($selected_role['name'] !== 'ADMIN'): ?>
                                <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; text-align: right;">
                                    <button type="submit" name="update_perms" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Cấu hình</button>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #94a3b8;">
                            <i class="fas fa-user-shield" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>Vui lòng chọn một Vai trò để thiết lập quyền hạn.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Users List Section -->
                <?php if ($selected_role_id): 
                    // Filters (Defined before use)
                    $f_dept = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
                    $f_proj = isset($_GET['proj']) ? (int)$_GET['proj'] : 0;
                    $f_pos = isset($_GET['pos']) ? (int)$_GET['pos'] : 0;
                    
                    $user_sql = "SELECT u.id as user_id, u.username, u.fullname as user_name, u.email,
                                        e.code, e.fullname as emp_name, d.name as dept_name, p.name as proj_name, pos.name as pos_name
                                 FROM users u
                                 LEFT JOIN employees e ON u.employee_id = e.id
                                 LEFT JOIN departments d ON e.department_id = d.id
                                 LEFT JOIN projects p ON e.current_project_id = p.id
                                 LEFT JOIN positions pos ON e.position_id = pos.id
                                 WHERE u.role_id = ?";
                    $user_params = [$selected_role_id];
                    
                    $role_users = db_fetch_all($user_sql, $user_params);
                ?>
                <div class="card">
                    <h3 style="margin-top: 0; color: var(--primary-color); border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
                        <i class="fas fa-users"></i> Nhân viên thuộc vai trò này (<?php echo count($role_users); ?>)
                    </h3>
                    
                    <!-- Filters -->
                    <form method="GET" class="filter-section" style="margin-bottom: 20px; display: flex; gap: 10px;">
                        <input type="hidden" name="id" value="<?php echo $selected_role_id; ?>">
                        <select name="dept" class="form-control" style="max-width: 200px;" onchange="this.form.submit()">
                            <option value="0">-- Tất cả Phòng ban --</option>
                            <?php foreach($departments as $d) echo "<option value='{$d['id']}' ".($f_dept==$d['id']?'selected':'').">{$d['name']}</option>"; ?>
                        </select>
                        <select name="proj" class="form-control" style="max-width: 200px;" onchange="this.form.submit()">
                            <option value="0">-- Tất cả Dự án --</option>
                            <?php foreach($projects as $p) echo "<option value='{$p['id']}' ".($f_proj==$p['id']?'selected':'').">{$p['name']}</option>"; ?>
                        </select>
                        <?php if($f_dept || $f_proj): ?>
                            <a href="?id=<?php echo $selected_role_id; ?>" class="btn btn-secondary btn-sm" title="Xóa lọc"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>

                    <div class="table-container">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tài khoản</th>
                                    <th>Nhân viên</th>
                                    <th>Vị trí / Dự án</th>
                                    <th width="80" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($role_users)): ?>
                                    <tr><td colspan="4" class="text-center text-muted" style="padding: 30px;">Chưa có nhân viên nào được gán vai trò này hoặc không tìm thấy kết quả phù hợp.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($role_users as $u): 
                                        $display_name = !empty($u['emp_name']) ? $u['emp_name'] : $u['user_name'];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $u['username']; ?></strong><br>
                                                <small class="text-muted"><?php echo $u['email']; ?></small>
                                            </td>
                                            <td>
                                                <?php echo $display_name; ?><br>
                                                <small class="text-muted"><?php echo $u['code'] ?? 'Chưa liên kết NV'; ?></small>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.9rem;"><?php echo $u['pos_name'] ?: '---'; ?></div>
                                                <small class="text-muted"><?php echo $u['proj_name'] ?: 'Chưa gán DA'; ?></small>
                                            </td>
                                            <td class="text-center">
                                                <div style="display: flex; gap: 15px; justify-content: center; align-items: center;">
                                                    <button type="button" class="btn-icon text-warning" onclick="confirmRemoveRole(<?php echo $u['user_id']; ?>, '<?php echo addslashes($display_name); ?>')" title="Gỡ vai trò">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                    <button type="button" class="btn-icon text-danger" onclick="confirmDeleteUser(<?php echo $u['user_id']; ?>, '<?php echo addslashes($display_name); ?>')" title="Xóa tài khoản">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
                    
    <!-- Modal Thêm vai trò -->            
    <div id="addRoleModal" class="modal-overlay">
        <div class="modal-box" style="text-align: left;">
            <h3 class="modal-title">Thêm Vai trò mới</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Mã vai trò (Viết hoa, không dấu, không cách)</label>
                    <input type="text" name="name" class="form-control" placeholder="VD: HR_PAYROLL" required>
                </div>
                <div class="form-group">
                    <label>Tên hiển thị</label>
                    <input type="text" name="display_name" class="form-control" placeholder="VD: HR Phụ trách lương" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="$('#addRoleModal').fadeOut()">Hủy</button>
                    <button type="submit" name="add_role" class="btn btn-primary">Thêm ngay</button>
                </div>
            </form>
        </div>
    </div>

<style>
.role-item:hover { background: #f1f5f9; }
.role-item.active { background: #e0f2fe; border-left: 4px solid #3b82f6; }
.perm-group label span { font-size: 0.95rem; color: #334155; }
.dropdown { position: relative; display: inline-block; }
.dropdown-menu { display: none; position: absolute; right: 0; background-color: #fff; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 4px; border: 1px solid #eee; }
.dropdown:hover .dropdown-menu { display: block; }
.dropdown-item { color: black; padding: 8px 16px; text-decoration: none; display: block; font-size: 0.9rem; }
.dropdown-item:hover { background-color: #f1f5f9; }
.dropdown-divider { border-top: 1px solid #eee; margin: 4px 0; }

/* DARK MODE */
body.dark-mode .card { background-color: #1e293b; border-color: #334155; }
body.dark-mode .card div[style*="background: #f8fafc"] { background-color: #0f172a !important; border-bottom-color: #334155 !important; color: #94a3b8 !important; }
body.dark-mode .role-item { border-bottom-color: #334155 !important; }
body.dark-mode .role-item:hover { background-color: rgba(255,255,255,0.02) !important; }
body.dark-mode .role-item.active { background-color: rgba(59, 130, 246, 0.1) !important; border-left-color: #3b82f6 !important; }
body.dark-mode .role-item div[style*="color: var(--primary-dark)"] { color: #4ade80 !important; }
body.dark-mode h3[style*="color: var(--primary-color)"] { color: #4ade80 !important; }
body.dark-mode .perm-group h4 { border-bottom-color: #334155 !important; }
body.dark-mode .perm-group label span { color: #cbd5e1; }
body.dark-mode div[style*="border-top: 1px solid #eee"] { border-top-color: #334155 !important; }
body.dark-mode .modal-box { background-color: #1e293b; color: #f1f5f9; border: 1px solid #334155; }
body.dark-mode .form-control { background-color: #0f172a; border-color: #334155; color: #f1f5f9; }
body.dark-mode .dropdown-menu { background-color: #1e293b; border-color: #334155; }
body.dark-mode .dropdown-item { color: #cbd5e1; }
body.dark-mode .dropdown-item:hover { background-color: #334155; }
body.dark-mode .dropdown-divider { border-color: #334155; }
</style>

<script>
function confirmDeleteRole(roleId) {
    Modal.confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa vai trò này?<br>Tất cả nhân viên đang giữ vai trò này sẽ bị mất quyền truy cập ngay lập tức.', () => {
        document.getElementById('deleteRoleForm').submit();
    });
}

function confirmRemoveRole(userId, userName) {
    Modal.confirm(`Bạn muốn gỡ vai trò này khỏi nhân viên "<strong>${userName}</strong>"?`, () => {
        window.location.href = `roles.php?id=<?php echo $selected_role_id; ?>&action=remove_role&user_id=${userId}&role_id=<?php echo $selected_role_id; ?>`;
    });
}

function confirmDeleteUser(userId, userName) {
    Modal.confirm(`CẢNH BÁO NGUY HIỂM:<br>Bạn sắp xóa hoàn toàn tài khoản đăng nhập của "<strong>${userName}</strong>".<br>Hành động này không thể hoàn tác!`, () => {
        window.location.href = `roles.php?id=<?php echo $selected_role_id; ?>&action=delete_user&user_id=${userId}&role_id=<?php echo $selected_role_id; ?>`;
    });
}
</script>

<?php include '../../../includes/footer.php'; ?>
