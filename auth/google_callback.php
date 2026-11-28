<?php
/**
 * Endpoint Callback Google OAuth 2.0
 * File: auth/google_callback.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/env.php';

$client_id     = trim(env('GOOGLE_CLIENT_ID', ''));
$client_secret = trim(env('GOOGLE_CLIENT_SECRET', ''));
$redirect_uri  = trim(env('GOOGLE_REDIRECT_URI', 'http://localhost/layarmasjid/auth/google_callback.php'));

// 1. Cek jika user membatalkan login di Google
if (isset($_GET['error'])) {
    header("Location: ../login?error=" . urlencode("Login Google dibatalkan: " . $_GET['error']));
    exit;
}

// 2. Validasi parameter code dan state CSRF
$code  = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code) || empty($state) || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
    unset($_SESSION['oauth_state']);
    header("Location: ../login?error=" . urlencode("Autentikasi gagal atau sesi telah kedaluwarsa. Silakan coba lagi."));
    exit;
}
unset($_SESSION['oauth_state']);

// Helper HTTP POST (kompatibel cURL atau native PHP stream)
function http_post_google($url, $data) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    $options = [
        'http' => [
            'header'        => "Content-type: application/x-www-form-urlencoded\r\nUser-Agent: LayarMasjid-App\r\n",
            'method'        => 'POST',
            'content'       => http_build_query($data),
            'ignore_errors' => true,
            'timeout'       => 15
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ];
    return @file_get_contents($url, false, stream_context_create($options));
}

// Helper HTTP GET (kompatibel cURL atau native PHP stream)
function http_get_google($url, $bearer_token = '') {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($bearer_token) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $bearer_token, 'User-Agent: LayarMasjid-App']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    $headers = "User-Agent: LayarMasjid-App\r\n";
    if ($bearer_token) {
        $headers .= "Authorization: Bearer {$bearer_token}\r\n";
    }

    $options = [
        'http' => [
            'header'        => $headers,
            'method'        => 'GET',
            'ignore_errors' => true,
            'timeout'       => 15
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ];
    return @file_get_contents($url, false, stream_context_create($options));
}

// 3. Tukarkan 'code' dengan Access Token via HTTP POST ke Google
$token_url = 'https://oauth2.googleapis.com/token';
$post_fields = [
    'code'          => $code,
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code'
];

$response = http_post_google($token_url, $post_fields);
$token_data = json_decode($response, true);

if (empty($token_data['access_token'])) {
    $err_msg = $token_data['error_description'] ?? ($token_data['error'] ?? 'Gagal mendapatkan token akses dari Google.');
    header("Location: ../login?error=" . urlencode($err_msg));
    exit;
}

// 4. Ambil informasi profil pengguna dari Google UserInfo API
$userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
$userinfo_res = http_get_google($userinfo_url, $token_data['access_token']);
$user_profile = json_decode($userinfo_res, true);

if (empty($user_profile['sub']) || empty($user_profile['email'])) {
    header("Location: ../login?error=" . urlencode("Gagal mengambil informasi profil dari akun Google Anda."));
    exit;
}

$google_id = $user_profile['sub'];
$email     = strtolower(trim($user_profile['email']));
$nama      = trim($user_profile['name'] ?? 'Pengurus DKM');
$avatar    = trim($user_profile['picture'] ?? '');

try {
    // 5. Cari user berdasarkan google_id
    $stmt = $pdo->prepare("
        SELECT u.*, m.nama AS masjid_nama 
        FROM users u 
        JOIN masjid m ON u.masjid_id = m.id 
        WHERE u.google_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$google_id]);
    $user = $stmt->fetch();

    // Jika belum ketemu dengan google_id, cari berdasarkan email
    if (!$user) {
        $stmtEmail = $pdo->prepare("
            SELECT u.*, m.nama AS masjid_nama 
            FROM users u 
            JOIN masjid m ON u.masjid_id = m.id 
            WHERE u.email = ? 
            LIMIT 1
        ");
        $stmtEmail->execute([$email]);
        $user = $stmtEmail->fetch();

        if ($user) {
            // Tautkan google_id ke akun yang sudah ada
            $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar = COALESCE(avatar, ?), updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$google_id, $avatar, $user['id']]);
        }
    }

    // 6. Jika user sama sekali baru -> Pendaftaran Otomatis!
    if (!$user) {
        // Tentukan ID masjid baru (ambil nomor ID masjid terbesar + 1)
        $stmtMax = $pdo->query("SELECT MAX(CAST(id AS UNSIGNED)) AS max_id FROM masjid");
        $max_id = (int)($stmtMax->fetchColumn() ?: 100);
        $new_masjid_id = (string)(max(101, $max_id + 1));

        // Buat data masjid baru
        $stmtNewMasjid = $pdo->prepare("
            INSERT INTO masjid (id, nama, lokasi, latitude, longitude, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $masjid_nama_awal = 'Masjid ' . $nama;
        $stmtNewMasjid->execute([$new_masjid_id, $masjid_nama_awal, '', -6.140000, 106.590000]);

        // Buat default schedule untuk masjid baru
        $pdo->prepare("
            INSERT INTO schedule (masjid_id, latitude, longitude, timezone, hijri_offset, calc_method, calc_fajr_angle, calc_isha_angle, calc_asr_method, tune_fajr, tune_sunrise, tune_dhuhr, tune_asr, tune_maghrib, tune_isha)
            VALUES (?, -6.140000, 106.590000, 7, 0, 'Kemenag', 20.0, 18.0, 'Standard', 1, 0, 0, 2, 6, 1)
        ")->execute([$new_masjid_id]);

        // Buat default timer untuk masjid baru
        $pdo->prepare("
            INSERT INTO timer (masjid_id, timer_wait_adzan, timer_adzan, timer_sholat, countdown_beep, kecepatan_running_text, iqomah_fajr, iqomah_dhuhr, iqomah_asr, iqomah_maghrib, iqomah_isha)
            VALUES (?, 1, 5, 10, 10, 5, 10, 10, 10, 5, 10)
        ")->execute([$new_masjid_id]);

        // Buat username unik dari bagian depan email
        $base_username = preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]);
        if (empty($base_username)) $base_username = 'dkm';
        $username = $base_username;
        $counter = 1;
        while (true) {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmtCheck->execute([$username]);
            if (!$stmtCheck->fetch()) {
                break;
            }
            $username = $base_username . $counter;
            $counter++;
        }

        // Simpan user baru
        $stmtInsertUser = $pdo->prepare("
            INSERT INTO users (masjid_id, username, email, google_id, nama, avatar, role, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'admin', NOW(), NOW())
        ");
        $stmtInsertUser->execute([$new_masjid_id, $username, $email, $google_id, $nama, $avatar]);
        $new_user_id = $pdo->lastInsertId();

        // Update static sync file untuk masjid baru
        update_sync_file($new_masjid_id);

        $user = [
            'id'          => $new_user_id,
            'username'    => $username,
            'nama'        => $nama,
            'role'        => 'admin',
            'masjid_id'   => $new_masjid_id,
            'masjid_nama' => $masjid_nama_awal
        ];
    }

    // 7. Simpan sesi login & redirect ke halaman profile
    $_SESSION['user_id']     = (int)$user['id'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['user_nama']   = $user['nama'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['masjid_id']   = $user['masjid_id'];
    $_SESSION['masjid_nama'] = $user['masjid_nama'];

    header("Location: ../profile");
    exit;

} catch (Exception $e) {
    header("Location: ../login?error=" . urlencode("Terjadi kesalahan sistem saat memproses login: " . $e->getMessage()));
    exit;
}
