<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Fetch settings from database
$raw_settings = db_fetch_all("SELECT * FROM settings WHERE setting_key IN ('company_name', 'company_email', 'company_phone', 'company_website', 'system_version')");
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$company_name = $settings['company_name'] ?? 'CÔNG TY TNHH KHASERVICE';
$company_email = $settings['company_email'] ?? 'it@khaservice.com.vn';
$company_phone = $settings['company_phone'] ?? '028 3825 3041';
$company_website = $settings['company_website'] ?? 'www.khaservice.com.vn';

$php_version = phpversion();
$mysql_version = db_fetch_row("SELECT VERSION() as v")['v'];
$os = PHP_OS;
$system_version = $settings['system_version'] ?? '1.0.0';
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
                        <p style="color: var(--text-sub); margin: 5px 0;"><?php echo $company_name; ?></p>
                        <span class="badge badge-success">Lead Developer</span>
                    </div>
                </div>
                <p style="line-height: 1.6; color: var(--text-main);">Dự án <strong>Khaservice HRMS</strong> được thiết kế và xây dựng nhằm tối ưu hóa quy trình quản lý nhân sự, chấm công và tính lương cho các dự án bất động sản và dịch vụ quản lý tòa nhà.</p>
                
                <div style="margin-top: 20px;">
                    <div class="contact-item">
                        <i class="fas fa-envelope text-primary"></i> <span><?php echo $company_email; ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone text-primary"></i> <span><?php echo $company_phone; ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-globe text-primary"></i> <span><?php echo $company_website; ?></span>
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
                        <h4>Cẩm nang Hướng dẫn</h4>
                        <p>Tài liệu chi tiết quy trình vận hành hệ thống.</p>
                        <button onclick="openGuideModal()" class="btn btn-secondary btn-sm">Đọc tài liệu</button>
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
</div>

