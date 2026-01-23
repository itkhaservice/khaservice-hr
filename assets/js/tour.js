/**
 * Khaservice HRMS - Product Tour System
 * Thư viện sử dụng: Driver.js
 */

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
        { element: 'button[onclick="openImportModal()"]', popover: { title: 'Nạp dữ liệu hàng loạt', description: 'Nhập hàng trăm nhân viên từ file Excel chỉ trong vài giây.', side: "bottom" } }
    ],

    // 6. HỆ THỐNG
    '/support/index.php': [
};

window.startProductTour = function(force = false) {
    try {
        let driverObj = null;
        if (window.driver && window.driver.js && window.driver.js.driver) {
            driverObj = window.driver.js.driver;
        } else {
            console.warn('Driver.js not found');
            if (force) alert('Thư viện hướng dẫn chưa được tải.');
            return;
        }

        const currentPath = window.location.pathname;
        let steps = [];

        if (force) {
            steps = [...tourConfigs['common']];
        }

        for (const path in tourConfigs) {
            if (path !== 'common' && currentPath.includes(path)) {
                steps = [...steps, ...tourConfigs[path]];
                break;
            }
        }

        if (steps.length > 0) {
            const d = driverObj({
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
            if (window.Toast) window.Toast.info('Trang này chưa có hướng dẫn chi tiết.');
        }
    } catch (e) {
        console.error('Tour error:', e);
    }
};

$(document).ready(function() {
    setTimeout(() => {
        if (typeof startProductTour === 'function') startProductTour(false);
    }, 1000);
});
