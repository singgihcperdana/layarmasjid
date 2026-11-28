-- =============================================================================
-- Skema Database Layar Masjid Multi-Tenant (Clean & Normalized)
-- Database: layarmasjid
-- Model: 1 Menu = 1 Tabel
-- =============================================================================

DROP TABLE IF EXISTS `running_text`;
DROP TABLE IF EXISTS `slides`;
DROP TABLE IF EXISTS `timer`;
DROP TABLE IF EXISTS `schedule`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `masjid`;
DROP TABLE IF EXISTS `profile`;

-- Hapus tabel lama (jika ada)
DROP TABLE IF EXISTS `masjid_running_text`;
DROP TABLE IF EXISTS `masjid_slides`;
DROP TABLE IF EXISTS `masjid_setting`;

-- =============================================================================
-- 0. TABEL WILAYAH INDONESIA (38 Provinsi & 514 Kabupaten/Kota - Kemendagri/BPS)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `provinces` (
  `id` CHAR(2) NOT NULL PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `regencies` (
  `id` CHAR(4) NOT NULL PRIMARY KEY,
  `province_id` CHAR(2) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `latitude` DECIMAL(10, 6) DEFAULT NULL,
  `longitude` DECIMAL(10, 6) DEFAULT NULL,
  INDEX `idx_province_id` (`province_id`),
  FOREIGN KEY (`province_id`) REFERENCES `provinces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 1. TABEL MASJID (Identitas Fisik, Alamat & Lokasi Koordinat Peta)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `masjid` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `nama` VARCHAR(150) NOT NULL,
  `lokasi` TEXT NULL,
  `province_id` CHAR(2) NULL,
  `regency_id` CHAR(4) NULL,
  `latitude` DECIMAL(10, 6) DEFAULT -6.140000,
  `longitude` DECIMAL(10, 6) DEFAULT 106.590000,
  `kontak_info` TEXT NULL,
  `logo` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_masjid_province` (`province_id`),
  INDEX `idx_masjid_regency` (`regency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. TABEL USERS (Kredensial Login Pengurus / DKM - Multi-User per Masjid)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `masjid_id` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `google_id` VARCHAR(100) NULL,
  `password` VARCHAR(255) NULL,
  `nama` VARCHAR(100) NULL,
  `avatar` VARCHAR(255) NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_google_id` (`google_id`),
  INDEX `idx_masjid_id` (`masjid_id`),
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. TABEL SCHEDULE (Koordinat Geografis, Metode Hisab & Ihtiyat Menit)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `schedule` (
  `masjid_id` VARCHAR(50) NOT NULL PRIMARY KEY,
  
  -- Titik Geografis & Timezone
  `latitude` DECIMAL(10, 6) DEFAULT -6.140000,
  `longitude` DECIMAL(10, 6) DEFAULT 106.590000,
  `timezone` INT DEFAULT 7,
  `hijri_offset` INT DEFAULT 0,
  
  -- Metode Kalkulasi Hisab (Standar Kemenag RI / Internasional)
  `calc_method` VARCHAR(30) DEFAULT 'Kemenag',
  `calc_fajr_angle` DECIMAL(4, 1) DEFAULT 20.0,
  `calc_isha_angle` DECIMAL(4, 1) DEFAULT 18.0,
  `calc_asr_method` VARCHAR(20) DEFAULT 'Standard',
  
  -- Koreksi Menit Ihtiyat (+ / -) Subuh s.d. Isya
  `tune_fajr` INT DEFAULT 1,
  `tune_sunrise` INT DEFAULT 0,
  `tune_dhuhr` INT DEFAULT 0,
  `tune_asr` INT DEFAULT 2,
  `tune_maghrib` INT DEFAULT 6,
  `tune_isha` INT DEFAULT 1,
  
  -- Mode Sholat Jum'at (Layar Hening Khutbah & Sholat saat Dzuhur di Hari Jum'at)
  `jumat_mode` VARCHAR(10) NOT NULL DEFAULT 'auto',
  `jumat_duration` INT NOT NULL DEFAULT 45,
  
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. TABEL TIMER (Siklus Layar TV, Kecepatan Teks, Audio Beep & Jeda Iqomah)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `timer` (
  `masjid_id` VARCHAR(50) NOT NULL PRIMARY KEY,
  
  -- Siklus Waktu Layar TV
  `timer_wait_adzan` INT DEFAULT 1,       -- Menjelang adzan (menit)
  `timer_adzan` INT DEFAULT 5,            -- Lama adzan (menit)
  `timer_sholat` INT DEFAULT 10,          -- Layar gelap saat sholat berjamaah (menit)
  `timer_jumat` INT DEFAULT 45,           -- Layar hening khutbah & sholat Jum'at (menit)
  `countdown_beep` INT DEFAULT 10,        -- Hitung mundur bunyi beep sebelum adzan/iqomah (detik)
  `kecepatan_running_text` INT DEFAULT 5, -- Kecepatan teks berjalan (level 1-10)
  
  -- Jeda Mundur Iqomah Sholat Fardhu (Menit)
  `iqomah_fajr` INT DEFAULT 10,
  `iqomah_dhuhr` INT DEFAULT 10,
  `iqomah_asr` INT DEFAULT 10,
  `iqomah_maghrib` INT DEFAULT 5,
  `iqomah_isha` INT DEFAULT 10,
  
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. TABEL SLIDES (Materi Slide TV: Gambar, Pengumuman Info & Dalil)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `slides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `masjid_id` VARCHAR(50) NOT NULL,
  `tipe` ENUM('image', 'info', 'quranHadist') NOT NULL DEFAULT 'image',
  `urutan` INT DEFAULT 1,
  `judul` VARCHAR(200) NULL,
  `ayat` TEXT NULL,
  `arti` TEXT NULL,
  `surah` VARCHAR(100) NULL,
  `konten` TEXT NULL,
  `durasi` INT DEFAULT 15,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. TABEL RUNNING_TEXT (Daftar Pesan Marquee Bawah Layar)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `running_text` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `masjid_id` VARCHAR(50) NOT NULL,
  `teks` TEXT NOT NULL,
  `urutan` INT DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA AWAL: MASJID ID 369448 (6 DIGIT: BANTEN) & USER ADMIN
-- =============================================================================

INSERT INTO `masjid` (`id`, `nama`, `lokasi`, `province_id`, `regency_id`, `latitude`, `longitude`, `kontak_info`, `logo`)
VALUES (
  '369448',
  'Masjid Al Ikhlas',
  'Kabupaten Tangerang • Banten',
  '36',
  '3603',
  -6.140000,
  106.590000,
  'Info / Rekening / Kontak: BSI 7123 456 789 a.n. Masjid Al Ikhlas • WA: 0812-3456-7890',
  'assets/img/contoh_logo/masjid_astra.png'
);

INSERT INTO `users` (`masjid_id`, `email`, `password`, `nama`, `role`)
VALUES (
  '369448',
  'admin@layarmasjid.com',
  '$2y$12$kWq6jERW68b28GYxl3AgC.gm0wVyL79731cAzdCk4jYnls73foLje',
  'Administrator DKM',
  'admin'
);

INSERT INTO `schedule` (`masjid_id`, `latitude`, `longitude`, `timezone`, `hijri_offset`, `calc_method`, `calc_fajr_angle`, `calc_isha_angle`, `calc_asr_method`, `tune_fajr`, `tune_sunrise`, `tune_dhuhr`, `tune_asr`, `tune_maghrib`, `tune_isha`)
VALUES (
  '369448',
  -6.140000,
  106.590000,
  7,
  0,
  'Kemenag',
  20.0,
  18.0,
  'Standard',
  1, 0, 0, 2, 6, 1
);

INSERT INTO `timer` (`masjid_id`, `timer_wait_adzan`, `timer_adzan`, `timer_sholat`, `timer_jumat`, `countdown_beep`, `kecepatan_running_text`, `iqomah_fajr`, `iqomah_dhuhr`, `iqomah_asr`, `iqomah_maghrib`, `iqomah_isha`)
VALUES (
  '369448',
  1, 5, 10, 45, 10, 5,
  10, 10, 10, 5, 10
);

INSERT INTO `slides` (`masjid_id`, `tipe`, `urutan`, `judul`, `ayat`, `arti`, `surah`, `konten`, `durasi`, `is_active`)
VALUES
  ('369448', 'quranHadist', 1, NULL, 'وَاسْتَعِينُوا بِالصَّبْرِ وَالصَّلَاةِ ۚ وَإِنَّهَا لَكَبِيرَةٌ إِلَّا عَلَى الْخَاشِعِينَ', 'Dan mohonlah pertolongan (kepada Allah) dengan sabar dan sholat. Dan (sholat) itu sungguh berat, kecuali bagi orang-orang yang khusyuk.', 'QS. Al-Baqarah: 45', NULL, 15, 1),
  ('369448', 'info', 2, 'KAJIAN RUTIN AHAD SUBUH', NULL, NULL, NULL, '<p>Bersama <b>Ustadz Dr. Fulan, Lc., M.A.</b></p><p style=\"opacity: 0.85;\">Pembahasan Kitab Riyadhus Shalihin</p>', 15, 1),
  ('369448', 'image', 3, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-a.png', 15, 1),
  ('369448', 'image', 4, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-2.png', 15, 1),
  ('369448', 'image', 5, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-3.png', 15, 1),
  ('369448', 'image', 6, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-4.png', 15, 1),
  ('369448', 'image', 7, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-5.png', 15, 1),
  ('369448', 'image', 8, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-6.png', 15, 1);

INSERT INTO `running_text` (`masjid_id`, `teks`, `urutan`, `is_active`)
VALUES
  ('369448', 'Selamat datang di Masjid Al Ikhlas', 1, 1),
  ('369448', 'Pengumuman kajian ba\'da Maghrib setiap malam Ahad', 2, 1),
  ('369448', 'Mohon menonaktifkan nada dering handphone sebelum sholat dimulai', 3, 1),
  ('369448', 'Lurus dan rapatkan shaf untuk kesempurnaan sholat berjamaah', 4, 1),
  ('369448', 'Infaq operasional via rekening BSI 7123 456 789 a.n. Masjid Al Ikhlas', 5, 1);
