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

require_once __DIR__ . '/../api/db.php';

// Auto-sync jika ID masjid berubah di database
if (isset($_SESSION['user_id'])) {
    $stmtUserCheck = $pdo->prepare("SELECT u.masjid_id, m.nama AS masjid_nama FROM users u JOIN masjid m ON u.masjid_id = m.id WHERE u.id = ? LIMIT 1");
    $stmtUserCheck->execute([$_SESSION['user_id']]);
    $userRow = $stmtUserCheck->fetch();
    if ($userRow && $userRow['masjid_id'] !== $_SESSION['masjid_id']) {
        $_SESSION['masjid_id'] = $userRow['masjid_id'];
        $_SESSION['masjid_nama'] = $userRow['masjid_nama'];
    }
}

$current_masjid_id = $_SESSION['masjid_id'];
$current_masjid_nama = $_SESSION['masjid_nama'] ?? 'Pengurus Masjid';
