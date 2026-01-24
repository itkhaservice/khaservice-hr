<?php
/**
 * API SERVER: HYBRID STORAGE SYNC
 * Xử lý đồng bộ file giữa Hosting và Local Node
 */
require_once '../config/db.php';

// Cấu hình
$API_SECRET_MASTER = "KHA_SERVICE_FILE_SYNC_MASTER_KEY_2026"; // Key để đăng ký Node mới
ini_set('memory_limit', '256M');
header('Content-Type: application/json');

// Helper Response
function json_response($status, $message, $data = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

// 1. REGISTER NODE (Đăng ký máy trạm mới)
// Client gửi: action=register, name=PC_VANPHONG, master_key=...
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $master_key = $_POST['master_key'] ?? '';
    if ($master_key !== $API_SECRET_MASTER) json_response('error', 'Invalid Master Key');

    $name = clean_input($_POST['name'] ?? 'Unknown_Node');
    $node_key = md5($name . time() . rand()); // Tạo ID định danh
    $auth_token = hash('sha256', $node_key . $API_SECRET_MASTER); // Token dùng cho các request sau

    // Lưu vào DB
    try {
        db_query("INSERT INTO storage_nodes (node_name, node_key, auth_token, status) VALUES (?, ?, ?, 'online')", [$name, $node_key, $auth_token]);
        json_response('success', 'Node registered', ['node_key' => $node_key, 'auth_token' => $auth_token]);
    } catch (Exception $e) {
        json_response('error', 'Cannot register node: ' . $e->getMessage());
    }
}

// --- CÁC REQUEST SAU CẦN AUTH TOKEN ---
$headers = getallheaders();
$auth_token = $headers['X-Node-Token'] ?? $_POST['auth_token'] ?? '';

if (!$auth_token) json_response('error', 'Missing Auth Token');

// Xác thực Node
$node = db_fetch_row("SELECT * FROM storage_nodes WHERE auth_token = ?", [$auth_token]);
if (!$node) json_response('error', 'Invalid Auth Token');

// Cập nhật Heartbeat
db_query("UPDATE storage_nodes SET last_heartbeat = NOW(), status = 'online', ip_address = ? WHERE id = ?", [$_SERVER['REMOTE_ADDR'], $node['id']]);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // 2.1 GET PENDING COUNT
    case 'count_pending':
        $count = db_fetch_row("
            SELECT COUNT(*) as total
            FROM documents 
            WHERE storage_status IN ('online', 'synced')
            AND (stored_nodes_json IS NULL OR JSON_SEARCH(stored_nodes_json, 'one', ?) IS NULL)
        ", [$node['id']]);
        
        json_response('success', 'Count', ['total' => (int)$count['total']]);
        break;

    // 2. GET PENDING FILES (Lấy danh sách file cần tải)
    case 'get_pending':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        // Logic: Lấy file có status 'online' hoặc 'synced'
        // VÀ Node hiện tại CHƯA nằm trong danh sách stored_nodes_json
        // Lưu ý: JSON_CONTAINS hoặc LIKE đều được, dùng LIKE cho tương thích MySQL cũ
        // stored_nodes_json lưu mảng ID của các node: [1, 5, ...]
        
        $node_id_str = '"' . $node['id'] . '"'; // Tìm chuỗi "1" trong JSON (nếu lưu string) hoặc số 1
        
        // MySQL 5.7+ hỗ trợ JSON tốt hơn, ở đây dùng text search đơn giản cho an toàn
        // Cần đảm bảo stored_nodes_json là mảng JSON hợp lệ
        
        $files = db_fetch_all("
            SELECT id, file_path, file_hash 
            FROM documents 
            WHERE storage_status IN ('online', 'synced')
            AND (stored_nodes_json IS NULL OR JSON_SEARCH(stored_nodes_json, 'one', ?) IS NULL)
            ORDER BY id DESC
            LIMIT ?
        ", [$node['id'], $limit]); // JSON_SEARCH trả về path nếu tìm thấy, NULL nếu không

        // Trả về full URL để download
        $base_upload_url = "http://" . $_SERVER['HTTP_HOST'] . "/khaservice-hr/"; // Cần config chuẩn
        if (defined('BASE_URL_FULL')) $base_upload_url = BASE_URL_FULL;

        foreach ($files as &$f) {
            $f['url'] = $base_upload_url . $f['file_path'];
        }

        json_response('success', 'Pending files', ['files' => $files]);
        break;

    // 3. CONFIRM SYNC (Xác nhận đã tải xong)
    case 'confirm':
        $file_id = (int)$_POST['file_id'];
        $file_hash = $_POST['file_hash'] ?? ''; // Hash do Client tính sau khi tải về

        $doc = db_fetch_row("SELECT * FROM documents WHERE id = ?", [$file_id]);
        if (!$doc) json_response('error', 'File not found');

        // Verify Hash (Nếu server đã có hash thì so sánh, chưa có thì cập nhật)
        if (!empty($doc['file_hash']) && $doc['file_hash'] !== $file_hash) {
            // Hash mismatch -> File trên host có thể bị lỗi hoặc bị sửa đổi
            // Client nên tải lại hoặc báo lỗi
            json_response('error', 'Hash mismatch');
        }

        // Cập nhật Hash nếu chưa có
        if (empty($doc['file_hash'])) {
            db_query("UPDATE documents SET file_hash = ? WHERE id = ?", [$file_hash, $file_id]);
        }

        // Cập nhật danh sách Node đã lưu
        $nodes = json_decode($doc['stored_nodes_json'] ?? '[]', true);
        if (!is_array($nodes)) $nodes = [];
        
        if (!in_array($node['id'], $nodes)) {
            $nodes[] = $node['id']; // Lưu ID của node
            
            // Cập nhật DB
            $new_json = json_encode($nodes);
            $new_status = 'synced'; // Đã có ít nhất 1 node lưu -> Synced
            
            db_query("UPDATE documents SET stored_nodes_json = ?, storage_status = ?, synced_at = NOW() WHERE id = ?", [$new_json, $new_status, $file_id]);
        }

        json_response('success', 'Confirmed');
        break;

    default:
        json_response('error', 'Unknown action');
}
?>