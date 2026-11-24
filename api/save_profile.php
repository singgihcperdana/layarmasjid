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
    $kontak_info = trim($_POST['kontak_info'] ?? '');
    $remove_logo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';

    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama masjid tidak boleh kosong']);
        exit;
    }

    // Ambil data logo saat ini
    $stmt = $pdo->prepare("SELECT logo FROM masjid WHERE id = ? LIMIT 1");
    $stmt->execute([$masjid_id]);
    $current_logo = $stmt->fetchColumn() ?: '';

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
    $stmt = $pdo->prepare("UPDATE masjid SET nama = ?, lokasi = ?, kontak_info = ?, logo = ? WHERE id = ?");
    $stmt->execute([$nama, $lokasi, $kontak_info, $logo_to_save, $masjid_id]);

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
