/**
 * Khaservice HRMS - Product Tour System
 * Thư viện sử dụng: Driver.js
 * Cập nhật: 23/01/2026 - Thêm Module Hợp đồng, Hỗ trợ & Topbar
 */

let driver = null;
try {
    if (window.driver && window.driver.js) {
        driver = window.driver.js.driver;
    }
} catch (e) {
    console.warn('Driver.js not loaded:', e);
}

const tourConfigs = {
    // 0. CHUNG (Xuất hiện ở mọi trang qua nút ?)
    'common': [
        { element: '#sidebarToggle', popover: { title: 'Thanh điều hướng', description: 'Bấm vào đây để thu gọn hoặc mở rộng danh sách chức năng.', side: "right" } },
        { element: '#start-tour', popover: { title: 'Hướng dẫn sử dụng', description: 'Bạn có thể nhấn vào đây bất cứ lúc nào để xem lại hướng dẫn của trang này.', side: "bottom" } },
        { element: '#theme-toggle', popover: { title: 'Chế độ giao diện', description: 'Chuyển đổi linh hoạt giữa giao diện Sáng (Light) và Tối (Dark) để bảo vệ mắt.', side: "bottom" } },
        { element: '.user-info', popover: { title: 'Tài khoản', description: 'Quản lý thông tin cá nhân, đổi mật khẩu hoặc đăng xuất khỏi hệ thống.', side: "bottom" } }
    ],

    // 1. MODULE NHÂN SỰ
    '/employees/index.php': [
        { element: '.page-title', popover: { title: 'Quản lý Nhân sự', description: 'Nơi lưu trữ và theo dõi toàn bộ vòng đời của nhân viên.', side: "bottom" } },
        { element: '.filter-section', popover: { title: 'Bộ lọc thông minh', description: 'Lọc nhanh nhân viên theo dự án hoặc trạng thái hồ sơ (Đủ/Thiếu).', side: "bottom" } },
        { element: 'button[onclick="openImportModal()"]', popover: { title: 'Nạp dữ liệu hàng loạt', description: 'Nhập hàng trăm nhân viên từ file Excel chỉ trong vài giây.', side: "bottom" } },
        { element: 'a[title="Hồ sơ"]', popover: { title: 'Giấy tờ pháp lý', description: 'Quản lý bản scan CCCD, Bằng cấp... và duyệt tính hợp lệ.', side: "left" } }
    ],
    '/employees/add.php': [
        { element: '.tab-item[data-tab="personal"]', popover: { title: 'Thông tin cá nhân', description: 'Nhập các thông tin cơ bản, số CCCD và ảnh khuôn mặt.', side: "bottom" } },
        { element: '.tab-item[data-tab="job"]', popover: { title: 'Thông tin công việc', description: 'Thiết lập Phòng ban, Chức vụ và Dự án công tác.', side: "bottom" } }
    ],
    '/employees/pending_docs.php': [
        { element: '.badge-info', popover: { title: 'Hồ sơ chờ duyệt', description: 'Tổng số hồ sơ do quản lý dự án nộp lên đang chờ bạn xác nhận.', side: "bottom" } }
    ],

    // 2. MODULE CHẤM CÔNG
    '/attendance/index.php': [
        { element: '.attendance-table', popover: { title: 'Bảng chấm công', description: 'Kéo chọn vùng để nhập công nhanh. Cột STT và Nhân viên luôn được cố định.', side: "top" } },
        { element: '.header-actions .btn-primary', popover: { title: 'Lưu dữ liệu', description: 'Hệ thống không tự lưu, hãy nhấn nút này sau khi nhập xong.', side: "bottom" } }
    ],

    // 3. MODULE HỢP ĐỒNG & BẢO HIỂM
    '/contracts/index.php': [
        { element: 'td:nth-child(4)', popover: { title: 'Thời hạn hợp đồng', description: 'Hệ thống cảnh báo màu Đỏ nếu hết hạn, màu Cam nếu sắp hết hạn (trong 30 ngày).', side: "right" } },
        { element: 'td:nth-child(6)', popover: { title: 'Trạng thái bảo hiểm', description: 'Theo dõi nhân viên đã được tham gia các chế độ BHXH, BHYT, BHTN hay chưa.', side: "left" } }
    ],
    '/contracts/edit.php': [
        { element: '.tab-item:first-child', popover: { title: 'Chi tiết Hợp đồng', description: 'Số hợp đồng, loại hợp đồng và lương cơ bản dùng để đóng bảo hiểm.', side: "bottom" } },
        { element: '.tab-item:last-child', popover: { title: 'Thông tin Bảo hiểm', description: 'Thiết lập số sổ BHXH và nơi đăng ký khám chữa bệnh ban đầu.', side: "bottom" } }
    ],

    // 4. MODULE LƯƠNG
    '/salary/index.php': [
        { element: 'button[name="calculate_payroll"]', popover: { title: 'Tính toán lương', description: 'Quét dữ liệu từ bảng công và cấu hình lương để xuất số liệu.', side: "bottom" } }
    ],

    // 5. BÁO CÁO
    '/reports/index.php': [
        { element: '.card[style*="border-top"]', popover: { title: 'Tổng quan định biên', description: 'Biểu đồ theo dõi tỷ lệ lấp đầy nhân sự so with kế hoạch.', side: "bottom" } }
    ],

    // 6. HỖ TRỢ & HỆ THỐNG
    '/support/index.php': [
        { element: '.card:nth-child(1)', popover: { title: 'Đội ngũ phát triển', description: 'Thông tin liên hệ và đơn vị xây dựng hệ thống Khaservice HRMS.', side: "bottom" } },
        { element: '.card:nth-child(2)', popover: { title: 'Thông số kỹ thuật', description: 'Chi tiết về phiên bản phần mềm, ngôn ngữ lập trình và môi trường vận hành.', side: "bottom" } },
        { element: '.support-box:nth-child(2)', popover: { title: 'Trung tâm trợ giúp', description: 'Nơi bạn có thể gửi yêu cầu hỗ trợ kỹ thuật hoặc xem lại các kịch bản hướng dẫn.', side: "top" } }
    ]
};

function startProductTour(force = false) {
    if (!driver) {
        if (force) console.warn('Product Tour: Driver library not available.');
        return;
    }
    const currentPath = window.location.pathname;
    let steps = [];

    // 1. Luôn thêm các bước chung nếu nhấn nút ?
    if (force) {
        steps = [...tourConfigs['common']];
    }

    // 2. Tìm kịch bản phù hợp với đường dẫn hiện tại
    for (const path in tourConfigs) {
        if (path !== 'common' && currentPath.includes(path)) {
            steps = [...steps, ...tourConfigs[path]];
            break;
        }
    }

    if (steps.length > 0) {
        const d = driver({
            showProgress: true,
            nextBtnText: 'Tiếp theo',
            prevBtnText: 'Quay lại',
            doneBtnText: 'Hoàn tất',
            steps: steps
        });

        if (!force) {
            const tourKey = 'tour_seen_' + currentPath.replace(/\//g, '_');
            if (localStorage.getItem(tourKey)) return;
            localStorage.setItem(tourKey, 'true');
        }

        d.drive();
    } else if (force) {
        Toast.info('Trang này hiện chưa có hướng dẫn chi tiết.');
    }
}

window.addEventListener('load', () => {
    setTimeout(() => startProductTour(false), 800);
});