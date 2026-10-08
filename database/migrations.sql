-- database/migrations.sql
-- Idempotent schema updates, applied on every container start (see docker-entrypoint.sh)
-- and safe to run by hand on an existing database:
--   mysql -u root -p nuahseasonalapp_db < database/migrations.sql

-- Notification log read by admin analytics / history / CSV export.
CREATE TABLE IF NOT EXISTS `notifications_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `recipient_group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `override` tinyint(1) NOT NULL DEFAULT '0',
  `sent_by` int DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recipient_group` (`recipient_group`),
  KEY `sent_at` (`sent_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-user activity trail used by the admin dashboard, analytics and staff actions.
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff approve/reject writes 'approved' / 'rejected' (and the dashboard counts 'pending'),
-- which the original enum did not allow; under MariaDB strict mode those updates failed.
-- Only altered while the enum still lacks those values, so the table isn't rebuilt every boot.
SET @nuahn_need_enum := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'applications'
     AND COLUMN_NAME = 'status' AND COLUMN_TYPE NOT LIKE '%approved%'
);
SET @nuahn_sql := IF(@nuahn_need_enum > 0,
  "ALTER TABLE `applications` MODIFY `status` enum('applied','accepted','completed','pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'applied'",
  'DO 0');
PREPARE nuahn_stmt FROM @nuahn_sql;
EXECUTE nuahn_stmt;
DEALLOCATE PREPARE nuahn_stmt;

-- Demo accounts listed in the README always sign in with password123.
-- Touches only the password column of these four rows, and only when it differs;
-- no other users or data are changed. If a demo user changes their password on
-- the preview, it is put back to password123 at the next boot.
UPDATE `users` SET `password` = '$2y$10$1Wwonh1E3PH11uue4xeUc.taAv7WghV7XeB0lVFKOkGvzfABskYW6'
 WHERE `email` IN ('alice@example.com', 'bob@example.com', 'carol@example.com', 'david@example.com')
   AND `password` <> '$2y$10$1Wwonh1E3PH11uue4xeUc.taAv7WghV7XeB0lVFKOkGvzfABskYW6';
