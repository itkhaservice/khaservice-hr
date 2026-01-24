-- MIGRATION SCRIPT FOR PRODUCTION (Run this in PHPMyAdmin on Hosting)

-- 1. Cập nhật bảng Payroll (Lương)
-- Thêm cột project_id để lưu lịch sử dự án khi tính lương
ALTER TABLE `payroll` ADD COLUMN `project_id` INT(11) DEFAULT 0 AFTER `employee_id`;
ALTER TABLE `payroll` ADD INDEX (`project_id`);

-- 2. Tạo bảng Khóa sổ chấm công (nếu chưa có)
CREATE TABLE IF NOT EXISTS `attendance_locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `locked_by` int(11) DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lock` (`project_id`,`month`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Xóa các cài đặt dư thừa (nếu có)
DELETE FROM `settings` WHERE `setting_key` = 'union_fee_amount';

-- 4. Thêm các cài đặt mới cho Chấm công & Phép (Dùng INSERT IGNORE để không lỗi nếu đã có)
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES 
('attendance_weekly_off', ''),          -- Ngày nghỉ tuần (Mặc định chỉ CN, nếu '6' là thêm Thứ 7)
('leave_monthly_accrual', '1.0'),       -- Định mức phép/tháng
('salary_union_fee_default', '54000');  -- Đoàn phí mặc định

-- 5. Cập nhật lại các giá trị mặc định cho Bảo hiểm (nếu chưa có)
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES 
('insurance_bhxh_percent', '8'),
('insurance_bhyt_percent', '1.5'),
('insurance_bhtn_percent', '1');