<!-- GUIDEBOOK MODAL -->
<div id="guideBookModal" class="guide-modal">
    <div class="guide-content">
        <div class="guide-header">
            <h2><i class="fas fa-book-reader"></i> Hướng dẫn Vận hành Hệ thống Khaservice HR</h2>
            <button onclick="closeGuideModal()" class="close-btn">&times;</button>
        </div>
        <div class="guide-body">
            <div class="toc">
                <h3>Mục lục</h3>
                <ul>
                    <li><a href="#chuong1">1. Trang chủ (Dashboard)</a></li>
                    <li><a href="#chuong2">2. Quản lý Dự án</a></li>
                    <li><a href="#chuong3">3. Quản lý Nhân sự</a></li>
                    <li><a href="#chuong4">4. Chấm công & Lương</a></li>
                </ul>
            </div>
            <div class="content-text">
                
                <!-- CHƯƠNG 1: TRANG CHỦ -->
                <div id="chuong1" class="chapter">
                    <h3>1. Trang chủ (Dashboard)</h3>
                    <p>Trang chủ là trung tâm điều khiển, cung cấp cái nhìn tổng quan về tình hình nhân sự và hoạt động của toàn hệ thống theo thời gian thực.</p>
                    
                    <h4>1.1. Cảnh báo thiếu nhân sự</h4>
                    <p>Hệ thống tự động so sánh số lượng nhân viên thực tế đang làm việc tại dự án với định biên (Headcount) đã được thiết lập.</p>
                    <ul>
                        <li><strong class="text-danger">Màu đỏ:</strong> Hiển thị số lượng vị trí đang thiếu người.</li>
                        <li><strong>Hành động:</strong> Nhấn vào thẻ cảnh báo để xem chi tiết dự án nào đang thiếu vị trí nào (Bảo vệ, Tạp vụ, Kỹ thuật...) để lên kế hoạch tuyển dụng kịp thời.</li>
                    </ul>

                    <h4>1.2. Tiến độ Chốt công</h4>
                    <p>Công cụ hỗ trợ bộ phận C&B theo dõi trạng thái chốt sổ cuối tháng.</p>
                    <ul>
                        <li><strong>Cơ chế tự động:</strong> Trong 5 ngày đầu tháng, hệ thống hiển thị tiến độ của tháng trước. Từ ngày 6 trở đi, hiển thị tháng hiện tại.</li>
                        <li><strong>Trạng thái:</strong> <i class="fas fa-check-circle text-success"></i> Đã chốt (Dữ liệu an toàn) hoặc <i class="far fa-circle"></i> Đang thực hiện.</li>
                    </ul>

                    <h4>1.3. Hoạt động Chấm công gần nhất</h4>
                    <p>Bảng tin cập nhật theo thời gian thực (Real-time) về các thao tác chấm công vừa diễn ra trên toàn hệ thống.</p>
                    <ul>
                        <li>Hiển thị <strong>Tên Dự án</strong> vừa có phát sinh chấm công.</li>
                        <li>Hiển thị <strong>Tên Nhân viên</strong> được chấm và <strong>Thời gian</strong> ghi nhận cụ thể.</li>
                        <li>Giúp Ban Quản lý nắm bắt được dự án nào đang hoạt động tích cực.</li>
                    </ul>

                    <h4>1.4. Lối tắt nhanh</h4>
                    <p>Truy cập nhanh vào các chức năng thường dùng nhất mà không cần tìm kiếm trong menu: Thêm nhân viên mới, Chấm công hôm nay, Xem báo cáo...</p>
                </div>

                <!-- CHƯƠNG 2: QUẢN LÝ DỰ ÁN -->
                <div id="chuong2" class="chapter">
                    <h3>2. Quản lý Dự án</h3>
                    <p>Module quản lý danh sách các mục tiêu, tòa nhà hoặc văn phòng mà công ty đang cung cấp dịch vụ.</p>

                    <h4>2.1. Tìm kiếm Dự án</h4>
                    <p>Tại giao diện danh sách dự án, sử dụng thanh tìm kiếm phía trên cùng:</p>
                    <ul>
                        <li>Nhập <strong>Tên dự án</strong> hoặc <strong>Mã dự án</strong>.</li>
                        <li>Hệ thống sẽ lọc kết quả ngay lập tức (Instant Search) mà không cần tải lại trang.</li>
                    </ul>

                    <h4>2.2. Bộ lọc Dự án</h4>
                    <p>Sử dụng các bộ lọc nâng cao để phân nhóm dự án:</p>
                    <ul>
                        <li><strong>Lọc theo Trạng thái:</strong> Đang hoạt động (Active) / Tạm dừng / Đã đóng.</li>
                        <li><strong>Lọc theo Khu vực:</strong> Quận/Huyện (nếu có cấu hình).</li>
                    </ul>

                    <h4>2.3. Xem Chi tiết & Định biên</h4>
                    <p>Nhấn vào tên dự án hoặc nút <i class="fas fa-eye"></i> để vào trang chi tiết:</p>
                    <ul>
                        <li><strong>Thông tin chung:</strong> Địa chỉ, Ngày bắt đầu, Người quản lý.</li>
                        <li><strong>Định biên (Headcount):</strong> Thiết lập số lượng nhân sự cần thiết cho từng vị trí. Đây là cơ sở để hệ thống đưa ra các <em>Cảnh báo thiếu nhân sự</em> ngoài trang chủ.</li>
                        <li><strong>Danh sách nhân viên:</strong> Xem toàn bộ nhân sự đang làm việc tại dự án đó.</li>
                    </ul>
                </div>

                <!-- CHƯƠNG 3: NHÂN SỰ -->
                <div id="chuong3" class="chapter">
                    <h3>3. Quản lý Nhân sự</h3>
                    <p>Quy trình quản lý vòng đời nhân viên từ khi vào làm đến khi nghỉ việc.</p>
                    <ul>
                        <li><strong>Hồ sơ:</strong> Yêu cầu bắt buộc nộp đủ 5 loại giấy tờ (CCCD, Hộ khẩu, SYLL, Bằng cấp, GKSK). Hệ thống sẽ đánh dấu <span class="text-danger">Thiếu</span> nếu chưa đủ.</li>
                        <li><strong>Hợp đồng:</strong> Quản lý thời hạn hợp đồng, tự động cảnh báo trước 30 ngày khi hợp đồng sắp hết hạn.</li>
                        <li><strong>Điều chuyển:</strong> Sử dụng chức năng "Điều chuyển" để chuyển nhân viên từ dự án này sang dự án khác mà vẫn giữ nguyên lịch sử làm việc.</li>
                    </ul>
                </div>

                <!-- CHƯƠNG 4: CHẤM CÔNG & LƯƠNG -->
                <div id="chuong4" class="chapter">
                    <h3>4. Chấm công & Tính lương</h3>
                    <div class="formula-box">
                        <strong>Nguyên tắc:</strong> Dữ liệu Chấm công là ĐẦU VÀO duy nhất và quan trọng nhất cho bảng Lương.
                    </div>
                    <ul>
                        <li><strong>Chấm công ngày:</strong> Quản lý dự án thực hiện chấm công hàng ngày (X, P, L, OFF).</li>
                        <li><strong>Chốt công:</strong> Cuối tháng, hệ thống khóa dữ liệu chấm công để chuyển sang tính lương.</li>
                        <li><strong>Bảng lương:</strong> Được tính tự động dựa trên: (Lương cơ bản / Công chuẩn) * Công thực tế + Phụ cấp - Khấu trừ (BHXH, Tạm ứng).</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Guide Modal Styling */
