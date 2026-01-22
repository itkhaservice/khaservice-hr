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
}

$roles = db_fetch_all("SELECT * FROM roles ORDER BY id ASC");
$permissions = db_fetch_all("SELECT * FROM permissions ORDER BY module ASC, name ASC");

// Nhóm quyền theo Module để hiển thị cho đẹp
$grouped_perms = [];
foreach ($permissions as $p) {
    $grouped_perms[$p['module']][] = $p;
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Quản lý Phân quyền HR</h1>
            <button class="btn btn-primary" onclick="$('#addRoleModal').fadeIn()"><i class="fas fa-plus"></i> Thêm Vai trò mới</button>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 25px;">
            <!-- Danh sách vai trò -->
            <div class="card" style="padding: 0;">
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
            <div class="card">
                <?php 
                $selected_role_id = isset($_GET['id']) ? (int)$_GET['id'] : ($roles[0]['id'] ?? 0);
                if ($selected_role_id): 
                    $selected_role = db_fetch_row("SELECT * FROM roles WHERE id = ?", [$selected_role_id]);
                    $active_perms = array_column(db_fetch_all("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$selected_role_id]), 'permission_id');
                ?>
                    <h3 style="margin-top: 0; color: var(--primary-color);">Gán quyền cho: <?php echo $selected_role['display_name']; ?></h3>
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 25px;">Chọn các chức năng mà vai trò này được phép truy cập và thao tác.</p>

                    <form method="POST">
                        <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                            <?php foreach ($grouped_perms as $module => $perms): ?>
                                <div class="perm-group" style="margin-bottom: 20px;">
                                    <h4 style="text-transform: uppercase; font-size: 0.8rem; color: #94a3b8; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px;">
                                        Module: <?php echo $module; ?>
                                    </h4>
                                    <?php foreach ($perms as $p): ?>
                                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
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
                            <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                                <button type="submit" name="update_perms" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật quyền hạn</button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Vai trò ADMIN mặc định có toàn bộ quyền trong hệ thống.</div>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; color: #94a3b8;">
                        <i class="fas fa-user-shield" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>Vui lòng chọn một Vai trò để thiết lập quyền hạn.</p>
                    </div>
                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Thêm vai trò -->            
            <!-- Modal Thêm vai trò --><div id="addRoleModal" class="modal-overlay">
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
</style>

<?php include '../../../includes/footer.php'; ?>
