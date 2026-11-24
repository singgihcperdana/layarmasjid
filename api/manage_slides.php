<?php
/**
 * API Kelola Konten Slide Layar TV Masjid
 * File: api/manage_slides.php
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
    // 1. LIST SLIDES
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM masjid_slides WHERE masjid_id = ? ORDER BY urutan ASC, id ASC");
        $stmt->execute([$masjid_id]);
        $slides = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $slides]);
        exit;
    }

    // 2. GET SINGLE SLIDE
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM masjid_slides WHERE id = ? AND masjid_id = ? LIMIT 1");
        $stmt->execute([$id, $masjid_id]);
        $slide = $stmt->fetch();
        if ($slide) {
            echo json_encode(['status' => 'success', 'data' => $slide]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Slide tidak ditemukan']);
        }
        exit;
    }

    // 3. SAVE (CREATE / UPDATE) SLIDE
    if ($action === 'save') {
        $id         = (int)($_POST['id'] ?? 0);
        $tipe       = $_POST['tipe'] ?? 'image';
        $raw_durasi = (int)($_POST['durasi'] ?? 15);
        if ($raw_durasi > 30) {
            echo json_encode(['status' => 'error', 'message' => 'Durasi tampil maksimal 30 detik']);
            exit;
        }
        $durasi    = max(5, $raw_durasi);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!in_array($tipe, ['image', 'info', 'quranHadist'])) {
            echo json_encode(['status' => 'error', 'message' => 'Tipe slide tidak valid']);
            exit;
        }

        $judul  = trim($_POST['judul'] ?? '');
        $konten = trim($_POST['konten'] ?? '');
        $ayat   = trim($_POST['ayat'] ?? '');
        $arti   = trim($_POST['arti'] ?? '');
        $surah  = trim($_POST['surah'] ?? '');

        // Validasi per tipe
        if ($tipe === 'quranHadist') {
            if (empty($ayat) || empty($arti)) {
                echo json_encode(['status' => 'error', 'message' => 'Teks ayat dan terjemahan/arti wajib diisi']);
                exit;
            }
        } elseif ($tipe === 'info') {
            if (empty($judul) && empty($konten)) {
                echo json_encode(['status' => 'error', 'message' => 'Judul atau isi pengumuman wajib diisi']);
                exit;
            }
        } elseif ($tipe === 'image') {
            // Cek file upload gambar
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($ext, $allowed)) {
                    echo json_encode(['status' => 'error', 'message' => 'Format gambar harus JPG, PNG, atau WEBP']);
                    exit;
                }

                if ($file['size'] > 10 * 1024 * 1024) {
                    echo json_encode(['status' => 'error', 'message' => 'Ukuran gambar maksimal 10MB']);
                    exit;
                }

                $upload_dir = __DIR__ . '/../uploads/' . $masjid_id;
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = 'slide_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
                $dest = $upload_dir . '/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $konten = 'uploads/' . $masjid_id . '/' . $filename;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file gambar']);
                    exit;
                }
            } else {
                // Jika edit dan tidak upload baru, pertahankan konten lama
                if ($id > 0) {
                    $stmtOld = $pdo->prepare("SELECT konten FROM masjid_slides WHERE id = ? AND masjid_id = ?");
                    $stmtOld->execute([$id, $masjid_id]);
                    $konten = $stmtOld->fetchColumn() ?: '';
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Silakan pilih file gambar yang ingin diunggah']);
                    exit;
                }
            }
        }

        if ($id > 0) {
            // UPDATE SLIDE
            $sql = "UPDATE masjid_slides SET 
                        tipe = ?, judul = ?, konten = ?, ayat = ?, arti = ?, surah = ?, durasi = ?, is_active = ?
                    WHERE id = ? AND masjid_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tipe, $judul, $konten, $ayat, $arti, $surah, $durasi, $is_active, $id, $masjid_id]);
            $msg = "Slide berhasil diperbarui";
        } else {
            // INSERT SLIDE BARU
            $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(urutan), 0) FROM masjid_slides WHERE masjid_id = ?");
            $stmtMax->execute([$masjid_id]);
            $max_urutan = (int)$stmtMax->fetchColumn();

            $sql = "INSERT INTO masjid_slides 
                        (masjid_id, tipe, urutan, judul, konten, ayat, arti, surah, durasi, is_active)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$masjid_id, $tipe, $max_urutan + 1, $judul, $konten, $ayat, $arti, $surah, $durasi, $is_active]);
            $msg = "Slide baru berhasil ditambahkan";
        }

        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

    // 4. MOVE SLIDE (UP / DOWN)
    if ($action === 'move') {
        $id        = (int)($_POST['id'] ?? 0);
        $direction = strtolower($_POST['direction'] ?? '');

        if (!in_array($direction, ['up', 'down'])) {
            echo json_encode(['status' => 'error', 'message' => 'Arah pergerakan tidak valid']);
            exit;
        }

        // Ambil semua slide urut
        $stmt = $pdo->prepare("SELECT id FROM masjid_slides WHERE masjid_id = ? ORDER BY urutan ASC, id ASC");
        $stmt->execute([$masjid_id]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $currentIndex = array_search($id, $ids);
        if ($currentIndex === false) {
            echo json_encode(['status' => 'error', 'message' => 'Slide tidak ditemukan']);
            exit;
        }

        $swapIndex = ($direction === 'up') ? $currentIndex - 1 : $currentIndex + 1;
        if ($swapIndex >= 0 && $swapIndex < count($ids)) {
            // Tukar posisi di array
            $temp = $ids[$currentIndex];
            $ids[$currentIndex] = $ids[$swapIndex];
            $ids[$swapIndex] = $temp;

            // Simpan urutan baru
            $stmtUpdate = $pdo->prepare("UPDATE masjid_slides SET urutan = ? WHERE id = ? AND masjid_id = ?");
            foreach ($ids as $newUrutan => $slideId) {
                $stmtUpdate->execute([$newUrutan + 1, $slideId, $masjid_id]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Urutan slide berhasil diubah']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Slide sudah berada di posisi paling ujung']);
        }
        exit;
    }

    // 5. TOGGLE AKTIF / NONAKTIF
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE masjid_slides SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND masjid_id = ?");
        $stmt->execute([$id, $masjid_id]);
        echo json_encode(['status' => 'success', 'message' => 'Status aktif slide berhasil diubah']);
        exit;
    }

    // 6. DELETE SLIDE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        // Ambil data slide untuk cek file gambar
        $stmt = $pdo->prepare("SELECT tipe, konten FROM masjid_slides WHERE id = ? AND masjid_id = ?");
        $stmt->execute([$id, $masjid_id]);
        $slide = $stmt->fetch();

        if ($slide) {
            if ($slide['tipe'] === 'image' && !empty($slide['konten']) && strpos($slide['konten'], 'uploads/') === 0) {
                $filePath = __DIR__ . '/../' . $slide['konten'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $stmtDel = $pdo->prepare("DELETE FROM masjid_slides WHERE id = ? AND masjid_id = ?");
            $stmtDel->execute([$id, $masjid_id]);

            // Renumber urutan
            $stmt = $pdo->prepare("SELECT id FROM masjid_slides WHERE masjid_id = ? ORDER BY urutan ASC, id ASC");
            $stmt->execute([$masjid_id]);
            $remainingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmtUpdate = $pdo->prepare("UPDATE masjid_slides SET urutan = ? WHERE id = ? AND masjid_id = ?");
            foreach ($remainingIds as $idx => $rId) {
                $stmtUpdate->execute([$idx + 1, $rId, $masjid_id]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Slide berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Slide tidak ditemukan']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
