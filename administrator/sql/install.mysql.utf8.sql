CREATE TABLE IF NOT EXISTS `#__clm_api_keys` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100) NOT NULL,
  `contact`      VARCHAR(255) NULL,
  `key_prefix`   CHAR(8) NOT NULL,
  `key_hash`     CHAR(64) NOT NULL,
  `rate_limit`   INT UNSIGNED NOT NULL DEFAULT 60,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`   DATETIME NULL,
  `revoked_at`   DATETIME NULL,
  `last_used_at` DATETIME NULL,
  `note`         TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key_hash` (`key_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__clm_api_usage` (
  `api_key_id`  INT UNSIGNED NOT NULL,
  `minute_slot` INT UNSIGNED NOT NULL,
  `hits`        INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`api_key_id`, `minute_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
