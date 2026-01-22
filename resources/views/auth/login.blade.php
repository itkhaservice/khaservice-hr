<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Khaservice HR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin_style.css') }}">
    <style>
        body { 
            background: #f1f5f9; 
            display: flex; align-items: center; justify-content: center; 
            height: 100vh; margin: 0; 
        }
        .login-card {
            width: 100%; max-width: 400px;
            background: #fff; border-radius: 12px;
            padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header i { font-size: 3rem; color: var(--primary-color); margin-bottom: 15px; }
        .login-header h2 { margin: 0; font-weight: 800; color: #1e293b; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-building"></i>
            <h2>KHASERVICE HR</h2>
            <p style="color: #64748b; margin-top: 5px;">Hệ thống Quản trị Nhân sự v1.0</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" class="form-control" required autofocus value="{{ old('username') }}">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
                </label>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; height: 45px; font-size: 1rem;">
                ĐĂNG NHẬP <i class="fas fa-sign-in-alt"></i>
            </button>
        </form>
    </div>
</body>
</html>
