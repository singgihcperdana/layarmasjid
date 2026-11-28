<?php
/**
 * API Pengecekan Versi Konfigurasi Layar Masjid (Live Sync Endpoint)
 * Digunakan oleh polling TV Display setiap 15 detik (super ringan, ~40 bytes)
 * Endpoint: GET /api/check_sync.php?id=101
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/db.php';

$masjid_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($masjid_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter ID masjid diperlukan']);
    exit;
}

$clean_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $masjid_id);
$sync_file = dirname(__DIR__) . '/uploads/sync/' . $clean_id . '.json';

// 1. Jika file statis sudah ada di disk, langsung sajikan (0 Query ke Database)
if (file_exists($sync_file)) {
    readfile($sync_file);
    exit;
}

// 2. Jika file belum ada di disk (kasus baru), ambil dari database lalu buat filenya
try {
    $stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(updated_at) AS sync_hash FROM masjid WHERE id = ? LIMIT 1");
    $stmt->execute([$masjid_id]);
    $sync_hash = $stmt->fetchColumn();

    if ($sync_hash === false || $sync_hash === null) {
        $sync_hash = time();
    }

    // Pastikan file static JSON juga terbuat
    update_sync_file($masjid_id, $sync_hash);

    echo json_encode([
        'status'    => 'success',
        'masjid_id' => $masjid_id,
        'sync_hash' => (string)$sync_hash
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memeriksa sinkronisasi']);
}
