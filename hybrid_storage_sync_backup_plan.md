# 📦 KẾ HOẠCH THIẾT KẾ HỆ THỐNG SYNC & BACKUP FILE (HYBRID STORAGE)

> Tài liệu này dùng để **AI Assistant / Dev / Admin** đọc và triển khai hệ thống lưu trữ phân tầng giữa **Hosting (Cloud)** và **Máy Local (PC Văn phòng, Laptop)**.

---

## 1️⃣ MỤC TIÊU HỆ THỐNG

- Website chạy **PHP + MySQL trên Hosting**.
- Hosting **KHÔNG phải nơi lưu file vĩnh viễn**.
- File chỉ lưu tạm trên Hosting để người dùng xem online.
- Sau **X ngày (mặc định 60 ngày)**:
  - File bị xóa vật lý trên Hosting.
  - Dữ liệu trong Database **KHÔNG bị xóa**.
- File được **tự động sao lưu vĩnh viễn** về **PC Local (máy bàn văn phòng là chính)**.
- Hệ thống hoạt động **ổn định với IP động**, không cần mở port.

---

## 2️⃣ KIẾN TRÚC TỔNG THỂ

```
[ Người dùng ]
      |
      v
[ Website - Hosting ]
 PHP + MySQL (DB chính)
      |
      |  (API Pull)
      v
[ Agent Local ]  --->  [ Ổ cứng PC Văn phòng ]
```

### Vai trò:
- **Hosting**: Điều phối, lưu DB, lưu file tạm.
- **Local Agent**: Chủ động kéo file về lưu trữ lâu dài.

---

## 3️⃣ THÀNH PHẦN HỆ THỐNG

### 3.1 Hosting (Cloud)

- PHP Backend
- MySQL Database
- Cronjob
- REST API cho Agent

### 3.2 Máy Local (PC / Laptop)

- Windows hoặc Linux
- Tool Agent chạy nền:
  - Windows: Task Scheduler / Service
  - Linux: Cron / systemd
- Không cần IP tĩnh

---

## 4️⃣ CẤU TRÚC DATABASE (ĐỀ XUẤT)

### 4.1 Bảng `files`

```sql
id
file_name
file_path          -- đường dẫn tạm (null nếu đã offline)
file_hash          -- SHA256
created_at
expired_at
storage_status     -- online | offline
stored_nodes       -- JSON: ["PC_VANPHONG", "LAPTOP_A"]
```

---

### 4.2 Bảng `storage_nodes`

```sql
id
node_name
node_type          -- primary | secondary
auth_token
last_heartbeat
status             -- online | offline
created_at
```

---

## 5️⃣ CƠ CHẾ AGENT LOCAL

### 5.1 Nguyên tắc

- Agent **LUÔN là bên chủ động**.
- Hosting **KHÔNG gọi ngược về Local**.
- IP Local động → không ảnh hưởng.

---

### 5.2 Các chức năng chính của Agent

1. Heartbeat (báo sống)
2. Kiểm tra file mới
3. Tải file
4. Verify hash
5. Lưu file
6. Báo kết quả về Hosting

---

### 5.3 Flow hoạt động Agent

```
[ Agent Start ]
      |
      v
Check Internet
      |
      v
Send Heartbeat
      |
      v
Request New Files
      |
      v
Download File
      |
      v
Verify Hash
      |
      v
Save to Disk
      |
      v
Confirm to Hosting
```

---

## 6️⃣ API TRÊN HOSTING (MÔ TẢ)

### 6.1 Heartbeat

```
POST /api/node/heartbeat
Headers: X-NODE-TOKEN
```

---

### 6.2 Lấy danh sách file cần sync

```
GET /api/files/pending?node=PC_VANPHONG
```

Response:
```json
[
  {
    "id": 12,
    "file_url": "https://site.com/uploads/tmp/a.pdf",
    "hash": "abc123"
  }
]
```

---

### 6.3 Xác nhận đã lưu file

```
POST /api/files/confirm
```

Body:
```json
{
  "file_id": 12,
  "node": "PC_VANPHONG"
}
```

---

## 7️⃣ CƠ CHẾ XÓA FILE TRÊN HOSTING

### 7.1 Cronjob (1 lần / ngày)

Điều kiện:
- File > 60 ngày
- Đã được lưu tại **ít nhất 1 node Local**

Hành động:
- Xóa file vật lý
- Cập nhật:
  - `storage_status = 'offline'`
  - `file_path = NULL`

---

## 8️⃣ TRUY CẬP FILE ĐÃ OFFLINE

### 8.1 Trong LAN
- Trỏ trực tiếp về Local Agent (localhost / LAN IP)

### 8.2 Ngoài LAN
- Hiển thị thông báo:
  > File đã được lưu trữ ngoại tuyến tại máy chủ nội bộ

---

## 9️⃣ XỬ LÝ IP ĐỘNG

- Không cần IP tĩnh
- Không cần NAT / Port Forward
- Agent chỉ cần **Internet Outbound**

---

## 10️⃣ AN TOÀN & BẢO MẬT

- Token riêng cho mỗi node
- HTTPS
- Verify file hash
- Không cho Agent xóa DB
- Không expose thư mục file

---

## 11️⃣ CÁC TÌNH HUỐNG ĐẶC BIỆT

### PC tắt / mất điện
- Hosting giữ file
- Khi PC bật lại → Agent sync tiếp

### Mất Internet
- Agent retry
- Không mất data

---

## 12️⃣ LỘ TRÌNH TRIỂN KHAI ĐỀ XUẤT

1. Chuẩn hóa DB
2. Viết API Hosting
3. Viết Agent Local
4. Setup cron cleanup
5. Test các case lỗi

---

## 13️⃣ KẾT LUẬN

- Mô hình phù hợp cho:
  - Website nhiều file
  - Hosting giới hạn dung lượng
  - Doanh nghiệp nhỏ – vừa
- Hệ thống ổn định, mở rộng được, không phụ thuộc IP

---
✦ Tổng kết quy trình vận hành hoàn chỉnh cho bạn:

   1. Hàng ngày: Bạn hoặc nhân viên nhập dữ liệu nhân sự, chấm công, lương trên Hosting bình thường.
   2. Mỗi khi bạn mở máy tính văn phòng (Máy A/B): Bạn chạy 2 tool trong thư mục tools/ (hoặc tôi có thể gộp chúng lại
      làm một). Dữ liệu và File sẽ được kéo về lưu trữ vĩnh viễn tại máy đó.
   3. Tự động trên Hosting: Cứ sau 60 ngày, script cron_cleanup_hosting.php sẽ tự dọn dẹp các file đã cũ (nhưng chỉ xóa
      nếu nó biết chắc chắn máy A hoặc B của bạn đã tải về rồi).

  Như vậy:
   - Bạn không lo mất dữ liệu (vì máy Local luôn giữ bản gốc).
   - Bạn không lo đầy hosting (vì nó tự dọn dẹp).
   - Bạn có thể xem lại dữ liệu cũ bất cứ lúc nào trên máy Local.

  Bạn có muốn tôi gộp 2 tool sync_from_cloud.php (DB) và file_sync_agent.php (File) thành 1 giao diện duy nhất để bạn
  chỉ cần bấm 1 nút là nó đồng bộ tất cả không?

📌 **Tài liệu này dùng làm input cho AI Assistant để code, review hoặc mở rộng hệ thống.**

