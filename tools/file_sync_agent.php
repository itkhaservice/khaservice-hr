<?php
// TOOL ĐỒNG BỘ FILE (HYBRID STORAGE AGENT)
// Chạy trên máy Local

// Cấu hình
$HOSTING_API_URL = "http://khaservice.free.nf/api/sync_files_server.php";
$MASTER_KEY = "KHA_SERVICE_FILE_SYNC_MASTER_KEY_2026";
$LOCAL_STORAGE_PATH = __DIR__ . '/../local_storage/'; // Thư mục lưu file trên máy này

// Config & Setup
$_SERVER['SERVER_NAME'] = 'localhost';
require_once '../config/db.php';
require_once '../includes/functions.php'; // For permissions

$CONFIG_FILE = 'agent_config.json';

// Tăng thời gian chạy
set_time_limit(0);

// --- FUNCTIONS ---
function call_api($action, $data = [], $token = '') {
    global $HOSTING_API_URL;
    $url = $HOSTING_API_URL . "?action=" . $action;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [];
    if ($token) $headers[] = "X-Node-Token: $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Giả lập trình duyệt để tránh bị chặn bởi Hosting miễn phí
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return ['status' => 'error', 'message' => 'CURL Error: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    if (!$decoded) {
        return ['status' => 'error', 'message' => 'Invalid JSON from Server. Raw response: ' . substr($response, 0, 100)];
    }
    
    return $decoded;
}

function load_config() {
    global $CONFIG_FILE;
    if (file_exists($CONFIG_FILE)) {
        return json_decode(file_get_contents($CONFIG_FILE), true);
    }
    return null;
}

function save_config($data) {
    global $CONFIG_FILE;
    file_put_contents($CONFIG_FILE, json_encode($data));
}

// --- LOGIC CHÍNH ---
$config = load_config();
$message = '';

// 1. ĐĂNG KÝ NODE
if (isset($_POST['save_manual'])) {
    $config = [
        'node_name' => $_POST['node_name'],
        'node_key' => 'manual_' . time(), // Fake key for manual setup
        'auth_token' => $_POST['auth_token']
    ];
    save_config($config);
    echo "Saved";
    exit;
}

if (isset($_POST['register'])) {
    $name = trim($_POST['node_name']);
    
    // Auto-fill Master Key if Admin, otherwise require input
    $key_to_use = '';
    if (is_admin()) {
        $key_to_use = $MASTER_KEY;
    } else {
        $key_to_use = $_POST['master_key'] ?? '';
    }

    $res = call_api('register', ['action' => 'register', 'name' => $name, 'master_key' => $key_to_use]);
    
    if ($res && $res['status'] === 'success') {
        $config = [
            'node_name' => $name,
            'node_key' => $res['node_key'],
            'auth_token' => $res['auth_token']
        ];
        save_config($config);
        $message = "<div style='color:green'>Đăng ký thành công! Token đã được lưu.</div>";
    } else {
        $message = "<div style='color:red'>Lỗi đăng ký: " . ($res['message'] ?? 'Unknown') . "</div>";
    }
}

// 2. CHECK COUNT (Đếm số file cần tải)
if (isset($_GET['check_count']) && $config) {
    $res = call_api('count_pending', [], $config['auth_token']);
    echo json_encode($res);
    exit;
}

// 3. CHẠY BATCH (Tải 1 nhóm file)
if (isset($_GET['run_batch']) && $config) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $res = call_api('get_pending', ['limit' => $limit], $config['auth_token']); // Send limit in POST/GET
    
    $result = ['status' => 'success', 'synced' => 0, 'errors' => [], 'logs' => []];
    
    if ($res && $res['status'] === 'success') {
        $files = $res['files'];
        if (empty($files)) {
            $result['message'] = "No files";
        } else {
            if (!file_exists($LOCAL_STORAGE_PATH)) mkdir($LOCAL_STORAGE_PATH, 0777, true);

            foreach ($files as $f) {
                $remote_url = $f['url'];
                $local_path = $LOCAL_STORAGE_PATH . basename($f['file_path']);
                
                $file_content = @file_get_contents($remote_url);
                if ($file_content === false) {
                    $result['errors'][] = "Failed to download: " . basename($f['file_path']);
                    continue;
                }

                file_put_contents($local_path, $file_content);
                $my_hash = hash_file('sha256', $local_path);

                $confirm_res = call_api('confirm', [
                    'action' => 'confirm', 
                    'file_id' => $f['id'],
                    'file_hash' => $my_hash,
                    'auth_token' => $config['auth_token']
                ], $config['auth_token']);

                if ($confirm_res && $confirm_res['status'] === 'success') {
                    $result['synced']++;
                    $result['logs'][] = "Synced: " . basename($f['file_path']);
                } else {
                    $result['errors'][] = "Confirm failed: " . basename($f['file_path']);
                }
            }
        }
    } else {
        $result['status'] = 'error';
        $result['message'] = $res['message'] ?? 'API Error';
    }
    echo json_encode($result);
    exit;
}

