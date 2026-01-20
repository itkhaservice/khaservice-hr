<?php
require_once 'config/db.php';
echo "--- NHAN VIEN DU AN 37 ---
";
$emps = db_fetch_all("SELECT id, fullname, status FROM employees WHERE current_project_id = 37");
print_r($emps);

foreach($emps as $e) {
    echo "\n--- HO SO CUA: " . $e['fullname'] . " ---
";
    $docs = db_fetch_all("SELECT doc_type, is_submitted FROM documents WHERE employee_id = " . $e['id']);
    print_r($docs);
}

echo "\n--- DANH MUC BAT BUOC ---
";
$req = db_fetch_all("SELECT code FROM document_settings WHERE is_required = 1");
print_r($req);
?>
