-- ========================================================
-- Link Checker - Database Schema & Initial Data
-- CSDL: link_checker
-- ========================================================

CREATE DATABASE IF NOT EXISTS `link_checker` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `link_checker`;

-- 1. Bảng users - Quản lý người dùng
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50)  NOT NULL UNIQUE,
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng sites - Các website cần kiểm tra
CREATE TABLE IF NOT EXISTS `sites` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `name`            VARCHAR(200) NOT NULL,
    `seed_url`        VARCHAR(2048) NOT NULL,
    `allowed_domains` TEXT NOT NULL COMMENT 'JSON array of allowed domains',
    `created_by`      INT NOT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng scan_runs - Mỗi lần chạy kiểm tra
CREATE TABLE IF NOT EXISTS `scan_runs` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `site_id`         INT NOT NULL,
    `status`          ENUM('pending', 'running', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `max_pages`       INT NOT NULL DEFAULT 100,
    `max_depth`       INT NOT NULL DEFAULT 5,
    `timeout`         INT NOT NULL DEFAULT 30 COMMENT 'Timeout per request in seconds',
    `max_total_time`  INT NOT NULL DEFAULT 300 COMMENT 'Max total scan time in seconds',
    `pages_scanned`   INT NOT NULL DEFAULT 0,
    `links_checked`   INT NOT NULL DEFAULT 0,
    `errors_found`    INT NOT NULL DEFAULT 0,
    `started_at`      TIMESTAMP NULL,
    `finished_at`     TIMESTAMP NULL,
    `created_by`      INT NOT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng pages - Các trang đã duyệt
CREATE TABLE IF NOT EXISTS `pages` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `scan_id`     INT NOT NULL,
    `url`         VARCHAR(2048) NOT NULL,
    `http_status` INT NULL COMMENT 'HTTP status code or NULL if not reached',
    `content_type`VARCHAR(100) NULL,
    `depth`       INT NOT NULL DEFAULT 0,
    `status`      ENUM('pending', 'scanned', 'error', 'skipped', 'timeout', 'out_of_scope') NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `scanned_at`  TIMESTAMP NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`scan_id`) REFERENCES `scan_runs`(`id`) ON DELETE CASCADE,
    INDEX `idx_pages_scan_url` (`scan_id`, `url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng links - Các liên kết tìm thấy
CREATE TABLE IF NOT EXISTS `links` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `scan_id`       INT NOT NULL,
    `source_page_id`INT NOT NULL,
    `target_url`    VARCHAR(2048) NOT NULL,
    `link_type`     ENUM('anchor', 'image', 'stylesheet', 'script', 'other') NOT NULL DEFAULT 'anchor',
    `link_text`     VARCHAR(500) NULL COMMENT 'Text or alt attribute',
    `html_element`  VARCHAR(50) NULL COMMENT 'Tag name: a, img, link, etc.',
    `line_in_source`INT NULL COMMENT 'Line number in source HTML',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`scan_id`) REFERENCES `scan_runs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`source_page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_links_target` (`scan_id`, `target_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng findings - Kết quả kiểm tra (lỗi phát hiện)
CREATE TABLE IF NOT EXISTS `findings` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `scan_id`       INT NOT NULL,
    `link_id`       INT NULL,
    `target_url`    VARCHAR(2048) NOT NULL,
    `error_type`    ENUM('http_error', 'broken_image', 'missing_anchor', 'timeout', 'connection_error', 'redirect_out_of_scope', 'unknown') NOT NULL,
    `http_status`   INT NULL,
    `final_url`     VARCHAR(2048) NULL COMMENT 'URL after redirects',
    `message`       TEXT NULL,
    `is_resolved`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Marked as resolved in re-check',
    `checked_at`    TIMESTAMP NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`scan_id`) REFERENCES `scan_runs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`link_id`) REFERENCES `links`(`id`) ON DELETE SET NULL,
    INDEX `idx_findings_scan` (`scan_id`, `error_type`),
    INDEX `idx_findings_target` (`target_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Dữ liệu mẫu ban đầu (Seeding)
-- Mật khẩu mặc định cho các tài khoản là: password123
-- ========================================================

INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$afAF0rgapK9E82QsOVL7ieKDWU1vrgmwmiqh8Dula36JWJazbJVaC', 'admin'),
(2, 'user1', 'user@example.com', '$2y$10$afAF0rgapK9E82QsOVL7ieKDWU1vrgmwmiqh8Dula36JWJazbJVaC', 'user'),
(3, 'tester', 'tester@example.com', '$2y$10$afAF0rgapK9E82QsOVL7ieKDWU1vrgmwmiqh8Dula36JWJazbJVaC', 'user');

INSERT IGNORE INTO `sites` (`id`, `name`, `seed_url`, `allowed_domains`, `created_by`) VALUES
(1, 'Website Thu Nghiem', 'http://localhost:8888', '["localhost:8888"]', 1);

