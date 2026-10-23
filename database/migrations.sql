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

-- =====================================================================================================
-- MARKETPLACE BOOKING BACKEND (Winter Snow Removal + Mobile Mechanics)
-- Server-side work credited in docs/CREDITS.md. Full notes for review: docs/BOOKING-BACKEND.md.
--
-- Decisions for review:
--   * `users` is MyISAM (no foreign keys possible), so every user-id column below is a plain indexed INT
--     and ownership is enforced in PHP. All new tables are InnoDB, with real foreign keys between them.
--   * Money is DECIMAL(10,2) in CAD. Payment state lives in `payments` and is mirrored on tasks.payment_status;
--     it is never folded into the job state machine (tasks.status).
--   * No card data is stored anywhere: `payments.processor_ref` holds only a processor token
--     (Stripe PaymentIntent id, or a fake "sbx_" token from the sandbox provider).
--   * Everything is CREATE TABLE IF NOT EXISTS, so this file is safe to run on every deploy.
-- =====================================================================================================

-- Contractor profile + vetting record. One row per provider user; only 'approved' may offer or quote.
CREATE TABLE IF NOT EXISTS `contractor_profiles` (
  `user_id` int NOT NULL,
  `trades` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `vetting_status` enum('draft','pending','in_review','changes_requested','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `chk_identity` enum('missing','review','ok','rejected','na') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'missing',
  `chk_insurance` enum('missing','review','ok','rejected','na') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'missing',
  `chk_licence` enum('missing','review','ok','rejected','na') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'missing',
  `chk_cert` enum('missing','review','ok','rejected','na') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `vet_note` text COLLATE utf8mb4_unicode_ci,
  `vetted_by` int DEFAULT NULL,
  `vetted_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `headline` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `about` text COLLATE utf8mb4_unicode_ci,
  `equipment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `base_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `base_lat` decimal(9,6) DEFAULT NULL,
  `base_lng` decimal(9,6) DEFAULT NULL,
  `radius_km` smallint NOT NULL DEFAULT 15,
  `is_online` tinyint(1) NOT NULL DEFAULT 1,
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int NOT NULL DEFAULT 0,
  `jobs_done` int NOT NULL DEFAULT 0,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `vetting_status` (`vetting_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vetting documents (served only through public/media.php to the owner and staff).
CREATE TABLE IF NOT EXISTS `contractor_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `kind` enum('identity','insurance','licence','cert') COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bytes` int NOT NULL DEFAULT 0,
  `expires_on` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_kind` (`user_id`,`kind`),
  CONSTRAINT `fk_cdoc_profile` FOREIGN KEY (`user_id`) REFERENCES `contractor_profiles` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A snow event recorded by operations (or the weather feed later); one visit task is created per active seasonal plan.
CREATE TABLE IF NOT EXISTS `snow_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `snowfall_cm` decimal(5,1) NOT NULL,
  `area` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `visits_created` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_key` (`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks: one row per job request. `status` = job state machine, `payment_status` = mirror of payments.state.
--   status: awaiting_payment (roadside/card step not finished) | searching (roadside broadcast) | no_provider | quote_pending (scheduled repair waiting for quotes) |
--           open_for_bids | confirmed (funds held) | on_the_way | arrived | in_progress | paused (revised quote
--           awaiting client) | pending_approval | approved | disputed | cancelled
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service` enum('snow-removal','mobile-mechanics') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` enum('on_demand','seasonal','roadside','scheduled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `client_id` int NOT NULL,
  `contractor_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `snow_event_id` int DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `target_budget` decimal(10,2) DEFAULT NULL,
  `boost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `agreed_amount` decimal(10,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('awaiting_payment','searching','no_provider','quote_pending','open_for_bids','confirmed','on_the_way','arrived','in_progress','paused','pending_approval','approved','disputed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open_for_bids',
  `payment_status` enum('unpaid','authorized','captured','released','partially_refunded','refunded','disputed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `accepted_offer_id` int DEFAULT NULL,
  `pending_offer_id` int DEFAULT NULL,
  `window_label` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `eta_minutes` smallint DEFAULT NULL,
  `search_expires_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `arrived_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `auto_release_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completion_note` text COLLATE utf8mb4_unicode_ci,
  `checklist` text COLLATE utf8mb4_unicode_ci,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`,`status`),
  KEY `contractor_id` (`contractor_id`,`status`),
  KEY `board` (`service`,`status`),
  KEY `auto_release` (`status`,`auto_release_at`),
  KEY `parent_id` (`parent_id`),
  KEY `snow_event_id` (`snow_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers: snow = accept-at-price or counter-bid; roadside = (none, first accept wins); repair = the quote's headline price.
CREATE TABLE IF NOT EXISTS `task_offers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `contractor_id` int NOT NULL,
  `kind` enum('accept','counter','quote') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'accept',
  `amount` decimal(10,2) NOT NULL,
  `message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `eta_minutes` smallint DEFAULT NULL,
  `status` enum('sent','accepted','declined','withdrawn','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_offer_each` (`task_id`,`contractor_id`),
  KEY `contractor_id` (`contractor_id`),
  CONSTRAINT `fk_offer_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Before / after / dispute evidence. `storage` = 'upload' (file under uploads/jobs/) or 'asset' (bundled demo image).
CREATE TABLE IF NOT EXISTS `job_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `kind` enum('before','after','dispute','issue') COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage` enum('upload','asset') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'upload',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bytes` int NOT NULL DEFAULT 0,
  `width` smallint DEFAULT NULL,
  `height` smallint DEFAULT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_kind` (`task_id`,`kind`),
  CONSTRAINT `fk_photo_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments (one per task). State machine is separate from the job: unpaid -> authorized (escrow hold) ->
-- captured (client approved; payout queued) -> released (payout sent); or refunded / partially_refunded / disputed / failed.
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `client_id` int NOT NULL,
  `contractor_id` int DEFAULT NULL,
  `provider` enum('sandbox','stripe') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `state` enum('unpaid','authorized','captured','released','partially_refunded','refunded','disputed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CAD',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_pct` decimal(5,2) NOT NULL DEFAULT 12.00,
  `commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payout_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `captured_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `processor_ref` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payout_status` enum('none','pending','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `payout_ref` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorized_at` datetime DEFAULT NULL,
  `captured_at` datetime DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  KEY `state` (`state`),
  KEY `processor_ref` (`processor_ref`),
  KEY `contractor_id` (`contractor_id`),
  CONSTRAINT `fk_pay_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ledger of every call made to the payment processor (idempotency keys, amounts, outcome). Never holds card data.
CREATE TABLE IF NOT EXISTS `payment_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_id` int NOT NULL,
  `event` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `ref` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idem_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `fk_pev_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Processed processor webhook events (replay protection).
CREATE TABLE IF NOT EXISTS `webhook_events` (
  `event_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mechanic quotes. kind 'initial' = v1 for the whole job; kind 'revision' = on-site addition (only the NEW items),
-- which pauses the job until the client re-approves. status: sent | question | approved | declined | expired | superseded.
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `offer_id` int DEFAULT NULL,
  `contractor_id` int NOT NULL,
  `version` smallint NOT NULL DEFAULT 1,
  `kind` enum('initial','revision') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'initial',
  `status` enum('sent','question','approved','declined','expired','superseded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `scope` text COLLATE utf8mb4_unicode_ci,
  `includes_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `excludes_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `found_note` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `windows` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `chosen_window` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expires_at` datetime DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`,`status`),
  KEY `contractor_id` (`contractor_id`),
  CONSTRAINT `fk_quote_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_id` int NOT NULL,
  `type` enum('Parts','Labour','Fee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort` smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  CONSTRAINT `fk_qitem_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Disputes: opened by the client from "pending approval"; funds stay held until operations resolves.
CREATE TABLE IF NOT EXISTS `disputes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `opened_by` int NOT NULL,
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `wanted` enum('refund','partial','redo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refund',
  `status` enum('open','info_requested','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `resolution` enum('release','refund','split') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contractor_share` decimal(10,2) DEFAULT NULL,
  `resolution_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_by` int DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_dispute_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One review per approved task, written by the client.
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `client_id` int NOT NULL,
  `contractor_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  KEY `contractor_id` (`contractor_id`),
  CONSTRAINT `fk_review_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-task conversation (client <-> contractor, plus quote questions).
CREATE TABLE IF NOT EXISTS `job_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `body` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`,`id`),
  CONSTRAINT `fk_msg_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- In-app notifications for job events (email / SMS delivery is pending; see docs/BOOKING-BACKEND.md).
CREATE TABLE IF NOT EXISTS `job_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `link` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_unread` (`user_id`,`read_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log of every state change, payment movement and staff decision.
CREATE TABLE IF NOT EXISTS `mk_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actor_id` int DEFAULT NULL,
  `actor_role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `detail` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `entity` (`entity`,`entity_id`),
  KEY `actor_id` (`actor_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================================================================
-- DEMO SEED (idempotent). Realistic preview data so every screen has something real to show: contractors with
-- vetting status and tasks in different states. All rows carry is_demo = 1 and are inserted only while no demo task
-- exists. Demo providers sign in with password123 (like the other demo accounts). Remove with:
--   DELETE FROM tasks WHERE is_demo = 1;  (children cascade)  DELETE FROM contractor_profiles WHERE is_demo = 1;
-- =====================================================================================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `firstname`, `lastname`)
SELECT v.n, v.e, '$2y$10$1Wwonh1E3PH11uue4xeUc.taAv7WghV7XeB0lVFKOkGvzfABskYW6', 'provider', 'active', v.f, v.l
FROM (SELECT 'Jordan Mercer' n, 'jordan@demo.nuahn.test' e, 'Jordan' f, 'Mercer' l UNION ALL SELECT 'Priya Nair', 'priya@demo.nuahn.test', 'Priya', 'Nair'
      UNION ALL SELECT 'Dana Kowalski', 'dana@demo.nuahn.test', 'Dana', 'Kowalski' UNION ALL SELECT 'Marcus Tran', 'marcus@demo.nuahn.test', 'Marcus', 'Tran'
      UNION ALL SELECT 'Sam Okafor', 'sam@demo.nuahn.test', 'Sam', 'Okafor') v
WHERE NOT EXISTS (SELECT 1 FROM `users` u WHERE u.`email` = v.e);

SET @alice := (SELECT `id` FROM `users` WHERE `email` = 'alice@example.com' LIMIT 1);
SET @bob := (SELECT `id` FROM `users` WHERE `email` = 'bob@example.com' LIMIT 1);
SET @carol := (SELECT `id` FROM `users` WHERE `email` = 'carol@example.com' LIMIT 1);
SET @paul := (SELECT `id` FROM `users` WHERE `email` = 'atuleptnt@gmail.com' LIMIT 1);
SET @kweku := (SELECT `id` FROM `users` WHERE `email` = 'km@gmail.com' LIMIT 1);
SET @chris := (SELECT `id` FROM `users` WHERE `email` = 'ct@gmail.com' LIMIT 1);
SET @jordan := (SELECT `id` FROM `users` WHERE `email` = 'jordan@demo.nuahn.test' LIMIT 1);
SET @priya := (SELECT `id` FROM `users` WHERE `email` = 'priya@demo.nuahn.test' LIMIT 1);
SET @dana := (SELECT `id` FROM `users` WHERE `email` = 'dana@demo.nuahn.test' LIMIT 1);
SET @marcus := (SELECT `id` FROM `users` WHERE `email` = 'marcus@demo.nuahn.test' LIMIT 1);
SET @sam := (SELECT `id` FROM `users` WHERE `email` = 'sam@demo.nuahn.test' LIMIT 1);
SET @seeded := (SELECT COUNT(*) FROM `tasks` WHERE `is_demo` = 1);

INSERT IGNORE INTO `contractor_profiles` (`user_id`, `trades`, `vetting_status`, `chk_identity`, `chk_insurance`, `chk_licence`, `chk_cert`, `vetted_at`, `submitted_at`, `headline`, `about`, `equipment`, `base_address`, `base_lat`, `base_lng`, `radius_km`, `rating_avg`, `rating_count`, `jobs_done`, `is_demo`)
SELECT @jordan, 'snow-removal', 'approved', 'ok', 'ok', 'na', 'na', NOW(), NOW(), 'Driveways, walkways and steps, cleared before the school run.', 'Independent snow contractor. I run my own truck with one helper, and I quote the job I will actually do.', 'Plow truck, 2 walk-behind blowers, ice melt on board', 'Edmonton SW', 53.4690, -113.5200, 20, 4.90, 212, 318, 1 FROM DUAL WHERE @jordan IS NOT NULL
UNION ALL SELECT @priya, 'snow-removal', 'approved', 'ok', 'ok', 'na', 'na', NOW(), NOW(), 'Careful hand-shovel work for steps, stairs and tight driveways.', 'I specialise in homes with steps, narrow lanes and long walkways where a truck does not fit.', 'Walk-behind blower, shovels, salt and sand', 'Edmonton West', 53.5400, -113.6000, 15, 4.80, 97, 141, 1 FROM DUAL WHERE @priya IS NOT NULL
UNION ALL SELECT @dana, 'snow-removal', 'approved', 'ok', 'ok', 'na', 'na', NOW(), NOW(), 'Large driveways and small commercial lots.', 'Plow driver with my own insurance and equipment.', 'Pickup with 8 ft plow, tow-behind salter', 'Sherwood Park', 53.5400, -113.3000, 30, 4.70, 58, 76, 1 FROM DUAL WHERE @dana IS NOT NULL
UNION ALL SELECT @marcus, 'mobile-mechanics', 'approved', 'ok', 'ok', 'ok', 'ok', NOW(), NOW(), 'Red Seal technician. Brakes, batteries, no-start and diagnostics at your door.', 'Fifteen years in the trade, fully equipped service van.', 'Service van, scan tool, battery tester, jump pack', 'Edmonton Central', 53.5500, -113.4900, 40, 4.90, 143, 205, 1 FROM DUAL WHERE @marcus IS NOT NULL
UNION ALL SELECT @sam, 'mobile-mechanics', 'in_review', 'ok', 'review', 'review', 'na', NULL, NOW(), 'Mobile tire and battery service.', 'New to the platform. Documents are with the vetting team.', 'Tire changer, battery tester', 'Edmonton North', 53.6100, -113.5000, 30, 0.00, 0, 0, 1 FROM DUAL WHERE @sam IS NOT NULL
UNION ALL SELECT @bob, 'snow-removal,mobile-mechanics', 'approved', 'ok', 'ok', 'ok', 'na', NOW(), NOW(), 'Snow clearing and roadside help.', '', 'Truck, jump pack', 'Edmonton', 53.5461, -113.4938, 25, 0.00, 0, 0, 1 FROM DUAL WHERE @bob IS NOT NULL
UNION ALL SELECT @chris, 'snow-removal', 'pending', 'review', 'review', 'na', 'na', NULL, NOW(), 'Snow clearing, steps and walkways.', '', 'Snow blower', 'Edmonton East', 53.5600, -113.4000, 15, 0.00, 0, 0, 1 FROM DUAL WHERE @chris IS NOT NULL;

-- 1) Alice, snow, open for bids with two offers.
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `address`, `lat`, `lng`, `details`, `notes`, `target_budget`, `status`, `payment_status`, `is_demo`, `created_at`)
SELECT 'snow-removal', 'on_demand', 'snow', 'Snow clearing · Standard driveway + walkway', @alice, '112 Maple Crescent, Edmonton AB', 53.5420, -113.5100,
  '{"size":"standard","sqft":null,"walk":"front","steps":true,"ice":true,"plan":"demand","estimate":[75,105]}', 'Gate on the left side stays shut please.', 85.00, 'open_for_bids', 'unpaid', 1, DATE_SUB(NOW(), INTERVAL 40 MINUTE) FROM DUAL WHERE @seeded = 0 AND @alice IS NOT NULL;
SET @t1 := LAST_INSERT_ID();
INSERT INTO `task_offers` (`task_id`, `contractor_id`, `kind`, `amount`, `message`, `eta_minutes`, `status`)
SELECT @t1, @jordan, 'accept', 85.00, 'I can be there within the hour with the truck.', 45, 'sent' FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL
UNION ALL SELECT @t1, @priya, 'counter', 95.00, 'Includes hand-shovelling the steps and salting the walkway.', 90, 'sent' FROM DUAL WHERE @seeded = 0 AND @priya IS NOT NULL;

-- 2) Alice, snow, confirmed (escrow held).
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `contractor_id`, `address`, `lat`, `lng`, `details`, `target_budget`, `agreed_amount`, `tax_amount`, `status`, `payment_status`, `window_label`, `accepted_at`, `is_demo`, `created_at`)
SELECT 'snow-removal', 'on_demand', 'snow', 'Snow clearing · Long driveway', @alice, @jordan, '112 Maple Crescent, Edmonton AB', 53.5420, -113.5100,
  '{"size":"large","sqft":null,"walk":"none","steps":false,"ice":false,"plan":"demand","estimate":[95,135]}', 110.00, 110.00, 5.50, 'confirmed', 'authorized', 'Tomorrow morning', DATE_SUB(NOW(), INTERVAL 3 HOUR), 1, DATE_SUB(NOW(), INTERVAL 5 HOUR) FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;
SET @t2 := LAST_INSERT_ID();
INSERT INTO `task_offers` (`task_id`, `contractor_id`, `kind`, `amount`, `message`, `status`) SELECT @t2, @jordan, 'accept', 110.00, 'Happy to do it at your price.', 'accepted' FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;
UPDATE `tasks` SET `accepted_offer_id` = (SELECT `id` FROM `task_offers` WHERE `task_id` = @t2 LIMIT 1) WHERE `id` = @t2 AND @seeded = 0;
INSERT INTO `payments` (`task_id`, `client_id`, `contractor_id`, `provider`, `state`, `subtotal`, `tax`, `total`, `processor_ref`, `authorized_at`)
SELECT @t2, @alice, @jordan, 'sandbox', 'authorized', 110.00, 5.50, 115.50, 'sbx_auth_demo0002', NOW() FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;

-- 3) Alice, snow, completed and waiting for approval (photos on file, 30 h left before auto-release).
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `contractor_id`, `address`, `lat`, `lng`, `details`, `target_budget`, `agreed_amount`, `tax_amount`, `status`, `payment_status`, `window_label`, `accepted_at`, `started_at`, `arrived_at`, `submitted_at`, `auto_release_at`, `completion_note`, `checklist`, `is_demo`, `created_at`)
SELECT 'snow-removal', 'on_demand', 'snow', 'Snow clearing · Standard driveway + steps', @alice, @dana, '14 Birch Lane, Edmonton AB', 53.5230, -113.4700,
  '{"size":"standard","sqft":null,"walk":"front","steps":true,"ice":true,"plan":"demand","estimate":[85,115]}', 90.00, 90.00, 4.50, 'pending_approval', 'authorized', 'This morning', DATE_SUB(NOW(), INTERVAL 20 HOUR), DATE_SUB(NOW(), INTERVAL 18 HOUR), DATE_SUB(NOW(), INTERVAL 18 HOUR), DATE_SUB(NOW(), INTERVAL 18 HOUR), DATE_ADD(NOW(), INTERVAL 30 HOUR),
  'Cleared to the pavement edge and salted the steps. Gate left closed.', '["Driveway cleared to the surface","Walkway and steps cleared","Ice melt applied to the steps","Snow piled away from the garage door"]', 1, DATE_SUB(NOW(), INTERVAL 22 HOUR) FROM DUAL WHERE @seeded = 0 AND @dana IS NOT NULL;
SET @t3 := LAST_INSERT_ID();
INSERT INTO `task_offers` (`task_id`, `contractor_id`, `kind`, `amount`, `message`, `status`) SELECT @t3, @dana, 'accept', 90.00, '', 'accepted' FROM DUAL WHERE @seeded = 0 AND @dana IS NOT NULL;
UPDATE `tasks` SET `accepted_offer_id` = (SELECT `id` FROM `task_offers` WHERE `task_id` = @t3 LIMIT 1) WHERE `id` = @t3 AND @seeded = 0;
INSERT INTO `payments` (`task_id`, `client_id`, `contractor_id`, `provider`, `state`, `subtotal`, `tax`, `total`, `processor_ref`, `authorized_at`)
SELECT @t3, @alice, @dana, 'sandbox', 'authorized', 90.00, 4.50, 94.50, 'sbx_auth_demo0003', DATE_SUB(NOW(), INTERVAL 20 HOUR) FROM DUAL WHERE @seeded = 0 AND @dana IS NOT NULL;
INSERT INTO `job_photos` (`task_id`, `user_id`, `kind`, `storage`, `path`, `mime`, `bytes`, `width`, `height`)
SELECT @t3, @dana, 'before', 'asset', 'work-before-800.jpg', 'image/jpeg', 0, 800, 600 FROM DUAL WHERE @seeded = 0 AND @dana IS NOT NULL
UNION ALL SELECT @t3, @dana, 'after', 'asset', 'work-after-800.jpg', 'image/jpeg', 0, 800, 600 FROM DUAL WHERE @seeded = 0 AND @dana IS NOT NULL;

-- 4) Alice, snow, approved and paid out (12% commission), with a review.
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `contractor_id`, `address`, `lat`, `lng`, `details`, `target_budget`, `agreed_amount`, `tax_amount`, `status`, `payment_status`, `window_label`, `accepted_at`, `submitted_at`, `approved_at`, `completion_note`, `is_demo`, `created_at`)
SELECT 'snow-removal', 'on_demand', 'snow', 'Snow clearing · Standard driveway', @alice, @jordan, '112 Maple Crescent, Edmonton AB', 53.5420, -113.5100,
  '{"size":"standard","sqft":null,"walk":"none","steps":false,"ice":false,"plan":"demand","estimate":[65,95]}', 80.00, 80.00, 4.00, 'approved', 'released', 'Saturday', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 'All clear.', 1, DATE_SUB(NOW(), INTERVAL 7 DAY) FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;
SET @t4 := LAST_INSERT_ID();
INSERT INTO `task_offers` (`task_id`, `contractor_id`, `kind`, `amount`, `message`, `status`) SELECT @t4, @jordan, 'accept', 80.00, '', 'accepted' FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;
UPDATE `tasks` SET `accepted_offer_id` = (SELECT `id` FROM `task_offers` WHERE `task_id` = @t4 LIMIT 1) WHERE `id` = @t4 AND @seeded = 0;
INSERT INTO `payments` (`task_id`, `client_id`, `contractor_id`, `provider`, `state`, `subtotal`, `tax`, `total`, `commission_pct`, `commission`, `payout_amount`, `captured_amount`, `processor_ref`, `payout_status`, `payout_ref`, `authorized_at`, `captured_at`, `released_at`)
SELECT @t4, @alice, @jordan, 'sandbox', 'released', 80.00, 4.00, 84.00, 12.00, 9.60, 70.40, 84.00, 'sbx_cap_demo0004', 'paid', 'sbx_payout_demo0004', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY) FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;
INSERT INTO `job_photos` (`task_id`, `user_id`, `kind`, `storage`, `path`, `mime`, `bytes`, `width`, `height`)
SELECT @t4, @jordan, 'before', 'asset', 'work-before-800.jpg', 'image/jpeg', 0, 800, 600 FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL
UNION ALL SELECT @t4, @jordan, 'after', 'asset', 'work-after-800.jpg', 'image/jpeg', 0, 800, 600 FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;
INSERT INTO `reviews` (`task_id`, `client_id`, `contractor_id`, `rating`, `comment`)
SELECT @t4, @alice, @jordan, 5, 'On time, clean edges, and he cleared the steps without being asked.' FROM DUAL WHERE @seeded = 0 AND @jordan IS NOT NULL;

-- 5) Paul, roadside battery, searching for a technician (hold placed, search window left open for the demo).
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `address`, `lat`, `lng`, `details`, `target_budget`, `status`, `payment_status`, `search_expires_at`, `is_demo`, `created_at`)
SELECT 'mobile-mechanics', 'roadside', 'battery', 'Jump start or battery · 2018 Honda Civic', @paul, 'Whyte Ave & 104 St, Edmonton AB', 53.5190, -113.5030,
  '{"vehicle":{"year":"2018","make":"Honda","model":"Civic","trim":"","engine":""},"extras":{}}', 98.00, 'searching', 'authorized', DATE_ADD(NOW(), INTERVAL 30 DAY), 1, DATE_SUB(NOW(), INTERVAL 3 MINUTE) FROM DUAL WHERE @seeded = 0 AND @paul IS NOT NULL;
