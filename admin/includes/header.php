<?php
/**
 * Header & Sidebar Layout Template Admin
 */
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../api/db.php';

// Ambil info masjid untuk header
$stmt = $pdo->prepare("SELECT * FROM masjid WHERE id = ? LIMIT 1");
$stmt->execute([$current_masjid_id]);
$masjid = $stmt->fetch();

$page_title = $page_title ?? 'Panel Pengurus';
$current_page = $current_page ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($masjid['nama'] ?? 'Layar Masjid') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Layar Masjid Admin Global CSS -->
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

  <div class="app-wrapper">

    <!-- Backdrop untuk Mobile -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- SIDEBAR NAVIGASI -->
    <aside id="sidebar" class="sidebar">
      <div class="sidebar-header">
        <i class="bi bi-moon-stars-fill text-warning fs-3"></i>
        <div class="overflow-hidden">
          <div class="fw-bold text-white text-truncate" id="sidebarMasjidName"><?= htmlspecialchars($masjid['nama'] ?? 'Layar Masjid') ?></div>
          <span class="badge-masjid-id">ID: <?= htmlspecialchars($current_masjid_id) ?></span>
        </div>
      </div>

      <nav class="sidebar-nav nav flex-column">
        <a class="nav-link <?= $current_page === 'profile' ? 'active' : '' ?>" href="profile">
          <i class="bi bi-building"></i>
          <span>Profil</span>
        </a>
        <a class="nav-link <?= $current_page === 'schedule' ? 'active' : '' ?>" href="schedule">
          <i class="bi bi-compass"></i>
          <span>Jadwal Sholat</span>
        </a>
        <a class="nav-link <?= $current_page === 'timer' ? 'active' : '' ?>" href="timer">
          <i class="bi bi-stopwatch"></i>
          <span>Timer</span>
        </a>
        <a class="nav-link <?= $current_page === 'slides' ? 'active' : '' ?>" href="slides">
          <i class="bi bi-images"></i>
          <span>Konten Slide</span>
        </a>
        <a class="nav-link <?= $current_page === 'running_text' ? 'active' : '' ?>" href="running_text">
          <i class="bi bi-broadcast"></i>
          <span>Teks Berjalan</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="display/<?= htmlspecialchars($current_masjid_id) ?>" target="_blank" class="btn btn-masjid w-100 mb-2 shadow-sm">
          <i class="bi bi-tv me-1"></i> Buka Layar TV
        </a>
        <a href="logout" class="btn btn-sm btn-outline-light w-100 opacity-75">
          <i class="bi bi-box-arrow-right me-1"></i> Keluar
        </a>
      </div>
    </aside>

    <!-- AREA KONTEN UTAMA -->
    <div class="main-content">
      
      <!-- Top Header -->
      <header class="top-header">
        <div class="d-flex align-items-center gap-3">
          <button type="button" id="sidebarToggle" class="btn btn-sm btn-outline-success d-lg-none">
            <i class="bi bi-list fs-5"></i>
          </button>
          <h5 class="mb-0 fw-bold text-success"><?= htmlspecialchars($page_title) ?></h5>
        </div>

        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted d-none d-sm-inline">Pengurus DKM: <strong><?= htmlspecialchars($current_masjid_nama) ?></strong></span>
          <a href="display/<?= htmlspecialchars($current_masjid_id) ?>" target="_blank" class="btn btn-sm btn-masjid d-none d-sm-inline-flex">
            <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau TV
          </a>
        </div>
      </header>

      <!-- Content Container -->
      <main class="p-3 p-md-4">
