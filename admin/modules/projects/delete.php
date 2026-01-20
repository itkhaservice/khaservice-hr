<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Check if there are employees or attendance records linked to this project
    $check_emp = db_fetch_row("SELECT COUNT(*) as count FROM employees WHERE current_project_id = ?", [$id]);
    
    if ($check_emp['count'] > 0) {
        echo "<script>alert('Không thể xóa dự án đang có nhân viên tham gia!'); window.history.back();</script>";
        exit;
    }

    // Delete related shifts first
    db_query("DELETE FROM shifts WHERE project_id = ?", [$id]);
    
    // Delete project
    db_query("DELETE FROM projects WHERE id = ?", [$id]);
}

redirect('index.php');
?>
