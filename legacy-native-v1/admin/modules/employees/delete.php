<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if ($employee) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // 1. Delete documents and their physical files
        $docs = db_fetch_all("SELECT file_path FROM documents WHERE employee_id = ?", [$id]);
        foreach ($docs as $doc) {
            if ($doc['file_path'] && file_exists("../../../" . $doc['file_path'])) {
                unlink("../../../" . $doc['file_path']);
            }
        }
        db_query("DELETE FROM documents WHERE employee_id = ?", [$id]);

        // 2. Delete avatar physical file
        if ($employee['avatar'] && file_exists("../../../" . $employee['avatar'])) {
            unlink("../../../" . $employee['avatar']);
        }

        // 3. Delete contracts (optional, if you want to keep history you might just soft delete)
        db_query("DELETE FROM contracts WHERE employee_id = ?", [$id]);

        // 4. Delete employee
        db_query("DELETE FROM employees WHERE id = ?", [$id]);

        $pdo->commit();
        set_toast('success', 'Đã xóa nhân viên và toàn bộ dữ liệu liên quan thành công!');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_toast('error', 'Lỗi khi xóa nhân viên: ' . $e->getMessage());
    }
} else {
    set_toast('error', 'Không tìm thấy nhân viên!');
}

redirect('index.php');
?>