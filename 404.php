<?php
session_start();
$error_type = $_GET['error'] ?? '';
$perm_code = $_GET['code'] ?? '';
$is_permission_error = ($error_type === 'no_permission');

$requested_page = $_SERVER['HTTP_REFERER'] ?? 'Không rõ';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_permission_error ? '403 - Từ chối truy cập' : '404 - Không tìm thấy trang'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/khaservice-hr/assets/css/admin_style.css">
    <style>
        body { background: #f1f5f9; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; font-family: 'Inter', sans-serif; }
        .error-card { text-align: center; max-width: 550px; width: 90%; padding: 40px; background: #fff; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .icon-box { font-size: 5rem; margin-bottom: 20px; }
        .status-code { font-size: 1.2rem; font-weight: 700; padding: 4px 12px; border-radius: 4px; display: inline-block; margin-bottom: 15px; }
        
        /* Màu sắc theo loại lỗi */
        .forbidden { color: #f59e0b; } .bg-forbidden { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .notfound { color: #ef4444; } .bg-notfound { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        
        .error-title { font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-bottom: 15px; }
        .error-message { color: #64748b; line-height: 1.6; margin-bottom: 30px; font-size: 1.1rem; }
        .debug-info { background: #f8fafc; padding: 15px; border-radius: 8px; text-align: left; font-size: 0.85rem; color: #94a3b8; border: 1px solid #e2e8f0; }
        .btn-home { display: inline-flex; align-items: center; gap: 10px; padding: 12px 30px; background: var(--primary-color); color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box <?php echo $is_permission_error ? 'forbidden' : 'notfound'; ?>">
            <i class="fas <?php echo $is_permission_error ? 'fa-user-lock' : 'fa-search-location'; ?>"></i>
        </div>
        
        <div class="status-code <?php echo $is_permission_error ? 'bg-forbidden' : 'bg-notfound'; ?>">
            <?php echo $is_permission_error ? '403 FORBIDDEN' : '404 NOT FOUND'; ?>
        </div>

        <h1 class="error-title">
            <?php echo $is_permission_error ? 'BẠN KHÔNG CÓ QUYỀN TRUY CẬP' : 'TRANG KHÔNG TỒN TẠI'; ?>
        </h1>

        <p class="error-message">
            <?php 
            if ($is_permission_error) {
                echo "Hệ thống xác định tài khoản <strong>" . ($_SESSION['user_login'] ?? 'đang dùng') . "</strong> chưa được cấp mã quyền <code>$perm_code</code> để truy cập khu vực này.";
            } else {
                echo "Đường dẫn tệp tin bạn yêu cầu không có trên máy chủ. Có thể tệp đã bị xóa, đổi tên hoặc bạn gõ sai URL.";
            }
            ?>
        </p>

        <div class="debug-info">
            <strong>Thông tin kỹ thuật:</strong><br>
            - Thời gian: <?php echo date('H:i:s d/m/Y'); ?><br>
            - Link gốc: <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></code>
        </div>

        <a href="/khaservice-hr/admin/index.php" class="btn-home">
            <i class="fas fa-home"></i> Quay về Dashboard
        </a>
    </div>
</body>
</html>
