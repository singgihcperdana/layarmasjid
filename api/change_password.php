<?php
/**
 * API Ganti Password Admin DKM
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
    $old_password     = $_POST['old_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom password wajib diisi.']);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password baru minimal 6 karakter.']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password baru tidak cocok.']);
        exit;
    }

    // Ambil password saat ini dari database
    $stmt = $pdo->prepare("SELECT password FROM profile WHERE id = ? LIMIT 1");
    $stmt->execute([$masjid_id]);
    $current_hash = $stmt->fetchColumn();

    if (!$current_hash || !password_verify($old_password, $current_hash)) {
        echo json_encode(['status' => 'error', 'message' => 'Password lama salah. Silakan periksa kembali.']);
        exit;
    }

    // Hash password baru & simpan
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE profile SET password = ? WHERE id = ?");
    $stmt->execute([$new_hash, $masjid_id]);

    echo json_encode(['status' => 'success', 'message' => 'Password berhasil diperbarui! Gunakan password baru untuk login berikutnya.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
