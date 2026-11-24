<?php
/**
 * Middleware Pengecekan Login Admin DKM
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['masjid_id'])) {
    header("Location: login");
    exit;
}

$current_masjid_id = $_SESSION['masjid_id'];
$current_masjid_nama = $_SESSION['masjid_nama'] ?? 'Pengurus Masjid';
