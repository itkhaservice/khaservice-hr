-- MIGRATION CHO HỆ THỐNG HYBRID STORAGE (SYNC FILE)

-- 1. Bảng quản lý các máy Local (Nodes)
CREATE TABLE IF NOT EXISTS `storage_nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_name` varchar(100) NOT NULL COMMENT 'Tên máy (VD: PC_VANPHONG)',
  `node_key` varchar(64) NOT NULL COMMENT 'Key định danh duy nhất',
  `auth_token` varchar(255) DEFAULT NULL COMMENT 'Token bảo mật trao đổi với API',
  `node_type` enum('primary','secondary') DEFAULT 'primary',
  `last_heartbeat` datetime DEFAULT NULL,
  `status` enum('online','offline') DEFAULT 'offline',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_node_key` (`node_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bổ sung cột cho bảng DOCUMENTS để quản lý trạng thái lưu trữ
-- storage_status: 'online' (trên hosting), 'offline' (đã xóa trên host, chỉ còn ở local), 'synced' (có cả 2)
ALTER TABLE `documents` 
ADD COLUMN `file_hash` varchar(64) DEFAULT NULL COMMENT 'SHA256 Hash để verify',
ADD COLUMN `storage_status` enum('online','synced','offline') DEFAULT 'online',
ADD COLUMN `stored_nodes_json` JSON DEFAULT NULL COMMENT 'Danh sách node đã lưu file này',
ADD COLUMN `synced_at` datetime DEFAULT NULL COMMENT 'Thời điểm hoàn tất sync xuống local chính';

-- Thêm Index để query nhanh file cần sync
ALTER TABLE `documents` ADD INDEX `idx_storage_status` (`storage_status`);
