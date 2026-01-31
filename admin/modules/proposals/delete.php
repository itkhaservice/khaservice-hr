<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if (!isset($_SESSION['user_id'])) redirect(BASE_URL . 'admin/login.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$proposal = db_fetch_row("SELECT * FROM material_proposals WHERE id = ?", [$id]);

if (!$proposal) {
    set_toast('error', 'Không tìm thấy phiếu đề xuất.');
    redirect('index.php');
}

// Logic phân quyền xóa
$can_delete = false;

if (has_permission('manage_system')) {
    // Admin quyền lực tối cao
    $can_delete = true;
} else {
    // Nhân viên: Chỉ xóa của chính mình VÀ trạng thái là draft/cancelled
    if ($proposal['created_by'] == $_SESSION['user_id'] && in_array($proposal['status'], ['draft', 'cancelled'])) {
        $can_delete = true;
    }
}

if ($can_delete) {
    db_query("DELETE FROM material_proposals WHERE id = ?", [$id]);
    // Các items sẽ tự xóa nhờ ON DELETE CASCADE trong DB
    set_toast('success', 'Đã xóa phiếu đề xuất.');
} else {
    set_toast('error', 'Bạn không có quyền xóa phiếu này (chỉ xóa được phiếu Nháp hoặc Đã hủy).');
}

redirect('index.php');
?>
