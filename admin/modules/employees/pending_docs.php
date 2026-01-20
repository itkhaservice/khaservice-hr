<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Only Admin or HR
if (!is_hr_staff()) {
    echo "<div class='main-content'><div class='content-wrapper'><h3>Bạn không có quyền truy cập trang này.</h3></div></div>";
    include '../../../includes/footer.php';
    exit;
}

// Handle Approval Actions
if (isset($_GET['action']) && isset($_GET['doc_id'])) {
    $doc_id = (int)$_GET['doc_id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        db_query("UPDATE documents SET approval_status = 'approved' WHERE id = ?", [$doc_id]);
        set_toast('success', 'Đã duyệt hồ sơ thành công!');
    } elseif ($action == 'reject') {
        // Delete file and record
        $doc = db_fetch_row("SELECT file_path FROM documents WHERE id = ?", [$doc_id]);
        if ($doc && file_exists("../../../" . $doc['file_path'])) {
            unlink("../../../" . $doc['file_path']);
        }
        db_query("DELETE FROM documents WHERE id = ?", [$doc_id]);
        set_toast('success', 'Đã từ chối và xóa hồ sơ!');
    }
    
    redirect('pending_docs.php');
}

// Fetch all pending documents
$sql = "SELECT 
            d.id, d.file_path, d.created_at, d.doc_type, d.employee_id,
            e.fullname, e.code as emp_code, e.identity_card,
            s.name as doc_name
        FROM documents d
        LEFT JOIN employees e ON d.employee_id = e.id
        LEFT JOIN document_settings s ON d.doc_type = s.code
        WHERE d.approval_status = 'pending'
        ORDER BY d.created_at ASC";

$pending_docs = db_fetch_all($sql);

?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info">
            <span><?php echo $_SESSION['user_name']; ?></span>
            <div class="user-avatar">A</div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Duyệt hồ sơ nhân sự</h1>
            <div class="header-actions">
                <span class="badge badge-info">Tổng cộng: <?php echo count($pending_docs); ?> hồ sơ chờ xử lý</span>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Loại hồ sơ</th>
                            <th>Thời gian nộp</th>
                            <th>Xem file</th>
                            <th width="200" style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_docs)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color: #94a3b8;">Hiện không có hồ sơ nào đang chờ duyệt.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_docs as $pd): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $pd['fullname']; ?></strong><br>
                                        <small class="text-sub"><?php echo $pd['emp_code']; ?> - CCCD: <?php echo $pd['identity_card']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?php echo $pd['doc_name']; ?></span>
                                    </td>
                                    <td><?php echo date('H:i d/m/Y', strtotime($pd['created_at'])); ?></td>
                                    <td>
                                        <a href="/khaservice-hr/<?php echo $pd['file_path']; ?>" target="_blank" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i> Xem file
                                        </a>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <a href="?action=approve&doc_id=<?php echo $pd['id']; ?>" class="btn btn-primary btn-sm" style="background: #24a25c;">
                                                <i class="fas fa-check"></i> Duyệt
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmReject(<?php echo $pd['id']; ?>)" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Từ chối
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../../../includes/footer.php'; ?>

<script>
function confirmReject(id) {
    Modal.confirm('Bạn có chắc chắn muốn từ chối hồ sơ này không? Người lao động sẽ thấy trạng thái bị từ chối và cần nộp lại.', () => {
        location.href = '?action=reject&doc_id=' + id;
    });
}
</script>

<style>
.text-sub { color: #94a3b8; }
.btn-sm { padding: 6px 12px; font-size: 0.8rem; }
</style>
