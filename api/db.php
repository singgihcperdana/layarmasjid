<?php
/**
 * Koneksi Database MySQL Layar Masjid
 * Mendukung localhost XAMPP & Hosting Rumahweb
 */

// Set zona waktu default PHP ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Muat konfigurasi .env
require_once __DIR__ . '/env.php';

// Konfigurasi Database (Bisa diatur melalui .env atau default di sini)
$db_host = env('DB_HOST', 'localhost');
$db_name = env('DB_NAME', 'layarmasjid');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Helper Auto Cache-Busting Aset (CSS, JS) berbasis filemtime
 * Otomatis mendeteksi lokasi file baik di folder admin maupun display
 *
 * @param string $relative_path Path relatif aset, misal 'assets/css/layout.css'
 * @param string|null $base_dir Opsional direktori dasar
 * @return string Path aset lengkap dengan ?v={filemtime}
 */
function asset_v($relative_path, $base_dir = null) {
    $clean_path = ltrim($relative_path, '/\\');
    $root = dirname(__DIR__);
    $full_path = null;

    if ($base_dir && file_exists(rtrim($base_dir, '/\\') . '/' . $clean_path)) {
        $full_path = rtrim($base_dir, '/\\') . '/' . $clean_path;
    } elseif (file_exists($root . '/display/' . $clean_path)) {
        $full_path = $root . '/display/' . $clean_path;
    } elseif (file_exists($root . '/admin/' . $clean_path)) {
        $full_path = $root . '/admin/' . $clean_path;
    } elseif (file_exists($root . '/' . $clean_path)) {
        $full_path = $root . '/' . $clean_path;
    }

    $version = ($full_path && file_exists($full_path)) ? filemtime($full_path) : '1';
    return $relative_path . '?v=' . $version;
}

/**
 * Update file statis JSON sinkronisasi TV Display (Nol beban PHP & MySQL)
 * Menyimpan hash perubahan ke uploads/sync/{masjid_id}.json
 *
 * @param string $masjid_id ID Masjid
 * @param int|string|null $timestamp Nilai UNIX timestamp / hash
 * @return string Nilai sync hash
 */
function update_sync_file($masjid_id, $timestamp = null) {
    if (empty($masjid_id)) return '';
    $timestamp = (string)($timestamp ?: time());
    $dir = dirname(__DIR__) . '/uploads/sync';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $clean_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $masjid_id);
    $file = $dir . '/' . $clean_id . '.json';
    $data = json_encode([
        'status'    => 'success',
        'masjid_id' => (string)$masjid_id,
        'sync_hash' => $timestamp
    ]);
    @file_put_contents($file, $data);
    return $timestamp;
}

/**
 * Generate ID Masjid Baru dengan format PPNNNN (6 digit: 2 digit Provinsi + 4 digit Angka Acak)
 * Contoh: 328194 (Jawa Barat), 314052 (DKI Jakarta)
 *
 * @param string $province_id Kode Provinsi 2 digit (default: '32' Jawa Barat)
 * @return string ID 6 digit
 */
function generate_masjid_id($province_id = '32') {
    global $pdo;
    $prov = str_pad(preg_replace('/[^0-9]/', '', (string)$province_id), 2, '0', STR_PAD_LEFT);
    if (strlen($prov) > 2) $prov = substr($prov, 0, 2);
    if (empty($prov) || $prov === '00') $prov = '32';

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM masjid WHERE id = ?");

    // Generate 4 digit angka acak (1000 - 9999) dan pastikan unik di database
    $attempts = 0;
    do {
        $rand_num = random_int(1000, 9999);
        $candidate_id = $prov . (string)$rand_num;
        $stmtCheck->execute([$candidate_id]);
        $exists = (int)$stmtCheck->fetchColumn() > 0;
        $attempts++;
    } while ($exists && $attempts < 100);

    return $candidate_id;
}