SET @t5 := LAST_INSERT_ID();
INSERT INTO `payments` (`task_id`, `client_id`, `provider`, `state`, `subtotal`, `tax`, `total`, `processor_ref`, `authorized_at`)
SELECT @t5, @paul, 'sandbox', 'authorized', 98.00, 4.90, 102.90, 'sbx_auth_demo0005', NOW() FROM DUAL WHERE @seeded = 0 AND @paul IS NOT NULL;

-- 6) Paul, scheduled brake repair, one itemised quote waiting (parts + labour + call-out fee + GST).
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `address`, `lat`, `lng`, `details`, `notes`, `status`, `payment_status`, `window_label`, `is_demo`, `created_at`)
SELECT 'mobile-mechanics', 'scheduled', 'brakes', 'Brake pads and rotors · 2016 Toyota RAV4', @paul, '88 Ravine Drive, Edmonton AB', 53.5700, -113.4400,
  '{"vehicle":{"year":"2016","make":"Toyota","model":"RAV4","trim":"LE","engine":"2.5L"},"extras":{"window":"Weekday mornings"}}', 'Squealing when braking, mostly the front.', 'quote_pending', 'unpaid', 'Weekday mornings', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR) FROM DUAL WHERE @seeded = 0 AND @paul IS NOT NULL;
