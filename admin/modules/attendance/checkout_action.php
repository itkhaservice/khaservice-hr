<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $now = date('Y-m-d H:i:s');
    // We could add logic here to check if they are early or late based on shift
    db_query("UPDATE attendance SET check_out = ?, status = 'completed' WHERE id = ?", [$now, $id]);
}

redirect('index.php');
?>
