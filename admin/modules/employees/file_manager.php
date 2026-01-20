<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Only Admin or HR
if (!is_hr_staff()) {
    echo "<div class='main-content'><div class='content-wrapper'><h3>Bạn không có quyền truy cập trang này.</h3></div></div>";
    include '../includes/footer.php';
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_path = $_GET['delete']; // Relative to root_dir
    $full_path = $root_dir . $delete_path;
    
    // Security Check
    if (strpos($delete_path, '..') === false && file_exists($full_path)) {
        if (is_dir($full_path)) {
            // Check if empty
            $files_in_dir = scandir($full_path);
            if (count($files_in_dir) <= 2) { // . and ..
                if (rmdir($full_path)) {
                    set_toast('success', 'Đã xóa thư mục!');
                } else {
                    set_toast('error', 'Không thể xóa thư mục (Lỗi hệ thống)!');
                }
            } else {
                set_toast('error', 'Thư mục không rỗng! Vui lòng xóa các file bên trong trước.');
            }
        } else {
            if (unlink($full_path)) {
                set_toast('success', 'Đã xóa file!');
            } else {
                set_toast('error', 'Không thể xóa file (Lỗi hệ thống)!');
            }
        }
    } else {
        set_toast('error', 'Đường dẫn không hợp lệ hoặc file không tồn tại!');
    }
    
    // Return to current dir logic
    $parent_of_deleted = dirname($delete_path);
    if ($parent_of_deleted == '.') $parent_of_deleted = '';
    
    // If we deleted a file inside a folder, stay in that folder.
    // If we deleted the folder itself, go up.
    // Logic: The 'delete' param is the item being deleted. So we should go to its parent dir OR the current dir from GET.
    // Better: Stick to $_GET['dir'] if provided, unless we deleted the directory we are currently viewing (rare case).
    redirect("file_manager.php?dir=" . urlencode(isset($_GET['dir']) ? $_GET['dir'] : $parent_of_deleted));
}

$root_dir = "../../../upload/documents/";
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$search_kw = isset($_GET['kw']) ? $_GET['kw'] : '';

// Security Check (Prevent path traversal)
if (strpos($current_dir, '..') !== false) $current_dir = '';

$scan_path = $root_dir . $current_dir;
$files = [];
$dirs = [];

if (is_dir($scan_path)) {
    $items = scandir($scan_path);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        // Filter by keyword
        if ($search_kw && stripos($item, $search_kw) === false) continue;

        $full_path = $scan_path . '/' . $item;
        if (is_dir($full_path)) {
            $dirs[] = $item;
        } else {
            $files[] = $item;
        }
    }
}
?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info">
            <span><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
            <div class="user-avatar">A</div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Trình quản lý File Server</h1>
            <div class="header-actions" style="display: flex; gap: 10px;">
                <form method="GET" style="display: flex; gap: 5px;">
                    <?php if ($current_dir): ?><input type="hidden" name="dir" value="<?php echo htmlspecialchars($current_dir); ?>"><?php endif; ?>
                    <input type="text" name="kw" class="form-control" placeholder="Tìm kiếm file/thư mục..." value="<?php echo htmlspecialchars($search_kw); ?>" style="width: 200px; padding: 6px 10px;">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    <?php if($search_kw): ?>
                        <a href="?dir=<?php echo urlencode($current_dir); ?>" class="btn btn-secondary btn-sm" title="Xóa tìm kiếm"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
                <?php if ($current_dir): ?>
                    <a href="file_manager.php" class="btn btn-secondary btn-sm"><i class="fas fa-home"></i> Gốc</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-folder-open text-warning"></i>
                <span style="color: #64748b; font-weight: 500;">upload/documents/<?php echo $current_dir; ?></span>
            </div>

            <div class="table-container" style="border: none; border-radius: 0;">
                <table class="table">
                    <thead style="background: #fff;">
                        <tr>
                            <th width="60" style="text-align:center;"></th>
                            <th>Tên tệp tin / Thư mục</th>
                            <th width="150" style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($current_dir): 
                            $parent = dirname($current_dir);
                            if ($parent == '.') $parent = '';
                        ?>
                            <tr>
                                <td style="text-align:center;"><i class="fas fa-level-up-alt" style="color: #94a3b8;"></i></td>
                                <td colspan="2"><a href="?dir=<?php echo urlencode($parent); ?>" style="color: #64748b; font-weight: 500;">... (Quay lại)</a></td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($dirs as $d): ?>
                            <tr>
                                <td style="text-align:center;"><i class="fas fa-folder" style="color: #f59e0b; font-size: 1.2rem;"></i></td>
                                <td>
                                    <a href="?dir=<?php echo urlencode(($current_dir ? $current_dir . '/' : '') . $d); ?>" style="font-weight: 600; color: var(--text-main); display: block;">
                                        <?php echo $d; ?>
                                    </a>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <a href="?dir=<?php echo urlencode(($current_dir ? $current_dir . '/' : '') . $d); ?>" class="btn btn-sm btn-primary" style="padding: 4px 12px; font-size: 0.75rem;">Mở</a>
                                        <a href="javascript:void(0)" onclick="confirmDeleteFile('<?php echo htmlspecialchars(($current_dir ? $current_dir . '/' : '') . $d); ?>', 'dir')" class="btn btn-sm btn-danger" style="padding: 4px 8px; font-size: 0.75rem;"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php foreach ($files as $f): ?>
                            <tr>
                                <td style="text-align:center;"><i class="fas fa-file-alt" style="color: #3b82f6; font-size: 1.2rem;"></i></td>
                                <td>
                                    <div style="font-weight: 500; color: #334155;"><?php echo $f; ?></div>
                                    <small style="color: #94a3b8;"><?php echo round(filesize($scan_path . '/' . $f) / 1024, 1); ?> KB</small>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <a href="/khaservice-hr/upload/documents/<?php echo ($current_dir ? $current_dir . '/' : '') . $f; ?>" target="_blank" class="btn btn-sm btn-secondary" style="padding: 4px 12px; font-size: 0.75rem;">
                                            <i class="fas fa-download"></i> Tải
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmDeleteFile('<?php echo htmlspecialchars(($current_dir ? $current_dir . '/' : '') . $f); ?>', 'file')" class="btn btn-sm btn-danger" style="padding: 4px 8px; font-size: 0.75rem;"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($dirs) && empty($files)): ?>
                            <tr><td colspan="3" style="text-align:center; padding: 40px; color: #94a3b8; font-style: italic;">Không tìm thấy dữ liệu phù hợp</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
function confirmDeleteFile(path, type) {
    let msg = type === 'dir' 
        ? 'Bạn có chắc chắn muốn xóa thư mục này? Thư mục phải rỗng mới xóa được.' 
        : 'Bạn có chắc chắn muốn xóa file này vĩnh viễn?';
        
    Modal.confirm(msg, () => {
        location.href = '?dir=<?php echo urlencode($current_dir); ?>&delete=' + path;
    });
}
</script>

<style>
    .table td { padding: 12px 20px; }
    .table tr:hover { background-color: #f8fafc; }
    .btn-sm { border-radius: 4px; }
</style>

<?php include '../../../includes/footer.php'; ?>
