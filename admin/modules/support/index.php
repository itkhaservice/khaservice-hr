<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Giả lập một số thông số hệ thống
$php_version = phpversion();
$mysql_version = db_fetch_row("SELECT VERSION() as v")['v'];
$os = PHP_OS;
$system_version = db_fetch_row("SELECT setting_value FROM settings WHERE setting_key = 'system_version'")['setting_value'] ?? '1.0.0';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Hỗ trợ & Thông tin Hệ thống</h1>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            
            <!-- 1. THÔNG TIN PHÁT TRIỂN -->
            <div class="card">
                <h3 style="margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                    <i class="fas fa-code"></i> Đội ngũ Phát triển
                </h3>
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem;">
                        <i class="fas fa-user-astronaut"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 1.2rem;">Phòng Công nghệ Thông tin</h4>
                        <p style="color: var(--text-sub); margin: 5px 0;">CÔNG TY TNHH KHASERVICE</p>
                        <span class="badge badge-success">Lead Developer</span>
                    </div>
                </div>
                <p style="line-height: 1.6; color: var(--text-main);">Dự án <strong>Khaservice HRMS</strong> được thiết kế và xây dựng nhằm tối ưu hóa quy trình quản lý nhân sự, chấm công và tính lương cho các dự án bất động sản và dịch vụ quản lý tòa nhà.</p>
                
                <div style="margin-top: 20px;">
                    <div class="contact-item">
                        <i class="fas fa-envelope text-primary"></i> <span>it@khaservice.com.vn</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone text-primary"></i> <span>028 3825 3041</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-globe text-primary"></i> <span>www.khaservice.com.vn</span>
                    </div>
                </div>
            </div>

            <!-- 2. THÔNG SỐ KỸ THUẬT -->
            <div class="card">
                <h3 style="margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                    <i class="fas fa-microchip"></i> Thông số Kỹ thuật
                </h3>
                <table class="table table-sm">
                    <tr>
                        <td width="150" class="text-sub">Phiên bản Ứng dụng</td>
                        <td><strong>v<?php echo $system_version; ?> (Stable)</strong></td>
                    </tr>
                    <tr>
                        <td class="text-sub">Ngôn ngữ lập trình</td>
                        <td>PHP <?php echo $php_version; ?></td>
                    </tr>
                    <tr>
                        <td class="text-sub">Hệ quản trị CSDL</td>
                        <td>MariaDB / MySQL <?php echo explode('-', $mysql_version)[0]; ?></td>
                    </tr>
                    <tr>
                        <td class="text-sub">Môi trường vận hành</td>
                        <td><?php echo $os; ?> / Apache Server</td>
                    </tr>
                    <tr>
                        <td class="text-sub">Thư viện UI/UX</td>
                        <td>Inter Font, FontAwesome 6, Driver.js</td>
                    </tr>
                    <tr>
                        <td class="text-sub">Cập nhật cuối</td>
                        <td>23/01/2026</td>
                    </tr>
                </table>
                <div style="margin-top: 20px; padding: 15px; background: #fffbeb; border-radius: 8px; border: 1px solid #fcd34d; font-size: 0.85rem; color: #92400e;">
                    <i class="fas fa-info-circle"></i> Hệ thống đang hoạt động trong môi trường <strong>Production</strong>. Vui lòng không thay đổi các tệp tin cấu hình hạt nhân nếu không có sự hướng dẫn của quản trị viên.
                </div>
            </div>

            <!-- 3. CỔNG LIÊN HỆ NHANH -->
            <div class="card" style="grid-column: span 2;">
                <h3 style="margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                    <i class="fas fa-headset"></i> Trung tâm Trợ giúp
                </h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div class="support-box">
                        <i class="fas fa-book"></i>
                        <h4>Tài liệu hướng dẫn</h4>
                        <p>Xem kịch bản hướng dẫn chi tiết các module.</p>
                        <a href="javascript:void(0)" onclick="startProductTour(true)" class="btn btn-secondary btn-sm">Xem Tour</a>
                    </div>
                    <div class="support-box">
                        <i class="fab fa-rocketchat"></i>
                        <h4>Hỗ trợ kỹ thuật</h4>
                        <p>Gửi yêu cầu hỗ trợ khi gặp lỗi hệ thống.</p>
                        <button class="btn btn-primary btn-sm">Gửi Ticket</button>
                    </div>
                    <div class="support-box">
                        <i class="fas fa-sync"></i>
                        <h4>Kiểm tra cập nhật</h4>
                        <p>Kiểm tra các tính năng mới từ máy chủ.</p>
                        <button class="btn btn-info btn-sm">Check Update</button>
                    </div>
                            </div>
                
                        </div>
                    </div>
                </div>
                
                <style>
.contact-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; font-size: 0.95rem; }
.contact-item i { width: 20px; text-align: center; }
.support-box { text-align: center; padding: 25px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.3s; }
.support-box:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: var(--primary-color); }
.support-box i { font-size: 2.5rem; color: var(--primary-color); margin-bottom: 15px; }
.support-box h4 { margin: 0 0 10px 0; }
.support-box p { font-size: 0.85rem; color: var(--text-sub); margin-bottom: 15px; }

/* DARK MODE */
body.dark-mode .card { background-color: #1e293b; border-color: #334155; }
body.dark-mode .support-box { background-color: #0f172a; border-color: #334155; }
body.dark-mode .support-box:hover { border-color: #4ade80; }
body.dark-mode .table td { border-bottom-color: #334155; color: #cbd5e1; }
body.dark-mode .text-sub { color: #94a3b8; }
body.dark-mode h3 { border-bottom-color: #334155 !important; }
body.dark-mode div[style*="background: #fffbeb"] { background-color: rgba(245, 158, 11, 0.1) !important; border-color: rgba(245, 158, 11, 0.3) !important; color: #fbbf24 !important; }
</style>

<?php include '../../../includes/footer.php'; ?>
