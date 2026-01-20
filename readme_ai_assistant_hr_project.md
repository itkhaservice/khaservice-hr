# README – AI Assistant Code Context

## 1. Mục tiêu dự án
Xây dựng hệ thống **quản lý nhân sự – vận hành theo dự án**, phục vụ môi trường chung cư / toà nhà / dự án kỹ thuật, với trọng tâm:
- Quản lý hồ sơ – giấy tờ nhân sự
- Quản lý hợp đồng & bảo hiểm
- Quản lý ca làm việc theo dự án
- Theo dõi **trạng thái làm việc thực tế (vào ca – ra ca)**
- Tổng hợp báo cáo theo **dự án** và **bộ phận**

> ⚠️ Không theo dõi việc ra/vào nhiều lần trong ngày, chỉ ghi nhận **1 lần vào ca – 1 lần ra ca**.

---

## 2. Phạm vi nghiệp vụ

### 2.1 Đối tượng nhân sự
- Nhân viên hành chính
- Nhân viên kỹ thuật
- Nhân viên bảo vệ
- Nhân viên tạp vụ / hồ bơi

### 2.2 Loại hình ca làm việc

#### 🔹 Ca hành chính cố định
- Thời gian: **08:00 – 17:00**
- Áp dụng cho:
  - Tổ trưởng kỹ thuật
  - Kỹ sư
  - Trưởng ban
  - Kế toán
  - Lễ tân

#### 🔹 Ca 12 tiếng / 24 tiếng (theo dự án)
- Áp dụng cho:
  - Nhân viên kỹ thuật
  - Nhân viên bảo vệ
- Thời gian giao ca:
  - Cấu hình **tuỳ từng dự án**
  - Ví dụ: 06:00–18:00, 18:00–06:00

#### 🔹 Ca 8 tiếng (theo dự án)
- Áp dụng cho:
  - Nhân viên tạp vụ / hồ bơi

---

## 3. Trạng thái làm việc (WORK STATUS)

Mỗi nhân viên – mỗi ngày chỉ có **1 trạng thái chính**:
- 🟢 Đang làm việc
- 🔵 Đã vào ca
- 🔴 Đã ra ca
- ⚪ Nghỉ / không đi làm
- 🟡 Nghỉ phép / nghỉ bù

> Trạng thái được xác định dựa trên **giờ vào ca – giờ ra ca** so với ca chuẩn.

---

## 4. Quản lý hồ sơ nhân sự

### 4.1 Hồ sơ bắt buộc
- CCCD
- Hộ khẩu / xác nhận cư trú
- Sơ yếu lý lịch
- Bằng cấp / chứng chỉ
- Hợp đồng lao động
- Bảo hiểm (BHXH, BHYT, BHTN)

### 4.2 Trạng thái hồ sơ
- ✅ Đủ hồ sơ
- ⚠️ Thiếu hồ sơ
- ⛔ Hồ sơ hết hạn

### 4.3 Yêu cầu báo cáo
- Danh sách nhân viên **chưa thu đủ hồ sơ**
- Chi tiết **thiếu giấy tờ gì**

---

## 5. Quản lý hợp đồng & bảo hiểm

### 5.1 Hợp đồng
- Loại hợp đồng
- Ngày bắt đầu – ngày kết thúc
- Trạng thái:
  - Còn hạn
  - Sắp hết hạn
  - Hết hạn

### 5.2 Bảo hiểm
- Đã tham gia / chưa tham gia
- Thời gian đóng
- Trạng thái hiệu lực

---

## 6. Quản lý dự án

Mỗi dự án bao gồm:
- Thông tin dự án
- Danh sách nhân sự tham gia
- Cấu hình ca làm việc riêng

### 6.1 Đề xuất theo dự án
- Đề xuất nhân sự
- Đề xuất thay ca
- Đề xuất bổ sung người
- Đề xuất vận hành

### 6.2 Báo cáo
- Tổng hợp đề xuất **theo dự án**
- Tổng hợp đề xuất **theo bộ phận**

---

## 7. Định hướng kỹ thuật cho AI Assistant

AI Assistant cần:
- Hiểu rõ **logic ca làm việc theo dự án**
- Không suy diễn "ra/vào nhiều lần"
- Ưu tiên dữ liệu:
  - Nhân viên
  - Dự án
  - Ca làm việc
  - Trạng thái ngày

### 7.1 Chức năng AI hỗ trợ
- Sinh code CRUD cho các module
- Gợi ý database schema
- Viết API theo nghiệp vụ
- Sinh báo cáo SQL / API
- Đề xuất nâng cấp tính năng

---

## 8. Khả năng mở rộng trong tương lai

- Chấm công bằng QR / thẻ / mobile app
- Phê duyệt nghỉ phép
- Tính lương theo ca & OT
- Cảnh báo tự động (hồ sơ, hợp đồng)
- Dashboard realtime cho ban quản lý

---

## 9. Nguyên tắc quan trọng

- Không hard-code ca làm việc
- Ca làm việc **phải gắn với dự án**
- Dữ liệu rõ ràng – không dư thừa
- Ưu tiên vận hành thực tế hơn lý thuyết

---

## 10. Ghi chú cho AI Code Generator

> Đây là hệ thống **vận hành thật**, không phải demo học tập.
> Mọi logic cần đơn giản, dễ bảo trì, dễ mở rộng.

