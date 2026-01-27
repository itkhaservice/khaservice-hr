<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE attendance");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " | " . $col['Type'] . "\n";
}
?>