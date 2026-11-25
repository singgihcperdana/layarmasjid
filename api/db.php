<?php
/**
 * Koneksi Database MySQL Layar Masjid
 * Mendukung localhost XAMPP & Hosting Rumahweb
 */

// Set zona waktu default PHP ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database (Sesuaikan saat deploy ke Rumahweb)
$db_host = 'localhost';
$db_name = 'layarmasjid';
$db_user = 'root';
$db_pass = '';

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
