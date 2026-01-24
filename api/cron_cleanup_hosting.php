<?php
/**
 * CRONJOB: CLEANUP HOSTING STORAGE
 * Tự động xóa file trên hosting sau 60 ngày nếu đã được sync về local
 */
require_once '../config/db.php';

// Bảo mật: Chỉ cho phép chạy từ CLI hoặc qua Secret Key
$CLEANUP_KEY = "K_SERVICE_CLEANUP_2026";
if (php_sapi_name() !== 'cli' && ($_GET['key'] ?? '') !== $CLEANUP_KEY) {
    die("Unauthorized");
}

echo "Starting cleanup process...\n";

// 1. Tìm các file (documents) thỏa mãn:
// - storage_status = 'synced' (Đã được ít nhất 1 node local tải về)
// - created_at < NOW - 60 days
$days = 60;
$sql = "SELECT id, file_path, file_name 
        FROM documents 
        WHERE storage_status = 'synced' 
        AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";

$files = db_fetch_all($sql, [$days]);

$count = 0;
foreach ($files as $f) {
    $full_path = "../" . $f['file_path'];
    
    if (file_exists($full_path)) {
        if (unlink($full_path)) {
            // Cập nhật trạng thái trong DB thành 'offline'
            // Xóa file_path để web không trỏ vào link chết
            db_query("UPDATE documents SET storage_status = 'offline' WHERE id = ?", [$f['id']]);
            echo "Deleted: " . $f['file_name'] . "\n";
            $count++;
        }
    } else {
        // File không tồn tại nhưng status vẫn là synced -> Cập nhật luôn sang offline
        db_query("UPDATE documents SET storage_status = 'offline' WHERE id = ?", [$f['id']]);
    }
}

echo "Cleanup finished. Total files removed: $count\n";
?>
