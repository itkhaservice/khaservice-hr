<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Check permissions
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access Denied");
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'structure'; // structure or missing

// Set Headers to force download
$filename = "BaoCao_" . date('Y-m-d_H-i') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Security: Allowed Projects
$allowed_projs = get_allowed_projects();

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM (Byte Order Mark) to fix UTF-8 display in Excel
fputs($output, "\xEF\xBB\xBF");

// Base WHERE Clause
$where_clause = "e.status = 'working'";
$params = [];

// Apply Permissions
if ($allowed_projs !== 'ALL') {
    if ($project_id) {
        if (!in_array($project_id, $allowed_projs)) {
            die("Access Denied to this Project");
        }
        $where_clause .= " AND e.current_project_id = ?";
        $params[] = $project_id;
    } else {
        if (empty($allowed_projs)) {
            $where_clause .= " AND 1=0";
        } else {
            $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
            $where_clause .= " AND e.current_project_id IN ($in_placeholder)";
            $params = array_merge($params, $allowed_projs);
        }
    }
} elseif ($project_id) {
    $where_clause .= " AND e.current_project_id = ?";
    $params[] = $project_id;
}

// Logic
if ($type == 'structure') {

    $sql = "
        SELECT 
            p.name as project_name,
            d.name as dept_name,
            e.position,
            COUNT(e.id) as emp_count
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN projects p ON e.current_project_id = p.id
        WHERE $where_clause
        GROUP BY p.name, d.name, e.position
        ORDER BY p.name ASC, d.name ASC, e.position ASC
    ";

    $data = db_fetch_all($sql, $params);

    $stt = 1;
    foreach ($data as $row) {
        fputcsv($output, [
            $stt++,
            $row['project_name'] ?? 'Chưa phân loại',
            $row['dept_name'] ?? 'Chưa phân loại',
            $row['position'] ?? 'Chưa có chức vụ',
            $row['emp_count']
        ]);
    }

} elseif ($type == 'missing') {
    // Header Row
    fputcsv($output, ['STT', 'Mã NV', 'Họ và tên', 'Dự án', 'Số loại thiếu', 'Chi tiết giấy tờ thiếu']);

    // Dynamic Required Documents from DB
    $required_docs_db = db_fetch_all("SELECT code, name FROM document_settings WHERE is_required = 1");
    $mandatory_docs = [];
    $doc_names = [];
    
    foreach ($required_docs_db as $rd) {
        $mandatory_docs[] = $rd['code'];
        $doc_names[$rd['code']] = $rd['name'];
    }

    $sql = "
        SELECT e.code, e.fullname, p.name as proj_name,
        (SELECT GROUP_CONCAT(doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1) as submitted_types
        FROM employees e
        LEFT JOIN projects p ON e.current_project_id = p.id
        WHERE $where_clause
    ";

    $data = db_fetch_all($sql, $params);
    
    $stt = 1;
    foreach ($data as $row) {
        $submitted = $row['submitted_types'] ? explode(',', $row['submitted_types']) : [];
        $missing = array_diff($mandatory_docs, $submitted);
        
        if (!empty($missing)) {
            // Convert codes to full names
            $missing_full = array_map(function($code) use ($doc_names) {
                return isset($doc_names[$code]) ? $doc_names[$code] : $code;
            }, $missing);

            fputcsv($output, [
                $stt++,
                $row['code'],
                $row['fullname'],
                $row['proj_name'],
                count($missing),
                implode(', ', $missing_full) // Export full names
            ]);
        }
    }
}

fclose($output);
exit;
?>