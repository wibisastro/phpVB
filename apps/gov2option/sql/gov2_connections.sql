-- Registry koneksi ekosistem gov3 (menu "Gurita") — #6134 slice C.
-- Skema kanonik: journal note-3 #6134. `jenis` sengaja VARCHAR (bukan ENUM)
-- sesuai guardrail "komponen baru = konfigurasi, bukan migrasi skema".
CREATE TABLE IF NOT EXISTS gov2_connections (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis       VARCHAR(20)  NOT NULL,            -- 'gurita'|'kambing'|'lebah'|... (validasi di kode)
  nama        VARCHAR(190) NOT NULL,            -- label tampilan, mis. "Gurita Kemdikbud"
  url         VARCHAR(255) NOT NULL,            -- endpoint dasar
  status      ENUM('on','off') NOT NULL DEFAULT 'on',
  auth_type   VARCHAR(20)  NOT NULL DEFAULT 'none',  -- none|bearer|basic|apikey
  credential  TEXT NULL,                        -- terenkripsi app-side (sodium, key dari env instance)
  tools       JSON NULL,                        -- cache inventori tools/list hasil discovery
  meta        JSON NULL,                        -- provenance: discovered_via, verified_at, dsb.
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by  INT UNSIGNED NULL,
  modify_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  modify_by   INT UNSIGNED NULL,
  UNIQUE KEY uq_conn (jenis, url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
