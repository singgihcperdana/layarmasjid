<?php
/**
 * API Upload Gambar Pamflet / Slide Kajian
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File gambar tidak ditemukan']);
    exit;
}

$file = $_FILES['image'];
$durasi = max(5, (int)($_POST['durasi'] ?? 15));

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file. Error code: ' . $file['error']]);
    exit;
}

// Validasi tipe file
$allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_exts)) {
    echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Harap upload format JPG, PNG, atau WebP.']);
    exit;
}

// Validasi ukuran (max 8MB)
if ($file['size'] > 8 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar (maksimal 8 MB).']);
    exit;
}

// Buat folder uploads/{masjid_id}/ jika belum ada
$upload_dir = __DIR__ . '/../uploads/' . $masjid_id;
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Nama file acak yang aman
$filename = 'slide_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
$target_path = $upload_dir . '/' . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $web_path = 'uploads/' . $masjid_id . '/' . $filename;

    // Ambil urutan terakhir
    $stmt = $pdo->prepare("SELECT MAX(urutan) FROM slides WHERE masjid_id = ?");
    $stmt->execute([$masjid_id]);
    $max_urutan = (int)$stmt->fetchColumn();

    // Simpan ke database
    $stmt = $pdo->prepare("INSERT INTO slides (masjid_id, tipe, urutan, konten, durasi, is_active) VALUES (?, 'image', ?, ?, ?, 1)");
    $stmt->execute([$masjid_id, $max_urutan + 1, $web_path, $durasi]);

    // Update updated_at masjid dan static sync file
    $pdo->prepare("UPDATE masjid SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
    update_sync_file($masjid_id);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Gambar pamflet berhasil diunggah!',
        'path'    => $web_path
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file yang diunggah ke server.']);
}
