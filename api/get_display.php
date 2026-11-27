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

// 1. Ambil Data Profil Masjid (Eksklusif kolom publik, tanpa password)
$stmt = $pdo->prepare("SELECT id, nama, lokasi, kontak_info, logo, UNIX_TIMESTAMP(updated_at) AS sync_hash FROM profile WHERE id = ? LIMIT 1");
$stmt->execute([$masjid_id]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Masjid dengan ID tersebut tidak ditemukan']);
    exit;
}

// 2. Ambil Pengaturan Jadwal Sholat (schedule)
$stmt = $pdo->prepare("SELECT * FROM schedule WHERE masjid_id = ? LIMIT 1");
$stmt->execute([$masjid_id]);
$schedule = $stmt->fetch() ?: [
    'latitude' => -6.140000, 'longitude' => 106.590000, 'timezone' => 7, 'hijri_offset' => 0,
    'calc_method' => 'Kemenag', 'calc_fajr_angle' => 20.0, 'calc_isha_angle' => 18.0, 'calc_asr_method' => 'Standard',
    'tune_fajr' => 1, 'tune_sunrise' => 0, 'tune_dhuhr' => 0, 'tune_asr' => 2, 'tune_maghrib' => 6, 'tune_isha' => 1,
    'imsak_mode' => 'auto', 'imsak_minutes' => 10
];

// 3. Ambil Pengaturan Timer Layar TV (timer)
$stmt = $pdo->prepare("SELECT * FROM timer WHERE masjid_id = ? LIMIT 1");
$stmt->execute([$masjid_id]);
$timer = $stmt->fetch() ?: [
    'timer_wait_adzan' => 1, 'timer_adzan' => 5, 'timer_sholat' => 10,
    'countdown_beep' => 10, 'kecepatan_running_text' => 5,
    'iqomah_fajr' => 10, 'iqomah_dhuhr' => 10, 'iqomah_asr' => 10, 'iqomah_maghrib' => 5, 'iqomah_isha' => 10
];

// 4. Ambil Slide yang Aktif (slides)
$stmt = $pdo->prepare("SELECT id, tipe, judul, ayat, arti, surah, konten, durasi FROM slides WHERE masjid_id = ? AND is_active = 1 ORDER BY urutan ASC, id ASC");
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

// 5. Ambil Running Text yang Aktif (running_text)
$stmt = $pdo->prepare("SELECT teks FROM running_text WHERE masjid_id = ? AND is_active = 1 ORDER BY urutan ASC, id ASC");
$stmt->execute([$masjid_id]);
$running_text = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Susun Format JSON Resmi Layar Masjid
$sync_hash = (string)($profile['sync_hash'] ?? time());
update_sync_file($masjid_id, $sync_hash);

$response = [
    'sync_hash' => $sync_hash,
    'setting' => [
        'id'           => $profile['id'],
        'nama'         => $profile['nama'],
        'lokasi'       => $profile['lokasi'] ?? '',
        'kontak_info'  => $profile['kontak_info'] ?? '',
        'logo'         => $profile['logo'] ?? '',
        'latitude'     => (float)($schedule['latitude'] ?? -6.14),
        'longitude'    => (float)($schedule['longitude'] ?? 106.59),
        'timeZone'     => (int)($schedule['timezone'] ?? 7),
        'dst'          => 0,
        'hijri_offset' => (int)($schedule['hijri_offset'] ?? 0),
        'imsak_mode'    => $schedule['imsak_mode'] ?? 'auto',
        'imsak_minutes' => (int)($schedule['imsak_minutes'] ?? 10)
    ],
    'prayTimesAdjust' => [
        'imsak'   => ((int)($schedule['imsak_minutes'] ?? 10)) . ' min',
        'fajr'    => (float)($schedule['calc_fajr_angle'] ?? 20),
        'dhuhr'   => 0,
        'asr'     => $schedule['calc_asr_method'] ?? 'Standard',
        'maghrib' => 0,
        'isha'    => (float)($schedule['calc_isha_angle'] ?? 18)
    ],
    'prayTimesTune' => [
        'fajr'    => (int)$schedule['tune_fajr'],
        'sunrise' => (int)$schedule['tune_sunrise'],
        'dhuhr'   => (int)$schedule['tune_dhuhr'],
        'asr'     => (int)$schedule['tune_asr'],
        'maghrib' => (int)$schedule['tune_maghrib'],
        'isha'    => (int)$schedule['tune_isha']
    ],
    'prayName' => [
        'imsak'   => 'Imsak',
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
        'slide_interval'        => 15,
        'wait_adzan'            => (int)$timer['timer_wait_adzan'],
        'countdown_beep'        => (int)($timer['countdown_beep'] ?? 10),
        'adzan'                 => (int)$timer['timer_adzan'],
        'sholat'                => (int)$timer['timer_sholat'],
        'kecepatan_running_text'=> (int)$timer['kecepatan_running_text']
    ],
    'iqomahTimer' => [
        'fajr'    => (int)$timer['iqomah_fajr'],
        'dhuhr'   => (int)$timer['iqomah_dhuhr'],
        'asr'     => (int)$timer['iqomah_asr'],
        'maghrib' => (int)$timer['iqomah_maghrib'],
        'isha'    => (int)$timer['iqomah_isha']
    ],
    'slides'       => $slides,
    'running_text' => $running_text
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
