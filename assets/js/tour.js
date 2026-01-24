/**
 * Khaservice HRMS - Product Tour System
 * Thư viện sử dụng: Driver.js
 * Dựa trên: PRODUCT_TOUR_GUIDE.md
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
        { element: '.page-title', popover: { title: 'Quản lý Nhân sự', description: 'Quản lý vòng đời nhân viên và tính pháp lý của hồ sơ.', side: "bottom" } },
        { element: 'a[href="add.php"]', popover: { title: 'Thêm nhân viên mới', description: 'Tạo hồ sơ nhân viên mới thủ công tại đây.', side: "bottom" } },
        { element: 'select[name="doc_status"]', popover: { title: 'Lọc trạng thái hồ sơ', description: 'Lọc nhanh những nhân viên còn thiếu hồ sơ (CCCD, SYLL...) để đốc thúc bổ sung.', side: "bottom" } },
        { element: '.table td .badge-warning', popover: { title: 'Cảnh báo hồ sơ', description: 'Badge này cho biết số lượng giấy tờ còn thiếu của nhân sự cần bổ sung.', side: "top" } },
        { element: 'a[href*="documents.php"]', popover: { title: 'Quản lý Hồ sơ', description: 'Vào đây để tải lên, xem bản scan hoặc duyệt tính hợp lệ của hồ sơ.', side: "left" } }
    ],

    // 2. MODULE CHẤM CÔNG
    '/attendance/index.php': [
        { element: '.page-title', popover: { title: 'Bảng Chấm công', description: 'Ghi nhận công và tăng ca với trải nghiệm giống Excel.', side: "bottom" } },
        { element: '.attendance-table', popover: { title: 'Thao tác kéo thả', description: 'Hỗ trợ nhấn và kéo chuột để chọn nhiều ngày công cùng lúc để nhập nhanh.', side: "top" } },
        { element: '.att-input.ot:first-of-type', popover: { title: 'Tăng ca & Dự án phụ', description: 'Nhập số giờ tăng ca vào ô dưới. Nhấp CHUỘT PHẢI để gán dự án hỗ trợ (Cross-Project).', side: "right" } },
        { element: '.btn-fullscreen', popover: { title: 'Chế độ toàn màn hình', description: 'Mở toàn màn hình để có không gian làm việc rộng rãi nhất cho bảng công lớn.', side: "bottom" } },
        { element: 'button[onclick="saveAttendance()"]', popover: { title: 'Lưu dữ liệu', description: 'Hệ thống không tự lưu, hãy nhấn nút này sau khi hoàn tất chỉnh sửa.', side: "bottom" } }
    ],

    // 3. MODULE TIỀN LƯƠNG
    '/salary/index.php': [
        { element: '.page-title', popover: { title: 'Quản lý Lương', description: 'Tính toán thu nhập dựa trên công thực tế và cấu hình biến động.', side: "bottom" } },
        { element: 'a[href*="config.php"]', popover: { title: 'Cấu hình biến động', description: 'Thiết lập thưởng, phạt và tạm ứng cho nhân viên trước khi tính lương.', side: "bottom" } },
        { element: 'button[name="calculate_payroll"]', popover: { title: 'Tính lương', description: 'Nhấn "Tính chi tiết" để hệ thống quét bảng công và xuất bảng lương tự động.', side: "bottom" } },
        { element: '.table th:last-child', popover: { title: 'Thực lĩnh', description: 'Cột Thực lĩnh sẽ tự động trừ các khoản bảo hiểm, thuế và tạm ứng.', side: "left" } }
    ],

    // 4. MODULE BÁO CÁO (Dựa trên mô tả, giả định cấu trúc vì trang reports chưa được inspect kỹ)
    '/reports/index.php': [
        { element: '.page-title', popover: { title: 'Báo cáo Tổng hợp', description: 'Cung cấp cái nhìn tổng thể về định biên và nhân lực.', side: "bottom" } },
        { element: '.dashboard-grid', popover: { title: 'Các loại báo cáo', description: 'Xem ma trận cơ cấu nhân sự, biến động nhân sự và quỹ phép.', side: "top" } }
    ],

    // 5. MODULE HỆ THỐNG
    '/system/roles.php': [
        { element: '.table', popover: { title: 'Danh sách Vai trò', description: 'Quản lý các nhóm quyền (Nhân sự, Kế toán, Admin) và phân quyền chi tiết.', side: "top" } }
    ],
    '/system/settings.php': [
        { element: '.nav-tabs', popover: { title: 'Cài đặt hệ thống', description: 'Cấu hình các tham số toàn cục như mức lương cơ sở, tỷ lệ bảo hiểm.', side: "bottom" } }
    ]
};

window.startProductTour = function(force = false) {
    try {
        let driverObj = null;
        if (window.driver && window.driver.js && window.driver.js.driver) {
            driverObj = window.driver.js.driver;
        } else {
            console.warn('Driver.js not found');
            if (force) alert('Thư viện hướng dẫn đang tải, vui lòng thử lại sau giây lát.');
            return;
        }

        const currentPath = window.location.pathname;
        let steps = [];

        // Luôn bắt đầu bằng hướng dẫn chung nếu force = true (người dùng bấm nút)
        if (force) {
            steps = [...tourConfigs['common']];
        }

        // Tìm config phù hợp với trang hiện tại
        let pageSpecificSteps = [];
        for (const path in tourConfigs) {
            if (path !== 'common' && currentPath.includes(path)) {
                // Kiểm tra xem element có tồn tại không trước khi add step
                // Để tránh driver.js lỗi nếu selector không tìm thấy
                const validSteps = tourConfigs[path].filter(step => {
                    return document.querySelector(step.element) !== null;
                });
                pageSpecificSteps = validSteps;
                break;
            }
        }

        // Nếu là lần đầu vào trang (auto) -> Chỉ hiện hướng dẫn riêng của trang đó
        // Nếu bấm nút (?) -> Hiện chung + riêng
        if (!force && pageSpecificSteps.length > 0) {
             steps = pageSpecificSteps;
        } else if (force) {
             steps = [...steps, ...pageSpecificSteps];
        }

        if (steps.length > 0) {
            const d = driverObj({
                showProgress: true,
                nextBtnText: 'Tiếp theo',
                prevBtnText: 'Quay lại',
                doneBtnText: 'Hoàn tất',
                steps: steps
            });

            // Logic check đã xem chưa (chỉ áp dụng cho auto-show)
            // Dùng key v3 để reset lại tour cho người dùng cũ
            if (!force) {
                const tourKey = 'tour_seen_v3_' + currentPath.replace(/\//g, '_'); 
                if (localStorage.getItem(tourKey)) return;
                localStorage.setItem(tourKey, 'true');
            }

            d.drive();
        } else if (force) {
            if (window.Toast) window.Toast.info('Trang này hiện chưa có hướng dẫn chi tiết.');
        }
    } catch (e) {
        console.error('Tour error:', e);
    }
};

$(document).ready(function() {
    // Delay nhẹ để đảm bảo DOM và Driver.js load xong
    setTimeout(() => {
        if (typeof startProductTour === 'function') startProductTour(false);
    }, 1500);
});
