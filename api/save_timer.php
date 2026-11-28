<?php
/**
 * API Simpan Pengaturan Timer Layar TV
 * File: api/save_timer.php
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['masjid_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi login telah habis']);
    exit;
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan']);
    exit;
}

$masjid_id = $_SESSION['masjid_id'];

// Ambil input
$kecepatan_running_text = isset($_POST['kecepatan_running_text']) ? max(1, min(10, (int)$_POST['kecepatan_running_text'])) : 5;
$timer_wait_adzan       = isset($_POST['timer_wait_adzan']) ? max(0, (int)$_POST['timer_wait_adzan']) : 1;
$timer_adzan            = isset($_POST['timer_adzan']) ? max(0, (int)$_POST['timer_adzan']) : 5;
$timer_sholat           = isset($_POST['timer_sholat']) ? max(0, (int)$_POST['timer_sholat']) : 10;
$countdown_beep         = isset($_POST['countdown_beep']) ? max(0, (int)$_POST['countdown_beep']) : 10;

$iqomah_fajr    = isset($_POST['iqomah_fajr']) ? max(0, (int)$_POST['iqomah_fajr']) : 10;
$iqomah_dhuhr   = isset($_POST['iqomah_dhuhr']) ? max(0, (int)$_POST['iqomah_dhuhr']) : 10;
$iqomah_asr     = isset($_POST['iqomah_asr']) ? max(0, (int)$_POST['iqomah_asr']) : 10;
$iqomah_maghrib = isset($_POST['iqomah_maghrib']) ? max(0, (int)$_POST['iqomah_maghrib']) : 5;
$iqomah_isha    = isset($_POST['iqomah_isha']) ? max(0, (int)$_POST['iqomah_isha']) : 10;

try {
    // Cek apakah setting sudah ada
    $stmt = $pdo->prepare("SELECT * FROM timer WHERE masjid_id = ? LIMIT 1");
    $stmt->execute([$masjid_id]);
    $exists = $stmt->fetch();

    $timer_jumat = isset($_POST['timer_jumat']) ? max(1, (int)$_POST['timer_jumat']) : (int)($exists['timer_jumat'] ?? 45);

    if ($exists) {
        $sql = "UPDATE timer SET 
                    kecepatan_running_text = ?,
                    timer_wait_adzan = ?,
                    timer_adzan = ?,
                    timer_sholat = ?,
                    timer_jumat = ?,
                    countdown_beep = ?,
                    iqomah_fajr = ?,
                    iqomah_dhuhr = ?,
                    iqomah_asr = ?,
                    iqomah_maghrib = ?,
                    iqomah_isha = ?
                WHERE masjid_id = ?";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([
            $kecepatan_running_text,
            $timer_wait_adzan,
            $timer_adzan,
            $timer_sholat,
            $timer_jumat,
            $countdown_beep,
            $iqomah_fajr,
            $iqomah_dhuhr,
            $iqomah_asr,
            $iqomah_maghrib,
            $iqomah_isha,
            $masjid_id
        ]);
    } else {
        $sql = "INSERT INTO timer 
                    (masjid_id, kecepatan_running_text, timer_wait_adzan, timer_adzan, timer_sholat, timer_jumat, countdown_beep, iqomah_fajr, iqomah_dhuhr, iqomah_asr, iqomah_maghrib, iqomah_isha)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sql);
        $stmtInsert->execute([
            $masjid_id,
            $kecepatan_running_text,
            $timer_wait_adzan,
            $timer_adzan,
            $timer_sholat,
            $timer_jumat,
            $countdown_beep,
            $iqomah_fajr,
            $iqomah_dhuhr,
            $iqomah_asr,
            $iqomah_maghrib,
            $iqomah_isha
        ]);
    }

    // Sentuh updated_at di masjid agar TV display mendeteksi perubahan
    $pdo->prepare("UPDATE masjid SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
    update_sync_file($masjid_id);

    echo json_encode(['status' => 'success', 'message' => 'Pengaturan timer berhasil disimpan']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan database: ' . $e->getMessage()]);
}
