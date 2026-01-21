<?php
require_once 'config/db.php';

echo "Cleaning up duplicate attendance records...\n";

// 1. Identify duplicates and keep the one with MAX(id)
// We can't use simple DELETE with JOIN in all MySQL versions easily for this logic without temp table, 
// but we can select IDs to keep.

$sql = "
    DELETE t1 FROM attendance t1
    INNER JOIN attendance t2 
    WHERE 
        t1.id < t2.id AND 
        t1.employee_id = t2.employee_id AND 
        t1.date = t2.date
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "Deleted " . $stmt->rowCount() . " duplicate records.\n";
} catch (Exception $e) {
    echo "Error deleting duplicates: " . $e->getMessage() . "\n";
}

// 2. Add Unique Index
echo "Adding UNIQUE index on (employee_id, date)...";
try {
    db_query("ALTER TABLE attendance ADD UNIQUE KEY `unique_emp_date` (`employee_id`, `date`)");
    echo "Index added successfully.\n";
} catch (Exception $e) {
    echo "Error adding index (might already exist or duplicates remain): " . $e->getMessage() . "\n";
}
?>
