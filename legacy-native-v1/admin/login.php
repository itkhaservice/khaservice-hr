<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    // Simple query (In production use password_verify)
    $user = db_fetch_row("SELECT * FROM users WHERE username = ? AND status = 1", [$username]);

    $login_success = false;
    
    if ($user) {
        // 1. Check hash
        if (password_verify($password, $user['password'])) {
            $login_success = true;
        } 
        // 2. Check plaintext (legacy fallback)
        elseif ($user['password'] === $password) {
            $login_success = true;
            // Upgrade to hash automatically
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password = ? WHERE id = ?", [$new_hash, $user['id']]);
        }
    }

    if ($login_success) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_role'] = $user['role'];
        redirect('index.php');
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Khaservice HR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .brand { text-align: center; margin-bottom: 30px; color: #24a25c; font-size: 1.5rem; font-weight: 700; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #475569; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 12px; background: #24a25c; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        .btn-login:hover { background: #1b7a43; }
        .error-msg { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        
        /* Toggle Styling */
        .password-wrapper { position: relative; }
        .password-toggle-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">KHASERVICE HR</div>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="login_pass" class="form-control" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('login_pass', this.querySelector('i'))">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
