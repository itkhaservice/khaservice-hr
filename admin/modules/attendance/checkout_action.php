<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Security: Check Permissions
    $att = db_fetch_row("SELECT project_id FROM attendance WHERE id = ?", [$id]);
    
    if ($att) {
        $allowed_projs = get_allowed_projects();
        if ($allowed_projs !== 'ALL' && !in_array($att['project_id'], $allowed_projs)) {
            die("Access Denied: You cannot checkout employees from other projects.");
        }

        $now = date('Y-m-d H:i:s');
        // We could add logic here to check if they are early or late based on shift
        db_query("UPDATE attendance SET check_out = ?, status = 'completed' WHERE id = ?", [$now, $id]);
    }
}

redirect('index.php');
?>