SET @t6 := LAST_INSERT_ID();
INSERT INTO `task_offers` (`task_id`, `contractor_id`, `kind`, `amount`, `message`, `status`) SELECT @t6, @marcus, 'quote', 372.00, 'Front pads and rotors, same-day if parts are in stock.', 'sent' FROM DUAL WHERE @seeded = 0 AND @marcus IS NOT NULL;
INSERT INTO `quotes` (`task_id`, `offer_id`, `contractor_id`, `version`, `kind`, `status`, `scope`, `includes_text`, `excludes_text`, `windows`, `subtotal`, `gst`, `pst`, `total`, `expires_at`)
SELECT @t6, (SELECT `id` FROM `task_offers` WHERE `task_id` = @t6 LIMIT 1), @marcus, 1, 'initial', 'sent', 'Replace front brake pads and rotors, inspect calipers and brake fluid.', 'Parts, labour, brake clean, test drive', 'Caliper replacement, brake fluid flush', 'Tomorrow 9am to 11am|Tomorrow 1pm to 3pm|Saturday 10am to 12pm', 372.00, 18.60, 0.00, 390.60, DATE_ADD(NOW(), INTERVAL 22 HOUR) FROM DUAL WHERE @seeded = 0 AND @marcus IS NOT NULL;
SET @q6 := LAST_INSERT_ID();
INSERT INTO `quote_items` (`quote_id`, `type`, `description`, `qty`, `unit_price`, `sort`)
SELECT @q6, 'Parts', 'Front brake pads (ceramic)', 1, 74.00, 0 FROM DUAL WHERE @seeded = 0 AND @marcus IS NOT NULL
UNION ALL SELECT @q6, 'Parts', 'Front rotors', 2, 63.00, 1 FROM DUAL WHERE @seeded = 0 AND @marcus IS NOT NULL
UNION ALL SELECT @q6, 'Labour', 'Brake service, 1.5 h', 1.5, 90.00, 2 FROM DUAL WHERE @seeded = 0 AND @marcus IS NOT NULL
UNION ALL SELECT @q6, 'Fee', 'Mobile call-out fee', 1, 37.00, 3 FROM DUAL WHERE @seeded = 0 AND @marcus IS NOT NULL;

