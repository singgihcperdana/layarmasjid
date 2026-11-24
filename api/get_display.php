<?php
/**
 * API Get Display Config untuk Layar TV Masjid
 * Endpoint: GET /api/get_display.php?id=101 (atau /api/get_display?id=101)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$masjid_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($masjid_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter ID masjid diperlukan']);
    exit;
}

// 1. Ambil Data Masjid
$stmt = $pdo->prepare("SELECT * FROM masjid WHERE id = ? LIMIT 1");
$stmt->execute([$masjid_id]);
$masjid = $stmt->fetch();

if (!$masjid) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Masjid dengan ID tersebut tidak ditemukan']);
    exit;
}

// 2. Ambil Pengaturan Timer & Waktu Sholat
$stmt = $pdo->prepare("SELECT * FROM masjid_setting WHERE masjid_id = ? LIMIT 1");
$stmt->execute([$masjid_id]);
$setting = $stmt->fetch();

// Default values jika belum diset
if (!$setting) {
    $setting = [
        'tune_fajr' => 1, 'tune_sunrise' => 0, 'tune_dhuhr' => 0, 'tune_asr' => 2, 'tune_maghrib' => 6, 'tune_isha' => 1,
        'timer_slide' => 15, 'timer_wait_adzan' => 1, 'timer_adzan' => 5, 'timer_sholat' => 10, 'kecepatan_running_text' => 5,
        'iqomah_fajr' => 10, 'iqomah_dhuhr' => 10, 'iqomah_asr' => 10, 'iqomah_maghrib' => 5, 'iqomah_isha' => 10
    ];
}

// 3. Ambil Slide yang Aktif
$stmt = $pdo->prepare("SELECT id, tipe, judul, ayat, arti, surah, konten, durasi FROM masjid_slides WHERE masjid_id = ? AND is_active = 1 ORDER BY urutan ASC, id ASC");
$stmt->execute([$masjid_id]);
$raw_slides = $stmt->fetchAll();

$slides = [];
foreach ($raw_slides as $s) {
    if ($s['tipe'] === 'quranHadist') {
        $slides[] = [
            'id'    => (int)$s['id'],
            'type'  => 'quranHadist',
            'ayat'  => $s['ayat'] ?? '',
            'arti'  => $s['arti'] ?? '',
            'surah' => $s['surah'] ?? ''
        ];
    } elseif ($s['tipe'] === 'info') {
        $slides[] = [
            'id'       => (int)$s['id'],
            'type'     => 'info',
            'title'    => $s['judul'] ?? '',
            'content'  => $s['konten'] ?? '',
            'duration' => (int)($s['durasi'] ?: 15)
        ];
    } else { // image
        $slides[] = [
            'id'       => (int)$s['id'],
            'type'     => 'image',
            'content'  => $s['konten'] ?? '',
            'duration' => (int)($s['durasi'] ?: 15)
        ];
    }
}

// 4. Ambil Running Text yang Aktif
$stmt = $pdo->prepare("SELECT teks FROM masjid_running_text WHERE masjid_id = ? AND is_active = 1 ORDER BY urutan ASC, id ASC");
$stmt->execute([$masjid_id]);
$running_text = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Susun Format JSON Resmi Layar Masjid
$response = [
    'setting' => [
        'id'           => $masjid['id'],
        'nama'         => $masjid['nama'],
        'lokasi'       => $masjid['lokasi'] ?? '',
        'kontak_info'  => $masjid['kontak_info'] ?? '',
        'logo'         => $masjid['logo'] ?? '',
        'latitude'     => (float)($masjid['latitude'] ?? -6.14),
        'longitude'    => (float)($masjid['longitude'] ?? 106.59),
        'timeZone'     => (int)($masjid['timezone'] ?? 7),
        'dst'          => 0,
        'hijri_offset' => (int)($masjid['hijri_offset'] ?? 0)
    ],
    'prayTimesAdjust' => [
        'fajr'    => (float)($setting['calc_fajr_angle'] ?? 20),
        'dhuhr'   => 0,
        'asr'     => $setting['calc_asr_method'] ?? 'Standard',
        'maghrib' => 0,
        'isha'    => (float)($setting['calc_isha_angle'] ?? 18)
    ],
    'prayTimesTune' => [
        'fajr'    => (int)$setting['tune_fajr'],
        'sunrise' => (int)$setting['tune_sunrise'],
        'dhuhr'   => (int)$setting['tune_dhuhr'],
        'asr'     => (int)$setting['tune_asr'],
        'maghrib' => (int)$setting['tune_maghrib'],
        'isha'    => (int)$setting['tune_isha']
    ],
    'prayName' => [
        'fajr'    => 'Subuh',
        'sunrise' => 'Syuruq',
        'dhuhr'   => 'Dzuhur',
        'asr'     => 'Ashar',
        'maghrib' => 'Maghrib',
        'isha'    => 'Isya'
    ],
    'dayName' => [
        'Sunday'    => 'Ahad',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jum\'at',
        'Saturday'  => 'Sabtu'
    ],
    'monthName' => [
        'January'   => 'Januari',
        'February'  => 'Februari',
        'March'     => 'Maret',
        'April'     => 'April',
        'May'       => 'Mei',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'August'    => 'Agustus',
        'September' => 'September',
        'October'   => 'Oktober',
        'November'  => 'November',
        'December'  => 'Desember'
    ],
    'timer' => [
        'slide_interval'        => (int)$setting['timer_slide'],
        'wait_adzan'            => (int)$setting['timer_wait_adzan'],
        'countdown_beep'        => (int)($setting['countdown_beep'] ?? 10),
        'adzan'                 => (int)$setting['timer_adzan'],
        'sholat'                => (int)$setting['timer_sholat'],
        'kecepatan_running_text'=> (int)$setting['kecepatan_running_text']
    ],
    'iqomahTimer' => [
        'fajr'    => (int)$setting['iqomah_fajr'],
        'dhuhr'   => (int)$setting['iqomah_dhuhr'],
        'asr'     => (int)$setting['iqomah_asr'],
        'maghrib' => (int)$setting['iqomah_maghrib'],
        'isha'    => (int)$setting['iqomah_isha']
    ],
    'slides'       => $slides,
    'running_text' => $running_text
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
