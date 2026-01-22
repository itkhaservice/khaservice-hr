<header class="main-header">
    <div class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </div>

    <div class="header-right">
        <div class="user-info" id="userDropdown">
            <div class="user-avatar">
                {{ substr(Auth::user()->fullname ?? 'Admin', 0, 1) }}
            </div>
            <span class="user-name">{{ Auth::user()->fullname ?? 'Administrator' }}</span>
            <i class="fas fa-chevron-down"></i>

            <div class="user-dropdown" id="dropdownMenu">
                <a href="#"><i class="fas fa-user-circle"></i> Hồ sơ của tôi</a>
                <a href="#"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                <hr>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
                <form id="logout-form" action="#" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>
