<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-building"></i>
        <span>KHASERVICE HR</span>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Bảng điều khiển</span>
            </a>
        </li>
        <li>
            <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Quản lý Nhân sự</span>
            </a>
        </li>
        <li>
            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <i class="fas fa-project-diagram"></i>
                <span>Dự án & Vận hành</span>
            </a>
        </li>
        <li>
            <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i>
                <span>Bảng Chấm công</span>
            </a>
        </li>
        <li>
            <a href="#" class="">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Tính lương (v2)</span>
            </a>
        </li>
        <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="#">
                <i class="fas fa-cog"></i>
                <span>Cài đặt hệ thống</span>
            </a>
        </li>
    </ul>
</div>
