<?php
// TOOL ĐỒNG BỘ DỮ LIỆU TỪ CLOUD (HOSTING) VỀ LOCALHOST
// Chạy trên máy local (XAMPP)

// 1. Cấu hình
$HOSTING_URL = "http://khaservice.free.nf/api/sync.php"; // Thay bằng domain thật của bạn
$API_KEY = "KHA_SERVICE_SECURE_SYNC_2026";

// Mock server name để dùng chung config DB
$_SERVER['SERVER_NAME'] = 'localhost';
require_once '../config/db.php';

// Tăng thời gian chạy
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "<h1>🔄 Khaservice Data Sync (Cloud -> Local)</h1>";
echo "<pre>";

// Danh sách bảng cần đồng bộ
$tables = [
    'departments', 'positions', 'projects', // Danh mục trước
    'employees', 'employee_salaries',       // Nhân sự
    'attendance', 'attendance_logs',        // Chấm công (Dữ liệu lớn)
    'payroll',                              // Lương
    'documents', 'document_settings',
    'settings', 'users'
];

foreach ($tables as $table) {
    echo "Processing table: <strong>$table</strong>... ";
    flush();

    // 1. Lấy thời gian cập nhật cuối cùng trong Local DB
    $last_sync = '2000-01-01 00:00:00';
    try {
        $cols = db_fetch_all("SHOW COLUMNS FROM `$table`");
        $col_names = array_column($cols, 'Field');
        $time_col = null;
        if (in_array('updated_at', $col_names)) $time_col = 'updated_at';
        elseif (in_array('created_at', $col_names)) $time_col = 'created_at';

        if ($time_col) {
            $row = db_fetch_row("SELECT MAX($time_col) as last FROM `$table`");
            if ($row && $row['last']) {
                $last_sync = $row['last'];
            }
        }
    } catch (Exception $e) {
        // Table chưa có ở local? Bỏ qua bước lấy time, sẽ sync full
    }

    // 2. Loop để tải dữ liệu (Pagination)
    $offset = 0;
    $limit = 100;
    $total_synced = 0;

    while (true) {
        $url = "$HOSTING_URL?table=$table&limit=$limit&offset=$offset&last_sync=" . urlencode($last_sync);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-KEY: $API_KEY"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            echo "<span style='color:red'>Lỗi kết nối API ($http_code)</span><br>";
            break;
        }

        $json = json_decode($response, true);
        if (!$json || $json['status'] !== 'success') {
            echo "<span style='color:red'>Lỗi dữ liệu: " . ($json['message'] ?? 'Unknown') . "</span><br>";
            break;
        }

        $rows = $json['data'];
        if (empty($rows)) {
            break; // Hết dữ liệu
        }

        // 3. Insert/Update vào Local DB
        foreach ($rows as $row) {
            $keys = array_keys($row);
            $vals = array_values($row);
            
            // Build SQL dynamic
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $columns = implode('`, `', $keys);
            
            $update_parts = [];
            foreach ($keys as $k) {
                if ($k === 'id') continue; // Giữ nguyên ID
                $update_parts[] = "`$k` = VALUES(`$k`)";
            }
            $update_sql = implode(', ', $update_parts);

            $sql = "INSERT INTO `$table` (`$columns`) VALUES ($placeholders) 
                    ON DUPLICATE KEY UPDATE $update_sql";
            
            db_query($sql, $vals);
            $total_synced++;
        }

        $offset += $limit;
        
        // Nếu số lượng lấy về nhỏ hơn limit, nghĩa là đã hết
        if (count($rows) < $limit) break;
    }

    echo "<span style='color:green'>Done ($total_synced records).</span><br>";
    flush();
}

echo "\n------------------------------------------------\n";
echo "✅ Đồng bộ hoàn tất!";
echo "</pre>";
?>
