-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 09:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `khaservice_hr_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `status` enum('working','completed','absent','leave','late','early') DEFAULT 'working',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `project_id`, `shift_id`, `date`, `check_in`, `check_out`, `status`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 37, 1, '2026-01-20', '2026-01-20 05:23:24', NULL, 'working', NULL, '2026-01-20 04:23:24', '2026-01-20 04:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `contract_number` varchar(50) DEFAULT NULL,
  `contract_type` enum('thu_viec','co_thoi_han','khong_thoi_han','khoan_viec') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `allowance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `description`) VALUES
(1, 'Ban Giám Đốc', 'BGD', NULL),
(3, 'Ban Kế toán', 'KT', NULL),
(6, 'Ban Vệ sinh - Tạp Vụ', 'VS', NULL),
(8, 'Ban Công nghệ thông tin', 'IT', NULL),
(14, 'Ban Kinh doanh', 'KD', NULL),
(15, 'Ban Nhân sự', 'NS', NULL),
(17, 'Ban Kỹ thuật', 'KYT', NULL),
(19, 'Ban Quản lý', 'BQL', NULL),
(20, 'Ban Cây xanh', 'CX', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `doc_type` varchar(50) NOT NULL COMMENT 'CCCD, HoKhau, SoYeuLyLich, BangCap, GiayKhamSucKhoe...',
  `doc_name` varchar(150) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_submitted` tinyint(1) DEFAULT 0,
  `approval_status` enum('approved','pending','rejected') DEFAULT 'approved',
  `expiry_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `employee_id`, `doc_type`, `doc_name`, `file_path`, `is_submitted`, `approval_status`, `expiry_date`, `note`, `created_at`) VALUES
(10, 2, 'CCCD', NULL, 'upload/documents/CAO_MINH_THANG_9061/CCCD_1768895517_471.jpg', 1, 'approved', '0000-00-00', NULL, '2026-01-20 07:51:57');

-- --------------------------------------------------------

--
-- Table structure for table `document_settings`
--

CREATE TABLE `document_settings` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `is_multiple` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_settings`
--

INSERT INTO `document_settings` (`id`, `code`, `name`, `is_required`, `is_multiple`, `created_at`) VALUES
(1, 'CCCD', 'Căn cước công dân', 1, 0, '2026-01-20 04:03:38'),
(2, 'HK', 'Hộ khẩu / Xác nhận cư trú', 1, 0, '2026-01-20 04:03:38'),
(3, 'SYLL', 'Sơ yếu lý lịch', 1, 0, '2026-01-20 04:03:38'),
(4, 'BC', 'Bằng cấp / Chứng chỉ', 0, 1, '2026-01-20 04:03:38'),
(5, 'GKSK', 'Giấy khám sức khỏe', 1, 0, '2026-01-20 04:03:38'),
(6, 'HDLD', 'Hợp đồng lao động', 1, 0, '2026-01-20 04:03:38'),
(7, 'BH', 'Hồ sơ bảo hiểm', 0, 0, '2026-01-20 04:03:38'),
(9, 'DXV', 'Đơn xin việc', 1, 0, '2026-01-20 05:08:59'),
(12, 'XNHK', 'Xác nhận hạnh kiểm', 0, 0, '2026-01-20 05:08:59'),
(13, 'CK', 'Cam kết nhân viên', 1, 0, '2026-01-20 05:08:59'),
(14, 'CKT', 'Cam kết thuế TNCN', 0, 0, '2026-01-20 05:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `gender` enum('Nam','N???','Kh??c') DEFAULT 'Nam',
  `dob` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `permanent_address` varchar(255) DEFAULT NULL COMMENT 'H??? kh???u th?????ng tr??',
  `identity_card` varchar(20) DEFAULT NULL COMMENT 'CCCD/CMND',
  `identity_date` date DEFAULT NULL,
  `identity_place` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `current_project_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL COMMENT 'Ch???c v???: T??? tr?????ng, K??? s??, NV...',
  `status` enum('working','resigned','maternity_leave','unpaid_leave') DEFAULT 'working',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `code`, `fullname`, `avatar`, `gender`, `dob`, `phone`, `email`, `address`, `permanent_address`, `identity_card`, `identity_date`, `identity_place`, `department_id`, `position_id`, `current_project_id`, `position`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 'NV001', 'Lê Hoàng Thái', NULL, 'Nam', '1996-11-14', '0349973754', 'thaishadow1411@gmail.com', NULL, NULL, '094096009060', NULL, NULL, 14, 23, 37, 'Nhân viên', 'working', '2025-01-01', NULL, '2026-01-20 03:52:17', '2026-01-20 04:16:46'),
(2, 'NV002', 'Cao Minh Thắng', NULL, 'Nam', '2000-06-25', '0376701749', 'thangminhcaoss@gmail.com', NULL, NULL, '094096009061', NULL, NULL, 8, 27, 13, 'Nhân viên', 'working', '2026-01-01', NULL, '2026-01-20 07:44:22', '2026-01-20 07:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `insurances`
--

CREATE TABLE `insurances` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `insurance_number` varchar(50) DEFAULT NULL,
  `bhxh_status` tinyint(1) DEFAULT 0 COMMENT '1: ???? tham gia, 0: Ch??a',
  `bhyt_status` tinyint(1) DEFAULT 0,
  `bhtn_status` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `hospital_registration` varchar(150) DEFAULT NULL COMMENT 'N??i ??K KCB ban ?????u',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `department_id`, `name`, `created_at`) VALUES
