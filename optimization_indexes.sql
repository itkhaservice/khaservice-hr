-- Tối ưu hóa Database cho Khaservice HR
-- Chạy file này trên phpMyAdmin

-- 1. Index cho bảng Attendance (Chấm công)
-- Giúp query theo Project+Date nhanh hơn (Dùng cho bảng công)
ALTER TABLE `attendance` ADD INDEX `idx_proj_date` (`project_id`, `date`);
-- Giúp query theo Employee+Date nhanh hơn (Dùng cho tính lương)
ALTER TABLE `attendance` ADD INDEX `idx_emp_date` (`employee_id`, `date`);

-- 2. Index cho bảng Payroll (Lương)
-- Giúp check lương tháng nhanh
ALTER TABLE `payroll` ADD INDEX `idx_emp_month_year` (`employee_id`, `month`, `year`);
-- Giúp list lương theo dự án nhanh
ALTER TABLE `payroll` ADD INDEX `idx_proj_month_year` (`project_id`, `month`, `year`);

-- 3. Index cho bảng Employees
-- Giúp lọc nhân viên theo dự án nhanh
ALTER TABLE `employees` ADD INDEX `idx_curr_proj_status` (`current_project_id`, `status`);
