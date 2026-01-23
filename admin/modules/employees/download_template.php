<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Check Auth
if (!isset($_SESSION['user_id'])) die("Access denied");

// 1. Fetch Data
$departments = db_fetch_all("SELECT code, name FROM departments ORDER BY code ASC");

// Updated: Get Positions WITH Department info to group them
$positions = db_fetch_all("
    SELECT p.code, p.name, d.name as dept_name, d.code as dept_code 
    FROM positions p 
    LEFT JOIN departments d ON p.department_id = d.id 
    ORDER BY d.code ASC, p.name ASC
");

$projects = db_fetch_all("SELECT code, name FROM projects ORDER BY code ASC");

$data = [
    'departments' => $departments,
    'positions' => $positions,
    'projects' => $projects
];

// 2. Save to Temp JSON
$temp_dir = '../../../upload/temp';
if (!file_exists($temp_dir)) mkdir($temp_dir, 0777, true);

$json_file = $temp_dir . '/template_data_' . time() . '.json';
$excel_file = $temp_dir . '/Template_Nhap_Nhan_Vien_' . date('Ymd') . '.xlsx';

file_put_contents($json_file, json_encode($data));

// 3. Call Python
$python_script = __DIR__ . '/generate_template.py';
if (!file_exists($python_script)) die("System Error: Script not found.");

// Escape paths for Windows
$cmd_json = str_replace('/', DIRECTORY_SEPARATOR, realpath($json_file));
// Output path might not exist yet, so construct absolute path manually or use relative
$cmd_excel = str_replace('/', DIRECTORY_SEPARATOR, realpath($temp_dir) . DIRECTORY_SEPARATOR . basename($excel_file));

$cmd = "python \"$python_script\" \"$cmd_json\" \"$cmd_excel\"";
putenv("PYTHONIOENCODING=utf-8");
$output = shell_exec($cmd);

// Cleanup JSON
unlink($json_file);

$res = json_decode($output, true);

if (isset($res['status']) && $res['status'] == 'success' && file_exists($excel_file)) {
    // 4. Download
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.basename($excel_file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($excel_file));
    readfile($excel_file);
    
    // Cleanup Excel after download (optional, usually good to keep for a moment or use job to clean)
    // But PHP script ends after readfile, so we can unlink.
    unlink($excel_file);
    exit;
} else {
    echo "Lỗi tạo file mẫu: " . ($res['message'] ?? $output);
}
?>
