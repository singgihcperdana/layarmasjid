<?php
/**
 * API Simpan Pengaturan Jadwal Sholat & Koordinat
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
    // 1. Ambil Parameter Geografis & Metode Hisab
    $latitude     = (float)($_POST['latitude'] ?? -6.14);
    $longitude    = (float)($_POST['longitude'] ?? 106.59);
    $timezone     = (int)($_POST['timezone'] ?? 7);
    $hijri_offset = (int)($_POST['hijri_offset'] ?? 0);

    $calc_method     = trim($_POST['calc_method'] ?? 'Kemenag');
    $calc_fajr_angle = (float)($_POST['calc_fajr_angle'] ?? 20.0);
    $calc_isha_angle = (float)($_POST['calc_isha_angle'] ?? 18.0);
    $calc_asr_method = in_array($_POST['calc_asr_method'] ?? '', ['Standard', 'Hanafi']) ? $_POST['calc_asr_method'] : 'Standard';

    // 2. Ambil Koreksi Menit Ihtiyat
    $tune_fajr       = (int)($_POST['tune_fajr'] ?? 1);
    $tune_sunrise    = (int)($_POST['tune_sunrise'] ?? 0);
    $tune_dhuhr      = (int)($_POST['tune_dhuhr'] ?? 0);
    $tune_asr        = (int)($_POST['tune_asr'] ?? 2);
    $tune_maghrib    = (int)($_POST['tune_maghrib'] ?? 6);
    $tune_isha       = (int)($_POST['tune_isha'] ?? 1);

    // 3. Simpan ke Tabel schedule (1 Tabel Mandiri)
    $sql = "INSERT INTO schedule 
            (masjid_id, latitude, longitude, timezone, hijri_offset, calc_method,
             calc_fajr_angle, calc_isha_angle, calc_asr_method,
             tune_fajr, tune_sunrise, tune_dhuhr, tune_asr, tune_maghrib, tune_isha)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
             latitude=VALUES(latitude), longitude=VALUES(longitude),
             timezone=VALUES(timezone), hijri_offset=VALUES(hijri_offset),
             calc_method=VALUES(calc_method), calc_fajr_angle=VALUES(calc_fajr_angle),
             calc_isha_angle=VALUES(calc_isha_angle), calc_asr_method=VALUES(calc_asr_method),
             tune_fajr=VALUES(tune_fajr), tune_sunrise=VALUES(tune_sunrise), tune_dhuhr=VALUES(tune_dhuhr),
             tune_asr=VALUES(tune_asr), tune_maghrib=VALUES(tune_maghrib), tune_isha=VALUES(tune_isha)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $masjid_id, $latitude, $longitude, $timezone, $hijri_offset, $calc_method,
        $calc_fajr_angle, $calc_isha_angle, $calc_asr_method,
        $tune_fajr, $tune_sunrise, $tune_dhuhr, $tune_asr, $tune_maghrib, $tune_isha
    ]);

    // Sentuh updated_at di profile agar TV display mendeteksi perubahan
    $pdo->prepare("UPDATE profile SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
    update_sync_file($masjid_id);

    echo json_encode(['status' => 'success', 'message' => 'Pengaturan jadwal sholat & koordinat berhasil disimpan!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