(17, 1, 'Tổng Giám Đốc', '2026-01-20 04:14:29'),
(18, 1, 'Phó Tổng Giám Đốc', '2026-01-20 04:14:29'),
(19, 3, 'Trưởng phòng Kế toán', '2026-01-20 04:14:29'),
(20, 3, 'Kế toán', '2026-01-20 04:14:29'),
(21, 3, 'Kế toán dự án', '2026-01-20 04:14:29'),
(22, 14, 'Trưởng phòng Kinh doanh', '2026-01-20 04:14:29'),
(23, 14, 'Nhân viên', '2026-01-20 04:14:29'),
(24, 15, 'Trưởng phòng Nhân sự', '2026-01-20 04:14:29'),
(25, 15, 'Nhân viên', '2026-01-20 04:14:29'),
(26, 8, 'Trưởng phòng IT', '2026-01-20 04:14:29'),
(27, 8, 'Nhân viên', '2026-01-20 04:14:29'),
(28, 17, 'Kỹ sư trưởng', '2026-01-20 04:14:29'),
(29, 17, 'Tổ trưởng Kỹ thuật', '2026-01-20 04:14:29'),
(30, 17, 'Nhân viên', '2026-01-20 04:14:29'),
(31, 6, 'Tổ trưởng vệ sinh - tạp vụ', '2026-01-20 04:14:29'),
(32, 6, 'Nhân viên', '2026-01-20 04:14:29'),
(33, 19, 'Trưởng Ban quản lý', '2026-01-20 05:16:38'),
(34, 19, 'Phó Ban quản lý', '2026-01-20 05:16:46'),
(35, 19, 'Giám sát dịch vụ', '2026-01-20 05:16:53'),
(36, 20, 'Nhân viên', '2026-01-20 06:29:30');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `stt` int(11) DEFAULT 0,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL COMMENT 'ID of the project manager (user_id)',
  `headcount_required` int(11) DEFAULT 0 COMMENT 'Định biên nhân sự cần thiết',
  `status` enum('active','completed','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `stt`, `name`, `code`, `address`, `manager_id`, `headcount_required`, `status`, `created_at`) VALUES
(1, 0, '4S RIVERSIDE GARDEN', 'DA4SRS', '75/15 Đường số 17 Khu Phố 3, Phường Hiệp Bình, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(2, 0, 'CANTAVIL PREMIER', 'DACTVPRM', 'Số 1 Song Hành, Phường Bình Trưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(3, 0, 'CITIZEN.TS', 'DACTZ', 'Đường số 9A Khu dân cư Trung Sơn, Phường Bình Đông, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(4, 0, 'CITRINE APARTMENT', 'DACTP', '127 Tăng Nhơn Phú, Phường Phước Long, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(5, 0, 'COPAC SQUARE', 'DACPSQ', '12 Tôn Đản, Phường Xóm Chiếu, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(6, 0, 'FLORA ANH ĐÀO', 'DAFLRAD', '619 Đỗ Xuân Hợp, Phường Phước Long, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(7, 0, 'FLORA KIKYO', 'DAFLRKKO', 'Tổ 9 Khu Phố 2, Phường Phú Thuận, TP.HCM', NULL, 0, 'pending', '2026-01-20 02:53:58'),
(8, 0, 'HOÀNG ANH GIA LAI 2', 'DAHAGL2', '769-783 Trần Xuân Soạn, Phường Tân Hưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(9, 0, 'HOMYLAND 2', 'DAHML2', '307 Đường Nguyễn Duy Trinh, Phường Bình Trưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(10, 0, 'HORIZON', 'DAHRZ', '214 Trần Quang Khải, Phường Tân Định, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(11, 0, 'HƯNG PHÁT', 'DAHP1', '928 Lê Văn Lương, Xã Nhà Bè, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(12, 0, 'HƯNG PHÁT SILVER STAR', 'DAHP2', '156A Nguyễn Hữu Thọ, Xã Nhà Bè, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(13, 0, 'KHÁNH HỘI 1', 'DAKH1', '360C Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(14, 0, 'KHÁNH HỘI 2', 'DAKH2', '360A Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(15, 0, 'KHÁNH HỘI 3', 'DAKH3', '360G Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(16, 0, 'LAN PHƯƠNG MHBR', 'DALPMHBR', '104 đường Hồ Văn Tư, Phường Trường Thọ, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(17, 0, 'LÔ R7 AN KHÁNH', 'DAR7AK', '23 Lưu Đình Lễ, Phường An Khánh, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(18, 0, 'NHẤT LAN II', 'DANL2', 'Đường 54A, Phường Tân Tạo, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(19, 0, 'ORIENT APARTMENT', 'DAORE', '331 Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(20, 0, 'PHỐ GIA PHÚC', 'DAPGP', '94 Tô Vĩnh Diện, Phường Thủ Đức, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(21, 0, 'PHÚ GIA', 'DAPGA', 'Khu dân cư Phú Gia, Xã Nhà Bè, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(22, 0, 'SAI GON METRO PARK', 'DASGMT', 'Đường số 1, Phường Thủ Đức, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(23, 0, 'SAMSORA RIVERSIDE', 'DASSRRS', '207A Quốc lộ 1A Khu phố Quyết Thắng, Phường Dĩ An, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(24, 0, 'SCREC II', 'DASCRII', 'Đường số 4 Khu Đô thị mới, Phường Bình Trưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(25, 0, 'SEN HỒNG A', 'DASHA', 'Khu phố Bình Đường 3, Phường Dĩ An, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(26, 0, 'SEN HỒNG BC', 'DASHBC', 'Khu phố Bình Đường 3, Phường Dĩ An, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(27, 0, 'SÔNG ĐÀ', 'DASDTW', '14B Kỳ Đồng, Phường Nhiêu Lộc, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(28, 0, 'TAM PHÚ', 'DASVTP', '1A-1B Đường Cây Keo, Phường Tam Bình, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(29, 0, 'TDH - PHƯỚC LONG', 'DATDHPL', 'Đường 672, Phường Phước Long, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(30, 0, 'THE STAR', 'DATS', '1123 Quốc Lộ 1A, Phường Tân Tạo, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(31, 0, 'THE USEFUL APARTMENT', 'DAUFAP', '654/06 Lạc Long Quân, Phường Tân Hòa, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(32, 0, 'TOPAZ CITY KHỐI B', 'DATPCT', '39 Cao Lỗ, Phường Chánh Hưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(33, 0, 'TOPAZ ELITE PHOENIX 1', 'DATPEP1', '547-549 Tạ Quang Bửu, Phường Chánh Hưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(34, 0, 'TOPAZ ELITE PHOENIX 2', 'DATPEP2', '37 Cao Lỗ, Phường Chánh Hưng, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(35, 0, 'TOPAZ HOME 2 - BLOCK B', 'DATPH2', '215 Đường số 138, Phường Tăng Nhơn Phú, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(36, 0, 'VẠN ĐÔ', 'DAVDA', '348 Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58'),
(37, 0, 'VĂN PHÒNG CÔNG TY', 'VPC', '360C Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM', NULL, 0, 'active', '2026-01-20 02:53:58');

-- --------------------------------------------------------

--
-- Table structure for table `project_positions`
--

CREATE TABLE `project_positions` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_name` varchar(100) NOT NULL COMMENT 'Lưu tên chức vụ để linh hoạt hoặc join với bảng positions',
  `count_required` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_positions`
--

INSERT INTO `project_positions` (`id`, `project_id`, `department_id`, `position_name`, `count_required`, `created_at`) VALUES
(1, 13, NULL, 'Trưởng Ban quản lý', 1, '2026-01-20 05:17:20'),
(3, 37, 8, 'Trưởng phòng IT', 1, '2026-01-20 08:15:01');

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL COMMENT 'Ng?????i t???o ????? xu???t',
  `project_id` int(11) DEFAULT NULL,
  `type` enum('leave','shift_change','personnel','overtime','other') NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'company_name', 'CÈNG TY TNHH KHASERVICE'),
(2, 'company_address', 'Tầng 1, Khu Thương Mại, 360C Bến Vân Đồn, Phường Vĩnh Hội, TP.HCM'),
(3, 'admin_email', 'admin@khaservice.vn'),
(4, 'system_version', '1.0.0'),
(8, 'company_phone', '02838253041'),
(9, 'company_website', 'https://khaservice.com.vn/');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL COMMENT 'NULL for global shifts (office), specific ID for project shifts',
  `name` varchar(100) NOT NULL COMMENT 'Ca H??nh ch??nh, Ca S??ng, Ca ????m...',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `type` enum('office','8h','12h','24h') NOT NULL DEFAULT '8h',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `project_id`, `name`, `start_time`, `end_time`, `type`, `description`) VALUES
(1, 37, 'Ca hành chính', '08:00:00', '17:00:00', '8h', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'staff',
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `username`, `password`, `fullname`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'admin', '$2y$10$GzcqUQwxLKC/wxCJP.g2l./Wi1GFc6mZXmhSlndw7iafRNSvmdLYe', 'Administrator', NULL, 'admin', 1, '2026-01-20 03:06:52', '2026-01-20 04:31:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `document_settings`
--
ALTER TABLE `document_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `identity_card` (`identity_card`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `current_project_id` (`current_project_id`);

--
-- Indexes for table `insurances`
--
ALTER TABLE `insurances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `project_positions`
--
ALTER TABLE `project_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_proj_pos` (`project_id`,`position_name`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_employees` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `document_settings`
--
ALTER TABLE `document_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `insurances`
--
ALTER TABLE `insurances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `project_positions`
--
ALTER TABLE `project_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_employees` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
