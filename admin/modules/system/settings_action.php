<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

$action = $data['action'];

try {
    switch ($action) {
        case 'save_company':
        case 'save_salary':
        case 'save_attendance':
            if (isset($data['settings']) && is_array($data['settings'])) {
                // Special handling for checkboxes in attendance settings (array to string)
                if ($action === 'save_attendance' && isset($data['settings']['attendance_weekly_off']) && is_array($data['settings']['attendance_weekly_off'])) {
                    $data['settings']['attendance_weekly_off'] = implode(',', $data['settings']['attendance_weekly_off']);
                } elseif ($action === 'save_attendance' && !isset($data['settings']['attendance_weekly_off'])) {
                    // If checkbox unchecked, it might not be sent, default to empty or just 0 (Sunday implied)
                    $data['settings']['attendance_weekly_off'] = ''; 
                }

                foreach ($data['settings'] as $key => $value) {
                    $key = clean_input($key);
                    $value = clean_input($value);
                    // Check if exists
                    $exists = db_fetch_row("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($exists) {
                        db_query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
                    } else {
                        db_query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
                    }
                }
                echo json_encode(['status' => 'success']);
            }
            break;

        case 'save_dept':
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            $name = clean_input($data['name']);
            $code = clean_input($data['code']);
            $stt = isset($data['stt']) ? (int)$data['stt'] : 99;

            if ($id > 0) {
                db_query("UPDATE departments SET name = ?, code = ?, stt = ? WHERE id = ?", [$name, $code, $stt, $id]);
            } else {
                db_query("INSERT INTO departments (name, code, stt) VALUES (?, ?, ?)", [$name, $code, $stt]);
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_dept':
            $id = (int)$data['id'];
            db_query("DELETE FROM departments WHERE id = ?", [$id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'save_pos':
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            $dept_id = (int)$data['department_id'];
            $name = clean_input($data['name']);
            $code = clean_input($data['code']);
            $stt = isset($data['stt']) ? (int)$data['stt'] : 99;

            if ($id > 0) {
                db_query("UPDATE positions SET department_id = ?, name = ?, code = ?, stt = ? WHERE id = ?", [$dept_id, $name, $code, $stt, $id]);
            } else {
                db_query("INSERT INTO positions (department_id, name, code, stt) VALUES (?, ?, ?, ?)", [$dept_id, $name, $code, $stt]);
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_pos':
            $id = (int)$data['id'];
            db_query("DELETE FROM positions WHERE id = ?", [$id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'save_doc':
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            $name = clean_input($data['name']);
            $code = clean_input($data['code']);
            $is_required = (int)$data['is_required'];
            $is_multiple = (int)$data['is_multiple'];

            if ($id > 0) {
                db_query("UPDATE document_settings SET name = ?, code = ?, is_required = ?, is_multiple = ? WHERE id = ?", [$name, $code, $is_required, $is_multiple, $id]);
            } else {
                db_query("INSERT INTO document_settings (name, code, is_required, is_multiple) VALUES (?, ?, ?, ?)", [$name, $code, $is_required, $is_multiple]);
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_doc':
            $id = (int)$data['id'];
            db_query("DELETE FROM document_settings WHERE id = ?", [$id]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Hành động không xác định']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
