<?php
require_once 'config/db.php';
try {
    $indices = db_fetch_all("SHOW INDEX FROM attendance");
    foreach ($indices as $idx) {
        echo "Key: " . $idx['Key_name'] . " | Col: " . $idx['Column_name'] . " | Unique: " . ($idx['Non_unique'] == 0 ? 'Yes' : 'No') . "\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
?>