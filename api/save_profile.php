<?php
/**
 * API Simpan Profil Identitas Masjid (Termasuk Upload/Hapus Logo)
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
    $nama        = trim($_POST['nama'] ?? '');
    $lokasi      = trim($_POST['lokasi'] ?? '');
    $province_id = trim($_POST['province_id'] ?? '');
    $regency_id  = trim($_POST['regency_id'] ?? '');
    $kontak_info = trim($_POST['kontak_info'] ?? '');
    $remove_logo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';
    $latitude    = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude   = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama masjid tidak boleh kosong']);
        exit;
    }

    if (mb_strlen($nama, 'UTF-8') > 35) {
        echo json_encode(['status' => 'error', 'message' => 'Panjang nama masjid maksimal 35 karakter']);
        exit;
    }

    if (empty($lokasi)) {
        echo json_encode(['status' => 'error', 'message' => 'Alamat lengkap masjid tidak boleh kosong']);
        exit;
    }

    if (mb_strlen($lokasi, 'UTF-8') > 80) {
        echo json_encode(['status' => 'error', 'message' => 'Panjang alamat masjid maksimal 80 karakter']);
        exit;
    }

    if (!empty($kontak_info) && mb_strlen($kontak_info, 'UTF-8') > 80) {
        echo json_encode(['status' => 'error', 'message' => 'Panjang informasi kontak & rekening maksimal 80 karakter']);
        exit;
    }

    // Ambil data masjid saat ini untuk pengecekan logo & proteksi wilayah terkunci
    $stmt = $pdo->prepare("SELECT logo, province_id, regency_id FROM masjid WHERE id = ? LIMIT 1");
    $stmt->execute([$masjid_id]);
    $curData = $stmt->fetch();
    $current_logo = $curData ? ($curData['logo'] ?? '') : '';

    // KUNCI PERMANEN: Jika wilayah sudah terisi, pertahankan data database (tidak boleh ditimpa)
    if ($curData && !empty($curData['province_id']) && !empty($curData['regency_id'])) {
        $province_id = $curData['province_id'];
        $regency_id  = $curData['regency_id'];
    }

    // VALIDASI PEMBATAS RADIUS WILAYAH (RADIUS GUARD)
    // Pastikan koordinat masjid tidak digeser keluar dari batas wajar kabupaten terdaftar (maks. 75 km)
    if ($latitude !== null && $longitude !== null && !empty($regency_id)) {
        $stmtRegCheck = $pdo->prepare("SELECT name, latitude, longitude FROM regencies WHERE id = ? LIMIT 1");
        $stmtRegCheck->execute([$regency_id]);
        $regRow = $stmtRegCheck->fetch();

        if ($regRow && $regRow['latitude'] !== null && $regRow['longitude'] !== null) {
            $regLat = (float)$regRow['latitude'];
            $regLng = (float)$regRow['longitude'];

            $earth_radius = 6371; // km
            $dLat = deg2rad($latitude - $regLat);
            $dLon = deg2rad($longitude - $regLng);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($regLat)) * cos(deg2rad($latitude)) *
                 sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $dist_km = $earth_radius * $c;

            // Kalibrasi radius proporsional:
            // 1. Kota: 15 km (wilayah kota sangat kompak)
            // 2. Kabupaten di Jawa/Bali: 35 km (mencakup pelosok kabupaten tanpa tembus ke kabupaten tetangga)
            // 3. Kabupaten luar Jawa: 55 km
            $regName    = $regRow['name'];
            $isCity     = (stripos($regName, 'Kota') === 0);
            $provNum    = (int)$province_id;
            $isJavaBali = in_array($provNum, [31, 32, 33, 34, 35, 36, 51]);

            if ($isCity) {
                $max_radius_km = 15;
            } elseif ($isJavaBali) {
                $max_radius_km = 35;
            } else {
                $max_radius_km = 55;
            }

            if ($dist_km > $max_radius_km) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Titik koordinat terlalu jauh (' . round($dist_km) . ' km) dari pusat wilayah ' . $regRow['name'] . ' (maks. ' . $max_radius_km . ' km). Titik masjid harus berada di dalam batas wilayah yang terdaftar.'
                ]);
                exit;
            }
        }
    }

    $logo_to_save = $current_logo;

    // 1. Jika ada perintah hapus logo
    if ($remove_logo) {
        $logo_to_save = '';
    }

    // 2. Jika ada file logo baru yang diunggah
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts)) {
            echo json_encode(['status' => 'error', 'message' => 'Format logo harus JPG, PNG, WebP, atau SVG.']);
            exit;
        }

        if ($file['size'] > 4 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'Ukuran file logo maksimal 4 MB.']);
            exit;
        }

        $upload_dir = __DIR__ . '/../uploads/' . $masjid_id;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = 'logo_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $logo_to_save = 'uploads/' . $masjid_id . '/' . $filename;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file logo ke server.']);
            exit;
        }
    }

    // Update profil masjid
    if ($latitude !== null && $longitude !== null) {
        $stmt = $pdo->prepare("UPDATE masjid SET nama = ?, lokasi = ?, province_id = ?, regency_id = ?, latitude = ?, longitude = ?, kontak_info = ?, logo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nama, $lokasi, $province_id ?: null, $regency_id ?: null, $latitude, $longitude, $kontak_info, $logo_to_save, $masjid_id]);

        // Sinkronkan ke tabel schedule agar hisab jadwal sholat selalu sinkron
        $stmtSched = $pdo->prepare("UPDATE schedule SET latitude = ?, longitude = ? WHERE masjid_id = ?");
        $stmtSched->execute([$latitude, $longitude, $masjid_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE masjid SET nama = ?, lokasi = ?, province_id = ?, regency_id = ?, kontak_info = ?, logo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nama, $lokasi, $province_id ?: null, $regency_id ?: null, $kontak_info, $logo_to_save, $masjid_id]);
    }

    update_sync_file($masjid_id);

    $_SESSION['masjid_nama'] = $nama;

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Profil masjid berhasil diperbarui!',
        'logo_url' => $logo_to_save
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
