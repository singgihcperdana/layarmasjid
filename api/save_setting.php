<?php
/**
 * API Simpan Pengaturan Masjid & Timer
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['masjid_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';

$masjid_id = $_SESSION['masjid_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // 1. Update Profil Masjid
    $nama        = trim($_POST['nama'] ?? '');
    $lokasi      = trim($_POST['lokasi'] ?? '');
    $kontak_info = trim($_POST['kontak_info'] ?? '');
    $latitude    = (float)($_POST['latitude'] ?? -6.14);
    $longitude   = (float)($_POST['longitude'] ?? 106.59);

    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama masjid tidak boleh kosong']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE masjid SET nama = ?, lokasi = ?, kontak_info = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmt->execute([$nama, $lokasi, $kontak_info, $latitude, $longitude, $masjid_id]);

    // Update nama di session juga
    $_SESSION['masjid_nama'] = $nama;

    // 2. Update Pengaturan Timer & Koreksi Jadwal
    $tune_fajr       = (int)($_POST['tune_fajr'] ?? 1);
    $tune_sunrise    = (int)($_POST['tune_sunrise'] ?? 0);
    $tune_dhuhr      = (int)($_POST['tune_dhuhr'] ?? 0);
    $tune_asr        = (int)($_POST['tune_asr'] ?? 2);
    $tune_maghrib    = (int)($_POST['tune_maghrib'] ?? 6);
    $tune_isha       = (int)($_POST['tune_isha'] ?? 1);

    $timer_slide     = max(5, (int)($_POST['timer_slide'] ?? 15));
    $timer_wait_adzan= max(1, (int)($_POST['timer_wait_adzan'] ?? 1));
    $timer_adzan     = max(1, (int)($_POST['timer_adzan'] ?? 5));
    $timer_sholat    = max(1, (int)($_POST['timer_sholat'] ?? 10));
    $kecepatan_rt    = min(10, max(1, (int)($_POST['kecepatan_running_text'] ?? 5)));

    $iqomah_fajr     = max(1, (int)($_POST['iqomah_fajr'] ?? 10));
    $iqomah_dhuhr    = max(1, (int)($_POST['iqomah_dhuhr'] ?? 10));
    $iqomah_asr      = max(1, (int)($_POST['iqomah_asr'] ?? 10));
    $iqomah_maghrib  = max(1, (int)($_POST['iqomah_maghrib'] ?? 5));
    $iqomah_isha     = max(1, (int)($_POST['iqomah_isha'] ?? 10));

    $sql = "INSERT INTO masjid_setting 
            (masjid_id, tune_fajr, tune_sunrise, tune_dhuhr, tune_asr, tune_maghrib, tune_isha,
             timer_slide, timer_wait_adzan, timer_adzan, timer_sholat, kecepatan_running_text,
             iqomah_fajr, iqomah_dhuhr, iqomah_asr, iqomah_maghrib, iqomah_isha)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
             tune_fajr=VALUES(tune_fajr), tune_sunrise=VALUES(tune_sunrise), tune_dhuhr=VALUES(tune_dhuhr),
             tune_asr=VALUES(tune_asr), tune_maghrib=VALUES(tune_maghrib), tune_isha=VALUES(tune_isha),
             timer_slide=VALUES(timer_slide), timer_wait_adzan=VALUES(timer_wait_adzan), timer_adzan=VALUES(timer_adzan),
             timer_sholat=VALUES(timer_sholat), kecepatan_running_text=VALUES(kecepatan_running_text),
             iqomah_fajr=VALUES(iqomah_fajr), iqomah_dhuhr=VALUES(iqomah_dhuhr), iqomah_asr=VALUES(iqomah_asr),
             iqomah_maghrib=VALUES(iqomah_maghrib), iqomah_isha=VALUES(iqomah_isha)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $masjid_id, $tune_fajr, $tune_sunrise, $tune_dhuhr, $tune_asr, $tune_maghrib, $tune_isha,
        $timer_slide, $timer_wait_adzan, $timer_adzan, $timer_sholat, $kecepatan_rt,
        $iqomah_fajr, $iqomah_dhuhr, $iqomah_asr, $iqomah_maghrib, $iqomah_isha
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
