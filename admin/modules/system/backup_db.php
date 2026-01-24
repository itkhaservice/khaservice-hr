<?php
// STREAMING BACKUP DATABASE SCRIPT (Optimized for Free Hosting)
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Check Permission
require_permission('manage_system');

// Disable buffering
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// Configuration
$db_name = DB_NAME;
$filename = $db_name . "_backup_" . date("Y-m-d_H-i-s") . ".sql";

// Headers
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");

// 1. Get Tables
$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

echo "-- KHASERVICE HR BACKUP\n";
echo "-- Date: " . date("Y-m-d H:i:s") . "\n\n";

// 2. Process (One by One)
foreach ($tables as $table) {
    echo "-- Table: $table --\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    
    // Structure
    $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
    echo $row2[1] . ";\n\n";

    // Data (Buffered Fetch)
    $result = mysqli_query($conn, "SELECT * FROM `$table`", MYSQLI_USE_RESULT); // Unbuffered query
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            echo "INSERT INTO `$table` VALUES(";
            $first = true;
            foreach ($row as $val) {
                if (!$first) echo ",";
                if ($val === null) echo "NULL";
                else echo "'" . mysqli_real_escape_string($conn, $val) . "'";
                $first = false;
            }
            echo ");\n";
            flush(); // Send to browser immediately
        }
        mysqli_free_result($result);
    }
    echo "\n\n";
    flush();
}

exit;
?>
