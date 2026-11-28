<?php
/**
 * API Data Wilayah Indonesia (Provinsi & Kabupaten/Kota)
 * File: api/get_regions.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$type = $_GET['type'] ?? 'provinces';

try {
    if ($type === 'provinces') {
        $stmt = $pdo->query("SELECT id, name FROM provinces ORDER BY name ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    if ($type === 'regencies') {
        $province_id = trim($_GET['province_id'] ?? '');
        if (empty($province_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter province_id diperlukan']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, province_id, name, latitude, longitude FROM regencies WHERE province_id = ? ORDER BY name ASC");
        $stmt->execute([$province_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    if ($type === 'regency') {
        $id = trim($_GET['id'] ?? '');
        $stmt = $pdo->prepare("SELECT id, province_id, name, latitude, longitude FROM regencies WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['status' => 'success', 'data' => $row]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kabupaten/Kota tidak ditemukan']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Tipe request tidak valid']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
