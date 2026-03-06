-- Tabel options untuk gov2option
-- Jalankan sekali di DB server (gov2ayam atau sesuai environment)

CREATE TABLE IF NOT EXISTS `options` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `app`         VARCHAR(64) NOT NULL DEFAULT '',
  `type`        VARCHAR(32) NOT NULL DEFAULT '',
  `level`       TINYINT(4) NOT NULL DEFAULT 1,
  `level_label` VARCHAR(32) NOT NULL DEFAULT 'cluster',
  `privilege`   VARCHAR(32) NOT NULL DEFAULT 'member',
  `parent_id`   INT(11) NOT NULL DEFAULT 0,
  `nama`        VARCHAR(128) NOT NULL DEFAULT '',
  `keterangan`  VARCHAR(255) DEFAULT NULL,
  `status`      VARCHAR(8) NOT NULL DEFAULT 'on',
  `value`       VARCHAR(255) DEFAULT NULL,
  `created_by`  VARCHAR(64) DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `modify_by`   VARCHAR(64) DEFAULT NULL,
  `modify_at`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_app_level_type_status` (`app`, `level`, `type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jika tabel sudah ada, jalankan ini untuk fix kolom datetime:
-- ALTER TABLE `options`
--   MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
--   MODIFY `modify_at`  DATETIME DEFAULT NULL,
--   MODIFY `modify_by`  VARCHAR(64) DEFAULT NULL;
