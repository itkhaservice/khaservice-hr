<?php
require_once 'config/db.php';
function show_cols($table) {
    global $pdo;
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "\nTable: $table\n";
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . " | " . $row['Type'] . "\n";
        }
    } catch (Exception $e) { echo "\nTable $table not found.\n"; }
}
show_cols('project_positions');
show_cols('shifts');
show_cols('project_shifts');
?>
