# Tài liệu Hướng dẫn Chức năng (Product Tour Guide)
## Dự án: Khaservice HRMS (PHP/MySQL)

Tài liệu này phân tích chuyên sâu các module của hệ thống để làm kịch bản cho tính năng **Product Tour** (hướng dẫn từng bước cho người dùng mới).

---

### 1. Module: Quản lý Nhân sự (Employees)
**Mục tiêu:** Quản lý vòng đời nhân viên và tính pháp lý của hồ sơ.

*   **Tính năng cốt lõi:**
    *   **Bộ lọc trạng thái hồ sơ:** Phân loại nhân viên đã nộp "Đủ" hoặc "Thiếu" chứng từ (CCCD, SYLL, GKSK...).
    *   **Cấu trúc thư mục File Server:** Tự động tổ chức folder theo format `HO_TEN_4_SO_CUOI_CCCD` để lưu trữ bản scan hồ sơ.
    *   **Import Python:** Sử dụng script Python để xử lý dữ liệu Excel phức tạp, đảm bảo chuẩn hóa mã phòng ban/dự án.
*   **Kịch bản Tour (Steps):**
    1.  `Selector: .action-header .btn-primary` -> "Thêm nhân viên mới tại đây."
    2.  `Selector: select[name="doc_status"]` -> "Lọc nhanh những nhân viên còn thiếu hồ sơ để đốc thúc bổ sung."
    3.  `Selector: .table td .badge-warning` -> "Badge này cho biết số lượng giấy tờ còn thiếu của nhân sự."
    4.  `Selector: a[title="Hồ sơ"]` -> "Vào đây để tải lên, xem bản scan hoặc duyệt tính hợp lệ của hồ sơ."

---

### 2. Module: Bảng Chấm công (Attendance)
**Mục tiêu:** Ghi nhận công và tăng ca với trải nghiệm giống Excel.

*   **Tính năng cốt lõi:**
    *   **Excel-like Interaction:** Hỗ trợ kéo chọn (drag select) nhiều ô để nhập ký hiệu `X`, `P`, `OF` hàng loạt.
    *   **Crosshair Hover:** Đường kẻ chữ thập màu xanh dương giúp định vị hàng (nhân viên) và cột (ngày) cực kỳ chính xác trên bảng lớn.
    *   **Gán dự án tăng cường (Cross-Project):** Chuột phải vào ô tăng ca để chọn dự án được hỗ trợ (phục vụ tính chi phí hỗ trợ sau này).
    *   **Khóa dữ liệu:** Chặn sửa đổi sau khi đã chốt công tính lương.
*   **Kịch bản Tour (Steps):**
    1.  `Selector: .attendance-table` -> "Nhấn và kéo chuột để chọn nhiều ngày công cùng lúc."
    2.  `Selector: .att-input.ot` -> "Nhập số giờ tăng ca vào ô dưới. Nhấp chuột phải để gán dự án hỗ trợ."
    3.  `Selector: .btn-fullscreen` -> "Mở toàn màn hình để có không gian làm việc rộng rãi nhất."
    4.  `Selector: button[onclick="saveAttendance()"]` -> "Hệ thống không tự lưu, hãy nhấn nút này sau khi hoàn tất chỉnh sửa."

---

### 3. Module: Tiền lương (Salary)
**Mục tiêu:** Tính toán thu nhập dựa trên công thực tế và lịch sử dự án.

*   **Tính năng cốt lõi:**
    *   **Cấu hình biến động:** Nhập thưởng, tạm ứng, đoàn phí linh hoạt từng tháng.
    *   **Bảo toàn lịch sử (Snapshot):** Lưu `project_id` vào bảng lương tại thời điểm tính để giữ đúng dữ liệu ngay cả khi nhân viên chuyển dự án.
    *   **Công thức tự động:** Tính lương dựa trên đơn giá ngày công (`Lương khoán / Công chuẩn`).
*   **Kịch bản Tour (Steps):**
    1.  `Selector: a[href*="config.php"]` -> "Thiết lập thưởng, phạt và tạm ứng cho nhân viên trước khi tính lương."
    2.  `Selector: button[name="calculate_payroll"]` -> "Nhấn 'Tính chi tiết' để hệ thống quét bảng công và xuất bảng lương."
    3.  `Selector: .table td:last-child` -> "Cột Thực lĩnh sẽ tự động trừ các khoản bảo hiểm, thuế và tạm ứng."

---

### 4. Module: Báo cáo (Reports)
**Mục tiêu:** Cung cấp cái nhìn tổng thể về định biên và nhân lực.

*   **Tính năng cốt lõi:**
    *   **Ma trận Cơ cấu:** Đối soát Thực tế vs Định biên theo từng vị trí (Ví dụ: Định biên cần 5 Bảo vệ, thực tế 4 -> Cảnh báo thiếu).
    *   **Leave Balance:** Theo dõi tổng hợp quỹ phép năm, đã nghỉ và còn lại của toàn dự án.
*   **Kịch bản Tour (Steps):**
    1.  `Selector: .dept-grid` -> "Theo dõi cơ cấu nhân sự theo từng phòng ban."
    2.  `Selector: .row-invalid` (trong ma trận) -> "Các ô màu đỏ chỉ ra vị trí đang thiếu người so with kế hoạch."
    3.  `Selector: .note-box` -> "Danh sách tổng hợp các trường hợp thiếu hồ sơ cần xử lý gấp."

---

### 5. Module: Hệ thống & Phân quyền (System)
**Mục tiêu:** Bảo mật dữ liệu theo dự án và vai trò.

*   **Tính năng cốt lõi:**
    *   **RBAC (Role-Based Access Control):** Phân quyền chi tiết theo Module (Xem/Sửa/Khóa).
    *   **Project Isolation:** Quản lý dự án A chỉ thấy nhân viên và bảng lương của dự án A.
*   **Kịch bản Tour (Steps):**
    1.  `Selector: .role-list` -> "Danh sách các vai trò trong hệ thống (Nhân sự, Kế toán, Admin)."
    2.  `Selector: .perm-group` -> "Tích chọn các quyền chi tiết cho từng vai trò."

---

### Ghi chú Kỹ thuật cho Developer (Future Update)
*   **Thư viện đề xuất:** [Driver.js](https://driverjs.com/) (Nhẹ, ổn định, không phụ thuộc jQuery/Bootstrap).
*   **Cách triển khai:**
    1. Thêm `driver.js` và `driver.css` vào `includes/header.php`.
    2. Khởi tạo tour trong `assets/js/main.js` hoặc file JS riêng biệt.
    3. Sử dụng `localStorage` để đánh dấu người dùng đã xem tour, tránh hiện lại nhiều lần.

---
*Tài liệu được khởi tạo ngày 23/01/2026. Sẽ cập nhật khi có tính năng mới.*