.guide-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
.guide-content { background: #fff; width: 900px; max-width: 95%; height: 85vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: slideUp 0.3s ease-out; }
.guide-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
.guide-header h2 { margin: 0; font-size: 1.25rem; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.guide-header .close-btn { background: none; border: none; font-size: 2rem; cursor: pointer; color: #64748b; transition: 0.2s; line-height: 1; }
.guide-header .close-btn:hover { color: #ef4444; }

.guide-body { display: flex; flex: 1; overflow: hidden; }
.toc { width: 250px; background: #f1f5f9; padding: 25px; border-right: 1px solid #e2e8f0; overflow-y: auto; flex-shrink: 0; }
.toc h3 { margin-top: 0; font-size: 1rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px; }
.toc ul { list-style: none; padding: 0; margin: 0; }
.toc li { margin-bottom: 10px; }
.toc a { text-decoration: none; color: #334155; font-size: 0.95rem; font-weight: 500; transition: 0.2s; display: block; padding: 5px 0; }
.toc a:hover { color: var(--primary-color); transform: translateX(5px); }

.content-text { flex: 1; padding: 40px; overflow-y: auto; line-height: 1.7; font-size: 1rem; color: #334155; scroll-behavior: smooth; }
.chapter { margin-bottom: 50px; padding-bottom: 30px; border-bottom: 1px dashed #e2e8f0; }
.chapter:last-child { border-bottom: none; }
.chapter h3 { color: var(--primary-color); font-size: 1.5rem; margin-top: 0; margin-bottom: 20px; border-left: 4px solid var(--primary-color); padding-left: 15px; }
.chapter ul, .chapter ol { padding-left: 20px; margin-bottom: 20px; }
.chapter li { margin-bottom: 10px; }
.formula-box { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; font-family: monospace; font-size: 1.1rem; }
.text-danger { color: #ef4444; font-weight: 600; }

@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Dark Mode Guide */
body.dark-mode .guide-content { background: #1e293b; color: #cbd5e1; }
body.dark-mode .guide-header { background: #0f172a; border-bottom-color: #334155; }
body.dark-mode .guide-header h2 { color: #f1f5f9; }
body.dark-mode .toc { background: #0f172a; border-right-color: #334155; }
body.dark-mode .toc h3 { color: #94a3b8; }
body.dark-mode .toc a { color: #cbd5e1; }
body.dark-mode .content-text { color: #cbd5e1; }
body.dark-mode .chapter { border-bottom-color: #334155; }
body.dark-mode .formula-box { background: rgba(22, 163, 74, 0.1); border-color: #166534; color: #4ade80; }

/* Responsive */
@media (max-width: 768px) {
    .guide-body { flex-direction: column; }
    .toc { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; padding: 15px; }
    .content-text { padding: 20px; }
}
</style>

<script>
function openGuideModal() {
    document.getElementById('guideBookModal').style.display = 'flex';
}
function closeGuideModal() {
    document.getElementById('guideBookModal').style.display = 'none';
}
// Close on outside click
window.onclick = function(event) {
    var modal = document.getElementById('guideBookModal');
    if (event.target == modal) {
        closeGuideModal();
    }
}
</script>
</div>
<?php include '../../../includes/footer.php'; ?>