-- 7) Kweku, snow job in dispute (funds held, case open in the disputes queue).
INSERT INTO `tasks` (`service`, `mode`, `category`, `title`, `client_id`, `contractor_id`, `address`, `lat`, `lng`, `details`, `target_budget`, `agreed_amount`, `tax_amount`, `status`, `payment_status`, `window_label`, `accepted_at`, `submitted_at`, `completion_note`, `is_demo`, `created_at`)
SELECT 'snow-removal', 'on_demand', 'snow', 'Snow clearing · Standard driveway + walkway', @kweku, @priya, '7 Willow Court, St. Albert AB', 53.6300, -113.6300,
  '{"size":"standard","sqft":null,"walk":"front","steps":false,"ice":false,"plan":"demand","estimate":[75,105]}', 95.00, 95.00, 4.75, 'disputed', 'disputed', 'Yesterday', DATE_SUB(NOW(), INTERVAL 30 HOUR), DATE_SUB(NOW(), INTERVAL 26 HOUR), 'Driveway done, walkway left for the second pass.', 1, DATE_SUB(NOW(), INTERVAL 32 HOUR) FROM DUAL WHERE @seeded = 0 AND @priya IS NOT NULL AND @kweku IS NOT NULL;
SET @t7 := LAST_INSERT_ID();
INSERT INTO `task_offers` (`task_id`, `contractor_id`, `kind`, `amount`, `message`, `status`) SELECT @t7, @priya, 'accept', 95.00, '', 'accepted' FROM DUAL WHERE @seeded = 0 AND @priya IS NOT NULL;
UPDATE `tasks` SET `accepted_offer_id` = (SELECT `id` FROM `task_offers` WHERE `task_id` = @t7 LIMIT 1) WHERE `id` = @t7 AND @seeded = 0;
INSERT INTO `payments` (`task_id`, `client_id`, `contractor_id`, `provider`, `state`, `subtotal`, `tax`, `total`, `processor_ref`, `authorized_at`)
SELECT @t7, @kweku, @priya, 'sandbox', 'disputed', 95.00, 4.75, 99.75, 'sbx_auth_demo0007', DATE_SUB(NOW(), INTERVAL 30 HOUR) FROM DUAL WHERE @seeded = 0 AND @priya IS NOT NULL;
INSERT INTO `job_photos` (`task_id`, `user_id`, `kind`, `storage`, `path`, `mime`, `bytes`, `width`, `height`)
SELECT @t7, @priya, 'before', 'asset', 'work-before-800.jpg', 'image/jpeg', 0, 800, 600 FROM DUAL WHERE @seeded = 0 AND @priya IS NOT NULL
UNION ALL SELECT @t7, @priya, 'after', 'asset', 'work-after-800.jpg', 'image/jpeg', 0, 800, 600 FROM DUAL WHERE @seeded = 0 AND @priya IS NOT NULL;
INSERT INTO `disputes` (`task_id`, `opened_by`, `reason`, `details`, `wanted`, `status`)
SELECT @t7, @kweku, 'Area missed (steps, walkway)', 'The front walkway to the door was not cleared.', 'redo', 'open' FROM DUAL WHERE @seeded = 0 AND @kweku IS NOT NULL;

INSERT INTO `mk_audit_log` (`actor_id`, `actor_role`, `action`, `entity`, `entity_id`, `detail`, `ip`)
SELECT @carol, 'superadmin', 'seed.demo', 'system', NULL, 'Demo marketplace data loaded', '' FROM DUAL WHERE @seeded = 0;
