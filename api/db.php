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
