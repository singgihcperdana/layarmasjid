-- =============================================================================
-- Skema Database Layar Masjid Multi-Tenant
-- Database: layarmasjid
-- =============================================================================

CREATE TABLE IF NOT EXISTS `masjid` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `nama` VARCHAR(150) NOT NULL,
  `lokasi` TEXT NULL,
  `kontak_info` TEXT NULL,
  `logo` VARCHAR(255) NULL,
  `latitude` DECIMAL(10, 6) DEFAULT -6.140000,
  `longitude` DECIMAL(10, 6) DEFAULT 106.590000,
  `timezone` INT DEFAULT 7,
  `hijri_offset` INT DEFAULT 0,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `masjid_setting` (
  `masjid_id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `tune_fajr` INT DEFAULT 1,
  `tune_sunrise` INT DEFAULT 0,
  `tune_dhuhr` INT DEFAULT 0,
  `tune_asr` INT DEFAULT 2,
  `tune_maghrib` INT DEFAULT 6,
  `tune_isha` INT DEFAULT 1,
  `calc_fajr_angle` DECIMAL(4, 1) DEFAULT 20.0,
  `calc_isha_angle` DECIMAL(4, 1) DEFAULT 18.0,
  `calc_asr_method` VARCHAR(20) DEFAULT 'Standard',
  `timer_slide` INT DEFAULT 15,
  `timer_wait_adzan` INT DEFAULT 1,
  `timer_adzan` INT DEFAULT 5,
  `timer_sholat` INT DEFAULT 10,
  `kecepatan_running_text` INT DEFAULT 5,
  `countdown_beep` INT DEFAULT 10,
  `iqomah_fajr` INT DEFAULT 10,
  `iqomah_dhuhr` INT DEFAULT 10,
  `iqomah_asr` INT DEFAULT 10,
  `iqomah_maghrib` INT DEFAULT 5,
  `iqomah_isha` INT DEFAULT 10,
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `masjid_slides` (
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

CREATE TABLE IF NOT EXISTS `masjid_running_text` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `masjid_id` VARCHAR(50) NOT NULL,
  `teks` TEXT NOT NULL,
  `urutan` INT DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`masjid_id`) REFERENCES `masjid`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA AWAL: MASJID ID 101 (Password default: admin123)
-- =============================================================================

INSERT INTO `masjid` (`id`, `nama`, `lokasi`, `kontak_info`, `logo`, `latitude`, `longitude`, `timezone`, `hijri_offset`, `password`)
VALUES (
  '101',
  'Masjid Al Ikhlas',
  'Jl. Contoh No. 12, Kebayoran Baru • Jakarta Selatan',
  'Info / Rekening / Kontak: BSI 7123 456 789 a.n. Masjid Al Ikhlas • WA: 0812-3456-7890',
  'assets/img/contoh_logo/masjid_astra.png',
  -6.140000,
  106.590000,
  7,
  0,
  '$2y$12$kWq6jERW68b28GYxl3AgC.gm0wVyL79731cAzdCk4jYnls73foLje'
) ON DUPLICATE KEY UPDATE `nama`=VALUES(`nama`);

INSERT INTO `masjid_setting` (`masjid_id`, `tune_fajr`, `tune_sunrise`, `tune_dhuhr`, `tune_asr`, `tune_maghrib`, `tune_isha`, `timer_slide`, `timer_wait_adzan`, `timer_adzan`, `timer_sholat`, `kecepatan_running_text`, `iqomah_fajr`, `iqomah_dhuhr`, `iqomah_asr`, `iqomah_maghrib`, `iqomah_isha`)
VALUES (
  '101',
  1, 0, 0, 2, 6, 1,
  15, 1, 5, 10, 5,
  10, 10, 10, 5, 10
) ON DUPLICATE KEY UPDATE `kecepatan_running_text`=VALUES(`kecepatan_running_text`);

INSERT INTO `masjid_slides` (`masjid_id`, `tipe`, `urutan`, `judul`, `ayat`, `arti`, `surah`, `konten`, `durasi`, `is_active`)
VALUES
  ('101', 'quranHadist', 1, NULL, 'وَاسْتَعِينُوا بِالصَّبْرِ وَالصَّلَاةِ ۚ وَإِنَّهَا لَكَبِيرَةٌ إِلَّا عَلَى الْخَاشِعِينَ', 'Dan mohonlah pertolongan (kepada Allah) dengan sabar dan sholat. Dan (sholat) itu sungguh berat, kecuali bagi orang-orang yang khusyuk.', 'QS. Al-Baqarah: 45', NULL, 15, 1),
  ('101', 'info', 2, 'KAJIAN RUTIN AHAD SUBUH', NULL, NULL, NULL, '<p>Bersama <b>Ustadz Dr. Fulan, Lc., M.A.</b></p><p style=\"opacity: 0.85;\">Pembahasan Kitab Riyadhus Shalihin</p>', 15, 1),
  ('101', 'image', 3, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-a.png', 15, 1),
  ('101', 'image', 4, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-2.png', 15, 1),
  ('101', 'image', 5, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-3.png', 15, 1),
  ('101', 'image', 6, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-4.png', 15, 1),
  ('101', 'image', 7, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-5.png', 15, 1),
  ('101', 'image', 8, NULL, NULL, NULL, NULL, 'assets/img/contoh_slide/info-6.png', 15, 1);

INSERT INTO `masjid_running_text` (`masjid_id`, `teks`, `urutan`, `is_active`)
VALUES
  ('101', 'Selamat datang di Masjid Al Ikhlas', 1, 1),
  ('101', 'Pengumuman kajian ba\'da Maghrib setiap malam Ahad', 2, 1),
  ('101', 'Mohon menonaktifkan nada dering handphone sebelum sholat dimulai', 3, 1),
  ('101', 'Lurus dan rapatkan shaf untuk kesempurnaan sholat berjamaah', 4, 1),
  ('101', 'Infaq operasional via rekening BSI 7123 456 789 a.n. Masjid Al Ikhlas', 5, 1);
