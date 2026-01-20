# 🎨 Tài liệu Quy chuẩn Thiết kế Front-end (UI/UX) - Project KHASERVICE-IT

Tài liệu này đúc kết toàn bộ ngôn ngữ thiết kế, cấu trúc UI và trải nghiệm người dùng (UX) của hệ thống KHASERVICE-IT.

---

## 1. Hệ thống nhận diện (Design System)

### 1.1. Bảng màu (Color Palette)
Dự án sử dụng tone màu xanh lá làm chủ đạo, gợi cảm giác an toàn và chuyên nghiệp cho hệ thống quản trị.

*   **Primary (Chủ đạo):**
    *   Base: `#24a25c`
    *   Gradient: `linear-gradient(135deg, #24a25c 0%, #1b7a43 100%)`
    *   Hover: `#1b7a43`
    *   Background Active: `#f0fdf4`
*   **Neutral (Trung tính):**
    *   Text Chính: `#0f172a` (Slate 900)
    *   Text Phụ: `#64748b` (Slate 500)
    *   Nền (Background): `#f1f5f9` (Slate 100)
    *   Viền (Border): `#cbd5e1` (Slate 300)
*   **Status (Trạng thái):**
    *   **Success:** `#166534` (Text) / `#dcfce7` (Bg)
    *   **Error/Danger:** `#991b1b` (Text) / `#fee2e2` (Bg)
    *   **Warning:** `#92400e` (Text) / `#fffbeb` (Bg)
    *   **Info:** `#1e40af` (Text) / `#dbeafe` (Bg)

### 1.2. Typography (Phông chữ)
*   **Font Family:** `'Inter', 'Segoe UI', sans-serif` (Ưu tiên Inter để giao diện hiện đại).
*   **Base Size:** `14px` (Phù hợp cho các bảng dữ liệu dày đặc).
*   **Line Height:** `1.5`.

---

## 2. Cấu trúc Layout (Bố cục)

### 2.1. Header (Sticky)
*   Cố định ở trên cùng (`sticky top: 0`).
*   Độ cao: `65px`.
*   Bóng đổ: `0 2px 8px rgba(0,0,0,0.06)`.
*   **Mobile:** Nút Hamburger bên trái, logo ở giữa, thông tin user thu gọn bên phải.

### 2.2. Navigation (Điều hướng)
*   **Desktop:** Nằm ngang, căn giữa. Link active có background xanh nhạt và chữ xanh đậm.
*   **Mobile:** Menu trượt từ bên trái (Drawer/Sidebar), chiều rộng `280px`.

### 2.3. Footer
*   Thiết kế tối giản (Minimalist).
*   Phần bên trái: Logo + Slogan.
*   Phần bên phải: Bản quyền & Thông tin phòng ban.

---

## 3. Các thành phần chính (Core Components)

### 3.1. Bảng dữ liệu (Content Table)
*   **Container:** Luôn bao ngoài bởi `.table-container` có `overflow-x: auto`.
*   **Header:** Nền `#f8fafc`, chữ in hoa, đậm.
*   **Row:** Hover đổi màu nền thành `#f1f5f9`.
*   **Actions:** Các nút thao tác dạng icon (`.btn-icon`) để tiết kiệm diện tích.
*   **Responsive:** Ép ảnh và bảng con không quá `100%` bề ngang.

### 3.2. Bộ lọc hiện đại (Filter Section)
*   Nằm trong `.card` với viền trái màu xanh đậm làm điểm nhấn.
*   **Tương tác:** Dropdown tự động submit form khi thay đổi giá trị.
*   **Tính năng nâng cao:**
    *   **Column Selector:** Chọn cột hiển thị qua checkbox (Lưu trạng thái vào LocalStorage).
    *   **Quick Search:** Tìm kiếm nhanh có gợi ý ngay dưới ô input.

### 3.3. Phân trang (Pagination)
*   Luôn nằm dưới cùng của bảng.
*   **Bên trái:** Chọn số dòng hiển thị (5, 10, 25, 50, 100).
*   **Bên phải:** Các nút số trang. Nút hiện tại có màu Gradient xanh và bóng đổ.

### 3.4. Badges (Nhãn trạng thái)
*   Bo góc tròn (`border-radius: 20px`).
*   Chữ in hoa nhỏ (`font-size: 0.7rem`), đậm.

---

## 4. Trải nghiệm người dùng (UX & Effects)

### 4.1. Hiệu ứng phản hồi (Feedback)
*   **Spinner:** Khi click nút Lưu hoặc đổi trang, hiện overlay xoay tròn để báo hiệu đang tải.
*   **Toast:** Thông báo kết quả (Thành công/Lỗi) trượt từ góc phải, tự ẩn sau 4 giây.
*   **Audio:** Phát âm thanh nhẹ nhàng khi có thông báo (Success, Error, Info).

### 4.2. Modal xác nhận
*   Không dùng `confirm()` mặc định của trình duyệt.
*   Dùng Modal thiết kế riêng với Icon cảnh báo to, giúp người dùng tập trung vào hành động quan trọng (Xóa/Gửi dữ liệu).

### 4.3. Biểu mẫu (Forms)
*   Grid 2 cột cho Desktop, 1 cột cho Mobile.
*   Input focus: Có vòng sáng màu xanh nhạt bao quanh (`box-shadow`).
*   Nút Lưu/Hủy: Thường nằm ở góc trên bên phải hoặc cuối form, phân biệt rõ màu Primary và Secondary.

---

## 5. CSS Core Variables (Để copy)

```css
:root {
    --primary-color: #24a25c;
    --primary-light-color: #4dc581;
    --primary-dark-color: #1b7a43;
    --gradient-primary: linear-gradient(135deg, #24a25c 0%, #1b7a43 100%);
    --secondary-color: #475569;
    --text-color: #0f172a;
    --background-color: #f1f5f9;
    --card-bg: #ffffff;
    --border-color: #cbd5e1;
    --border-radius-base: 6px;
    --border-radius-card: 12px;
}
```

---

## 6. Quy tắc Responsive (Media Queries)
*   **> 1100px:** Desktop (Menu ngang).
*   **768px - 1100px:** Tablet (Menu Hamburger, Filter 2 cột).
*   **< 768px:** Mobile (Filter 1 cột, Table cuộn ngang, Nút hành động dàn hàng dọc).
