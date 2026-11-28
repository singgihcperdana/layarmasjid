<?php
/**
 * API Kelola Pesan Running Text Layar TV Masjid
 * File: api/manage_running_text.php
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

$masjid_id = $_SESSION['masjid_id'];
$action    = $_POST['action'] ?? ($_GET['action'] ?? '');

try {
    // 1. LIST TEKS
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM running_text WHERE masjid_id = ? ORDER BY urutan ASC, id ASC");
        $stmt->execute([$masjid_id]);
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    // 2. GET SINGLE TEKS
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM running_text WHERE id = ? AND masjid_id = ? LIMIT 1");
        $stmt->execute([$id, $masjid_id]);
        $item = $stmt->fetch();
        if ($item) {
            echo json_encode(['status' => 'success', 'data' => $item]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Pesan teks tidak ditemukan']);
        }
        exit;
    }

    // 3. ADD / SAVE TEKS
    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $teks = trim($_POST['teks'] ?? '');

        if (empty($teks)) {
            echo json_encode(['status' => 'error', 'message' => 'Pesan teks tidak boleh kosong']);
            exit;
        }

        if (mb_strlen($teks, 'UTF-8') > 255) {
            echo json_encode(['status' => 'error', 'message' => 'Panjang pesan teks maksimal 255 karakter']);
            exit;
        }

        if ($id > 0) {
            // Edit teks
            $stmt = $pdo->prepare("UPDATE running_text SET teks = ? WHERE id = ? AND masjid_id = ?");
            $stmt->execute([$teks, $id, $masjid_id]);
            $msg = 'Pesan teks berhasil diperbarui';
        } else {
            // Tambah teks baru
            $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(urutan), 0) FROM running_text WHERE masjid_id = ?");
            $stmtMax->execute([$masjid_id]);
            $max_urutan = (int)$stmtMax->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO running_text (masjid_id, teks, urutan, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$masjid_id, $teks, $max_urutan + 1]);
            $msg = 'Pesan teks baru berhasil ditambahkan';
        }

        $pdo->prepare("UPDATE masjid SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
        update_sync_file($masjid_id);
        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

    // 4. MOVE TEKS (UP / DOWN)
    if ($action === 'move') {
        $id        = (int)($_POST['id'] ?? 0);
        $direction = strtolower($_POST['direction'] ?? '');

        if (!in_array($direction, ['up', 'down'])) {
            echo json_encode(['status' => 'error', 'message' => 'Arah pergerakan tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM running_text WHERE masjid_id = ? ORDER BY urutan ASC, id ASC");
        $stmt->execute([$masjid_id]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $currentIndex = array_search($id, $ids);
        if ($currentIndex === false) {
            echo json_encode(['status' => 'error', 'message' => 'Pesan teks tidak ditemukan']);
            exit;
        }

        $swapIndex = ($direction === 'up') ? $currentIndex - 1 : $currentIndex + 1;
        if ($swapIndex >= 0 && $swapIndex < count($ids)) {
            $temp = $ids[$currentIndex];
            $ids[$currentIndex] = $ids[$swapIndex];
            $ids[$swapIndex] = $temp;

            $stmtUpdate = $pdo->prepare("UPDATE running_text SET urutan = ? WHERE id = ? AND masjid_id = ?");
            foreach ($ids as $newUrutan => $textId) {
                $stmtUpdate->execute([$newUrutan + 1, $textId, $masjid_id]);
            }

            $pdo->prepare("UPDATE masjid SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
            update_sync_file($masjid_id);
            echo json_encode(['status' => 'success', 'message' => 'Urutan teks berhasil diubah']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Teks sudah berada di posisi paling ujung']);
        }
        exit;
    }

    // 5. TOGGLE AKTIF / NONAKTIF
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE running_text SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND masjid_id = ?");
        $stmt->execute([$id, $masjid_id]);
        $pdo->prepare("UPDATE masjid SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
        update_sync_file($masjid_id);
        echo json_encode(['status' => 'success', 'message' => 'Status aktif teks berhasil diubah']);
        exit;
    }

    // 6. DELETE TEKS
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM running_text WHERE id = ? AND masjid_id = ?");
        $stmt->execute([$id, $masjid_id]);

        // Renumber urutan
        $stmt = $pdo->prepare("SELECT id FROM running_text WHERE masjid_id = ? ORDER BY urutan ASC, id ASC");
        $stmt->execute([$masjid_id]);
        $remainingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmtUpdate = $pdo->prepare("UPDATE running_text SET urutan = ? WHERE id = ? AND masjid_id = ?");
        foreach ($remainingIds as $idx => $rId) {
            $stmtUpdate->execute([$idx + 1, $rId, $masjid_id]);
        }

        $pdo->prepare("UPDATE masjid SET updated_at = NOW() WHERE id = ?")->execute([$masjid_id]);
        update_sync_file($masjid_id);
        echo json_encode(['status' => 'success', 'message' => 'Pesan teks berhasil dihapus']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