// OLD LOGIC (Keep for backward compatibility or direct run)
$sync_log = [];
if (isset($_GET['run_sync']) && $config) {
    // A. Lấy danh sách file cần tải
    $res = call_api('get_pending', [], $config['auth_token']);
    
    if ($res && $res['status'] === 'success') {
        $files = $res['files'];
        if (empty($files)) {
            $sync_log[] = "Không có file mới cần tải.";
        } else {
            // B. Tạo thư mục lưu trữ
            if (!file_exists($LOCAL_STORAGE_PATH)) mkdir($LOCAL_STORAGE_PATH, 0777, true);

            foreach ($files as $f) {
                $remote_url = $f['url'];
                $local_path = $LOCAL_STORAGE_PATH . basename($f['file_path']); // Flatten structure for simple backup
                // Hoặc giữ nguyên cấu trúc thư mục:
                // $local_path = $LOCAL_STORAGE_PATH . $f['file_path'];
                // if (!file_exists(dirname($local_path))) mkdir(dirname($local_path), 0777, true);

                $sync_log[] = "Đang tải: " . basename($f['file_path']) . "...";
                
                // C. Tải file
                $file_content = @file_get_contents($remote_url);
                if ($file_content === false) {
                    $sync_log[] = "<span style='color:red'> -> Lỗi tải file!</span>";
                    continue;
                }

                // D. Lưu và Hash
                file_put_contents($local_path, $file_content);
                $my_hash = hash_file('sha256', $local_path);

                // E. Confirm
                $confirm_res = call_api('confirm', [
                    'action' => 'confirm', 
                    'file_id' => $f['id'],
                    'file_hash' => $my_hash,
                    'auth_token' => $config['auth_token'] // Post field fallback
                ], $config['auth_token']);

                if ($confirm_res && $confirm_res['status'] === 'success') {
                    $sync_log[] = "<span style='color:green'> -> OK (Hash: " . substr($my_hash, 0, 8) . "...)</span>";
                } else {
                    $sync_log[] = "<span style='color:red'> -> Lưu xong nhưng báo lỗi Server: " . ($confirm_res['message'] ?? '') . "</span>";
                }
            }
        }
    } else {
        $sync_log[] = "<span style='color:red'>Lỗi kết nối Server: " . ($res['message'] ?? 'Unknown') . "</span>";
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>File Sync Agent</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.5; }
        .box { border: 1px solid #ccc; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .btn { background: #24a25c; color: #fff; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; display: inline-block;}
        .log { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-top: 20px; height: 300px; overflow-y: auto; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="box">
    <h2><i class="fas fa-sync"></i> Hybrid Storage Agent</h2>
    <?php echo $message; ?>

    <?php if (!$config): ?>
        <h3>Bước 1: Đăng ký Máy trạm (Node)</h3>
        <p>Nhập tên máy tính này để đăng ký với hệ thống Cloud.</p>
        <form method="POST">
            <input type="text" name="node_name" placeholder="Ví dụ: PC_VANPHONG_01" required style="padding: 8px; width: 60%;">
            <button type="submit" name="register" class="btn">Đăng ký</button>
        </form>
    <?php else: ?>
        <div style="background: #e6fffa; padding: 10px; border: 1px solid #b2f5ea; margin-bottom: 20px;">
            <strong>Trạng thái:</strong> Đã kết nối<br>
            <strong>Node Name:</strong> <?php echo $config['node_name']; ?><br>
            <strong>Token:</strong> ...<?php echo substr($config['auth_token'], -8); ?>
        </div>

        <h3>Bước 2: Đồng bộ File</h3>
        <p>Tool sẽ tự động tải các file mới từ Hosting về máy này.</p>
        <a href="?run_sync=1" class="btn">Bắt đầu Đồng bộ Ngay</a>
        
        <?php if (!empty($sync_log)): ?>
            <div class="log">
                <?php foreach($sync_log as $l) echo "<div>$l</div>"; ?>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 20px; font-size: 0.9rem; color: #666;">
            * Mẹo: Bạn có thể treo tab này hoặc thiết lập Task Scheduler để chạy file này định kỳ.
        </p>
    <?php endif; ?>
</div>

</body>
</html>
