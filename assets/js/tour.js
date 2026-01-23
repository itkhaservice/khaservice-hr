/**
 * Khaservice HRMS - Product Tour System
 * Thư viện sử dụng: Driver.js
 */

const driver = window.driver.js.driver;

const tourConfigs = {
    // 1. Hướng dẫn trang Nhân sự
    '/employees/index.php': [
        { element: '.page-title', popover: { title: 'Quản lý Nhân sự', description: 'Chào mừng bạn đến với nơi quản lý toàn bộ vòng đời nhân viên.', side: "bottom", align: 'start' } },
        { element: 'a[href="add.php"]', popover: { title: 'Thêm nhân viên', description: 'Nhấn vào đây để thêm mới một nhân sự vào hệ thống.', side: "left", align: 'center' } },
        { element: '.filter-section', popover: { title: 'Bộ lọc thông minh', description: 'Bạn có thể lọc nhân viên theo dự án, phòng ban hoặc trạng thái hồ sơ Đủ/Thiếu.', side: "bottom", align: 'center' } },
        { element: '.badge-warning', popover: { title: 'Cảnh báo hồ sơ', description: 'Hệ thống tự động nhắc nhở nếu nhân viên chưa nộp đủ các chứng từ bắt buộc.', side: "right", align: 'center' } },
        { element: 'a[title="Hồ sơ"]', popover: { title: 'Kho dữ liệu số', description: 'Quản lý và duyệt các bản scan hồ sơ PDF/Ảnh của nhân viên tại đây.', side: "left", align: 'center' } }
    ],
    // 2. Hướng dẫn trang Chấm công
    '/attendance/index.php': [
        { element: '.attendance-table', popover: { title: 'Bảng công Excel', description: 'Nhấn và kéo chuột (Drag Select) để nhập công cho nhiều ngày cùng lúc như đang dùng Excel.', side: "top", align: 'center' } },
        { element: '.is-sunday', popover: { title: 'Cột Chủ Nhật', description: 'Các ngày Chủ Nhật được tô vàng để bạn dễ phân biệt với ngày thường.', side: "bottom", align: 'center' } },
        { element: '.att-input.ot', popover: { title: 'Tăng ca & Hỗ trợ', description: 'Nhập giờ tăng ca ở ô dưới. Nhấp chuột phải để gán giờ công này cho một dự án khác.', side: "bottom", align: 'center' } },
        { element: 'button[onclick="saveAttendance()"]', popover: { title: 'Lưu dữ liệu', description: 'Đừng quên nhấn Lưu sau khi hoàn tất để dữ liệu được ghi nhận vào hệ thống.', side: "bottom", align: 'center' } }
    ],
    // 3. Hướng dẫn trang Lương
    '/salary/index.php': [
        { element: 'button[name="calculate_payroll"]', popover: { title: 'Tính lương tự động', description: 'Hệ thống sẽ dựa vào bảng công để tính toán thu nhập, bảo hiểm và thuế.', side: "bottom", align: 'center' } },
        { element: 'a[href*="config.php"]', popover: { title: 'Cấu hình biến động', description: 'Thiết lập thưởng, phạt, tạm ứng riêng cho tháng này trước khi tính lương.', side: "left", align: 'center' } },
        { element: 'td[style*="background: #f0fdf4"]', popover: { title: 'Thực lĩnh', description: 'Số tiền cuối cùng sau khi đã trừ hết các khoản giảm trừ.', side: "left", align: 'center' } }
    ]
};

function startProductTour(force = false) {
    const currentPath = window.location.pathname;
    let steps = null;

    // Tìm kịch bản phù hợp với đường dẫn hiện tại
    for (const path in tourConfigs) {
        if (currentPath.includes(path)) {
            steps = tourConfigs[path];
            break;
        }
    }

    if (steps) {
        const d = driver({
            showProgress: true,
            nextBtnText: 'Tiếp theo',
            prevBtnText: 'Quay lại',
            doneBtnText: 'Hoàn tất',
            steps: steps
        });

        // Nếu force = false, kiểm tra xem đã xem chưa
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

// Tự động khởi chạy tour khi load trang (nếu là lần đầu)
window.addEventListener('load', () => {
    setTimeout(() => startProductTour(false), 1000);
});
